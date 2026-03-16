<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\SupplyUsageLog;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ─── Hub ─────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $this->authorize('manage system');

        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $now          = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth   = $now->copy()->endOfMonth();

        $monthRevenue    = Sale::where('status', 'completed')
                               ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
                               ->sum('total_amount');
        $todayAppts      = Appointment::whereDate('appointment_date', today())->count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $pendingExpenses = Expense::where('status', 'pending')->sum('total_amount');
        $lowStockCount   = Supply::active()->lowStock()->count();
        $totalStaff      = User::where('status', 'active')->count();

        // 6-month Revenue vs Expenses
        $months   = [];
        $revenues = [];
        $expensesArr = [];
        for ($i = 5; $i >= 0; $i--) {
            $m          = $now->copy()->subMonths($i);
            $months[]   = $m->format('M Y');
            $revenues[] = (float) Sale::where('status', 'completed')
                                       ->whereYear('sale_date', $m->year)
                                       ->whereMonth('sale_date', $m->month)
                                       ->sum('total_amount');
            $expensesArr[] = (float) Expense::where('status', 'paid')
                                             ->whereYear('expense_date', $m->year)
                                             ->whereMonth('expense_date', $m->month)
                                             ->sum('total_amount');
        }

        // Today Appointment status distribution
        $apptStatusToday = Appointment::whereDate('appointment_date', today())
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('reports.index', compact(
            'currencySymbol',
            'monthRevenue', 'todayAppts', 'activeCustomers',
            'pendingExpenses', 'lowStockCount', 'totalStaff',
            'months', 'revenues', 'expensesArr', 'apptStatusToday'
        ));
    }

    // ─── Sales & Revenue ──────────────────────────────────────────────────────

    public function sales(Request $request): \Illuminate\View\View
    {
        $this->authorize('manage system');

        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(29)->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        $baseQuery = Sale::with(['customer', 'user'])
            ->whereBetween('sale_date', [$startDate, $endDate]);

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        }
        if ($request->filled('payment_method')) {
            $baseQuery->whereHas('payments', fn($q) =>
                $q->where('payment_method', $request->input('payment_method'))
            );
        }
        if ($request->filled('user_id')) {
            $baseQuery->where('user_id', $request->input('user_id'));
        }

        $allSales = (clone $baseQuery)->get();

        $totalRevenue  = $allSales->where('status', 'completed')->sum('total_amount');
        $completedSales = $allSales->where('status', 'completed')->count();
        $avgOrderValue = $completedSales > 0 ? $totalRevenue / $completedSales : 0;
        $totalRefunds  = DB::table('refunds')->whereBetween('refund_date', [$startDate, $endDate])->sum('refund_amount');
        $taxCollected  = $allSales->where('status', 'completed')->sum('tax_amount');
        $discountGiven = $allSales->where('status', 'completed')->sum('discount_amount');

        // Revenue Trend
        $daysDiff = (int) $startDate->diffInDays($endDate);
        if ($daysDiff <= 31) {
            $revenueTrend = Sale::where('status', 'completed')
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total_amount) as total'))
                ->groupBy('date')->orderBy('date')->get()
                ->map(fn($r) => ['date' => $r->date, 'total' => (float) $r->total]);
        } else {
            $revenueTrend = Sale::where('status', 'completed')
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->select(DB::raw('YEAR(sale_date) as yr'), DB::raw('MONTH(sale_date) as mo'), DB::raw('SUM(total_amount) as total'))
                ->groupBy('yr', 'mo')->orderBy('yr')->orderBy('mo')->get()
                ->map(fn($r) => ['date' => Carbon::create($r->yr, $r->mo)->format('M Y'), 'total' => (float) $r->total]);
        }

        $trendDates  = $revenueTrend->pluck('date')->values()->all();
        $trendTotals = $revenueTrend->pluck('total')->values()->all();

        // Payment Method Breakdown
        $paymentBreakdown = Payment::join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->select('payments.payment_method', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('payments.payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        // Sales by Type
        $salesByType = Sale::where('status', 'completed')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->select('sale_type', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('sale_type')
            ->get();

        // Top Services by Revenue
        $topServices = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->select('item_name', DB::raw('SUM(line_total) as revenue'), DB::raw('SUM(quantity) as qty'))
            ->groupBy('item_name')
            ->orderByDesc('revenue')
            ->limit(10)->get();

        // Sales Summary by Date
        $salesByDate = Sale::where('status', 'completed')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')->orderByDesc('date')->limit(30)->get();

        // Top Staff by Revenue
        $topStaff = Sale::where('status', 'completed')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as sale_count'))
            ->groupBy('user_id')
            ->with('user')
            ->orderByDesc('revenue')
            ->limit(10)->get();

        // Recent Sales
        $recentSales = (clone $baseQuery)->orderByDesc('sale_date')->limit(25)->get();

        $staffList = User::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('reports.sales', compact(
            'currencySymbol',
            'startDate', 'endDate',
            'totalRevenue', 'completedSales', 'avgOrderValue',
            'totalRefunds', 'taxCollected', 'discountGiven',
            'trendDates', 'trendTotals',
            'paymentBreakdown', 'salesByType', 'topServices',
            'salesByDate', 'topStaff', 'recentSales', 'staffList'
        ));
    }

    // ─── Appointments ─────────────────────────────────────────────────────────

    public function appointments(Request $request): \Illuminate\View\View
    {
        $this->authorize('manage system');

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(29)->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        $query = Appointment::whereBetween('appointment_date', [$startDate, $endDate]);

        if ($request->filled('status'))     $query->where('status', $request->input('status'));
        if ($request->filled('user_id'))    $query->where('user_id', $request->input('user_id'));
        if ($request->filled('service_id')) $query->where('service_id', $request->input('service_id'));

        $allAppts = (clone $query)->get();

        $totalAppts       = $allAppts->count();
        $completed        = $allAppts->where('status', 'completed')->count();
        $cancelled        = $allAppts->where('status', 'cancelled')->count();
        $completionRate   = $totalAppts > 0 ? round(($completed / $totalAppts) * 100, 1) : 0;
        $cancellationRate = $totalAppts > 0 ? round(($cancelled / $totalAppts) * 100, 1) : 0;
        $daysDiff         = max(1, (int) $startDate->diffInDays($endDate));
        $avgPerDay        = round($totalAppts / $daysDiff, 1);

        // Daily Trend
        $dailyTrend = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->select(DB::raw('DATE(appointment_date) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        // Status Distribution
        $statusDist = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // By Day of Week (1=Sun ... 7=Sat)
        $dowRaw = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->select(DB::raw('DAYOFWEEK(appointment_date) as dow'), DB::raw('COUNT(*) as count'))
            ->groupBy('dow')->orderBy('dow')
            ->pluck('count', 'dow')->toArray();

        $dowLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $dowCounts = [];
        for ($d = 1; $d <= 7; $d++) {
            $dowCounts[] = $dowRaw[$d] ?? 0;
        }

        // Top Services
        $topServices = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->whereNotNull('service_id')
            ->select('service_id', DB::raw('COUNT(*) as count'))
            ->groupBy('service_id')
            ->with('service:id,name')
            ->orderByDesc('count')
            ->limit(10)->get();

        // Staff Utilization
        $staffUtilization = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as completed_count'),
                DB::raw('SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END) as cancelled_count')
            )
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total')
            ->get();

        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $staffList   = User::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $serviceList = Service::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.appointments', compact(
            'currencySymbol',
            'startDate', 'endDate',
            'totalAppts', 'completionRate', 'cancellationRate', 'avgPerDay',
            'dailyTrend', 'statusDist', 'dowLabels', 'dowCounts',
            'topServices', 'staffUtilization', 'staffList', 'serviceList'
        ));
    }

    // ─── Customers ────────────────────────────────────────────────────────────

    public function customers(Request $request): \Illuminate\View\View
    {
        $this->authorize('manage system');

        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subMonths(11)->startOfMonth();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        $totalCustomers  = Customer::count();
        $newThisMonth    = Customer::whereMonth('created_at', now()->month)
                                   ->whereYear('created_at', now()->year)->count();
        $activeCount     = Customer::where('status', 'active')->count();
        $activeRate      = $totalCustomers > 0 ? round(($activeCount / $totalCustomers) * 100, 1) : 0;

        // Avg lifetime value: total completed revenue / unique customers who purchased
        $avgLTV = (float) DB::table('sales')
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->selectRaw('SUM(total_amount) / NULLIF(COUNT(DISTINCT customer_id), 0) as avg_ltv')
            ->value('avg_ltv') ?? 0;

        // Monthly Acquisitions (12 months)
        $acqMonths = [];
        $acqCounts = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->copy()->subMonths($i);
            $acqMonths[] = $m->format('M Y');
            $acqCounts[] = Customer::whereYear('created_at', $m->year)
                                   ->whereMonth('created_at', $m->month)->count();
        }

        // Gender Breakdown
        $genderBreakdown = Customer::select('gender', DB::raw('COUNT(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        // Status Breakdown
        $statusBreakdown = Customer::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Top Customers by Spend — use correlated subquery to avoid ONLY_FULL_GROUP_BY
        $topCustomers = Customer::addSelect([
            'total_spent' => Sale::selectRaw('COALESCE(SUM(total_amount), 0)')
                ->whereColumn('customer_id', 'customers.id')
                ->where('status', 'completed')
                ->whereNull('deleted_at'),
        ])->orderByDesc('total_spent')->limit(10)->get();

        // Recently Joined
        $recentCustomers = Customer::orderByDesc('created_at')->limit(10)->get();

        // Inactive (no appointment in 90 days)
        $inactiveCustomers = Customer::where('status', 'active')
            ->whereDoesntHave('appointments', fn($q) =>
                $q->where('appointment_date', '>=', now()->subDays(90))
            )
            ->orderBy('first_name')
            ->limit(20)->get();

        return view('reports.customers', compact(
            'currencySymbol',
            'startDate', 'endDate',
            'totalCustomers', 'newThisMonth', 'activeRate', 'avgLTV',
            'acqMonths', 'acqCounts', 'genderBreakdown', 'statusBreakdown',
            'topCustomers', 'recentCustomers', 'inactiveCustomers'
        ));
    }

    // ─── Expenses ─────────────────────────────────────────────────────────────

    public function expenses(Request $request): \Illuminate\View\View
    {
        $this->authorize('manage system');

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subMonths(5)->startOfMonth();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now()->endOfMonth();

        $query = Expense::whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($request->filled('status'))         $query->where('status', $request->input('status'));
        if ($request->filled('category_id'))    $query->where('category_id', $request->input('category_id'));
        if ($request->filled('payment_method')) $query->where('payment_method', $request->input('payment_method'));

        $totalExpenses   = (clone $query)->sum('total_amount');
        $paidExpenses    = (clone $query)->where('status', 'paid')->sum('total_amount');
        $pendingApproval = (clone $query)->where('status', 'pending')->sum('total_amount');
        $thisMonth       = Expense::whereYear('expense_date', now()->year)
                                  ->whereMonth('expense_date', now()->month)
                                  ->sum('total_amount');

        // Monthly Trend (6 months)
        $trendMonths   = [];
        $trendAmounts  = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->copy()->subMonths($i);
            $trendMonths[]  = $m->format('M Y');
            $trendAmounts[] = (float) Expense::whereYear('expense_date', $m->year)
                                             ->whereMonth('expense_date', $m->month)
                                             ->sum('total_amount');
        }

        // By Category
        $byCategory = Expense::whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('category_id', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->with('category:id,name')
            ->orderByDesc('total')
            ->get();

        // Status Breakdown
        $statusBreakdown = Expense::whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->get();

        // Payment Method Distribution
        $paymentMethodDist = Expense::whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotNull('payment_method')
            ->select('payment_method', DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        // Recent Expenses
        $recentExpenses = (clone $query)->with(['category:id,name', 'creator:id,name'])
            ->orderByDesc('expense_date')
            ->limit(20)->get();

        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $categories = ExpenseCategory::orderBy('name')->get(['id', 'name']);

        return view('reports.expenses', compact(
            'currencySymbol',
            'startDate', 'endDate',
            'totalExpenses', 'paidExpenses', 'pendingApproval', 'thisMonth',
            'trendMonths', 'trendAmounts', 'byCategory', 'statusBreakdown',
            'paymentMethodDist', 'recentExpenses', 'categories'
        ));
    }

    // ─── Inventory ────────────────────────────────────────────────────────────

    public function inventory(Request $request): \Illuminate\View\View
    {
        $this->authorize('manage system');

        $totalSupplies   = Supply::active()->count();
        $lowStockCount   = Supply::active()->lowStock()->count();
        $outOfStockCount = Supply::active()->outOfStock()->count();
        $totalStockValue = (float) Supply::active()->sum(DB::raw('current_stock * unit_cost'));

        // All supplies with optional filters
        $query = Supply::with('category:id,name')->active();
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        $stockStatus = $request->input('stock_status', 'all');
        if ($stockStatus === 'low')  $query->lowStock();
        if ($stockStatus === 'out')  $query->outOfStock();
        $supplies = $query->orderBy('name')->get();

        // Stock by Category
        $stockByCategory = Supply::active()
            ->select('category_id', DB::raw('SUM(current_stock) as total_stock'), DB::raw('COUNT(*) as item_count'))
            ->groupBy('category_id')
            ->with('category:id,name')
            ->get();

        // Top Used Supplies (last 90 days)
        $topUsed = SupplyUsageLog::where('used_at', '>=', now()->subDays(90))
            ->select('supply_id', DB::raw('SUM(quantity_used) as total_used'), DB::raw('SUM(total_cost) as total_cost'))
            ->groupBy('supply_id')
            ->with('supply:id,name,unit_type')
            ->orderByDesc('total_used')
            ->limit(10)->get();

        // Stock Value by Category
        $stockValueByCategory = Supply::active()
            ->select('category_id', DB::raw('SUM(current_stock * unit_cost) as value'))
            ->groupBy('category_id')
            ->with('category:id,name')
            ->orderByDesc('value')
            ->get();

        // Low Stock Alerts
        $lowStockItems = Supply::active()->lowStock()->with('category:id,name')->orderBy('current_stock')->get();

        // Recent Usage
        $recentUsage = SupplyUsageLog::with(['supply:id,name', 'user:id,name'])
            ->orderByDesc('used_at')
            ->limit(20)->get();

        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $categories = SupplyCategory::active()->orderBy('name')->get(['id', 'name']);

        return view('reports.inventory', compact(
            'currencySymbol',
            'totalSupplies', 'lowStockCount', 'outOfStockCount', 'totalStockValue',
            'supplies', 'stockByCategory', 'topUsed', 'stockValueByCategory',
            'lowStockItems', 'recentUsage', 'categories', 'stockStatus'
        ));
    }

    // ─── CSV Export ───────────────────────────────────────────────────────────

    public function export(Request $request, string $type): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('manage system');

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(29)->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        if (!in_array($type, ['sales', 'appointments', 'customers', 'expenses', 'inventory'])) {
            abort(404);
        }

        [$headers, $rows, $filename] = match ($type) {
            'sales'        => $this->buildSalesCsvData($startDate, $endDate),
            'appointments' => $this->buildAppointmentsCsvData($startDate, $endDate),
            'customers'    => $this->buildCustomersCsvData($startDate, $endDate),
            'expenses'     => $this->buildExpensesCsvData($startDate, $endDate),
            'inventory'    => $this->buildInventoryCsvData(),
        };

        $tmpFile = tempnam(sys_get_temp_dir(), 'rns_csv_');
        $handle  = fopen($tmpFile, 'w');
        // UTF-8 BOM so Excel opens without garbled characters
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn($v) => (string) ($v ?? ''), $row));
        }
        fclose($handle);

        return response()->download($tmpFile, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
        ])->deleteFileAfterSend(true);
    }

    // ─── CSV Helpers ──────────────────────────────────────────────────────────

    private function buildSalesCsvData(Carbon $start, Carbon $end): array
    {
        $sales = Sale::with(['customer', 'user'])
            ->whereBetween('sale_date', [$start, $end])
            ->orderByDesc('sale_date')
            ->get();

        $headers = ['Sale #', 'Date', 'Customer', 'Staff', 'Type', 'Subtotal', 'Discount', 'Tax', 'Total', 'Status'];
        $rows = $sales->map(fn($s) => [
            $s->sale_number,
            $s->sale_date?->format('Y-m-d H:i'),
            $s->customer ? ($s->customer->first_name . ' ' . $s->customer->last_name) : 'Walk-in',
            $s->user?->name ?? '',
            $s->sale_type ?? '',
            $s->subtotal,
            $s->discount_amount,
            $s->tax_amount,
            $s->total_amount,
            $s->status,
        ])->all();

        return [$headers, $rows, 'sales-report-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.csv'];
    }

    private function buildAppointmentsCsvData(Carbon $start, Carbon $end): array
    {
        $appts = Appointment::with(['customer', 'user', 'service'])
            ->whereBetween('appointment_date', [$start, $end])
            ->orderByDesc('appointment_date')
            ->get();

        $headers = ['Date', 'Time', 'Customer', 'Staff', 'Service', 'Status'];
        $rows = $appts->map(fn($a) => [
            $a->appointment_date?->format('Y-m-d'),
            $a->appointment_date?->format('H:i'),
            $a->customer ? ($a->customer->first_name . ' ' . $a->customer->last_name) : '',
            $a->user?->name ?? '',
            $a->service?->name ?? '',
            $a->status,
        ])->all();

        return [$headers, $rows, 'appointments-report-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.csv'];
    }

    private function buildCustomersCsvData(Carbon $start, Carbon $end): array
    {
        $customers = Customer::select('customers.*', DB::raw('COALESCE(SUM(sales.total_amount),0) as total_spent'))
            ->leftJoin('sales', function ($j) {
                $j->on('sales.customer_id', '=', 'customers.id')
                  ->where('sales.status', 'completed')
                  ->whereNull('sales.deleted_at');
            })
            ->whereBetween('customers.created_at', [$start, $end])
            ->groupBy('customers.id')
            ->orderBy('customers.first_name')
            ->get();

        $headers = ['ID', 'First Name', 'Last Name', 'Phone', 'Email', 'Gender', 'Status', 'Total Spent', 'Joined'];
        $rows = $customers->map(fn($c) => [
            $c->id,
            $c->first_name,
            $c->last_name,
            $c->phone,
            $c->email,
            $c->gender,
            $c->status,
            number_format((float) $c->total_spent, 2),
            $c->created_at?->format('Y-m-d'),
        ])->all();

        return [$headers, $rows, 'customers-report-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.csv'];
    }

    private function buildExpensesCsvData(Carbon $start, Carbon $end): array
    {
        $expenses = Expense::with(['category:id,name', 'creator:id,name'])
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('expense_date')
            ->get();

        $headers = ['Expense #', 'Date', 'Title', 'Category', 'Vendor', 'Amount', 'Tax', 'Total', 'Payment', 'Status', 'Created By'];
        $rows = $expenses->map(fn($e) => [
            $e->expense_number,
            $e->expense_date?->format('Y-m-d'),
            $e->title,
            $e->category?->name ?? '',
            $e->vendor_name,
            $e->amount,
            $e->tax_amount,
            $e->total_amount,
            $e->payment_method,
            $e->status,
            $e->creator?->name ?? '',
        ])->all();

        return [$headers, $rows, 'expenses-report-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.csv'];
    }

    private function buildInventoryCsvData(): array
    {
        $supplies = Supply::with('category:id,name')->active()->orderBy('name')->get();

        $headers = ['Name', 'SKU', 'Category', 'Unit', 'Current Stock', 'Min Level', 'Max Level', 'Unit Cost', 'Stock Value', 'Status'];
        $rows = $supplies->map(fn($s) => [
            $s->name,
            $s->sku,
            $s->category?->name ?? '',
            $s->unit_type,
            $s->current_stock,
            $s->min_stock_level,
            $s->max_stock_level,
            $s->unit_cost,
            round($s->current_stock * $s->unit_cost, 2),
            $s->isOutOfStock() ? 'Out of Stock' : ($s->isLowStock() ? 'Low Stock' : 'OK'),
        ])->all();

        return [$headers, $rows, 'inventory-report-' . now()->format('Ymd') . '.csv'];
    }
}
