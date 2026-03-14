<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\SupplyStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SupplyController extends Controller
{
    /**
     * Display a listing of the supplies.
     */
    public function index()
    {
        $this->authorize('inventory.view');

        $supplies = Supply::with('category')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Supply::count(),
            'active' => Supply::where('is_active', true)->count(),
            'low_stock' => Supply::lowStock()->count(),
            'out_of_stock' => Supply::outOfStock()->count(),
        ];

        return view('inventory.supplies.index', compact('supplies', 'stats'));
    }

    /**
     * Show the form for creating a new supply.
     */
    public function create()
    {
        $this->authorize('inventory.manage');

        $categories = SupplyCategory::active()->orderBy('name')->get();
        $unitTypes = [
            'piece' => 'Piece',
            'bottle' => 'Bottle',
            'ml' => 'Milliliter (ml)',
            'oz' => 'Ounce (oz)',
            'gram' => 'Gram (g)',
            'kg' => 'Kilogram (kg)',
            'liter' => 'Liter (L)',
            'set' => 'Set',
        ];

        return view('inventory.supplies.create', compact('categories', 'unitTypes'));
    }

    /**
     * Store a newly created supply in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('inventory.manage');

        $request->validate([
            'name' => 'required|string|max:255|unique:supplies,name',
            'description' => 'nullable|string',
            'sku' => 'required|string|max:100|unique:supplies,sku',
            'barcode' => 'nullable|string|max:100|unique:supplies,barcode',
            'category_id' => 'nullable|exists:supply_categories,id',
            'brand' => 'nullable|string|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'unit_type' => 'required|in:piece,bottle,ml,oz,gram,kg,liter,set',
            'unit_size' => 'nullable|numeric|min:0',
            'min_stock_level' => 'required|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'retail_value' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'track_expiry' => 'boolean',
            'track_batch' => 'boolean',
            'usage_per_service' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'storage_location' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $supply = Supply::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'sku' => $request->sku,
                'barcode' => $request->barcode,
                'category_id' => $request->category_id,
                'brand' => $request->brand,
                'supplier_name' => $request->supplier_name,
                'unit_type' => $request->unit_type,
                'unit_size' => $request->unit_size,
                'min_stock_level' => $request->min_stock_level,
                'max_stock_level' => $request->max_stock_level,
                'current_stock' => $request->current_stock,
                'unit_cost' => $request->unit_cost,
                'retail_value' => $request->retail_value,
                'is_active' => $request->filled('is_active') ? $request->is_active : true,
                'track_expiry' => $request->filled('track_expiry') ? $request->track_expiry : false,
                'track_batch' => $request->filled('track_batch') ? $request->track_batch : false,
                'usage_per_service' => $request->usage_per_service,
                'location' => $request->location,
                'storage_location' => $request->storage_location,
                'notes' => $request->notes,
            ]);

            // Create initial stock movement if current_stock > 0
            if ($supply->current_stock > 0) {
                SupplyStockMovement::create([
                    'supply_id' => $supply->id,
                    'movement_type' => 'adjustment',
                    'quantity' => $supply->current_stock,
                    'quantity_before' => 0,
                    'quantity_after' => $supply->current_stock,
                    'reference_type' => null,
                    'reference_id' => null,
                    'reference_number' => 'Initial Stock',
                    'unit_cost' => $supply->unit_cost,
                    'total_cost' => $supply->current_stock * $supply->unit_cost,
                    'created_by' => Auth::id(),
                    'notes' => 'Initial stock creation',
                    'movement_date' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('inventory.supplies.index')->with('success', 'Supply created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating supply: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified supply.
     */
    public function show(Supply $supply)
    {
        $this->authorize('inventory.view');

        $supply->load(['category', 'stockMovements' => function ($query) {
            $query->orderBy('movement_date', 'desc')->limit(20);
        }, 'stockMovements.creator']);

        $stockMovements = $supply->stockMovements()->paginate(10);

        return view('inventory.supplies.show', compact('supply', 'stockMovements'));
    }

    /**
     * Show the form for editing the specified supply.
     */
    public function edit(Supply $supply)
    {
        $this->authorize('inventory.manage');

        $categories = SupplyCategory::active()->orderBy('name')->get();
        $unitTypes = [
            'piece' => 'Piece',
            'bottle' => 'Bottle',
            'ml' => 'Milliliter (ml)',
            'oz' => 'Ounce (oz)',
            'gram' => 'Gram (g)',
            'kg' => 'Kilogram (kg)',
            'liter' => 'Liter (L)',
            'set' => 'Set',
        ];

        return view('inventory.supplies.edit', compact('supply', 'categories', 'unitTypes'));
    }

    /**
     * Update the specified supply in storage.
     */
    public function update(Request $request, Supply $supply)
    {
        $this->authorize('inventory.manage');

        $request->validate([
            'name' => 'required|string|max:255|unique:supplies,name,' . $supply->id,
            'description' => 'nullable|string',
            'sku' => 'required|string|max:100|unique:supplies,sku,' . $supply->id,
            'barcode' => 'nullable|string|max:100|unique:supplies,barcode,' . $supply->id,
            'category_id' => 'nullable|exists:supply_categories,id',
            'brand' => 'nullable|string|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'unit_type' => 'required|in:piece,bottle,ml,oz,gram,kg,liter,set',
            'unit_size' => 'nullable|numeric|min:0',
            'min_stock_level' => 'required|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'retail_value' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'track_expiry' => 'boolean',
            'track_batch' => 'boolean',
            'usage_per_service' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'storage_location' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $supply->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'category_id' => $request->category_id,
            'brand' => $request->brand,
            'supplier_name' => $request->supplier_name,
            'unit_type' => $request->unit_type,
            'unit_size' => $request->unit_size,
            'min_stock_level' => $request->min_stock_level,
            'max_stock_level' => $request->max_stock_level,
            'unit_cost' => $request->unit_cost,
            'retail_value' => $request->retail_value,
            'is_active' => $request->filled('is_active') ? $request->is_active : $supply->is_active,
            'track_expiry' => $request->filled('track_expiry') ? $request->track_expiry : $supply->track_expiry,
            'track_batch' => $request->filled('track_batch') ? $request->track_batch : $supply->track_batch,
            'usage_per_service' => $request->usage_per_service,
            'location' => $request->location,
            'storage_location' => $request->storage_location,
            'notes' => $request->notes,
        ]);

        return redirect()->route('inventory.supplies.index')->with('success', 'Supply updated successfully.');
    }

    /**
     * Remove the specified supply from storage.
     */
    public function destroy(Supply $supply)
    {
        $this->authorize('inventory.manage');

        // Check if supply has any stock movements or usage logs
        // if ($supply->stockMovements()->count() > 0 || $supply->usageLogs()->count() > 0) {
        //     return redirect()->back()->with('error', 'Cannot delete supply that has stock movements or usage logs.');
        // }

        $supply->delete();

        return redirect()->route('inventory.supplies.index')->with('success', 'Supply deleted successfully.');
    }

    /**
     * Adjust stock for a supply.
     */
    public function adjustStock(Request $request, Supply $supply)
    {
        $this->authorize('inventory.supplies.adjust');

        $request->validate([
            'adjustment_type' => 'required|in:add,remove,set',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'movement_date' => 'nullable|date',
        ]);

        $quantity = $request->quantity;
        $adjustmentType = $request->adjustment_type;
        $quantityBefore = $supply->current_stock;
        $quantityAfter = $quantityBefore;

        switch ($adjustmentType) {
            case 'add':
                $quantityAfter = $quantityBefore + $quantity;
                $movementType = 'adjustment';
                break;
            case 'remove':
                $quantityAfter = $quantityBefore - $quantity;
                $movementType = 'adjustment';
                break;
            case 'set':
                $quantityAfter = $quantity;
                $movementType = 'adjustment';
                break;
            default:
                return redirect()->back()->with('error', 'Invalid adjustment type.');
        }

        // Update supply stock
        $supply->current_stock = $quantityAfter;
        $supply->save();

        // Create stock movement record
        SupplyStockMovement::create([
            'supply_id' => $supply->id,
            'movement_type' => $movementType,
            'quantity' => $adjustmentType === 'set' ? $quantityAfter - $quantityBefore : $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => null,
            'reference_id' => null,
            'reference_number' => 'Manual Adjustment',
            'unit_cost' => $supply->unit_cost,
            'total_cost' => abs($quantityAfter - $quantityBefore) * $supply->unit_cost,
            'created_by' => Auth::id(),
            'notes' => $request->reason . ($request->notes ? ': ' . $request->notes : ''),
            'movement_date' => $request->movement_date ?? now(),
        ]);

        return redirect()->route('inventory.supplies.show', $supply)->with('success', 'Stock adjusted successfully.');
    }

    /**
     * Display stock movement history for a supply.
     */
    public function history(Supply $supply)
    {
        $this->authorize('inventory.view');

        $stockMovements = $supply->stockMovements()
            ->with('creator')
            ->orderBy('movement_date', 'desc')
            ->paginate(20);

        return view('inventory.supplies.history', compact('supply', 'stockMovements'));
    }
}