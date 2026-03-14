# Comprehensive Inventory Management System Plan

## Overview

**Purpose:** Supply/stock tracking system for service-related supplies (nail polish, beauty products, etc.)
**Scope:** Internal inventory management only - NOT for retail sales
**Integration:** Seamlessly integrate with existing appointment and service booking system

**Current Status:** No existing inventory system found in codebase. This is a new module.

---

## Project Objectives

1. Track supplies used in services (nail polish, beauty products, cleaning supplies, etc.)
2. Monitor stock levels with automated low-stock alerts
3. Log supply usage during appointments/services
4. Manage purchase orders and restock operations
5. Generate inventory reports and usage analytics
6. Multi-location support (if business has multiple branches)
7. Role-based access control for inventory management
8. Audit trail for all inventory transactions

---

## Database Schema Design

### Core Tables

#### 1. `supplies` Table
Primary inventory items master table.

```php
Schema::create('supplies', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('sku')->unique(); // Stock Keeping Unit
    $table->string('barcode')->nullable()->unique();

    // Categorization
    $table->foreignId('category_id')->nullable()->constrained('supply_categories')->nullOnDelete();
    $table->string('brand')->nullable();
    $table->string('supplier_name')->nullable();

    // Stock Information
    $table->enum('unit_type', ['piece', 'bottle', 'ml', 'oz', 'gram', 'kg', 'liter', 'set'])->default('piece');
    $table->decimal('unit_size', 10, 2)->nullable(); // e.g., 15 for 15ml bottle
    $table->decimal('min_stock_level', 10, 2)->default(0); // Reorder threshold
    $table->decimal('max_stock_level', 10, 2)->nullable(); // Maximum stock capacity
    $table->decimal('current_stock', 10, 2)->default(0); // Current available quantity

    // Costing
    $table->decimal('unit_cost', 10, 2)->default(0); // Cost per unit
    $table->decimal('retail_value', 10, 2)->nullable(); // For reference only

    // Settings
    $table->boolean('is_active')->default(true);
    $table->boolean('track_expiry')->default(false);
    $table->boolean('track_batch')->default(false);
    $table->integer('usage_per_service')->nullable(); // Default usage amount

    // Location (if multi-location)
    $table->string('location')->nullable();
    $table->string('storage_location')->nullable(); // Shelf/bin location

    // Metadata
    $table->json('metadata')->nullable(); // Additional custom fields
    $table->text('notes')->nullable();

    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('sku');
    $table->index('category_id');
    $table->index('is_active');
    $table->index(['current_stock', 'min_stock_level']); // For low stock queries
});
```

#### 2. `supply_categories` Table
Organize supplies into categories.

```php
Schema::create('supply_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('supply_categories')->nullOnDelete();
    $table->string('icon')->nullable(); // Icon class for UI
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

#### 3. `supply_stock_movements` Table
Comprehensive ledger of all stock changes (purchases, usage, adjustments, waste).

```php
Schema::create('supply_stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();

    // Movement Details
    $table->enum('movement_type', [
        'purchase',      // Stock received
        'usage',         // Used in service
        'adjustment',    // Manual correction
        'waste',         // Expired/damaged
        'transfer',      // Location transfer
        'return'         // Return to supplier
    ]);
    $table->decimal('quantity', 10, 2); // Positive or negative
    $table->decimal('quantity_before', 10, 2); // Stock before movement
    $table->decimal('quantity_after', 10, 2); // Stock after movement

    // Reference
    $table->morphs('reference'); // appointment_id, purchase_order_id, etc.
    $table->string('reference_number')->nullable(); // Human-readable reference

    // Cost Tracking
    $table->decimal('unit_cost', 10, 2)->nullable();
    $table->decimal('total_cost', 10, 2)->nullable();

    // Batch/Expiry (if tracked)
    $table->string('batch_number')->nullable();
    $table->date('expiry_date')->nullable();

    // Location
    $table->string('from_location')->nullable();
    $table->string('to_location')->nullable();

    // User & Timestamp
    $table->foreignId('created_by')->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamp('movement_date')->useCurrent();

    $table->timestamps();

    // Indexes
    $table->index('supply_id');
    $table->index('movement_type');
    $table->index('movement_date');
    $table->index(['reference_type', 'reference_id']);
});
```

#### 4. `supply_usage_logs` Table
Detailed usage tracking linked to services/appointments.

```php
Schema::create('supply_usage_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
    $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
    $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();

    // Usage Details
    $table->decimal('quantity_used', 10, 2);
    $table->decimal('unit_cost', 10, 2)->nullable(); // Cost at time of use
    $table->decimal('total_cost', 10, 2)->nullable();

    // Who used it
    $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();

    // Batch tracking
    $table->string('batch_number')->nullable();

    $table->text('notes')->nullable();
    $table->timestamp('used_at')->useCurrent();
    $table->timestamps();

    // Indexes
    $table->index('supply_id');
    $table->index('appointment_id');
    $table->index('service_id');
    $table->index('used_at');
});
```

#### 5. `purchase_orders` Table
Track supply purchases and restocking.

```php
Schema::create('purchase_orders', function (Blueprint $table) {
    $table->id();
    $table->string('po_number')->unique(); // PO-2024-001

    // Supplier Information
    $table->string('supplier_name');
    $table->string('supplier_contact')->nullable();
    $table->string('supplier_email')->nullable();
    $table->string('supplier_phone')->nullable();

    // Order Details
    $table->enum('status', [
        'draft',
        'pending',
        'ordered',
        'partial',
        'received',
        'cancelled'
    ])->default('draft');
    $table->date('order_date');
    $table->date('expected_delivery_date')->nullable();
    $table->date('received_date')->nullable();

    // Financial
    $table->decimal('subtotal', 10, 2)->default(0);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('shipping', 10, 2)->default(0);
    $table->decimal('total', 10, 2)->default(0);

    // Tracking
    $table->string('tracking_number')->nullable();
    $table->string('invoice_number')->nullable();

    // User & Location
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->foreignId('received_by')->nullable()->constrained('users');
    $table->string('delivery_location')->nullable();

    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('po_number');
    $table->index('status');
    $table->index('order_date');
});
```

#### 6. `purchase_order_items` Table
Line items for purchase orders.

```php
Schema::create('purchase_order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
    $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();

    // Order Details
    $table->decimal('quantity_ordered', 10, 2);
    $table->decimal('quantity_received', 10, 2)->default(0);
    $table->decimal('unit_cost', 10, 2);
    $table->decimal('total_cost', 10, 2);

    // Batch/Expiry
    $table->string('batch_number')->nullable();
    $table->date('expiry_date')->nullable();

    $table->text('notes')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('purchase_order_id');
    $table->index('supply_id');
});
```

#### 7. `supply_alerts` Table
Low stock and expiry alerts.

```php
Schema::create('supply_alerts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();

    $table->enum('alert_type', [
        'low_stock',
        'out_of_stock',
        'expiring_soon',
        'expired'
    ]);
    $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
    $table->text('message');

    // Alert Status
    $table->boolean('is_resolved')->default(false);
    $table->timestamp('resolved_at')->nullable();
    $table->foreignId('resolved_by')->nullable()->constrained('users');

    // Reference Values
    $table->decimal('current_stock', 10, 2)->nullable();
    $table->decimal('min_stock_level', 10, 2)->nullable();
    $table->date('expiry_date')->nullable();

    $table->timestamps();

    // Indexes
    $table->index('supply_id');
    $table->index('alert_type');
    $table->index('is_resolved');
});
```

---

## Model Structure & Relationships

### 1. Supply Model
`app/Models/Supply.php`

```php
class Supply extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'sku', 'barcode',
        'category_id', 'brand', 'supplier_name',
        'unit_type', 'unit_size', 'min_stock_level', 'max_stock_level', 'current_stock',
        'unit_cost', 'retail_value',
        'is_active', 'track_expiry', 'track_batch', 'usage_per_service',
        'location', 'storage_location',
        'metadata', 'notes'
    ];

    protected $casts = [
        'unit_size' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'retail_value' => 'decimal:2',
        'is_active' => 'boolean',
        'track_expiry' => 'boolean',
        'track_batch' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function category(): BelongsTo
    public function stockMovements(): HasMany
    public function usageLogs(): HasMany
    public function purchaseOrderItems(): HasMany
    public function alerts(): HasMany
    public function services(): BelongsToMany // Optional: link supplies to services

    // Scopes
    public function scopeActive($query)
    public function scopeLowStock($query)
    public function scopeOutOfStock($query)
    public function scopeByCategory($query, $categoryId)

    // Methods
    public function isLowStock(): bool
    public function isOutOfStock(): bool
    public function addStock($quantity, $reference, $notes = null)
    public function removeStock($quantity, $reference, $notes = null)
    public function adjustStock($quantity, $reason, $notes = null)
    public function getCurrentValue(): float // current_stock * unit_cost
    public function getStockPercentage(): float // (current_stock / max_stock_level) * 100
}
```

### 2. SupplyCategory Model
`app/Models/SupplyCategory.php`

```php
class SupplyCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'parent_id', 'icon', 'sort_order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    public function children(): HasMany
    public function supplies(): HasMany

    // Scopes
    public function scopeActive($query)
    public function scopeRootCategories($query)

    // Methods
    public function isParent(): bool
}
```

### 3. SupplyStockMovement Model
`app/Models/SupplyStockMovement.php`

```php
class SupplyStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_id', 'movement_type', 'quantity', 'quantity_before', 'quantity_after',
        'reference_type', 'reference_id', 'reference_number',
        'unit_cost', 'total_cost',
        'batch_number', 'expiry_date',
        'from_location', 'to_location',
        'created_by', 'notes', 'movement_date'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'movement_date' => 'datetime',
    ];

    // Relationships
    public function supply(): BelongsTo
    public function reference(): MorphTo
    public function creator(): BelongsTo // User who created movement

    // Scopes
    public function scopeByType($query, $type)
    public function scopeBySupply($query, $supplyId)
    public function scopeByDateRange($query, $from, $to)
}
```

### 4. SupplyUsageLog Model
`app/Models/SupplyUsageLog.php`

```php
class SupplyUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_id', 'appointment_id', 'service_id',
        'quantity_used', 'unit_cost', 'total_cost',
        'used_by', 'customer_id', 'batch_number',
        'notes', 'used_at'
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    // Relationships
    public function supply(): BelongsTo
    public function appointment(): BelongsTo
    public function service(): BelongsTo
    public function user(): BelongsTo
    public function customer(): BelongsTo
}
```

### 5. PurchaseOrder Model
`app/Models/PurchaseOrder.php`

```php
class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number', 'supplier_name', 'supplier_contact', 'supplier_email', 'supplier_phone',
        'status', 'order_date', 'expected_delivery_date', 'received_date',
        'subtotal', 'tax', 'shipping', 'total',
        'tracking_number', 'invoice_number',
        'created_by', 'approved_by', 'received_by', 'delivery_location',
        'notes'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'received_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function items(): HasMany
    public function creator(): BelongsTo
    public function approver(): BelongsTo
    public function receiver(): BelongsTo

    // Scopes
    public function scopeByStatus($query, $status)
    public function scopePending($query)
    public function scopeReceived($query)

    // Methods
    public function calculateTotals()
    public function markAsReceived($userId)
    public function isPending(): bool
    public function isReceived(): bool
    public function canBeEdited(): bool
}
```

### 6. PurchaseOrderItem Model
`app/Models/PurchaseOrderItem.php`

```php
class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'supply_id',
        'quantity_ordered', 'quantity_received', 'unit_cost', 'total_cost',
        'batch_number', 'expiry_date', 'notes'
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    public function supply(): BelongsTo

    // Methods
    public function isFullyReceived(): bool
    public function remainingQuantity(): float
}
```

### 7. SupplyAlert Model
`app/Models/SupplyAlert.php`

```php
class SupplyAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_id', 'alert_type', 'severity', 'message',
        'is_resolved', 'resolved_at', 'resolved_by',
        'current_stock', 'min_stock_level', 'expiry_date'
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'current_stock' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function supply(): BelongsTo
    public function resolver(): BelongsTo

    // Scopes
    public function scopeUnresolved($query)
    public function scopeBySeverity($query, $severity)
    public function scopeByType($query, $type)

    // Methods
    public function resolve($userId)
    public function isCritical(): bool
}
```

---

## Integration with Existing System

### Service Model Enhancement
`app/Models/Service.php` - Add supply tracking relationship

```php
// Add to existing Service model
public function supplies(): BelongsToMany
{
    return $this->belongsToMany(Supply::class, 'service_supplies')
        ->withPivot('quantity_required', 'is_optional')
        ->withTimestamps();
}

// New migration for service_supplies pivot table
Schema::create('service_supplies', function (Blueprint $table) {
    $table->id();
    $table->foreignId('service_id')->constrained()->cascadeOnDelete();
    $table->foreignId('supply_id')->constrained()->cascadeOnDelete();
    $table->decimal('quantity_required', 10, 2)->default(1); // Default usage per service
    $table->boolean('is_optional')->default(false);
    $table->timestamps();
});
```

### Appointment Model Enhancement
`app/Models/Appointment.php` - Add supply usage tracking

```php
// Add to existing Appointment model
public function supplyUsageLogs(): HasMany
{
    return $this->hasMany(SupplyUsageLog::class);
}

// Method to auto-deduct supplies when appointment is completed
public function deductSupplies()
{
    if ($this->status !== 'completed') {
        return;
    }

    foreach ($this->services as $service) {
        foreach ($service->supplies as $supply) {
            // Create usage log
            SupplyUsageLog::create([
                'supply_id' => $supply->id,
                'appointment_id' => $this->id,
                'service_id' => $service->id,
                'quantity_used' => $supply->pivot->quantity_required,
                'unit_cost' => $supply->unit_cost,
                'total_cost' => $supply->unit_cost * $supply->pivot->quantity_required,
                'used_by' => $this->staff_id,
                'customer_id' => $this->customer_id,
                'used_at' => now(),
            ]);

            // Deduct stock
            $supply->removeStock(
                $supply->pivot->quantity_required,
                $this,
                "Used in appointment #{$this->id}"
            );
        }
    }
}
```

---

## Controllers & Routes

### Controllers Structure

```
app/Http/Controllers/Inventory/
├── SupplyController.php              # CRUD for supplies
├── SupplyCategoryController.php      # Category management
├── PurchaseOrderController.php       # Purchase order management
├── StockMovementController.php       # Stock movement logs
├── UsageLogController.php            # Usage tracking
├── AlertController.php               # Alert management
└── ReportController.php              # Inventory reports
```

### Route Structure
`routes/web.php`

```php
// Inventory Management Routes (Protected by auth + inventory permission)
Route::middleware(['auth'])->prefix('inventory')->name('inventory.')->group(function () {

    // Dashboard
    Route::get('/', [InventoryController::class, 'index'])->name('index');

    // Supplies
    Route::resource('supplies', SupplyController::class);
    Route::post('supplies/{supply}/adjust-stock', [SupplyController::class, 'adjustStock'])->name('supplies.adjust');
    Route::get('supplies/{supply}/history', [SupplyController::class, 'history'])->name('supplies.history');

    // Categories
    Route::resource('categories', SupplyCategoryController::class)->except(['show']);

    // Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    // Stock Movements
    Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    Route::get('stock-movements/{stockMovement}', [StockMovementController::class, 'show'])->name('stock-movements.show');

    // Usage Logs
    Route::get('usage-logs', [UsageLogController::class, 'index'])->name('usage-logs.index');
    Route::post('usage-logs', [UsageLogController::class, 'store'])->name('usage-logs.store');

    // Alerts
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/stock-valuation', [ReportController::class, 'stockValuation'])->name('reports.stock-valuation');
    Route::get('reports/usage-summary', [ReportController::class, 'usageSummary'])->name('reports.usage-summary');
    Route::get('reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('reports/purchase-history', [ReportController::class, 'purchaseHistory'])->name('reports.purchase-history');
});
```

---

## Views Structure

```
resources/views/inventory/
├── index.blade.php                   # Dashboard with overview
├── supplies/
│   ├── index.blade.php              # List all supplies
│   ├── create.blade.php             # Create supply
│   ├── edit.blade.php               # Edit supply
│   ├── show.blade.php               # View supply details + history
│   └── partials/
│       ├── form.blade.php           # Supply form fields
│       ├── stock-badge.blade.php    # Stock level badge
│       └── adjust-stock-modal.blade.php
├── categories/
│   ├── index.blade.php              # Category list
│   └── partials/
│       └── form.blade.php
├── purchase-orders/
│   ├── index.blade.php              # PO list
│   ├── create.blade.php             # Create PO
│   ├── edit.blade.php               # Edit PO
│   ├── show.blade.php               # View PO details
│   └── partials/
│       ├── form.blade.php
│       ├── items-table.blade.php
│       └── receive-modal.blade.php
├── stock-movements/
│   ├── index.blade.php              # Movement history
│   └── show.blade.php
├── usage-logs/
│   └── index.blade.php              # Usage history
├── alerts/
│   └── index.blade.php              # All alerts
└── reports/
    ├── index.blade.php              # Reports dashboard
    ├── stock-valuation.blade.php
    ├── usage-summary.blade.php
    ├── low-stock.blade.php
    └── purchase-history.blade.php
```

---

## Permissions & Roles

### Permission Structure
Using Spatie/Permission package (already in codebase)

```php
// Inventory Permissions
'inventory.view'              // View inventory module
'inventory.manage'            // Full inventory management
'inventory.supplies.create'   // Create supplies
'inventory.supplies.edit'     // Edit supplies
'inventory.supplies.delete'   // Delete supplies
'inventory.supplies.adjust'   // Adjust stock levels
'inventory.purchase.create'   // Create purchase orders
'inventory.purchase.approve'  // Approve purchase orders
'inventory.purchase.receive'  // Receive purchase orders
'inventory.reports.view'      // View reports
'inventory.alerts.manage'     // Manage alerts
```

### Role Assignment Examples

**Admin/Owner:**
- All inventory permissions

**Manager:**
- inventory.view
- inventory.manage
- inventory.supplies.*
- inventory.purchase.*
- inventory.reports.view

**Staff:**
- inventory.view
- inventory.supplies.view
- inventory.usage.create (log usage)

**Receptionist:**
- inventory.view (read-only)

---

## Key Features

### 1. Supply Management
- CRUD operations for supplies
- SKU/Barcode tracking
- Category organization
- Multi-unit support (pieces, ml, oz, kg, etc.)
- Min/Max stock levels
- Active/Inactive status
- Storage location tracking

### 2. Stock Tracking
- Real-time stock levels
- Stock movement ledger (all IN/OUT transactions)
- Adjustment capabilities with audit trail
- Batch number tracking (optional)
- Expiry date tracking (optional)
- Multi-location support

### 3. Purchase Orders
- Create and manage purchase orders
- Approval workflow
- Track order status (draft, pending, ordered, received)
- Partial receiving support
- Supplier management
- Cost tracking
- Invoice matching

### 4. Usage Tracking
- Automatic deduction when appointments are completed
- Manual usage logging
- Link supplies to services (default usage amounts)
- Track who used what and when
- Cost tracking per service

### 5. Alerts & Notifications
- Low stock alerts (below min level)
- Out of stock alerts
- Expiry alerts (configurable days before expiry)
- Email/system notifications
- Alert resolution tracking

### 6. Reports & Analytics
- Stock valuation report (total inventory value)
- Usage summary (by supply, service, date range)
- Low stock report
- Purchase history
- Most used supplies
- Cost analysis per service
- Waste tracking

### 7. Dashboard Widgets
- Total supplies count
- Low stock items count
- Out of stock items count
- Total inventory value
- Recent stock movements
- Pending purchase orders
- Active alerts
- Quick actions (add supply, create PO, adjust stock)

---

## Settings Integration

### New Settings to Add
`app/Models/Setting.php` - Add inventory-related settings

```php
// Inventory Settings
'inventory.enabled' => true,
'inventory.auto_deduct' => true,              // Auto-deduct on appointment completion
'inventory.track_batch' => false,              // Track batch numbers
'inventory.track_expiry' => false,             // Track expiry dates
'inventory.alert_low_stock' => true,           // Enable low stock alerts
'inventory.alert_expiry_days' => 30,           // Alert N days before expiry
'inventory.email_alerts' => false,             // Send email alerts
'inventory.default_unit' => 'piece',           // Default unit type
'inventory.currency_symbol' => '$',            // For cost display
```

### Settings UI
Add new tab in `resources/views/settings/index.blade.php`:

```blade
<!-- Inventory Settings Tab -->
<div class="tab-pane" id="inventory-settings">
    <h4 class="mb-3">Inventory Management Settings</h4>

    <form action="{{ route('settings.update') }}" method="POST">
        @csrf

        <!-- Enable/Disable Inventory Module -->
        <div class="mb-3">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="inventory-enabled"
                       name="settings[inventory.enabled]" value="1"
                       {{ setting('inventory.enabled', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="inventory-enabled">
                    Enable Inventory Management
                </label>
            </div>
        </div>

        <!-- Auto-deduct on Appointment Completion -->
        <div class="mb-3">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="inventory-auto-deduct"
                       name="settings[inventory.auto_deduct]" value="1"
                       {{ setting('inventory.auto_deduct', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="inventory-auto-deduct">
                    Auto-deduct supplies when appointment is completed
                </label>
            </div>
        </div>

        <!-- More settings... -->

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
```

---

## UI/UX Design

### Dashboard Layout
```
┌─────────────────────────────────────────────────────────────┐
│  Inventory Dashboard                                         │
├─────────────────────────────────────────────────────────────┤
│  [Total Supplies]  [Low Stock]  [Out of Stock]  [Total Value]│
│       150              12            3            $12,450    │
├─────────────────────────────────────────────────────────────┤
│  Quick Actions:                                              │
│  [+ Add Supply] [+ Purchase Order] [Adjust Stock] [Alerts]  │
├─────────────────────────────────────────────────────────────┤
│  Recent Stock Movements          │  Active Alerts            │
│  ├ Nail Polish Red added +50     │  ├ Low Stock: Polish Blue │
│  ├ Acetone used -5               │  ├ Expired: Gel #123      │
│  └ Cotton Pads adjusted +100     │  └ Out of Stock: Remover  │
├─────────────────────────────────────────────────────────────┤
│  Low Stock Items                                             │
│  [Table with supplies below min level + reorder buttons]    │
└─────────────────────────────────────────────────────────────┘
```

### Supply List Page
- DataTable with search, filter, sort
- Columns: SKU, Name, Category, Stock Level, Min Level, Status, Actions
- Stock level badges (green/yellow/red based on percentage)
- Quick stock adjustment from list
- Export to Excel/PDF

### Supply Detail Page
- Supply information card
- Current stock level with visual indicator
- Stock movement history (tabbed: All, Purchases, Usage, Adjustments)
- Related services using this supply
- Charts: Usage over time, Cost trends
- Quick actions: Adjust Stock, Create PO, Edit

### Purchase Order Form
- Supplier autocomplete (from previous orders)
- Line items table (add/remove rows)
- Auto-calculate totals
- Expected delivery date picker
- Attach documents (invoice, shipping docs)

---

## Implementation Phases

### Phase 1: Core Foundation (Days 1-3)
1. Create all database migrations
2. Create all Eloquent models with relationships
3. Create seeders for sample data (categories, supplies)
4. Create permissions and assign to roles

**Files:**
- `database/migrations/2024_*_create_supplies_table.php`
- `database/migrations/2024_*_create_supply_categories_table.php`
- `database/migrations/2024_*_create_supply_stock_movements_table.php`
- `database/migrations/2024_*_create_supply_usage_logs_table.php`
- `database/migrations/2024_*_create_purchase_orders_table.php`
- `database/migrations/2024_*_create_purchase_order_items_table.php`
- `database/migrations/2024_*_create_supply_alerts_table.php`
- `database/migrations/2024_*_create_service_supplies_table.php`
- `app/Models/Supply.php`
- `app/Models/SupplyCategory.php`
- `app/Models/SupplyStockMovement.php`
- `app/Models/SupplyUsageLog.php`
- `app/Models/PurchaseOrder.php`
- `app/Models/PurchaseOrderItem.php`
- `app/Models/SupplyAlert.php`
- `database/seeders/SupplySeeder.php`
- `database/seeders/SupplyCategorySeeder.php`

### Phase 2: Supply Management (Days 4-6)
1. Create SupplyController with CRUD operations
2. Create SupplyCategoryController
3. Create views for supplies and categories
4. Implement stock adjustment functionality
5. Add to navigation menu

**Files:**
- `app/Http/Controllers/Inventory/SupplyController.php`
- `app/Http/Controllers/Inventory/SupplyCategoryController.php`
- `resources/views/inventory/supplies/*.blade.php`
- `resources/views/inventory/categories/*.blade.php`
- `resources/js/pages/inventory-supplies.js`
- `routes/web.php` (add inventory routes)

### Phase 3: Purchase Orders (Days 7-9)
1. Create PurchaseOrderController
2. Create PO views with line items
3. Implement approval workflow
4. Implement receive functionality (update stock)
5. Add PO number auto-generation

**Files:**
- `app/Http/Controllers/Inventory/PurchaseOrderController.php`
- `resources/views/inventory/purchase-orders/*.blade.php`
- `resources/js/pages/inventory-purchase-orders.js`
- `app/Services/PurchaseOrderService.php`

### Phase 4: Usage Tracking & Integration (Days 10-12)
1. Create UsageLogController
2. Enhance Appointment model with supply deduction
3. Link services to supplies (pivot table)
4. Create usage logging views
5. Implement auto-deduction on appointment completion

**Files:**
- `app/Http/Controllers/Inventory/UsageLogController.php`
- `app/Models/Appointment.php` (enhance)
- `app/Models/Service.php` (enhance)
- `resources/views/inventory/usage-logs/*.blade.php`
- `app/Observers/AppointmentObserver.php`

### Phase 5: Alerts & Notifications (Days 13-14)
1. Create alert generation logic (scheduled job)
2. Create AlertController
3. Create alert views
4. Implement email notifications
5. Add alert widgets to dashboard

**Files:**
- `app/Console/Commands/GenerateSupplyAlerts.php`
- `app/Http/Controllers/Inventory/AlertController.php`
- `resources/views/inventory/alerts/*.blade.php`
- `app/Mail/SupplyAlertMail.php`
- `app/Console/Kernel.php` (schedule alerts)

### Phase 6: Reports & Dashboard (Days 15-17)
1. Create ReportController with various reports
2. Create report views with charts
3. Create inventory dashboard
4. Implement export functionality (Excel/PDF)
5. Add dashboard widgets

**Files:**
- `app/Http/Controllers/Inventory/ReportController.php`
- `resources/views/inventory/reports/*.blade.php`
- `resources/views/inventory/index.blade.php` (dashboard)
- `resources/js/pages/inventory-dashboard.js`
- `resources/js/pages/inventory-reports.js`

### Phase 7: Settings & Polish (Days 18-20)
1. Add inventory settings to Settings page
2. Implement settings in controllers
3. Add permission checks to all controllers
4. Write comprehensive tests
5. Documentation

**Files:**
- `resources/views/settings/partials/inventory.blade.php`
- `app/Http/Middleware/CheckInventoryPermission.php`
- `tests/Feature/Inventory/*.php`
- `docs/inventory-user-guide.md`

---

## Testing Checklist

### Unit Tests
- [ ] Supply model methods (addStock, removeStock, adjustStock, isLowStock)
- [ ] PurchaseOrder model methods (calculateTotals, markAsReceived)
- [ ] Alert generation logic

### Feature Tests
- [ ] Supply CRUD operations
- [ ] Category CRUD operations
- [ ] Purchase order creation and receiving
- [ ] Stock adjustment with movement logging
- [ ] Automatic supply deduction on appointment completion
- [ ] Alert generation and resolution
- [ ] Report generation
- [ ] Permission checks on all routes

### Integration Tests
- [ ] Create appointment → supplies auto-deducted → stock updated → movement logged
- [ ] Receive purchase order → stock updated → movement logged
- [ ] Stock below min level → alert generated
- [ ] Adjust stock → movement logged → alert resolved if applicable

### Manual Testing
- [ ] All forms validate correctly
- [ ] Stock levels update in real-time
- [ ] Alerts appear on dashboard
- [ ] Reports generate accurate data
- [ ] Export functionality works
- [ ] Multi-user concurrent access
- [ ] Mobile responsiveness

---

## Success Criteria

✅ **Core Functionality**
- Admins can create and manage supplies with categories
- Stock levels accurately track additions, usage, and adjustments
- All stock movements are logged with audit trail
- Purchase orders can be created, approved, and received
- Supplies are automatically deducted when appointments are completed

✅ **Alerts & Monitoring**
- Low stock alerts are generated automatically
- Out of stock alerts prevent service booking (if supply required)
- Expiry alerts notify before supplies expire
- Alerts can be resolved and tracked

✅ **Reporting & Analytics**
- Dashboard provides overview of inventory status
- Stock valuation report shows total inventory value
- Usage reports show consumption by service/staff/date
- Purchase history tracks spending and suppliers

✅ **Integration**
- Services can be linked to required supplies
- Appointments automatically deduct supplies on completion
- Settings control auto-deduction and alert thresholds
- Permissions restrict access based on roles

✅ **User Experience**
- Intuitive UI following existing app design patterns
- Fast data tables with search and filtering
- Visual stock level indicators (badges, progress bars)
- Responsive on mobile devices
- Export functionality for reports

---

## File Summary

### New Files to Create (~85 files)

**Migrations (8):**
- `database/migrations/*_create_supplies_table.php`
- `database/migrations/*_create_supply_categories_table.php`
- `database/migrations/*_create_supply_stock_movements_table.php`
- `database/migrations/*_create_supply_usage_logs_table.php`
- `database/migrations/*_create_purchase_orders_table.php`
- `database/migrations/*_create_purchase_order_items_table.php`
- `database/migrations/*_create_supply_alerts_table.php`
- `database/migrations/*_create_service_supplies_table.php`

**Models (7):**
- `app/Models/Supply.php`
- `app/Models/SupplyCategory.php`
- `app/Models/SupplyStockMovement.php`
- `app/Models/SupplyUsageLog.php`
- `app/Models/PurchaseOrder.php`
- `app/Models/PurchaseOrderItem.php`
- `app/Models/SupplyAlert.php`

**Controllers (7):**
- `app/Http/Controllers/Inventory/SupplyController.php`
- `app/Http/Controllers/Inventory/SupplyCategoryController.php`
- `app/Http/Controllers/Inventory/PurchaseOrderController.php`
- `app/Http/Controllers/Inventory/StockMovementController.php`
- `app/Http/Controllers/Inventory/UsageLogController.php`
- `app/Http/Controllers/Inventory/AlertController.php`
- `app/Http/Controllers/Inventory/ReportController.php`

**Views (25+):**
- `resources/views/inventory/index.blade.php`
- `resources/views/inventory/supplies/index.blade.php`
- `resources/views/inventory/supplies/create.blade.php`
- `resources/views/inventory/supplies/edit.blade.php`
- `resources/views/inventory/supplies/show.blade.php`
- `resources/views/inventory/supplies/partials/*.blade.php` (3 files)
- `resources/views/inventory/categories/index.blade.php`
- `resources/views/inventory/categories/partials/form.blade.php`
- `resources/views/inventory/purchase-orders/index.blade.php`
- `resources/views/inventory/purchase-orders/create.blade.php`
- `resources/views/inventory/purchase-orders/edit.blade.php`
- `resources/views/inventory/purchase-orders/show.blade.php`
- `resources/views/inventory/purchase-orders/partials/*.blade.php` (3 files)
- `resources/views/inventory/stock-movements/index.blade.php`
- `resources/views/inventory/stock-movements/show.blade.php`
- `resources/views/inventory/usage-logs/index.blade.php`
- `resources/views/inventory/alerts/index.blade.php`
- `resources/views/inventory/reports/index.blade.php`
- `resources/views/inventory/reports/*.blade.php` (4 report files)
- `resources/views/settings/partials/inventory.blade.php`

**JavaScript (8):**
- `resources/js/pages/inventory-dashboard.js`
- `resources/js/pages/inventory-supplies.js`
- `resources/js/pages/inventory-categories.js`
- `resources/js/pages/inventory-purchase-orders.js`
- `resources/js/pages/inventory-stock-movements.js`
- `resources/js/pages/inventory-usage-logs.js`
- `resources/js/pages/inventory-alerts.js`
- `resources/js/pages/inventory-reports.js`

**Services & Helpers (3):**
- `app/Services/InventoryService.php`
- `app/Services/PurchaseOrderService.php`
- `app/Services/AlertService.php`

**Jobs & Commands (2):**
- `app/Console/Commands/GenerateSupplyAlerts.php`
- `app/Jobs/ProcessSupplyDeduction.php`

**Observers (1):**
- `app/Observers/AppointmentObserver.php` (or enhance existing)

**Mail (1):**
- `app/Mail/SupplyAlertMail.php`

**Seeders (2):**
- `database/seeders/SupplySeeder.php`
- `database/seeders/SupplyCategorySeeder.php`

**Factories (7):**
- `database/factories/SupplyFactory.php`
- `database/factories/SupplyCategoryFactory.php`
- `database/factories/SupplyStockMovementFactory.php`
- `database/factories/SupplyUsageLogFactory.php`
- `database/factories/PurchaseOrderFactory.php`
- `database/factories/PurchaseOrderItemFactory.php`
- `database/factories/SupplyAlertFactory.php`

**Tests (15+):**
- `tests/Feature/Inventory/SupplyTest.php`
- `tests/Feature/Inventory/SupplyCategoryTest.php`
- `tests/Feature/Inventory/PurchaseOrderTest.php`
- `tests/Feature/Inventory/StockMovementTest.php`
- `tests/Feature/Inventory/UsageLogTest.php`
- `tests/Feature/Inventory/AlertTest.php`
- `tests/Feature/Inventory/ReportTest.php`
- `tests/Unit/Models/SupplyTest.php`
- `tests/Unit/Models/PurchaseOrderTest.php`
- (more unit tests for each model)

### Files to Modify (~5 files)

- `routes/web.php` - Add inventory routes
- `app/Models/Service.php` - Add supplies relationship
- `app/Models/Appointment.php` - Add supply deduction logic
- `resources/views/settings/index.blade.php` - Add inventory settings tab
- `vite.config.js` - Add new JS files to input array
- `resources/views/layouts/partials/menu.blade.php` - Add inventory menu item

---

## Sample Data Structure

### Sample Supply Categories
```
Nail Care
├── Polish & Lacquer
├── Gel Products
├── Acrylics
└── Nail Art Supplies

Hair Care
├── Shampoos
├── Conditioners
├── Styling Products
└── Color Products

Skin Care
├── Cleansers
├── Moisturizers
├── Masks
└── Treatments

Salon Supplies
├── Towels & Linens
├── Disposables
├── Cleaning Products
└── Tools & Equipment
```

### Sample Supplies
```sql
-- Nail Polish Red
SKU: NP-RED-001
Category: Polish & Lacquer
Unit: bottle
Unit Size: 15ml
Current Stock: 45 bottles
Min Level: 10 bottles
Max Level: 100 bottles
Unit Cost: $5.99

-- Acetone
SKU: AC-REG-001
Category: Nail Care > Remover
Unit: liter
Current Stock: 3.5 liters
Min Level: 2 liters
Unit Cost: $8.50

-- Cotton Pads
SKU: CP-100-001
Category: Salon Supplies > Disposables
Unit: piece
Pack Size: 100 pieces
Current Stock: 850 pieces
Min Level: 200 pieces
Unit Cost: $0.05
```

---

## Additional Considerations

### Future Enhancements (Phase 2)
- **Barcode Scanning:** Use device camera or barcode scanner for quick stock updates
- **Supplier Management:** Dedicated supplier module with contact info, payment terms
- **Automated Reordering:** Auto-create PO when stock reaches min level
- **Batch & Expiry Tracking:** Full lot tracking with FEFO (First Expire, First Out)
- **Multi-Location:** Support for multiple salon locations with stock transfers
- **Mobile App:** Mobile interface for quick stock checks and adjustments
- **Integration:** Connect with accounting software for cost tracking
- **Waste Tracking:** Detailed waste/shrinkage analysis
- **Supplier Pricing:** Track price changes over time
- **Contract Management:** Track supplier contracts and pricing agreements

### Performance Optimization
- Index all foreign keys and frequently queried columns
- Cache frequently accessed data (categories, active supplies)
- Paginate large data tables
- Optimize stock movement queries with proper indexes
- Use eager loading to prevent N+1 queries
- Consider archiving old stock movements (>1 year)

### Security Considerations
- All routes protected by authentication
- Permission checks on all sensitive operations
- Audit trail for all stock changes (who, what, when)
- Prevent negative stock (configurable setting)
- Validate all inputs (quantities, costs, dates)
- Soft deletes to prevent accidental data loss
- Regular database backups

---

## Conclusion

This comprehensive inventory management system will provide full visibility into supply levels, usage patterns, and purchasing needs. The integration with the existing appointment system ensures accurate tracking of supply consumption, while the alert system prevents stockouts and expired products. The modular design allows for incremental implementation and future enhancements.

**Total Estimated Development Time:** 15-20 days for complete implementation

**Key Benefits:**
- Never run out of critical supplies
- Accurate cost tracking per service
- Reduced waste from expired products
- Data-driven purchasing decisions
- Complete audit trail for compliance
- Improved operational efficiency

---

**Plan Created:** December 25, 2025
**Status:** Ready for Implementation
**Next Steps:** Review plan, get user approval, begin Phase 1 implementation
