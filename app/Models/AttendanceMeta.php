<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMeta extends Model
{
    protected $fillable = [
        'attendance_id',
        'meta_key',
        'meta_value',
    ];

    protected $casts = [
        'meta_value' => 'array',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the attendance record
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope: Meta with specific key
     */
    public function scopeWithKey($query, $key)
    {
        return $query->where('meta_key', $key);
    }

    /**
     * Scope: Meta with keys matching pattern
     */
    public function scopeWithKeyLike($query, $pattern)
    {
        return $query->where('meta_key', 'like', $pattern);
    }

    /**
     * Scope: Meta with specific value
     */
    public function scopeWithValue($query, $value)
    {
        return $query->where('meta_value', $value);
    }

    /**
     * Scope: Meta with JSON value containing key
     */
    public function scopeWithJsonContains($query, $key, $value)
    {
        return $query->whereJsonContains("meta_value->{$key}", $value);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get meta value with type casting
     */
    public function getMetaValueAttribute($value)
    {
        // Try to decode JSON, if it fails return as string
        $decoded = json_decode($value, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $value;
    }

    /**
     * Set meta value with JSON encoding for arrays
     */
    public function setMetaValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['meta_value'] = json_encode($value);
        } else {
            $this->attributes['meta_value'] = $value;
        }
    }

    /**
     * Get meta value as string
     */
    public function getValueAsStringAttribute(): string
    {
        $value = $this->meta_value;
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        return (string) $value;
    }

    /**
     * Get meta value as integer
     */
    public function getValueAsIntAttribute(): ?int
    {
        $value = $this->meta_value;
        
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        return null;
    }

    /**
     * Get meta value as float
     */
    public function getValueAsFloatAttribute(): ?float
    {
        $value = $this->meta_value;
        
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        return null;
    }

    /**
     * Get meta value as boolean
     */
    public function getValueAsBoolAttribute(): ?bool
    {
        $value = $this->meta_value;
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        
        return null;
    }

    /**
     * Get meta value as array
     */
    public function getValueAsArrayAttribute(): array
    {
        $value = $this->meta_value;
        
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return [$value];
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Set meta value for an attendance record
     */
    public static function setMeta($attendanceId, $key, $value): self
    {
        return static::updateOrCreate(
            [
                'attendance_id' => $attendanceId,
                'meta_key' => $key,
            ],
            [
                'meta_value' => $value,
            ]
        );
    }

    /**
     * Get meta value for an attendance record
     */
    public static function getMeta($attendanceId, $key, $default = null)
    {
        $meta = static::where('attendance_id', $attendanceId)
            ->where('meta_key', $key)
            ->first();
        
        return $meta ? $meta->meta_value : $default;
    }

    /**
     * Delete meta value for an attendance record
     */
    public static function deleteMeta($attendanceId, $key): bool
    {
        return static::where('attendance_id', $attendanceId)
            ->where('meta_key', $key)
            ->delete() > 0;
    }

    /**
     * Get all meta for an attendance record as key-value array
     */
    public static function getAllMeta($attendanceId): array
    {
        return static::where('attendance_id', $attendanceId)
            ->get()
            ->mapWithKeys(function ($meta) {
                return [$meta->meta_key => $meta->meta_value];
            })
            ->toArray();
    }

    /**
     * Set multiple meta values for an attendance record
     */
    public static function setMultipleMeta($attendanceId, array $metaData): void
    {
        foreach ($metaData as $key => $value) {
            static::setMeta($attendanceId, $key, $value);
        }
    }

    /**
     * Check if meta key exists for an attendance record
     */
    public static function hasMeta($attendanceId, $key): bool
    {
        return static::where('attendance_id', $attendanceId)
            ->where('meta_key', $key)
            ->exists();
    }

    /**
     * Get meta keys matching pattern for an attendance record
     */
    public static function getMetaKeys($attendanceId, $pattern = null): array
    {
        $query = static::where('attendance_id', $attendanceId);
        
        if ($pattern) {
            $query->where('meta_key', 'like', $pattern);
        }
        
        return $query->pluck('meta_key')->toArray();
    }

    /**
     * Increment numeric meta value
     */
    public static function incrementMeta($attendanceId, $key, $amount = 1): self
    {
        $meta = static::firstOrNew([
            'attendance_id' => $attendanceId,
            'meta_key' => $key,
        ]);
        
        $currentValue = $meta->exists ? $meta->value_as_int : 0;
        $meta->meta_value = $currentValue + $amount;
        $meta->save();
        
        return $meta;
    }

    /**
     * Decrement numeric meta value
     */
    public static function decrementMeta($attendanceId, $key, $amount = 1): self
    {
        return static::incrementMeta($attendanceId, $key, -$amount);
    }

    /**
     * Append value to array meta
     */
    public static function appendToMeta($attendanceId, $key, $value): self
    {
        $meta = static::firstOrNew([
            'attendance_id' => $attendanceId,
            'meta_key' => $key,
        ]);
        
        $currentArray = $meta->exists ? $meta->value_as_array : [];
        $currentArray[] = $value;
        $meta->meta_value = array_unique($currentArray);
        $meta->save();
        
        return $meta;
    }

    /**
     * Remove value from array meta
     */
    public static function removeFromMeta($attendanceId, $key, $value): self
    {
        $meta = static::where('attendance_id', $attendanceId)
            ->where('meta_key', $key)
            ->first();
        
        if (!$meta) {
            return new static();
        }
        
        $currentArray = $meta->value_as_array;
        $newArray = array_filter($currentArray, function ($item) use ($value) {
            return $item != $value;
        });
        
        $meta->meta_value = array_values($newArray);
        $meta->save();
        
        return $meta;
    }
}