<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'parent_id',
        'is_active',
        'sort_order',
        'budget_amount',
        'budget_period',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ExpenseBudget::class, 'category_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRootCategories(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // Methods
    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    public function getTotalExpenses(string $period = 'month'): float
    {
        $query = $this->expenses()->where('status', 'paid');

        // Apply period filter based on budget_period logic
        // This is a simplified implementation; you may need to adjust based on actual period logic
        if ($period === 'month') {
            $query->whereYear('expense_date', now()->year)
                ->whereMonth('expense_date', now()->month);
        } elseif ($period === 'quarter') {
            $quarter = ceil(now()->month / 3);
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $query->whereYear('expense_date', now()->year)
                ->whereBetween('expense_date', [
                    now()->setMonth($startMonth)->startOfMonth(),
                    now()->setMonth($endMonth)->endOfMonth()
                ]);
        } elseif ($period === 'year') {
            $query->whereYear('expense_date', now()->year);
        }

        return (float) $query->sum('total_amount');
    }

    public function getBudgetUtilization(): float
    {
        if (!$this->budget_amount || $this->budget_amount <= 0) {
            return 0;
        }

        $totalExpenses = $this->getTotalExpenses($this->budget_period ?? 'month');
        return ($totalExpenses / $this->budget_amount) * 100;
    }
}