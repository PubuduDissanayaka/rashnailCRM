<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the expense categories.
     */
    public function index()
    {
        $this->authorize('expenses.view');

        $categories = ExpenseCategory::with('parent')
            ->withTrashed()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => ExpenseCategory::count(),
            'active' => ExpenseCategory::active()->count(),
            'with_budget' => ExpenseCategory::where('budget_amount', '>', 0)->count(),
        ];

        return view('expenses.categories.index', compact('categories', 'stats'));
    }

    /**
     * Show the form for creating a new expense category.
     */
    public function create()
    {
        $this->authorize('expenses.manage');

        $parentCategories = ExpenseCategory::active()
            ->rootCategories()
            ->orderBy('name')
            ->get();

        $budgetPeriods = [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];

        return view('expenses.categories.create', compact('parentCategories', 'budgetPeriods'));
    }

    /**
     * Store a newly created expense category in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('expenses.manage');

        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'budget_amount' => 'nullable|numeric|min:0',
            'budget_period' => 'nullable|in:monthly,quarterly,yearly',
        ]);

        $category = ExpenseCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'color' => $request->color,
            'parent_id' => $request->parent_id,
            'is_active' => $request->filled('is_active') ? $request->is_active : true,
            'sort_order' => $request->sort_order ?? 0,
            'budget_amount' => $request->budget_amount,
            'budget_period' => $request->budget_period,
        ]);

        return redirect()->route('expenses.categories.index')->with('success', 'Expense category created successfully.');
    }

    /**
     * Display the specified expense category.
     */
    public function show(ExpenseCategory $category)
    {
        $this->authorize('expenses.view');

        $category->load(['parent', 'children', 'expenses' => function($query) {
            $query->latest()->take(10);
        }]);

        return view('expenses.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified expense category.
     */
    public function edit(ExpenseCategory $category)
    {
        $this->authorize('expenses.manage');

        $parentCategories = ExpenseCategory::active()
            ->where('id', '!=', $category->id)
            ->rootCategories()
            ->orderBy('name')
            ->get();

        $budgetPeriods = [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];

        return view('expenses.categories.edit', compact('category', 'parentCategories', 'budgetPeriods'));
    }

    /**
     * Update the specified expense category in storage.
     */
    public function update(Request $request, ExpenseCategory $category)
    {
        $this->authorize('expenses.manage');

        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'budget_amount' => 'nullable|numeric|min:0',
            'budget_period' => 'nullable|in:monthly,quarterly,yearly',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'color' => $request->color,
            'parent_id' => $request->parent_id,
            'is_active' => $request->filled('is_active') ? $request->is_active : $category->is_active,
            'sort_order' => $request->sort_order ?? $category->sort_order,
            'budget_amount' => $request->budget_amount,
            'budget_period' => $request->budget_period,
        ]);

        return redirect()->route('expenses.categories.index')->with('success', 'Expense category updated successfully.');
    }

    /**
     * Remove the specified expense category from storage.
     */
    public function destroy(ExpenseCategory $category)
    {
        $this->authorize('expenses.manage');

        // Check if category has any expenses
        if ($category->expenses()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete category that has expenses. Please reassign or delete the expenses first.');
        }

        $category->delete();

        return redirect()->route('expenses.categories.index')->with('success', 'Expense category deleted successfully.');
    }
}