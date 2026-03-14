<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_number',
        'title',
        'description',
        'category_id',
        'vendor_name',
        'vendor_contact',
        'amount',
        'tax_amount',
        'total_amount',
        'currency',
        'payment_method',
        'payment_reference',
        'expense_date',
        'due_date',
        'paid_date',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'is_recurring',
        'recurring_frequency',
        'recurring_end_date',
        'parent_expense_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expense_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'approved_at' => 'datetime',
        'is_recurring' => 'boolean',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID = 'paid';

    // Payment method constants
    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_CARD = 'card';
    const PAYMENT_METHOD_CHECK = 'check';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_ONLINE = 'online';

    // Recurring frequency constants
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_YEARLY = 'yearly';

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class, 'expense_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ExpenseComment::class, 'expense_id');
    }

    public function parentExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'parent_expense_id');
    }

    public function recurringExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'parent_expense_id');
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->whereDate('due_date', '<', now())
            ->whereNull('paid_date');
    }

    public function scopeRecurring(Builder $query): Builder
    {
        return $query->where('is_recurring', true);
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        return $currencySymbol . number_format($this->total_amount, 2);
    }

    public function getStatusBadgeAttribute(): array
    {
        $badgeClasses = [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_PAID => 'success',
        ];

        $statusText = ucfirst($this->status);

        return [
            'class' => 'badge bg-' . ($badgeClasses[$this->status] ?? 'secondary'),
            'text' => $statusText,
        ];
    }

    // Methods
    public function approve(User $user): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $user->id;
        $this->approved_at = now();
        $this->rejection_reason = null;

        return $this->save();
    }

    public function reject(User $user, string $reason): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        $this->approved_by = $user->id;
        $this->approved_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    public function markAsPaid(array $data): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $this->status = self::STATUS_PAID;
        $this->paid_date = $data['paid_date'] ?? now();
        $this->payment_method = $data['payment_method'] ?? $this->payment_method;
        $this->payment_reference = $data['payment_reference'] ?? $this->payment_reference;

        return $this->save();
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_APPROVED 
            && $this->due_date 
            && $this->due_date->isPast() 
            && !$this->paid_date;
    }

    public static function generateExpenseNumber(): string
    {
        $year = now()->year;
        $lastExpense = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastExpense && preg_match('/EXP-(\d{4})-(\d{4})/', $lastExpense->expense_number, $matches)) {
            $sequence = (int) $matches[2] + 1;
        }

        return sprintf('EXP-%04d-%04d', $year, $sequence);
    }

    // Boot method for auto-generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->expense_number)) {
                $expense->expense_number = self::generateExpenseNumber();
            }

            // Auto-calculate total_amount if not set
            if (empty($expense->total_amount)) {
                $expense->total_amount = $expense->amount + $expense->tax_amount;
            }

            // Set default currency from settings if not set
            if (empty($expense->currency)) {
                $expense->currency = Setting::get('payment.currency_code', 'USD');
            }

            // Set default status if not set
            if (empty($expense->status)) {
                $expense->status = self::STATUS_DRAFT;
            }
        });

        static::updating(function ($expense) {
            // Recalculate total_amount if amount or tax_amount changed
            if ($expense->isDirty(['amount', 'tax_amount'])) {
                $expense->total_amount = $expense->amount + $expense->tax_amount;
            }
        });
    }
}