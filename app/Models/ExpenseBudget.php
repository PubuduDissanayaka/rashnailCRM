<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ExpenseBudget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'budget_amount',
        'spent_amount',
        'start_date',
        'end_date',
        'is_active',
        'send_alerts',
        'alert_threshold',
        'created_by',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'send_alerts' => 'boolean',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Methods
    public function updateSpentAmount(): void
    {
        if (!$this->category_id) {
            // Global budget (no category) - sum all paid expenses within date range
            $query = Expense::where('status', 'paid')
                ->whereBetween('expense_date', [$this->start_date, $this->end_date]);
        } else {
            // Category-specific budget - sum paid expenses for this category within date range
            $query = $this->category->expenses()
                ->where('status', 'paid')
                ->whereBetween('expense_date', [$this->start_date, $this->end_date]);
        }

        $this->spent_amount = (float) $query->sum('total_amount');
        $this->save();
    }

    public function getUtilizationPercentage(): float
    {
        if (!$this->budget_amount || $this->budget_amount <= 0) {
            return 0;
        }

        return ($this->spent_amount / $this->budget_amount) * 100;
    }

    public function isOverBudget(): bool
    {
        return $this->spent_amount > $this->budget_amount;
    }

    // Boot method to auto-update spent_amount when creating/updating
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($budget) {
            // If spent_amount is not set or we need to recalculate, update it
            if ($budget->isDirty(['start_date', 'end_date', 'category_id']) || $budget->spent_amount === null) {
                $budget->updateSpentAmount();
            }
        });
    }
}