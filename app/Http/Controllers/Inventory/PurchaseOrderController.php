<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supply;
use App\Models\SupplyStockMovement;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    protected $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * Display a listing of the purchase orders.
     */
    public function index(Request $request)
    {
        $this->authorize('inventory.view');

        $status = $request->get('status');
        $search = $request->get('search');

        $purchaseOrders = PurchaseOrder::with(['creator', 'approver', 'receiver'])
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%")
                        ->orWhere('invoice_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => PurchaseOrder::count(),
            'draft' => PurchaseOrder::where('status', 'draft')->count(),
            'pending' => PurchaseOrder::where('status', 'pending')->count(),
            'ordered' => PurchaseOrder::where('status', 'ordered')->count(),
            'partial' => PurchaseOrder::where('status', 'partial')->count(),
            'received' => PurchaseOrder::where('status', 'received')->count(),
            'cancelled' => PurchaseOrder::where('status', 'cancelled')->count(),
        ];

        return view('inventory.purchase-orders.index', compact('purchaseOrders', 'stats', 'status', 'search'));
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create()
    {
        $this->authorize('inventory.purchase.create');

        $supplies = Supply::active()->orderBy('name')->get();
        $poNumber = $this->purchaseOrderService->generatePoNumber();

        return view('inventory.purchase-orders.create', compact('supplies', 'poNumber'));
    }

    /**
     * Store a newly created purchase order in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('inventory.purchase.create');

        $request->validate([
            'po_number' => 'required|string|max:50|unique:purchase_orders,po_number',
            'supplier_name' => 'required|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_phone' => 'nullable|string|max:50',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'delivery_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string',
            'tax' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $request->po_number,
                'supplier_name' => $request->supplier_name,
                'supplier_contact' => $request->supplier_contact,
                'supplier_email' => $request->supplier_email,
                'supplier_phone' => $request->supplier_phone,
                'status' => 'draft',
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'delivery_location' => $request->delivery_location,
                'subtotal' => 0,
                'tax' => $request->tax ?? 0,
                'shipping' => $request->shipping ?? 0,
                'total' => 0,
                'created_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $totalCost = $item['quantity_ordered'] * $item['unit_cost'];
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'supply_id' => $item['supply_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $totalCost,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->purchaseOrderService->calculateTotals($purchaseOrder);

            DB::commit();

            return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to create purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('inventory.view');

        $purchaseOrder->load(['items.supply', 'creator', 'approver', 'receiver']);

        return view('inventory.purchase-orders.show', compact('purchaseOrder'));
    }

    /**
     * Show the form for editing the specified purchase order.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('inventory.purchase.create');

        if (!$purchaseOrder->canBeEdited()) {
            return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
                ->with('error', 'Purchase order cannot be edited in its current status.');
        }

        $supplies = Supply::active()->orderBy('name')->get();
        $purchaseOrder->load('items.supply');

        return view('inventory.purchase-orders.edit', compact('purchaseOrder', 'supplies'));
    }

    /**
     * Update the specified purchase order in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('inventory.purchase.create');

        if (!$purchaseOrder->canBeEdited()) {
            return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
                ->with('error', 'Purchase order cannot be edited in its current status.');
        }

        $request->validate([
            'supplier_name' => 'required|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_phone' => 'nullable|string|max:50',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'delivery_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_order_items,id',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string',
            'tax' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $purchaseOrder->update([
                'supplier_name' => $request->supplier_name,
                'supplier_contact' => $request->supplier_contact,
                'supplier_email' => $request->supplier_email,
                'supplier_phone' => $request->supplier_phone,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'delivery_location' => $request->delivery_location,
                'tax' => $request->tax ?? 0,
                'shipping' => $request->shipping ?? 0,
                'notes' => $request->notes,
            ]);

            $existingItemIds = $purchaseOrder->items->pluck('id')->toArray();
            $updatedItemIds = [];

            foreach ($request->items as $item) {
                $totalCost = $item['quantity_ordered'] * $item['unit_cost'];
                
                if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                    // Update existing item
                    $purchaseOrderItem = PurchaseOrderItem::find($item['id']);
                    $purchaseOrderItem->update([
                        'supply_id' => $item['supply_id'],
                        'quantity_ordered' => $item['quantity_ordered'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $totalCost,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'notes' => $item['notes'] ?? null,
                    ]);
                    $updatedItemIds[] = $item['id'];
                } else {
                    // Create new item
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'supply_id' => $item['supply_id'],
                        'quantity_ordered' => $item['quantity_ordered'],
                        'quantity_received' => 0,
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $totalCost,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Delete items that were removed
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (!empty($itemsToDelete)) {
                PurchaseOrderItem::whereIn('id', $itemsToDelete)->delete();
            }

            $this->purchaseOrderService->calculateTotals($purchaseOrder);

            DB::commit();

            return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to update purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase order from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('inventory.purchase.create');

        if (!$purchaseOrder->canBeEdited()) {
            return redirect()->back()->with('error', 'Purchase order cannot be deleted in its current status.');
        }

        if ($purchaseOrder->items()->where('quantity_received', '>', 0)->exists()) {
            return redirect()->back()->with('error', 'Cannot delete purchase order that has received items.');
        }

        $purchaseOrder->delete();

        return redirect()->route('inventory.purchase-orders.index')
            ->with('success', 'Purchase order deleted successfully.');
    }

    /**
     * Approve a purchase order.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('inventory.purchase.approve');

        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return redirect()->back()->with('error', 'Purchase order cannot be approved in its current status.');
        }

        if ($purchaseOrder->items()->count() === 0) {
            return redirect()->back()->with('error', 'Cannot approve a purchase order with no items.');
        }

        try {
            DB::beginTransaction();

            $purchaseOrder->update([
                'status' => 'ordered',
                'approved_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order approved and marked as ordered.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to approve purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Receive items for a purchase order.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('inventory.purchase.receive');

        if (!in_array($purchaseOrder->status, ['ordered', 'partial'])) {
            return redirect()->back()->with('error', 'Purchase order cannot be received in its current status.');
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'received_date' => 'required|date',
            'tracking_number' => 'nullable|string|max:100',
            'invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $this->purchaseOrderService->receiveItems(
                $purchaseOrder,
                $request->items,
                Auth::id(),
                $request->received_date,
                $request->tracking_number,
                $request->invoice_number,
                $request->notes
            );

            DB::commit();

            return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Items received successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to receive items: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a purchase order.
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('inventory.purchase.create');

        if (!in_array($purchaseOrder->status, ['draft', 'pending', 'ordered'])) {
            return redirect()->back()->with('error', 'Purchase order cannot be cancelled in its current status.');
        }

        $purchaseOrder->update([
            'status' => 'cancelled',
        ]);

        return redirect()->route('inventory.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order cancelled successfully.');
    }
}