<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supply;
use App\Models\SupplyStockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Generate a purchase order number in format PO-YYYY-NNN
     * Example: PO-2025-001
     */
    public function generatePoNumber(): string
    {
        $year = date('Y');
        $lastPo = PurchaseOrder::where('po_number', 'like', "PO-{$year}-%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }

        return "PO-{$year}-{$nextNumber}";
    }

    /**
     * Calculate and update totals for a purchase order
     */
    public function calculateTotals(PurchaseOrder $purchaseOrder): void
    {
        $subtotal = $purchaseOrder->items()->sum('total_cost');
        
        $purchaseOrder->update([
            'subtotal' => $subtotal,
            'total' => $subtotal + $purchaseOrder->tax + $purchaseOrder->shipping,
        ]);
    }

    /**
     * Receive items for a purchase order
     */
    public function receiveItems(
        PurchaseOrder $purchaseOrder,
        array $itemsReceived,
        int $userId,
        string $receivedDate,
        ?string $trackingNumber = null,
        ?string $invoiceNumber = null,
        ?string $notes = null
    ): void {
        $allFullyReceived = true;
        $anyReceived = false;

        foreach ($itemsReceived as $itemData) {
            $item = PurchaseOrderItem::findOrFail($itemData['id']);
            
            if ($itemData['quantity_received'] > 0) {
                $anyReceived = true;
                
                // Update batch number and expiry date if provided
                if (isset($itemData['batch_number'])) {
                    $item->batch_number = $itemData['batch_number'];
                }
                if (isset($itemData['expiry_date'])) {
                    $item->expiry_date = $itemData['expiry_date'];
                }
                
                // Update received quantity
                $newQuantityReceived = $item->quantity_received + $itemData['quantity_received'];
                if ($newQuantityReceived > $item->quantity_ordered) {
                    throw new \Exception("Cannot receive more than ordered quantity for item: {$item->supply->name}");
                }
                
                $item->quantity_received = $newQuantityReceived;
                $item->save();
                
                // Update stock
                $this->updateStockFromReceipt($item, $itemData['quantity_received'], $userId, $receivedDate);
                
                // Check if fully received
                if (!$item->isFullyReceived()) {
                    $allFullyReceived = false;
                }
            }
        }

        // Update purchase order status
        $allItems = $purchaseOrder->items()->get();
        $isFullyReceived = $allItems->every(function ($item) {
            return $item->isFullyReceived();
        });
        
        $hasAnyReceipts = $allItems->sum('quantity_received') > 0;

        $newStatus = 'ordered'; // Default fallback
        if ($isFullyReceived) {
            $newStatus = 'received';
        } elseif ($hasAnyReceipts) {
            $newStatus = 'partial';
        } else {
            // Keep existing status if no receipts (shouldn't happen here due to $anyReceived check above, but safe)
             $newStatus = $purchaseOrder->status;
        }

        $purchaseOrder->update([
            'status' => $newStatus,
            'received_date' => $receivedDate,
            'received_by' => $userId,
            'tracking_number' => $trackingNumber,
            'invoice_number' => $invoiceNumber,
            'notes' => $notes ? ($purchaseOrder->notes ? $purchaseOrder->notes . "\n\nReceiving Notes: " . $notes : "Receiving Notes: " . $notes) : $purchaseOrder->notes,
        ]);
    }

    /**
     * Update stock from receipt of a purchase order item
     */
    public function updateStockFromReceipt(
        PurchaseOrderItem $item,
        float $quantityReceived,
        int $userId,
        string $movementDate
    ): void {
        $supply = $item->supply;
        
        // Update supply stock
        $quantityBefore = $supply->current_stock;
        $quantityAfter = $quantityBefore + $quantityReceived;
        
        $supply->current_stock = $quantityAfter;
        $supply->save();
        
        // Create stock movement record
        SupplyStockMovement::create([
            'supply_id' => $supply->id,
            'movement_type' => 'purchase',
            'quantity' => $quantityReceived,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => PurchaseOrder::class,
            'reference_id' => $item->purchase_order_id,
            'reference_number' => $item->purchaseOrder->po_number,
            'unit_cost' => $item->unit_cost,
            'total_cost' => $quantityReceived * $item->unit_cost,
            'batch_number' => $item->batch_number,
            'expiry_date' => $item->expiry_date,
            'created_by' => $userId,
            'notes' => "Received from purchase order {$item->purchaseOrder->po_number}",
            'movement_date' => $movementDate,
        ]);
    }

    /**
     * Get purchase order statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => PurchaseOrder::count(),
            'draft' => PurchaseOrder::where('status', 'draft')->count(),
            'pending' => PurchaseOrder::where('status', 'pending')->count(),
            'ordered' => PurchaseOrder::where('status', 'ordered')->count(),
            'partial' => PurchaseOrder::where('status', 'partial')->count(),
            'received' => PurchaseOrder::where('status', 'received')->count(),
            'cancelled' => PurchaseOrder::where('status', 'cancelled')->count(),
            'total_value' => PurchaseOrder::where('status', '!=', 'cancelled')->sum('total'),
        ];
    }

    /**
     * Get low stock supplies that need purchase orders
     */
    public function getLowStockSupplies(int $limit = 10): array
    {
        return Supply::active()
            ->lowStock()
            ->with('category')
            ->orderBy('current_stock', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($supply) {
                return [
                    'id' => $supply->id,
                    'name' => $supply->name,
                    'sku' => $supply->sku,
                    'current_stock' => $supply->current_stock,
                    'min_stock_level' => $supply->min_stock_level,
                    'unit_type' => $supply->unit_type,
                    'unit_cost' => $supply->unit_cost,
                    'supplier_name' => $supply->supplier_name,
                    'reorder_quantity' => max($supply->min_stock_level * 2 - $supply->current_stock, $supply->min_stock_level),
                ];
            })
            ->toArray();
    }

    /**
     * Create a purchase order from low stock supplies
     */
    public function createFromLowStockSupplies(array $supplyData, array $orderData, int $userId): PurchaseOrder
    {
        DB::beginTransaction();

        try {
            $poNumber = $this->generatePoNumber();
            
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_name' => $orderData['supplier_name'] ?? 'Multiple Suppliers',
                'supplier_contact' => $orderData['supplier_contact'] ?? null,
                'supplier_email' => $orderData['supplier_email'] ?? null,
                'supplier_phone' => $orderData['supplier_phone'] ?? null,
                'status' => 'draft',
                'order_date' => $orderData['order_date'] ?? now()->format('Y-m-d'),
                'expected_delivery_date' => $orderData['expected_delivery_date'] ?? null,
                'delivery_location' => $orderData['delivery_location'] ?? null,
                'subtotal' => 0,
                'tax' => $orderData['tax'] ?? 0,
                'shipping' => $orderData['shipping'] ?? 0,
                'total' => 0,
                'created_by' => $userId,
                'notes' => $orderData['notes'] ?? 'Auto-generated from low stock supplies',
            ]);

            foreach ($supplyData as $item) {
                $supply = Supply::findOrFail($item['supply_id']);
                $quantity = $item['quantity'] ?? max($supply->min_stock_level * 2 - $supply->current_stock, $supply->min_stock_level);
                $unitCost = $item['unit_cost'] ?? $supply->unit_cost;
                $totalCost = $quantity * $unitCost;
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'supply_id' => $supply->id,
                    'quantity_ordered' => $quantity,
                    'quantity_received' => 0,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->calculateTotals($purchaseOrder);

            DB::commit();

            return $purchaseOrder;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}