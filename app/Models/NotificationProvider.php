<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class NotificationProvider extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'channel',
        'provider',
        'name',
        'is_default',
        'priority',
        'config',
        'is_active',
        'last_test_at',
        'last_test_status',
        'daily_limit',
        'monthly_limit',
        'usage_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_test_at' => 'datetime',
        'daily_limit' => 'integer',
        'monthly_limit' => 'integer',
        'usage_count' => 'integer',
    ];

    /**
     * Get the configurations for this provider.
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(ProviderConfiguration::class);
    }

    /**
     * Scope a query to only include active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include providers for a specific channel.
     */
    public function scopeChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to order by priority (ascending).
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority');
    }

    /**
     * Get the decrypted config value for a given key.
     */
    public function getConfigValue(string $key, $default = null)
    {
        $config = $this->config ?? [];
        return $config[$key] ?? $default;
    }

    /**
     * Set a config value (encrypt if needed).
     */
    public function setConfigValue(string $key, $value, bool $encrypt = false): void
    {
        $config = $this->config ?? [];
        if ($encrypt && is_string($value)) {
            $value = Crypt::encryptString($value);
        }
        $config[$key] = $value;
        $this->config = $config;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): bool
    {
        return $this->increment('usage_count');
    }

    /**
     * Check if provider has reached daily limit.
     */
    public function hasReachedDailyLimit(): bool
    {
        if ($this->daily_limit === null) {
            return false;
        }
        // In a real implementation, you'd check usage for the current day
        return $this->usage_count >= $this->daily_limit;
    }

    /**
     * Check if provider has reached monthly limit.
     */
    public function hasReachedMonthlyLimit(): bool
    {
        if ($this->monthly_limit === null) {
            return false;
        }
        // In a real implementation, you'd check usage for the current month
        return $this->usage_count >= $this->monthly_limit;
    }

    /**
     * Mark provider as tested.
     */
    public function markTested(bool $success): bool
    {
        return $this->update([
            'last_test_at' => now(),
            'last_test_status' => $success ? 'success' : 'failed',
        ]);
    }
}
