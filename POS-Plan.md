# Enterprise Point of Sale (POS) System - Implementation Plan
## Rash Nail Lounge Management System

---

## Executive Summary

This document outlines the comprehensive implementation plan for an enterprise-level Point of Sale (POS) system integrated with the Rash Nail Lounge management platform. The POS system will leverage existing infrastructure (customers, services, appointments, settings) while introducing advanced features for sales processing, service management, financial reporting, and multi-location support.

**Key Objectives:**
- Streamline checkout and payment processing
- Generate professional invoices and receipts
- Track sales, revenue, and profitability
- Manage service packages and offerings
- Support multiple payment methods and split payments
- Provide comprehensive reporting and analytics
- Maintain UI consistency with UBold admin template

---

## Table of Contents

1. [System Architecture](#1-system-architecture)
2. [Database Design](#2-database-design)
3. [Models & Business Logic](#3-models--business-logic)
4. [Controllers & Routes](#4-controllers--routes)
5. [View Layer & UI Components](#5-view-layer--ui-components)
6. [Features Breakdown](#6-features-breakdown)
7. [Permissions & Security](#7-permissions--security)
8. [Implementation Phases](#8-implementation-phases)
9. [Integration Points](#9-integration-points)
10. [Testing Strategy](#10-testing-strategy)

---

## 1. System Architecture

### 1.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    POS System Architecture                   │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Frontend   │  │   Backend    │  │   Database   │      │
│  │              │  │              │  │              │      │
│  │ • POS Screen │  │ • Sale Ctrl  │  │ • sales      │      │
│  │ • Checkout   │  │ • Invoice    │  │ • sale_items │      │
│  │ • Reports    │  │ • Payment    │  │ • invoices   │      │
│  │ • Dashboard  │  │ • Product    │  │ • products   │      │
│  │              │  │ • Inventory  │  │ • inventory  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│         │                  │                  │              │
│         └──────────────────┴──────────────────┘              │
│                            │                                 │
│                ┌───────────┴───────────┐                    │
│                │  Existing System      │                    │
│                │  • Customers          │                    │
│                │  • Services           │                    │
│                │  • Appointments       │                    │
│                │  • Settings           │                    │
│                │  • Users/Permissions  │                    │
│                └───────────────────────┘                    │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Core Components

**1. Point of Sale Interface**
- Service and service package selection
- Cart management
- Customer lookup
- Payment processing
- Receipt generation

**2. Service Management**
- Service catalog
- Service package management
- Service pricing and duration
- Service categories
- Service provider assignments

**3. Invoice System**
- Invoice generation
- Receipt printing
- Email delivery
- PDF export
- Invoice numbering

**4. Payment Processing**
- Multiple payment methods
- Split payments
- Refunds and exchanges
- Payment reconciliation
- Cash drawer management

**5. Reporting & Analytics**
- Sales reports (daily, weekly, monthly)
- Service performance
- Staff performance
- Payment method breakdown
- Profit/loss analysis

### 1.3 Technology Stack

**Backend:**
- Laravel 12.x (PHP 8.2+)
- MySQL/SQLite
- Eloquent ORM
- Laravel Queue for background jobs
- Laravel Mail for receipt delivery

**Frontend:**
- Vite (asset compilation)
- Bootstrap 5.3+
- UBold Admin Template
- ApexCharts (analytics)
- DataTables (transaction listings)
- SweetAlert2 (notifications)

**Infrastructure:**
- Redis for caching
- Laravel Horizon for queue management
- Laravel Telescope for debugging
- Docker for containerization (optional)

**Integrations:**
- Existing customer/service infrastructure
- Spatie Permission System
- PDF Generation (DomPDF)

---

## 2. Database Design

### 2.1 New Tables

#### **service_packages** table
Stores bundled services/packages that can be sold together

```php
Schema::create('service_packages', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2); // Total package price
    $table->integer('duration')->default(0); // Total duration in minutes
    $table->json('included_services'); // Array of service IDs included in the package
    $table->integer('session_count')->default(1); // Number of sessions (for multi-session packages)
    $table->integer('validity_days')->default(30); // Package validity period in days
    $table->boolean('is_available_for_sale')->default(true);
    $table->boolean('is_active')->default(true);
    $table->string('image')->nullable();
    $table->string('slug')->unique();
    $table->softDeletes();
    $table->timestamps();

    $table->index('is_active');
    $table->index('is_available_for_sale');
});
```

#### **service_package_categories** table
```php
Schema::create('service_package_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('slug')->unique();
    $table->integer('order')->default(0);
    $table->timestamps();
});
```

#### **sales** table
Main transaction record

```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('sale_number')->unique(); // e.g., SALE-2025-00001
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->foreignId('user_id')->constrained('users'); // Staff member
    $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

    // Financial columns
    $table->decimal('subtotal', 10, 2);
    $table->decimal('tax_amount', 10, 2)->default(0);
    $table->decimal('discount_amount', 10, 2)->default(0);
    $table->decimal('total_amount', 10, 2);
    $table->decimal('amount_paid', 10, 2)->default(0);
    $table->decimal('change_amount', 10, 2)->default(0);

    // Metadata
    $table->enum('status', ['completed', 'pending', 'cancelled', 'refunded'])->default('completed');
    $table->enum('sale_type', ['walk_in', 'appointment', 'service_package'])->default('walk_in');
    $table->text('notes')->nullable();
    $table->timestamp('sale_date');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['sale_number', 'status', 'sale_date']);
    $table->index('sale_date');
});
```

#### **sale_items** table
Line items for each sale

```php
Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

    // Polymorphic: can be service OR service package
    $table->morphs('sellable'); // sellable_type, sellable_id

    $table->string('item_name'); // Snapshot of name at time of sale
    $table->string('item_code')->nullable(); // For service codes/packages
    $table->integer('quantity')->default(1);
    $table->decimal('unit_price', 10, 2);
    $table->decimal('discount_amount', 10, 2)->default(0);
    $table->decimal('tax_amount', 10, 2)->default(0);
    $table->decimal('line_total', 10, 2);
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index('sale_id');
});
```

#### **payments** table
Track individual payments (supports split payments)

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
    $table->enum('payment_method', ['cash', 'card', 'mobile', 'check', 'bank_transfer', 'store_credit']);
    $table->decimal('amount', 10, 2);
    $table->string('reference_number')->nullable(); // Card transaction ID, check number, etc.
    $table->text('notes')->nullable();
    $table->timestamp('payment_date');
    $table->timestamps();

    $table->index('sale_id');
});
```

#### **invoices** table
```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number')->unique(); // INV-2025-00001
    $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

    // Customer snapshot (in case customer is deleted)
    $table->json('customer_details'); // name, email, phone, address

    // Invoice details
    $table->date('invoice_date');
    $table->date('due_date')->nullable();
    $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('sent');

    // Financial
    $table->decimal('subtotal', 10, 2);
    $table->decimal('tax_amount', 10, 2)->default(0);
    $table->decimal('discount_amount', 10, 2)->default(0);
    $table->decimal('total_amount', 10, 2);
    $table->decimal('amount_paid', 10, 2)->default(0);
    $table->decimal('balance_due', 10, 2)->default(0);

    $table->text('notes')->nullable();
    $table->text('terms')->nullable(); // Payment terms
    $table->timestamp('sent_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['invoice_number', 'status', 'invoice_date']);
});
```

#### **refunds** table
```php
Schema::create('refunds', function (Blueprint $table) {
    $table->id();
    $table->string('refund_number')->unique(); // REF-2025-00001
    $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users'); // Staff who processed refund
    $table->decimal('refund_amount', 10, 2);
    $table->enum('refund_method', ['cash', 'card', 'store_credit', 'original_payment']);
    $table->text('reason')->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('refund_date');
    $table->timestamps();

    $table->index('sale_id');
});
```

#### **service_package_sales** table
Link service packages to sales for easy tracking of package usage

```php
Schema::create('service_package_sales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
    $table->foreignId('service_package_id')->constrained('service_packages')->cascadeOnDelete();
    $table->integer('sessions_used')->default(0);
    $table->integer('sessions_remaining');
    $table->timestamp('expires_at')->nullable();
    $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
    $table->timestamps();

    $table->index(['service_package_id', 'status']);
});
```

#### **cash_drawer_sessions** table
Track cash register sessions

```php
Schema::create('cash_drawer_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users'); // Cashier
    $table->decimal('opening_amount', 10, 2);
    $table->decimal('closing_amount', 10, 2)->nullable();
    $table->decimal('expected_amount', 10, 2)->nullable();
    $table->decimal('difference', 10, 2)->nullable(); // Over/short
    $table->timestamp('opened_at');
    $table->timestamp('closed_at')->nullable();
    $table->text('opening_notes')->nullable();
    $table->text('closing_notes')->nullable();
    $table->enum('status', ['open', 'closed'])->default('open');
    $table->timestamps();

    $table->index(['user_id', 'status', 'opened_at']);
});
```

### 2.2 Existing Tables to Leverage

**Use as-is:**
- `customers` (first_name, last_name, phone, email, etc.)
- `services` (name, price, duration, etc.)
- `appointments` (scheduling integration)
- `users` (staff members)
- `settings` (POS configuration)
- `transactions` (legacy financial records - kept for historical data)

**Integration Points:**
- Link `sales.customer_id` → `customers.id`
- Link `sales.appointment_id` → `appointments.id`
- Link `sale_items.sellable_id` → `services.id` (when sellable_type = 'Service')
- Link `sale_items.sellable_id` → `products.id` (when sellable_type = 'Product')

---

## 3. Models & Business Logic

### 3.1 ServicePackage Model

**File:** `app/Models/ServicePackage.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServicePackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'price', 'duration', 'included_services',
        'session_count', 'validity_days', 'is_available_for_sale', 'is_active', 'image', 'slug'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'included_services' => 'array', // Store as JSON array
        'session_count' => 'integer',
        'validity_days' => 'integer',
        'is_available_for_sale' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(ServicePackageCategory::class, 'category_id');
    }

    public function saleItems()
    {
        return $this->morphMany(SaleItem::class, 'sellable');
    }

    public function servicePackageSales()
    {
        return $this->hasMany(ServicePackageSale::class);
    }

    // Accessors
    public function getPriceAttribute($value)
    {
        return number_format($value, 2);
    }

    public function getIsAvailableForSaleAttribute()
    {
        return $this->is_active && $this->is_available_for_sale;
    }

    public function getIncludedServicesListAttribute()
    {
        $serviceIds = $this->included_services;
        if (is_array($serviceIds)) {
            return Service::whereIn('id', $serviceIds)->get();
        }
        return collect([]);
    }

    // Business Methods
    public function calculatePackagePrice()
    {
        // Option to calculate price based on included services
        $services = $this->included_services_list;
        $totalPrice = 0;

        foreach ($services as $service) {
            $totalPrice += $service->price;
        }

        // Apply any package discounts if needed
        return $totalPrice;
    }

    public function getSessionsRemaining($saleItemId)
    {
        $saleRecord = ServicePackageSale::where('sale_item_id', $saleItemId)
            ->where('service_package_id', $this->id)
            ->first();

        return $saleRecord ? $saleRecord->sessions_remaining : 0;
    }

    public function canBeSold(): bool
    {
        return $this->is_active && $this->is_available_for_sale;
    }

    // Auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($package) {
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->name) . '-' . Str::random(6);
            }
        });
    }

    // Route key binding
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
```

### 3.2 ServicePackageSale Model

**File:** `app/Models/ServicePackageSale.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackageSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_item_id', 'service_package_id', 'sessions_used', 'sessions_remaining', 'expires_at', 'status'
    ];

    protected $casts = [
        'sessions_used' => 'integer',
        'sessions_remaining' => 'integer',
        'expires_at' => 'datetime',
        'status' => 'string'
    ];

    // Relationships
    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function servicePackage()
    {
        return $this->belongsTo(ServicePackage::class);
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    public function getIsFullyUsedAttribute()
    {
        return $this->sessions_remaining <= 0;
    }

    // Business Methods
    public function useSession()
    {
        if ($this->is_fully_used || $this->is_expired) {
            throw new \Exception('Cannot use session: package is fully used or expired');
        }

        $this->sessions_used++;
        $this->sessions_remaining--;

        if ($this->sessions_remaining <= 0) {
            $this->status = 'used';
        }

        $this->save();

        return $this;
    }

    public function addSessions(int $count)
    {
        $this->sessions_remaining += $count;
        if ($this->status === 'used') {
            $this->status = 'active'; // Reactivate if it was marked as used
        }
        $this->save();

        return $this;
    }

    public function expirePackage()
    {
        $this->status = 'expired';
        $this->save();

        return $this;
    }

    public function isValid()
    {
        return $this->is_active && !$this->is_fully_used;
    }
}
```

### 3.3 Sale Model

**File:** `app/Models/Sale.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_number', 'customer_id', 'user_id', 'appointment_id',
        'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'amount_paid', 'change_amount', 'status', 'sale_type',
        'notes', 'sale_date'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'sale_date' => 'datetime',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    // Accessors
    public function getBalanceDueAttribute()
    {
        return $this->total_amount - $this->amount_paid;
    }

    public function getIsPaidAttribute()
    {
        return $this->amount_paid >= $this->total_amount;
    }

    public function getTotalRefundedAttribute()
    {
        return $this->refunds()->sum('refund_amount');
    }

    public function getNetSaleAttribute()
    {
        return $this->total_amount - $this->total_refunded;
    }

    // Business Methods
    public function calculateTotals()
    {
        $taxRate = Setting::get('payment.tax_rate', 0) / 100;

        $this->subtotal = $this->items()->sum('line_total');
        $this->tax_amount = $this->subtotal * $taxRate;
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();

        return $this;
    }

    public function addPayment(string $method, float $amount, ?string $reference = null)
    {
        $payment = $this->payments()->create([
            'payment_method' => $method,
            'amount' => $amount,
            'reference_number' => $reference,
            'payment_date' => now(),
        ]);

        $this->amount_paid += $amount;
        $this->save();

        return $payment;
    }

    public function generateInvoice()
    {
        if ($this->invoice) {
            return $this->invoice;
        }

        $invoiceNumber = $this->generateInvoiceNumber();
        $customerDetails = $this->customer ? [
            'name' => $this->customer->full_name,
            'email' => $this->customer->email,
            'phone' => $this->customer->phone,
            'address' => $this->customer->address,
        ] : [];

        return $this->invoice()->create([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $this->customer_id,
            'customer_details' => $customerDetails,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'balance_due' => $this->balance_due,
            'status' => $this->is_paid ? 'paid' : 'sent',
        ]);
    }

    private function generateInvoiceNumber()
    {
        $prefix = Setting::get('payment.invoice_prefix', 'INV');
        $nextNumber = Setting::get('payment.next_invoice_number', 1);

        $invoiceNumber = sprintf('%s-%s-%05d', $prefix, date('Y'), $nextNumber);

        Setting::set('payment.next_invoice_number', $nextNumber + 1, 'integer');

        return $invoiceNumber;
    }

    public function canBeRefunded(): bool
    {
        return $this->status === 'completed' && $this->total_refunded < $this->total_amount;
    }

    public function processRefund(float $amount, string $method, string $reason, ?string $notes = null)
    {
        if (!$this->canBeRefunded()) {
            throw new \Exception('Sale cannot be refunded');
        }

        if ($amount > ($this->total_amount - $this->total_refunded)) {
            throw new \Exception('Refund amount exceeds sale total');
        }

        $refundNumber = $this->generateRefundNumber();

        $refund = $this->refunds()->create([
            'refund_number' => $refundNumber,
            'user_id' => auth()->id(),
            'refund_amount' => $amount,
            'refund_method' => $method,
            'reason' => $reason,
            'notes' => $notes,
            'refund_date' => now(),
        ]);

        // If fully refunded, update status
        if (($this->total_refunded + $amount) >= $this->total_amount) {
            $this->status = 'refunded';
            $this->save();
        }

        return $refund;
    }

    private function generateRefundNumber()
    {
        $lastRefund = Refund::latest('id')->first();
        $nextNumber = $lastRefund ? ($lastRefund->id + 1) : 1;
        return sprintf('REF-%s-%05d', date('Y'), $nextNumber);
    }

    // Auto-generate sale number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = self::generateSaleNumber();
            }
            if (empty($sale->sale_date)) {
                $sale->sale_date = now();
            }
        });
    }

    private static function generateSaleNumber()
    {
        $lastSale = self::whereDate('created_at', today())->latest('id')->first();
        $nextNumber = $lastSale ? (intval(substr($lastSale->sale_number, -5)) + 1) : 1;
        return sprintf('SALE-%s-%05d', date('Ymd'), $nextNumber);
    }
}
```

### 3.3 SaleItem Model

**File:** `app/Models/SaleItem.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'sellable_type', 'sellable_id', 'item_name', 'item_sku',
        'quantity', 'unit_price', 'discount_amount', 'tax_amount', 'line_total', 'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function sellable()
    {
        return $this->morphTo();
    }

    // Business Methods
    public function calculateLineTotal()
    {
        $this->line_total = ($this->unit_price * $this->quantity) - $this->discount_amount + $this->tax_amount;
        $this->save();
        return $this;
    }

    // Snapshot service/service package details when creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if ($item->sellable) {
                $item->item_name = $item->sellable->name;
                if ($item->sellable instanceof ServicePackage) {
                    $item->item_code = $item->sellable->slug;
                }
            }
        });
    }
}
```

### 3.4 Invoice Model

**File:** `app/Models/Invoice.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'sale_id', 'customer_id', 'customer_details',
        'invoice_date', 'due_date', 'status', 'subtotal', 'tax_amount',
        'discount_amount', 'total_amount', 'amount_paid', 'balance_due',
        'notes', 'terms', 'sent_at'
    ];

    protected $casts = [
        'customer_details' => 'array',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'sent_at' => 'datetime',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date->isPast() && $this->balance_due > 0;
    }

    public function getIsPaidAttribute()
    {
        return $this->balance_due <= 0;
    }

    // Business Methods
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'amount_paid' => $this->total_amount,
            'balance_due' => 0,
        ]);
    }

    public function toPdf()
    {
        // PDF generation logic (using DomPDF or similar)
        // return PDF::loadView('pos.invoices.pdf', ['invoice' => $this])->output();
    }

    // Route key binding
    public function getRouteKeyName()
    {
        return 'invoice_number';
    }
}
```

### 3.5 CashDrawerSession Model

**File:** `app/Models/CashDrawerSession.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashDrawerSession extends Model
{
    protected $fillable = [
        'user_id', 'opening_amount', 'closing_amount', 'expected_amount',
        'difference', 'opened_at', 'closed_at', 'opening_notes', 'closing_notes', 'status'
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return Sale::where('user_id', $this->user_id)
            ->whereBetween('created_at', [$this->opened_at, $this->closed_at ?? now()])
            ->where('status', 'completed');
    }

    // Business Methods
    public function calculateExpectedAmount()
    {
        $cashSales = $this->sales()
            ->whereHas('payments', function ($query) {
                $query->where('payment_method', 'cash');
            })
            ->get()
            ->sum(function ($sale) {
                return $sale->payments()->where('payment_method', 'cash')->sum('amount');
            });

        $this->expected_amount = $this->opening_amount + $cashSales;
        $this->save();

        return $this->expected_amount;
    }

    public function close(float $closingAmount, ?string $notes = null)
    {
        $this->calculateExpectedAmount();

        $this->update([
            'closing_amount' => $closingAmount,
            'difference' => $closingAmount - $this->expected_amount,
            'closed_at' => now(),
            'closing_notes' => $notes,
            'status' => 'closed',
        ]);

        return $this;
    }

    // Check if drawer is currently open
    public static function currentSession()
    {
        return self::where('user_id', auth()->id())
            ->where('status', 'open')
            ->latest()
            ->first();
    }

    public static function hasOpenSession()
    {
        return self::currentSession() !== null;
    }
}
```

---

## 4. Controllers & Routes

### 4.1 POS Controllers

#### **POSController** - Main POS interface
**File:** `app/Http/Controllers/POSController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\CashDrawerSession;
use Illuminate\Http\Request;

class POSController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:process sales');
    }

    /**
     * Display POS interface
     */
    public function index()
    {
        $this->authorize('process sales');

        // Check if cash drawer is open
        if (!CashDrawerSession::hasOpenSession()) {
            return redirect()->route('pos.cash-drawer.open')
                ->with('warning', 'Please open the cash drawer before starting sales.');
        }

        $products = Product::where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->with('category')
            ->get();

        $services = Service::where('is_active', true)->get();

        $recentCustomers = Customer::latest()
            ->take(10)
            ->get();

        return view('pos.index', compact('products', 'services', 'recentCustomers'));
    }

    /**
     * Process sale (AJAX)
     */
    public function processSale(Request $request)
    {
        $this->authorize('process sales');

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:product,service',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,mobile,check',
            'payments.*.amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            \DB::beginTransaction();

            // Create sale
            $sale = Sale::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'status' => 'completed',
                'sale_type' => 'walk_in',
            ]);

            // Add items
            foreach ($validated['items'] as $item) {
                $sellable = $item['type'] === 'product'
                    ? Product::findOrFail($item['id'])
                    : Service::findOrFail($item['id']);

                $sale->items()->create([
                    'sellable_type' => get_class($sellable),
                    'sellable_id' => $sellable->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                ]);
            }

            // Calculate totals
            $sale->calculateTotals();

            // Add payments
            foreach ($validated['payments'] as $payment) {
                $sale->addPayment($payment['method'], $payment['amount']);
            }

            // Calculate change
            $totalPaid = collect($validated['payments'])->sum('amount');
            $sale->change_amount = max(0, $totalPaid - $sale->total_amount);
            $sale->save();

            // Generate invoice
            $invoice = $sale->generateInvoice();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully!',
                'sale' => $sale->load(['items.sellable', 'payments', 'invoice']),
                'invoice_url' => route('pos.invoices.show', $invoice->invoice_number),
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search products/services (AJAX)
     */
    public function searchItems(Request $request)
    {
        $query = $request->input('q');

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('barcode', 'LIKE', "%{$query}%");
            })
            ->where('quantity_in_stock', '>', 0)
            ->take(10)
            ->get();

        $services = Service::where('is_active', true)
            ->where('name', 'LIKE', "%{$query}%")
            ->take(10)
            ->get();

        return response()->json([
            'products' => $products,
            'services' => $services,
        ]);
    }
}
```

#### **CashDrawerController**
**File:** `app/Http/Controllers/CashDrawerController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\CashDrawerSession;
use Illuminate\Http\Request;

class CashDrawerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage cash drawer');
    }

    public function showOpenForm()
    {
        if (CashDrawerSession::hasOpenSession()) {
            return redirect()->route('pos.index')
                ->with('info', 'Cash drawer is already open.');
        }

        return view('pos.cash-drawer.open');
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (CashDrawerSession::hasOpenSession()) {
            return back()->with('error', 'Cash drawer is already open.');
        }

        $session = CashDrawerSession::create([
            'user_id' => auth()->id(),
            'opening_amount' => $validated['opening_amount'],
            'opening_notes' => $validated['notes'],
            'opened_at' => now(),
            'status' => 'open',
        ]);

        return redirect()->route('pos.index')
            ->with('success', 'Cash drawer opened successfully!');
    }

    public function showCloseForm()
    {
        $session = CashDrawerSession::currentSession();

        if (!$session) {
            return redirect()->route('pos.index')
                ->with('error', 'No open cash drawer session found.');
        }

        $session->calculateExpectedAmount();

        return view('pos.cash-drawer.close', compact('session'));
    }

    public function close(Request $request)
    {
        $validated = $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $session = CashDrawerSession::currentSession();

        if (!$session) {
            return back()->with('error', 'No open cash drawer session found.');
        }

        $session->close($validated['closing_amount'], $validated['notes']);

        return redirect()->route('pos.cash-drawer.report', $session->id)
            ->with('success', 'Cash drawer closed successfully!');
    }

    public function report($sessionId)
    {
        $session = CashDrawerSession::with('user')->findOrFail($sessionId);

        $this->authorize('view', $session);

        $sales = $session->sales()->with(['customer', 'items', 'payments'])->get();

        return view('pos.cash-drawer.report', compact('session', 'sales'));
    }
}
```

#### **SaleController**
**File:** `app/Http/Controllers/SaleController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view sales');
    }

    public function index(Request $request)
    {
        $this->authorize('view sales');

        $query = Sale::with(['customer', 'user', 'items'])
            ->latest('sale_date');

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $sales = $query->paginate(20);

        $stats = [
            'total_sales' => Sale::where('status', 'completed')->sum('total_amount'),
            'total_transactions' => Sale::where('status', 'completed')->count(),
            'average_sale' => Sale::where('status', 'completed')->avg('total_amount'),
        ];

        return view('pos.sales.index', compact('sales', 'stats'));
    }

    public function show(Sale $sale)
    {
        $this->authorize('view', $sale);

        $sale->load(['customer', 'user', 'items.sellable', 'payments', 'invoice', 'refunds']);

        return view('pos.sales.show', compact('sale'));
    }

    public function refund(Sale $sale)
    {
        $this->authorize('refund', $sale);

        return view('pos.sales.refund', compact('sale'));
    }

    public function processRefund(Request $request, Sale $sale)
    {
        $this->authorize('refund', $sale);

        $validated = $request->validate([
            'refund_amount' => 'required|numeric|min:0.01',
            'refund_method' => 'required|in:cash,card,store_credit,original_payment',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $refund = $sale->processRefund(
                $validated['refund_amount'],
                $validated['refund_method'],
                $validated['reason'],
                $validated['notes'] ?? null
            );

            return redirect()->route('pos.sales.show', $sale)
                ->with('success', 'Refund processed successfully!');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

#### **InvoiceController**
**File:** `app/Http/Controllers/InvoiceController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view invoices');
    }

    public function index()
    {
        $invoices = Invoice::with(['customer', 'sale'])
            ->latest('invoice_date')
            ->paginate(20);

        return view('pos.invoices.index', compact('invoices'));
    }

    public function show($invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->with(['sale.items.sellable', 'sale.payments', 'customer'])
            ->firstOrFail();

        $this->authorize('view', $invoice);

        return view('pos.invoices.show', compact('invoice'));
    }

    public function download($invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->with(['sale.items.sellable', 'customer'])
            ->firstOrFail();

        $this->authorize('view', $invoice);

        $pdf = $invoice->toPdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$invoice->invoice_number}.pdf\"");
    }

    public function email($invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->firstOrFail();

        $this->authorize('email', $invoice);

        if (!$invoice->customer || !$invoice->customer->email) {
            return back()->with('error', 'Customer email not found.');
        }

        // Send email logic
        // Mail::to($invoice->customer->email)->send(new InvoiceMail($invoice));

        $invoice->markAsSent();

        return back()->with('success', 'Invoice emailed successfully!');
    }
}
```

#### **ProductController**
**File:** `app/Http/Controllers/ProductController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view products');
    }

    public function index()
    {
        $products = Product::with(['category', 'supplier'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total_products' => Product::count(),
            'low_stock' => Product::whereColumn('quantity_in_stock', '<=', 'low_stock_threshold')->count(),
            'out_of_stock' => Product::where('quantity_in_stock', 0)->count(),
        ];

        return view('pos.products.index', compact('products', 'stats'));
    }

    public function create()
    {
        $this->authorize('create products');

        $categories = ProductCategory::all();
        $suppliers = Supplier::where('is_active', true)->get();

        return view('pos.products.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create products');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products,sku',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'barcode' => 'nullable|string|unique:products,barcode',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        return redirect()->route('pos.products.show', $product)
            ->with('success', 'Product created successfully!');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'supplier', 'inventoryAdjustments' => function ($query) {
            $query->latest()->take(10);
        }]);

        return view('pos.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->authorize('edit products');

        $categories = ProductCategory::all();
        $suppliers = Supplier::where('is_active', true)->get();

        return view('pos.products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('edit products');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image) {
                \Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('pos.products.show', $product)
            ->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete products');

        $product->delete();

        return redirect()->route('pos.products.index')
            ->with('success', 'Product deleted successfully!');
    }

    /**
     * Adjust inventory
     */
    public function adjustInventory(Request $request, Product $product)
    {
        $this->authorize('adjust inventory');

        $validated = $request->validate([
            'quantity_change' => 'required|integer|not_in:0',
            'type' => 'required|in:adjustment,damage,theft,return',
            'reason' => 'required|string',
        ]);

        if ($validated['quantity_change'] > 0) {
            $product->increaseStock($validated['quantity_change'], $validated['type']);
        } else {
            $product->decreaseStock(abs($validated['quantity_change']), $validated['type']);
        }

        return back()->with('success', 'Inventory adjusted successfully!');
    }
}
```

### 4.2 Routes

**File:** `routes/web.php`

```php
// POS Routes - Must be before catch-all routes
Route::middleware(['auth'])->prefix('pos')->name('pos.')->group(function () {

    // Main POS Interface
    Route::get('/', [POSController::class, 'index'])->name('index');
    Route::post('/process-sale', [POSController::class, 'processSale'])->name('process-sale');
    Route::get('/search', [POSController::class, 'searchItems'])->name('search');

    // Cash Drawer Management
    Route::prefix('cash-drawer')->name('cash-drawer.')->group(function () {
        Route::get('/open', [CashDrawerController::class, 'showOpenForm'])->name('open-form');
        Route::post('/open', [CashDrawerController::class, 'open'])->name('open');
        Route::get('/close', [CashDrawerController::class, 'showCloseForm'])->name('close-form');
        Route::post('/close', [CashDrawerController::class, 'close'])->name('close');
        Route::get('/report/{session}', [CashDrawerController::class, 'report'])->name('report');
    });

    // Sales Management
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('/{sale}', [SaleController::class, 'show'])->name('show');
        Route::get('/{sale}/refund', [SaleController::class, 'refund'])->name('refund');
        Route::post('/{sale}/refund', [SaleController::class, 'processRefund'])->name('process-refund');
    });

    // Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoiceNumber}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoiceNumber}/download', [InvoiceController::class, 'download'])->name('download');
        Route::post('/{invoiceNumber}/email', [InvoiceController::class, 'email'])->name('email');
    });

    // Products
    Route::resource('products', ProductController::class);
    Route::post('/products/{product}/adjust-inventory', [ProductController::class, 'adjustInventory'])
        ->name('products.adjust-inventory');

    // Product Categories
    Route::resource('product-categories', ProductCategoryController::class)
        ->except(['show']);

    // Suppliers
    Route::resource('suppliers', SupplierController::class);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/staff-performance', [ReportController::class, 'staffPerformance'])->name('staff');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
    });
});
```

---

## 5. View Layer & UI Components

### 5.1 Main POS Interface

**File:** `resources/views/pos/index.blade.php`

```blade
@extends('layouts.vertical', ['title' => 'Point of Sale'])

@section('css')
    @vite([
        'node_modules/choices.js/public/assets/styles/choices.min.css',
        'node_modules/sweetalert2/dist/sweetalert2.min.css'
    ])
    <style>
        .pos-product-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .pos-product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .numeric-keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .keypad-btn {
            height: 50px;
            font-size: 1.2rem;
        }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Point of Sale', 'subtitle' => 'Process sales and manage transactions'])

    <div class="row">
        <!-- Products/Services Column (Left) -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <!-- Search Bar -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" id="item-search"
                                   placeholder="Search services or packages (name, price, duration)...">
                        </div>
                    </div>

                    <!-- Tabs: Services / Service Packages -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#services-tab">
                                <i class="ti ti-briefcase me-1"></i> Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#packages-tab">
                                <i class="ti ti-package me-1"></i> Packages
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Services Tab -->
                        <div class="tab-pane show active" id="services-tab">
                            <div class="row row-cols-xxl-4 row-cols-md-3 row-cols-2 g-3" id="services-grid">
                                @foreach($services as $service)
                                <div class="col">
                                    <div class="card pos-product-card h-100"
                                         data-service-id="{{ $service->id }}"
                                         data-service-type="service"
                                         data-service-name="{{ $service->name }}"
                                         data-service-price="{{ $service->price }}">
                                        <div class="card-body text-center p-3">
                                            <div class="avatar-lg mx-auto mb-2">
                                                <span class="avatar-title bg-info-subtle text-info rounded">
                                                    <i class="ti ti-briefcase fs-24"></i>
                                                </span>
                                            </div>
                                            <h6 class="mb-1 fw-semibold">{{ $service->name }}</h6>
                                            <p class="text-muted mb-1 fs-sm">${{ number_format($service->price, 2) }}</p>
                                            <span class="badge badge-soft-info fs-xxs">{{ $service->duration }} min</span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Service Packages Tab -->
                        <div class="tab-pane" id="packages-tab">
                            <div class="row row-cols-xxl-4 row-cols-md-3 row-cols-2 g-3" id="packages-grid">
                                @foreach($servicePackages as $package)
                                <div class="col">
                                    <div class="card pos-product-card h-100"
                                         data-service-id="{{ $package->id }}"
                                         data-service-type="package"
                                         data-service-name="{{ $package->name }}"
                                         data-service-price="{{ $package->price }}">
                                        <div class="card-body text-center p-3">
                                            <div class="avatar-lg mx-auto mb-2">
                                                <span class="avatar-title bg-success-subtle text-success rounded">
                                                    <i class="ti ti-package fs-24"></i>
                                                </span>
                                            </div>
                                            <h6 class="mb-1 fw-semibold">{{ $package->name }}</h6>
                                            <p class="text-muted mb-1 fs-sm">${{ number_format($package->price, 2) }}</p>
                                            <span class="badge badge-soft-success fs-xxs">{{ $package->session_count }} Sessions</span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Column (Right) -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h5 class="card-title mb-0">Current Sale</h5>
                </div>
                <div class="card-body">
                    <!-- Customer Selection -->
                    <div class="mb-3">
                        <label class="form-label">Customer (Optional)</label>
                        <select class="form-select" id="customer-select">
                            <option value="">Walk-in Customer</option>
                            @foreach($recentCustomers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cart Items -->
                    <div class="mb-3" style="max-height: 300px; overflow-y: auto;">
                        <div id="cart-items">
                            <p class="text-muted text-center">No items in cart</p>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="fw-semibold" id="cart-subtotal">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (<span id="tax-rate">0</span>%):</span>
                            <span class="fw-semibold" id="cart-tax">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <span class="fw-semibold text-danger" id="cart-discount">-$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 mb-3">
                            <span class="fs-lg fw-bold">Total:</span>
                            <span class="fs-lg fw-bold text-primary" id="cart-total">$0.00</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-lg" id="btn-checkout" disabled>
                                <i class="ti ti-credit-card me-1"></i> Checkout
                            </button>
                            <button type="button" class="btn btn-light" id="btn-clear-cart">
                                <i class="ti ti-trash me-1"></i> Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkout-modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Payment Summary -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Order Summary</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="checkout-subtotal">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax:</span>
                                <span id="checkout-tax">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Discount:</span>
                                <span class="text-danger" id="checkout-discount">-$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between border-top pt-2 mb-3">
                                <span class="fw-bold fs-lg">Total Due:</span>
                                <span class="fw-bold fs-lg text-primary" id="checkout-total">$0.00</span>
                            </div>

                            <!-- Discount Input -->
                            <div class="mb-3">
                                <label class="form-label">Apply Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="discount-input"
                                           placeholder="0.00" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Payment Method</h6>
                            <div id="payment-methods">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                           id="payment-cash" value="cash" checked>
                                    <label class="form-check-label" for="payment-cash">
                                        <i class="ti ti-cash me-1"></i> Cash
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                           id="payment-card" value="card">
                                    <label class="form-check-label" for="payment-card">
                                        <i class="ti ti-credit-card me-1"></i> Card
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                           id="payment-mobile" value="mobile">
                                    <label class="form-check-label" for="payment-mobile">
                                        <i class="ti ti-device-mobile me-1"></i> Mobile Payment
                                    </label>
                                </div>
                            </div>

                            <!-- Amount Received (for cash) -->
                            <div class="mt-3" id="cash-payment-section">
                                <label class="form-label">Amount Received</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control form-control-lg"
                                           id="amount-received" placeholder="0.00" step="0.01" min="0">
                                </div>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Change:</span>
                                    <span class="text-success" id="change-amount">$0.00</span>
                                </div>
                            </div>

                            <!-- Numeric Keypad -->
                            <div class="mt-3 numeric-keypad">
                                <button type="button" class="btn btn-light keypad-btn" data-num="1">1</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="2">2</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="3">3</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="4">4</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="5">5</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="6">6</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="7">7</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="8">8</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="9">9</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num=".">.</button>
                                <button type="button" class="btn btn-light keypad-btn" data-num="0">0</button>
                                <button type="button" class="btn btn-danger keypad-btn" id="keypad-clear">
                                    <i class="ti ti-backspace"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mt-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="sale-notes" rows="2"
                                  placeholder="Add any notes for this sale..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-lg" id="btn-complete-sale">
                        <i class="ti ti-check me-1"></i> Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite([
        'resources/js/pages/pos.js',
        'node_modules/choices.js/public/assets/scripts/choices.min.js',
        'node_modules/sweetalert2/dist/sweetalert2.min.js'
    ])
@endsection
```

### 5.2 POS JavaScript

**File:** `resources/js/pages/pos.js`

```javascript
import Swal from 'sweetalert2';
import Choices from 'choices.js';

let cart = [];
let taxRate = 0;

document.addEventListener('DOMContentLoaded', function() {
    initializePOS();
});

function initializePOS() {
    // Initialize customer dropdown
    const customerSelect = document.getElementById('customer-select');
    if (customerSelect) {
        new Choices(customerSelect, {
            searchEnabled: true,
            placeholder: true,
        });
    }

    // Get tax rate from settings
    taxRate = parseFloat(document.querySelector('meta[name="tax-rate"]')?.content || 0);

    // Product/Service card click
    document.querySelectorAll('.pos-product-card').forEach(card => {
        card.addEventListener('click', function() {
            addToCart(this.dataset);
        });
    });

    // Checkout button
    document.getElementById('btn-checkout')?.addEventListener('click', openCheckoutModal);

    // Clear cart button
    document.getElementById('btn-clear-cart')?.addEventListener('click', clearCart);

    // Complete sale button
    document.getElementById('btn-complete-sale')?.addEventListener('click', completeSale);

    // Payment method change
    document.querySelectorAll('[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', handlePaymentMethodChange);
    });

    // Amount received input
    document.getElementById('amount-received')?.addEventListener('input', calculateChange);

    // Discount input
    document.getElementById('discount-input')?.addEventListener('input', updateCheckoutTotals);

    // Numeric keypad
    document.querySelectorAll('.keypad-btn[data-num]').forEach(btn => {
        btn.addEventListener('click', function() {
            appendToAmountReceived(this.dataset.num);
        });
    });

    document.getElementById('keypad-clear')?.addEventListener('click', clearAmountReceived);

    // Search functionality
    let searchTimeout;
    document.getElementById('item-search')?.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => searchItems(e.target.value), 300);
    });
}

function addToCart(itemData) {
    const existingItem = cart.find(item =>
        item.id === parseInt(itemData.productId) && item.type === itemData.productType
    );

    if (existingItem) {
        // Check stock for products
        if (itemData.productType === 'product') {
            const maxStock = parseInt(itemData.productStock);
            if (existingItem.quantity >= maxStock) {
                Swal.fire({
                    title: 'Out of Stock',
                    text: 'Cannot add more items. Maximum stock reached.',
                    icon: 'warning',
                    confirmButtonClass: 'btn btn-primary'
                });
                return;
            }
        }
        existingItem.quantity++;
    } else {
        cart.push({
            id: parseInt(itemData.productId),
            type: itemData.productType,
            name: itemData.productName,
            price: parseFloat(itemData.productPrice),
            quantity: 1,
            maxStock: itemData.productStock ? parseInt(itemData.productStock) : null
        });
    }

    renderCart();
    updateCartTotals();
}

function renderCart() {
    const cartContainer = document.getElementById('cart-items');

    if (cart.length === 0) {
        cartContainer.innerHTML = '<p class="text-muted text-center">No items in cart</p>';
        document.getElementById('btn-checkout').disabled = true;
        return;
    }

    document.getElementById('btn-checkout').disabled = false;

    let html = '';
    cart.forEach((item, index) => {
        const lineTotal = item.price * item.quantity;
        html += `
            <div class="cart-item">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="flex-grow-1">
                        <h6 class="mb-0">${item.name}</h6>
                        <small class="text-muted">$${item.price.toFixed(2)} each</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light"
                            onclick="removeFromCart(${index})">
                        <i class="ti ti-trash fs-sm"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-light"
                                onclick="updateQuantity(${index}, -1)">-</button>
                        <span class="btn btn-light disabled">${item.quantity}</span>
                        <button type="button" class="btn btn-light"
                                onclick="updateQuantity(${index}, 1)"
                                ${item.maxStock && item.quantity >= item.maxStock ? 'disabled' : ''}>+</button>
                    </div>
                    <span class="fw-semibold">$${lineTotal.toFixed(2)}</span>
                </div>
            </div>
        `;
    });

    cartContainer.innerHTML = html;
}

function updateQuantity(index, change) {
    const item = cart[index];
    item.quantity += change;

    if (item.quantity <= 0) {
        cart.splice(index, 1);
    } else if (item.maxStock && item.quantity > item.maxStock) {
        item.quantity = item.maxStock;
        Swal.fire({
            title: 'Stock Limit',
            text: 'Maximum available stock reached.',
            icon: 'warning',
            confirmButtonClass: 'btn btn-primary',
            timer: 2000
        });
    }

    renderCart();
    updateCartTotals();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
    updateCartTotals();
}

function clearCart() {
    if (cart.length === 0) return;

    Swal.fire({
        title: 'Clear Cart?',
        text: 'All items will be removed from the cart.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear it',
        cancelButtonText: 'Cancel',
        confirmButtonClass: 'btn btn-danger',
        cancelButtonClass: 'btn btn-light'
    }).then((result) => {
        if (result.isConfirmed) {
            cart = [];
            renderCart();
            updateCartTotals();
        }
    });
}

function updateCartTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;

    document.getElementById('cart-subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('cart-tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('cart-total').textContent = `$${total.toFixed(2)}`;
    document.getElementById('tax-rate').textContent = taxRate.toFixed(1);
}

function openCheckoutModal() {
    if (cart.length === 0) return;

    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;

    document.getElementById('checkout-subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('checkout-tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('checkout-discount').textContent = `-$0.00`;
    document.getElementById('checkout-total').textContent = `$${total.toFixed(2)}`;
    document.getElementById('discount-input').value = '';
    document.getElementById('amount-received').value = '';
    document.getElementById('change-amount').textContent = '$0.00';

    const modal = new bootstrap.Modal(document.getElementById('checkout-modal'));
    modal.show();

    // Auto-focus amount received
    setTimeout(() => {
        document.getElementById('amount-received')?.focus();
    }, 500);
}

function updateCheckoutTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = parseFloat(document.getElementById('discount-input').value || 0);
    const tax = (subtotal - discount) * (taxRate / 100);
    const total = subtotal + tax - discount;

    document.getElementById('checkout-discount').textContent = `-$${discount.toFixed(2)}`;
    document.getElementById('checkout-total').textContent = `$${total.toFixed(2)}`;

    calculateChange();
}

function handlePaymentMethodChange(e) {
    const cashSection = document.getElementById('cash-payment-section');
    if (e.target.value === 'cash') {
        cashSection.style.display = 'block';
    } else {
        cashSection.style.display = 'none';
        document.getElementById('amount-received').value = '';
        document.getElementById('change-amount').textContent = '$0.00';
    }
}

function calculateChange() {
    const total = parseFloat(document.getElementById('checkout-total').textContent.replace('$', ''));
    const received = parseFloat(document.getElementById('amount-received').value || 0);
    const change = Math.max(0, received - total);

    document.getElementById('change-amount').textContent = `$${change.toFixed(2)}`;
}

function appendToAmountReceived(digit) {
    const input = document.getElementById('amount-received');
    input.value += digit;
    calculateChange();
}

function clearAmountReceived() {
    const input = document.getElementById('amount-received');
    input.value = input.value.slice(0, -1);
    calculateChange();
}

function completeSale() {
    const customerId = document.getElementById('customer-select').value || null;
    const paymentMethod = document.querySelector('[name="payment_method"]:checked').value;
    const discount = parseFloat(document.getElementById('discount-input').value || 0);
    const notes = document.getElementById('sale-notes').value;
    const total = parseFloat(document.getElementById('checkout-total').textContent.replace('$', ''));

    let amountPaid = total;
    if (paymentMethod === 'cash') {
        amountPaid = parseFloat(document.getElementById('amount-received').value || 0);
        if (amountPaid < total) {
            Swal.fire({
                title: 'Insufficient Amount',
                text: 'Amount received is less than the total.',
                icon: 'error',
                confirmButtonClass: 'btn btn-danger'
            });
            return;
        }
    }

    const saleData = {
        customer_id: customerId,
        items: cart.map(item => ({
            type: item.type,
            id: item.id,
            quantity: item.quantity,
            price: item.price
        })),
        discount_amount: discount,
        payments: [{
            method: paymentMethod,
            amount: amountPaid
        }],
        notes: notes
    };

    // Show loading
    const submitBtn = document.getElementById('btn-complete-sale');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    fetch('/pos/process-sale', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(saleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('checkout-modal')).hide();

            // Clear cart
            cart = [];
            renderCart();
            updateCartTotals();

            // Show success with invoice link
            Swal.fire({
                title: 'Sale Completed!',
                html: `
                    <p>Sale processed successfully.</p>
                    <a href="${data.invoice_url}" target="_blank" class="btn btn-primary">
                        <i class="ti ti-file me-1"></i> View Invoice
                    </a>
                `,
                icon: 'success',
                confirmButtonClass: 'btn btn-success'
            });
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Error!',
            text: error.message || 'Failed to process sale',
            icon: 'error',
            confirmButtonClass: 'btn btn-danger'
        });
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function searchItems(query) {
    if (!query || query.length < 2) {
        // Show all items
        document.querySelectorAll('.pos-product-card').forEach(card => {
            card.parentElement.style.display = 'block';
        });
        return;
    }

    fetch(`/pos/search?q=${encodeURIComponent(query)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Hide all cards
        document.querySelectorAll('.pos-product-card').forEach(card => {
            card.parentElement.style.display = 'none';
        });

        // Show matching products
        data.products.forEach(product => {
            const card = document.querySelector(`[data-product-id="${product.id}"][data-product-type="product"]`);
            if (card) card.parentElement.style.display = 'block';
        });

        // Show matching services
        data.services.forEach(service => {
            const card = document.querySelector(`[data-product-id="${service.id}"][data-product-type="service"]`);
            if (card) card.parentElement.style.display = 'block';
        });
    })
    .catch(error => {
        console.error('Search error:', error);
    });
}

// Make functions globally available
window.updateQuantity = updateQuantity;
window.removeFromCart = removeFromCart;
```

*Due to character limit, continuing in next section...*

---

## 6. Features Breakdown

### 6.1 Core POS Features

**1. Product/Service Selection**
- Visual grid layout with product cards
- Tab-based navigation (Products vs Services)
- Quick search by name, SKU, or barcode
- Category filtering
- Stock status indicators
- Click to add to cart

**2. Shopping Cart Management**
- Real-time cart updates
- Quantity adjustment with +/- buttons
- Individual item removal
- Stock validation for products
- Line item totals
- Clear cart functionality

**3. Checkout Process**
- Customer selection (optional for walk-ins)
- Multiple payment methods (cash, card, mobile, check)
- Split payment support
- Discount application
- Tax calculation (configurable rate)
- Cash change calculation
- Numeric keypad for quick input
- Sale notes/comments

**4. Invoice Generation**
- Auto-generated invoice numbers
- Professional invoice layout
- Customer details snapshot
- Itemized line items
- Tax and discount breakdown
- PDF export
- Email delivery
- Print functionality

**5. Cash Drawer Management**
- Open drawer with starting cash
- Track all cash transactions during session
- Close drawer with actual cash count
- Calculate expected vs actual (over/short)
- Session reports
- Multi-user support (each cashier has own session)

### 6.2 Inventory Management Features

**1. Product Catalog**
- Product CRUD operations
- SKU and barcode management
- Cost price vs selling price
- Profit margin calculation
- Product categories
- Supplier tracking
- Product images
- Active/inactive status

**2. Stock Tracking**
- Real-time inventory updates
- Automatic stock decrease on sale
- Manual inventory adjustments
- Low stock alerts
- Out of stock indicators
- Inventory adjustment history
- Adjustment reasons (sale, purchase, damage, theft, return)

**3. Supplier Management**
- Supplier CRUD operations
- Contact information
- Active/inactive status
- Product-supplier relationships

### 6.3 Sales & Transaction Features

**1. Sales History**
- Paginated sales list
- Search and filtering (date, status, staff, customer)
- Sale detail view
- Item breakdown
- Payment details
- Invoice access
- Refund history

**2. Refund Processing**
- Full or partial refunds
- Multiple refund methods
- Refund reason tracking
- Automatic sale status update
- Stock restoration (for products)
- Refund receipt generation

**3. Payment Processing**
- Multiple payment methods
- Split payments (pay with multiple methods)
- Payment method validation
- Reference number tracking (card transaction ID, check number)
- Payment date tracking

### 6.4 Reporting & Analytics Features

**1. Sales Reports**
- Daily sales summary
- Sales by date range
- Sales by payment method
- Sales by category
- Top selling products/services
- Revenue trends (charts)
- Average sale value

**2. Product Performance**
- Best selling products
- Low performing products
- Profit by product
- Stock movement report
- Inventory valuation

**3. Staff Performance**
- Sales by staff member
- Transaction count per staff
- Average sale per staff
- Commission calculations (future)

**4. Financial Reports**
- Daily cash reconciliation
- Payment method breakdown
- Tax collected report
- Discount summary
- Refund summary
- Profit & loss overview

### 6.5 Additional Features

**1. Settings Integration**
- Currency configuration
- Tax rate setup
- Invoice numbering
- Payment methods enabled/disabled
- Receipt templates
- Business information on receipts

**2. Appointment Integration**
- Link sale to appointment
- Auto-populate cart from appointment services
- Track which sales came from appointments
- Service completion tracking

**3. Customer Loyalty** (Future)
- Track customer purchase history
- Loyalty points system
- Special pricing for VIP customers
- Customer lifetime value

**4. Multi-Location Support** (Future)
- Location-specific inventory
- Transfer stock between locations
- Consolidated reporting
- Location-based user access

---

## 7. Permissions & Security

### 7.1 New Permissions

**Add to RoleSeeder:**

```php
$permissions = [
    // Existing permissions...

    // POS Permissions
    'process sales',
    'view sales',
    'refund sales',
    'view invoices',
    'generate invoices',
    'email invoices',
    'manage cash drawer',
    'view cash drawer reports',

    // Product Permissions
    'view products',
    'create products',
    'edit products',
    'delete products',
    'adjust inventory',
    'view inventory reports',

    // Supplier Permissions
    'view suppliers',
    'create suppliers',
    'edit suppliers',
    'delete suppliers',

    // Report Permissions
    'view sales reports',
    'view product reports',
    'view staff reports',
    'view financial reports',
    'export reports',
];
```

### 7.2 Role Assignments

**Administrator Role:**
- All POS permissions
- All product permissions
- All supplier permissions
- All report permissions

**Staff Role:**
- process sales
- view sales (own sales only)
- view invoices (own sales only)
- generate invoices
- manage cash drawer (own sessions only)
- view cash drawer reports (own sessions only)
- view products
- view suppliers

**Manager Role** (New):
- All staff permissions
- refund sales
- view all sales
- view all cash drawer reports
- create/edit products
- adjust inventory
- view all reports

### 7.3 Security Measures

**1. Authorization Checks**
```php
// In controllers
$this->authorize('process sales');
$this->authorize('view', $sale);  // Policy-based
```

**2. Data Validation**
- Validate all input data
- Check stock availability
- Verify payment amounts
- Ensure positive quantities

**3. Audit Trail**
- Log all sales
- Track inventory adjustments
- Record cash drawer sessions
- Log refunds with user ID

**4. Financial Security**
- Require cash drawer session to process sales
- Track cash discrepancies
- Prevent negative inventory (configurable)
- Validate refund amounts

**5. Data Protection**
- Soft deletes for all major entities
- Customer data snapshot in invoices
- Prevent accidental data loss
- Regular database backups

---

## 8. Implementation Phases

### Phase 1: Foundation (Week 1-2)

**Database & Models:**
- [ ] Create all migrations
- [ ] Create all models with relationships
- [ ] Create seeders for test data
- [ ] Add new permissions to RoleSeeder

**Basic Product Management:**
- [ ] ProductController CRUD
- [ ] Product list view
- [ ] Product create/edit forms
- [ ] Product categories
- [ ] Suppliers

**Deliverables:**
- Working product catalog
- Ability to add/edit/delete products
- Basic inventory tracking

### Phase 2: Core POS Interface (Week 3-4)

**POS Screen:**
- [ ] Main POS layout
- [ ] Product grid display
- [ ] Cart functionality
- [ ] Basic checkout (single payment method)
- [ ] Simple receipt

**Sale Processing:**
- [ ] SaleController
- [ ] Sale model with calculations
- [ ] SaleItem model
- [ ] Payment processing
- [ ] Inventory updates on sale

**Deliverables:**
- Functional POS interface
- Ability to process basic sales
- Inventory decreases automatically
- Simple printed receipts

### Phase 3: Invoice System (Week 5)

**Invoicing:**
- [ ] Invoice model and generation
- [ ] Invoice views (show, list)
- [ ] PDF generation
- [ ] Email delivery
- [ ] Invoice numbering

**Deliverables:**
- Professional invoices
- PDF download
- Email to customers
- Invoice history

### Phase 4: Cash Management (Week 6)

**Cash Drawer:**
- [ ] CashDrawerSession model
- [ ] Open/close drawer flows
- [ ] Session reports
- [ ] Over/short calculations
- [ ] Cash reconciliation

**Deliverables:**
- Cash drawer workflow
- Session management
- Reconciliation reports
- Audit trail

### Phase 5: Sales Management & Refunds (Week 7)

**Sales Features:**
- [ ] Sales history
- [ ] Sale detail views
- [ ] Search and filtering
- [ ] Refund processing
- [ ] Refund reports

**Deliverables:**
- Complete sales history
- Refund functionality
- Stock restoration on refund

### Phase 6: Reporting & Analytics (Week 8-9)

**Reports:**
- [ ] Sales dashboard
- [ ] Sales reports (daily, weekly, monthly)
- [ ] Product performance
- [ ] Staff performance
- [ ] Financial reports
- [ ] Export to Excel/PDF

**Charts:**
- [ ] Sales trends (line chart)
- [ ] Sales by category (pie chart)
- [ ] Top products (bar chart)
- [ ] Payment method breakdown

**Deliverables:**
- Comprehensive reporting dashboard
- Multiple report types
- Visual analytics
- Export functionality

### Phase 7: Advanced Features (Week 10-11)

**Enhancements:**
- [ ] Split payments
- [ ] Discount codes/coupons
- [ ] Customer loyalty points
- [ ] Appointment integration
- [ ] Barcode scanning
- [ ] Receipt printer integration
- [ ] Multiple tax rates

**Deliverables:**
- Enhanced POS features
- Loyalty program
- Hardware integration

### Phase 8: Testing & Deployment (Week 12)

**Quality Assurance:**
- [ ] Unit tests for models
- [ ] Feature tests for controllers
- [ ] End-to-end testing
- [ ] Security audit
- [ ] Performance optimization
- [ ] User acceptance testing

**Documentation:**
- [ ] User manual
- [ ] Admin guide
- [ ] API documentation
- [ ] Training materials

**Deployment:**
- [ ] Production database migration
- [ ] Data migration from old system
- [ ] Staff training
- [ ] Go-live support

---

## 9. Integration Points

### 9.1 Existing System Integration

**Customers:**
- Link sales to existing customers
- Update customer `totalSpent()` method
- Track customer purchase history
- Customer selection in POS

**Services:**
- Sell services through POS
- Service-based transactions
- Link to appointments
- Service pricing

**Appointments:**
- Create sale from completed appointment
- Auto-populate cart with appointment services
- Link payment to appointment
- Appointment → Sale workflow

**Settings:**
- Use existing payment settings
- Currency configuration
- Tax rate from settings
- Invoice numbering from settings

**Users & Permissions:**
- Staff assignment to sales
- Permission-based access control
- User performance tracking

### 9.2 New Integrations

**Email System:**
- Invoice delivery
- Low stock alerts
- End-of-day reports
- Receipt emails

**PDF Generation:**
- Invoice PDFs
- Receipt PDFs
- Report exports

**File Storage:**
- Product images
- Invoice PDFs
- Backup exports

---

## 10. Testing Strategy

### 10.1 Unit Tests

**Model Tests:**
```php
// tests/Unit/SaleTest.php
public function test_sale_calculates_totals_correctly()
public function test_sale_generates_unique_sale_number()
public function test_sale_can_add_payment()
public function test_sale_generates_invoice()

// tests/Unit/ProductTest.php
public function test_product_decreases_stock()
public function test_product_detects_low_stock()
public function test_product_calculates_profit_margin()
```

### 10.2 Feature Tests

**Controller Tests:**
```php
// tests/Feature/POSTest.php
public function test_pos_screen_requires_authentication()
public function test_can_process_sale_with_products()
public function test_cannot_sell_out_of_stock_product()
public function test_sale_decreases_inventory()

// tests/Feature/RefundTest.php
public function test_can_refund_completed_sale()
public function test_cannot_refund_more_than_sale_total()
public function test_refund_restores_inventory()
```

### 10.3 Browser Tests (Laravel Dusk)

**End-to-End:**
```php
public function test_complete_sale_workflow()
public function test_cash_drawer_workflow()
public function test_refund_workflow()
public function test_invoice_generation()
```

### 10.4 Manual Testing Checklist

**POS Interface:**
- [ ] Can add products to cart
- [ ] Can add services to cart
- [ ] Quantity changes correctly
- [ ] Cart totals calculate correctly
- [ ] Checkout modal opens
- [ ] Payment methods work
- [ ] Cash change calculates
- [ ] Sale processes successfully

**Inventory:**
- [ ] Stock decreases on sale
- [ ] Low stock alert appears
- [ ] Out of stock prevents sale
- [ ] Manual adjustment works
- [ ] Adjustment history logs correctly

**Invoices:**
- [ ] Invoice generates correctly
- [ ] PDF downloads
- [ ] Email sends
- [ ] Invoice number increments

**Cash Drawer:**
- [ ] Drawer opens with starting amount
- [ ] Sales tracked during session
- [ ] Drawer closes with reconciliation
- [ ] Over/short calculates correctly

**Refunds:**
- [ ] Refund processes
- [ ] Stock restores
- [ ] Refund appears in sale history
- [ ] Multiple refunds work

**Reports:**
- [ ] Sales report shows correct data
- [ ] Charts render correctly
- [ ] Filters work
- [ ] Export functions

---

## Critical Files Summary

### Database
- `database/migrations/*_create_products_table.php`
- `database/migrations/*_create_product_categories_table.php`
- `database/migrations/*_create_suppliers_table.php`
- `database/migrations/*_create_sales_table.php`
- `database/migrations/*_create_sale_items_table.php`
- `database/migrations/*_create_payments_table.php`
- `database/migrations/*_create_invoices_table.php`
- `database/migrations/*_create_refunds_table.php`
- `database/migrations/*_create_inventory_adjustments_table.php`
- `database/migrations/*_create_cash_drawer_sessions_table.php`

### Models
- `app/Models/Product.php`
- `app/Models/ProductCategory.php`
- `app/Models/Supplier.php`
- `app/Models/Sale.php`
- `app/Models/SaleItem.php`
- `app/Models/Payment.php`
- `app/Models/Invoice.php`
- `app/Models/Refund.php`
- `app/Models/InventoryAdjustment.php`
- `app/Models/CashDrawerSession.php`

### Controllers
- `app/Http/Controllers/POSController.php`
- `app/Http/Controllers/SaleController.php`
- `app/Http/Controllers/InvoiceController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/ProductCategoryController.php`
- `app/Http/Controllers/SupplierController.php`
- `app/Http/Controllers/CashDrawerController.php`
- `app/Http/Controllers/ReportController.php`

### Views
- `resources/views/pos/index.blade.php` (Main POS)
- `resources/views/pos/sales/index.blade.php`
- `resources/views/pos/sales/show.blade.php`
- `resources/views/pos/sales/refund.blade.php`
- `resources/views/pos/invoices/index.blade.php`
- `resources/views/pos/invoices/show.blade.php`
- `resources/views/pos/invoices/pdf.blade.php`
- `resources/views/pos/products/index.blade.php`
- `resources/views/pos/products/create.blade.php`
- `resources/views/pos/products/edit.blade.php`
- `resources/views/pos/cash-drawer/open.blade.php`
- `resources/views/pos/cash-drawer/close.blade.php`
- `resources/views/pos/cash-drawer/report.blade.php`
- `resources/views/pos/reports/dashboard.blade.php`

### JavaScript
- `resources/js/pages/pos.js`
- `resources/js/pages/pos-reports.js`

### Routes
- `routes/web.php` (add POS routes group)

---

## Success Criteria

✅ **Core Functionality:**
- [ ] Can process sales with products and services
- [ ] Inventory updates automatically
- [ ] Invoices generate correctly
- [ ] Cash drawer sessions work
- [ ] Refunds process properly

✅ **User Experience:**
- [ ] UI matches UBold design system
- [ ] POS interface is fast and responsive
- [ ] Checkout process is intuitive
- [ ] Search works efficiently
- [ ] Mobile-friendly (responsive)

✅ **Business Logic:**
- [ ] Calculations are accurate (tax, discounts, change)
- [ ] Stock validation prevents overselling
- [ ] Permissions enforce proper access control
- [ ] Audit trails are complete
- [ ] Data integrity maintained

✅ **Reporting:**
- [ ] Reports show accurate data
- [ ] Charts render correctly
- [ ] Exports work (PDF, Excel)
- [ ] Real-time updates

✅ **Integration:**
- [ ] Works with existing customers
- [ ] Works with existing services
- [ ] Links to appointments
- [ ] Uses system settings

✅ **Security:**
- [ ] Authorization enforced
- [ ] Data validated
- [ ] SQL injection prevented
- [ ] XSS protection
- [ ] CSRF protection

---

## End of Plan

This comprehensive plan provides a complete roadmap for implementing an enterprise-level POS system for Rash Nail Lounge. The system leverages existing infrastructure while introducing powerful new capabilities for product management, sales processing, inventory tracking, and financial reporting.

**Estimated Timeline:** 12 weeks
**Team Size:** 2-3 developers
**Complexity:** High
**Business Impact:** High

For questions or clarifications during implementation, refer to the exploration reports from the planning phase or consult the Laravel and UBold documentation.
