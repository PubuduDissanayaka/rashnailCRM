<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\SupplyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplyCategoryController extends Controller
{
    /**
     * Display a listing of the supply categories.
     */
    public function index()
    {
        $this->authorize('inventory.view');

        $categories = SupplyCategory::with(['parent', 'children', 'supplies'])
            ->withTrashed()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $rootCategories = $categories->whereNull('parent_id');

        return view('inventory.categories.index', compact('categories', 'rootCategories'));
    }

    /**
     * Show the form for creating a new supply category.
     */
    public function create()
    {
        $this->authorize('inventory.manage');

        $parentCategories = SupplyCategory::active()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('inventory.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created supply category in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('inventory.manage');

        $request->validate([
            'name' => 'required|string|max:255|unique:supply_categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:supply_categories,id',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        SupplyCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->filled('is_active') ? $request->is_active : true,
        ]);

        return redirect()->route('inventory.categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing the specified supply category.
     */
    public function edit(SupplyCategory $category)
    {
        $this->authorize('inventory.manage');

        $parentCategories = SupplyCategory::active()
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('inventory.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified supply category in storage.
     */
    public function update(Request $request, SupplyCategory $category)
    {
        $this->authorize('inventory.manage');

        $request->validate([
            'name' => 'required|string|max:255|unique:supply_categories,name,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:supply_categories,id',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Prevent circular reference (category cannot be its own parent)
        if ($request->parent_id == $category->id) {
            return redirect()->back()->with('error', 'Category cannot be its own parent.');
        }

        // Prevent making a parent category a child of its own descendant
        if ($request->parent_id) {
            $descendantIds = $category->children->pluck('id')->toArray();
            if (in_array($request->parent_id, $descendantIds)) {
                return redirect()->back()->with('error', 'Category cannot be a child of its own descendant.');
            }
        }

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? $category->sort_order,
            'is_active' => $request->filled('is_active') ? $request->is_active : $category->is_active,
        ]);

        return redirect()->route('inventory.categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified supply category from storage.
     */
    public function destroy(SupplyCategory $category)
    {
        $this->authorize('inventory.manage');

        // Check if category has any supplies
        if ($category->supplies()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete category that has supplies. Please reassign or delete supplies first.');
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete category that has subcategories. Please delete or reassign subcategories first.');
        }

        $category->delete();

        return redirect()->route('inventory.categories.index')->with('success', 'Category deleted successfully.');
    }
}