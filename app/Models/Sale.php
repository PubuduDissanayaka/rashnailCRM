<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_number', 'customer_id', 'user_id', 'appointment_id',
        'subtotal', 'tax_amount', 'discount_amount', 'coupon_discount_amount', 'total_amount',
        'amount_paid', 'change_amount', 'status', 'sale_type',
        'notes', 'sale_date', 'applied_coupon_ids'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'coupon_discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'sale_date' => 'datetime',
        'applied_coupon_ids' => 'array',
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

    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'sale_coupons')
            ->withPivot('discount_amount', 'coupon_redemption_id')
            ->withTimestamps();
    }

    public function saleCoupons()
    {
        return $this->hasMany(SaleCoupon::class);
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
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount - $this->coupon_discount_amount;
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