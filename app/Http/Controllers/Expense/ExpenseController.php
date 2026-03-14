<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseComment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the expenses.
     */
    public function index(Request $request)
    {
        $this->authorize('expenses.view');

        $query = Expense::with(['category', 'creator'])
            ->withTrashed()
            ->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('expense_number', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $expenses = $query->paginate(20);

        // Stats
        $stats = [
            'total' => Expense::count(),
            'pending' => Expense::pending()->count(),
            'approved' => Expense::approved()->count(),
            'paid' => Expense::paid()->count(),
            'total_amount' => Expense::where('status', 'paid')->sum('total_amount'),
        ];

        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $statuses = [
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'paid' => 'Paid',
        ];

        return view('expenses.index', compact('expenses', 'stats', 'categories', 'statuses'));
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create()
    {
        $this->authorize('expenses.create');

        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $paymentMethods = [
            Expense::PAYMENT_METHOD_CASH => 'Cash',
            Expense::PAYMENT_METHOD_CARD => 'Card',
            Expense::PAYMENT_METHOD_CHECK => 'Check',
            Expense::PAYMENT_METHOD_BANK_TRANSFER => 'Bank Transfer',
            Expense::PAYMENT_METHOD_ONLINE => 'Online',
        ];
        $recurringFrequencies = [
            Expense::FREQUENCY_MONTHLY => 'Monthly',
            Expense::FREQUENCY_QUARTERLY => 'Quarterly',
            Expense::FREQUENCY_YEARLY => 'Yearly',
        ];
        $statuses = [
            Expense::STATUS_DRAFT => 'Draft',
            Expense::STATUS_PENDING => 'Pending',
        ];

        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $currencyCode = Setting::get('payment.currency_code', 'USD');

        return view('expenses.create', compact(
            'categories',
            'paymentMethods',
            'recurringFrequencies',
            'statuses',
            'currencySymbol',
            'currencyCode'
        ));
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('expenses.create');

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:expense_categories,id',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_contact' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'payment_method' => 'nullable|in:cash,card,check,bank_transfer,online',
            'payment_reference' => 'nullable|string|max:255',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:expense_date',
            'status' => 'required|in:draft,pending',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,1|in:monthly,quarterly,yearly',
            'recurring_end_date' => 'nullable|date|after:expense_date',
            'notes' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $expense = Expense::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'vendor_name' => $request->vendor_name,
            'vendor_contact' => $request->vendor_contact,
            'amount' => $request->amount,
            'tax_amount' => $request->tax_amount ?? 0,
            'currency' => $request->currency ?? Setting::get('payment.currency_code', 'USD'),
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'expense_date' => $request->expense_date,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'is_recurring' => $request->filled('is_recurring') ? $request->is_recurring : false,
            'recurring_frequency' => $request->recurring_frequency,
            'recurring_end_date' => $request->recurring_end_date,
            'notes' => $request->notes,
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("expenses/{$expense->id}", $filename, 'public');

                ExpenseAttachment::create([
                    'expense_id' => $expense->id,
                    'filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'attachment_type' => 'receipt',
                    'description' => null,
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense created successfully.');
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense)
    {
        $this->authorize('expenses.view');

        $expense->load(['category', 'creator', 'approver', 'attachments', 'comments.user']);

        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Expense $expense)
    {
        $this->authorize('expenses.manage');

        // Cannot edit if status is paid or rejected
        if (in_array($expense->status, [Expense::STATUS_PAID, Expense::STATUS_REJECTED])) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Cannot edit expense with status: ' . ucfirst($expense->status));
        }

        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $paymentMethods = [
            Expense::PAYMENT_METHOD_CASH => 'Cash',
            Expense::PAYMENT_METHOD_CARD => 'Card',
            Expense::PAYMENT_METHOD_CHECK => 'Check',
            Expense::PAYMENT_METHOD_BANK_TRANSFER => 'Bank Transfer',
            Expense::PAYMENT_METHOD_ONLINE => 'Online',
        ];
        $recurringFrequencies = [
            Expense::FREQUENCY_MONTHLY => 'Monthly',
            Expense::FREQUENCY_QUARTERLY => 'Quarterly',
            Expense::FREQUENCY_YEARLY => 'Yearly',
        ];
        $statuses = [
            Expense::STATUS_DRAFT => 'Draft',
            Expense::STATUS_PENDING => 'Pending',
        ];

        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $currencyCode = Setting::get('payment.currency_code', 'USD');

        return view('expenses.edit', compact(
            'expense',
            'categories',
            'paymentMethods',
            'recurringFrequencies',
            'statuses',
            'currencySymbol',
            'currencyCode'
        ));
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $this->authorize('expenses.manage');

        // Cannot update if status is paid or rejected
        if (in_array($expense->status, [Expense::STATUS_PAID, Expense::STATUS_REJECTED])) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Cannot update expense with status: ' . ucfirst($expense->status));
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:expense_categories,id',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_contact' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'payment_method' => 'nullable|in:cash,card,check,bank_transfer,online',
            'payment_reference' => 'nullable|string|max:255',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:expense_date',
            'status' => 'required|in:draft,pending',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,1|in:monthly,quarterly,yearly',
            'recurring_end_date' => 'nullable|date|after:expense_date',
            'notes' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $expense->update([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'vendor_name' => $request->vendor_name,
            'vendor_contact' => $request->vendor_contact,
            'amount' => $request->amount,
            'tax_amount' => $request->tax_amount ?? 0,
            'currency' => $request->currency ?? $expense->currency,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'expense_date' => $request->expense_date,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'is_recurring' => $request->filled('is_recurring') ? $request->is_recurring : false,
            'recurring_frequency' => $request->recurring_frequency,
            'recurring_end_date' => $request->recurring_end_date,
            'notes' => $request->notes,
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("expenses/{$expense->id}", $filename, 'public');

                ExpenseAttachment::create([
                    'expense_id' => $expense->id,
                    'filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'attachment_type' => 'receipt',
                    'description' => null,
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense updated successfully.');
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense)
    {
        $this->authorize('expenses.manage');

        // Cannot delete if status is paid
        if ($expense->status === Expense::STATUS_PAID) {
            return redirect()->back()->with('error', 'Cannot delete expense with status: Paid');
        }

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
    }

    /**
     * Approve the specified expense.
     */
    public function approve(Request $request, Expense $expense)
    {
        $this->authorize('expenses.approve');

        if ($expense->status !== Expense::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Only pending expenses can be approved.');
        }

        $success = $expense->approve(Auth::user());

        if ($success) {
            // Add comment
            ExpenseComment::create([
                'expense_id' => $expense->id,
                'user_id' => Auth::id(),
                'comment' => 'Expense approved.',
                'is_internal' => false,
            ]);

            return redirect()->route('expenses.show', $expense)->with('success', 'Expense approved successfully.');
        }

        return redirect()->back()->with('error', 'Failed to approve expense.');
    }

    /**
     * Reject the specified expense.
     */
    public function reject(Request $request, Expense $expense)
    {
        $this->authorize('expenses.approve');

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($expense->status !== Expense::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Only pending expenses can be rejected.');
        }

        $success = $expense->reject(Auth::user(), $request->rejection_reason);

        if ($success) {
            // Add comment
            ExpenseComment::create([
                'expense_id' => $expense->id,
                'user_id' => Auth::id(),
                'comment' => 'Expense rejected. Reason: ' . $request->rejection_reason,
                'is_internal' => false,
            ]);

            return redirect()->route('expenses.show', $expense)->with('success', 'Expense rejected successfully.');
        }

        return redirect()->back()->with('error', 'Failed to reject expense.');
    }

    /**
     * Mark the specified expense as paid.
     */
    public function markAsPaid(Request $request, Expense $expense)
    {
        $this->authorize('expenses.manage');

        $request->validate([
            'paid_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,check,bank_transfer,online',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        if ($expense->status !== Expense::STATUS_APPROVED) {
            return redirect()->back()->with('error', 'Only approved expenses can be marked as paid.');
        }

        $success = $expense->markAsPaid([
            'paid_date' => $request->paid_date,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
        ]);

        if ($success) {
            // Add comment
            ExpenseComment::create([
                'expense_id' => $expense->id,
                'user_id' => Auth::id(),
                'comment' => 'Expense marked as paid.',
                'is_internal' => false,
            ]);

            return redirect()->route('expenses.show', $expense)->with('success', 'Expense marked as paid successfully.');
        }

        return redirect()->back()->with('error', 'Failed to mark expense as paid.');
    }

    /**
     * Display the expense dashboard.
     */
    public function dashboard()
    {
        $this->authorize('expenses.view');

        // Basic stats
        $stats = [
            'paid_this_month' => Expense::where('status', 'paid')
                ->whereMonth('expense_date', now()->month)
                ->whereYear('expense_date', now()->year)
                ->sum('total_amount'),
            'total_pending' => Expense::pending()->count(),
            'total_approved' => Expense::approved()->count(),
            'total_paid' => Expense::paid()->count(),
            'total_amount' => Expense::where('status', 'paid')->sum('total_amount'),
        ];

        // Recent expenses (last 10)
        $recent_expenses = Expense::with(['category', 'creator'])
            ->orderBy('expense_date', 'desc')
            ->limit(10)
            ->get();

        // Overdue expenses (approved but not paid, due date passed)
        $overdue_expenses = Expense::overdue()
            ->with(['category', 'creator'])
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        // Monthly trend data for charts (last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'paid' => Expense::where('status', 'paid')
                    ->whereBetween('expense_date', [$monthStart, $monthEnd])
                    ->sum('total_amount'),
                'pending' => Expense::where('status', 'pending')
                    ->whereBetween('expense_date', [$monthStart, $monthEnd])
                    ->count(),
            ];
        }

        // Category breakdown for charts
        $categoryBreakdown = ExpenseCategory::withCount(['expenses' => function ($query) {
            $query->where('status', 'paid');
        }])->withSum(['expenses' => function ($query) {
            $query->where('status', 'paid');
        }], 'total_amount')
        ->having('expenses_count', '>', 0)
        ->orderBy('expenses_sum_total_amount', 'desc')
        ->limit(8)
        ->get();

        // Budget utilization
        $budgetUtilization = ExpenseCategory::where('budget_amount', '>', 0)
            ->withCount(['expenses' => function ($query) {
                $query->where('status', 'paid');
            }])
            ->withSum(['expenses' => function ($query) {
                $query->where('status', 'paid');
            }], 'total_amount')
            ->get()
            ->map(function ($category) {
                $utilization = $category->budget_amount > 0
                    ? ($category->expenses_sum_total_amount / $category->budget_amount) * 100
                    : 0;
                return [
                    'category' => $category->name,
                    'budget' => $category->budget_amount,
                    'spent' => $category->expenses_sum_total_amount,
                    'utilization' => min($utilization, 100),
                ];
            });

        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        return view('expenses.dashboard', compact(
            'stats',
            'recent_expenses',
            'overdue_expenses',
            'monthlyTrend',
            'categoryBreakdown',
            'budgetUtilization',
            'currencySymbol'
        ));
    }
}
