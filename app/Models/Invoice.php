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
        'sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
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
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    public function getIsSentAttribute()
    {
        return !is_null($this->sent_at);
    }

    // Business Methods
    public function markAsSent()
    {
        $this->sent_at = now();
        $this->save();
        return $this;
    }

    public function markAsPaid()
    {
        $this->status = 'paid';
        $this->amount_paid = $this->total_amount;
        $this->balance_due = 0;
        $this->save();
        return $this;
    }

    public function addPayment(float $amount)
    {
        $this->amount_paid += $amount;
        if ($this->amount_paid >= $this->total_amount) {
            $this->status = 'paid';
            $this->balance_due = 0;
        } else {
            $this->balance_due = $this->total_amount - $this->amount_paid;
        }
        $this->save();
        return $this;
    }

    public function generatePdf()
    {
        // This would generate a PDF for the invoice using DomPDF or similar
        // For now, we just return the view
        return view('invoices.pdf', ['invoice' => $this])->render();
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
                     ->where('due_date', '<', now()->format('Y-m-d'));
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    private static function generateInvoiceNumber()
    {
        $prefix = Setting::get('payment.invoice_prefix', 'INV');
        $nextNumber = Setting::get('payment.next_invoice_number', 1);

        $invoiceNumber = sprintf('%s-%s-%05d', $prefix, date('Y'), $nextNumber);

        Setting::set('payment.next_invoice_number', $nextNumber + 1, 'integer');

        return $invoiceNumber;
    }
}