<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ProviderConfiguration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'provider_id',
        'key',
        'value_encrypted',
        'is_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_secret' => 'boolean',
    ];

    /**
     * Get the provider that owns this configuration.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(NotificationProvider::class);
    }

    /**
     * Get the decrypted value.
     */
    public function getDecryptedValue(): string
    {
        try {
            return Crypt::decryptString($this->value_encrypted);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Set the encrypted value.
     */
    public function setEncryptedValue(string $value): void
    {
        $this->value_encrypted = Crypt::encryptString($value);
    }

    /**
     * Scope a query to only include secret configurations.
     */
    public function scopeSecret($query)
    {
        return $query->where('is_secret', true);
    }

    /**
     * Scope a query to only include non-secret configurations.
     */
    public function scopeNonSecret($query)
    {
        return $query->where('is_secret', false);
    }
}
