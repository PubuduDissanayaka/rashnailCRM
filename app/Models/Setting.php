<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'order',
        'encrypted'
    ];

    protected $casts = [
        'encrypted' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get setting value with type casting
     */
    public function getValueAttribute($value)
    {
        // If value is null, return null early to prevent type conversion issues
        if ($value === null) {
            return null;
        }

        if ($this->encrypted && $value) {
            $value = decrypt($value);
        }

        return match($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value
        };
    }

    /**
     * Set setting value with optional encryption
     */
    public function setValueAttribute($value)
    {
        // Auto-encode arrays to JSON regardless of type attribute
        // This handles mass assignment where type might not be set yet
        if (is_array($value)) {
            $value = json_encode($value);
        }

        if ($this->encrypted) {
            $value = encrypt($value);
        }

        $this->attributes['value'] = $value;
    }

    /**
     * Get a setting value (cached)
     */
    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever("setting.{$key}", function() use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting && $setting->value !== null ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value and clear cache
     */
    public static function set(string $key, $value, string $type = 'string'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => explode('.', $key)[0] ?? 'general'
            ]
        );

        Cache::forget("setting.{$key}");
        Cache::forget("settings.group." . (explode('.', $key)[0] ?? 'general'));
    }

    /**
     * Get all settings in a group (cached)
     */
    public static function getGroup(string $group): array
    {
        return Cache::rememberForever("settings.group.{$group}", function() use ($group) {
            $settings = self::where('group', $group)
                ->orderBy('order')
                ->select(['key', 'value', 'type', 'encrypted'])
                ->get()
                ->mapWithKeys(function($setting) {
                    $value = $setting->value; // This will use the accessor for proper type casting
                    return [$setting->key => $value];
                })
                ->toArray();

            return $settings;
        });
    }

    /**
     * Clear all settings cache
     */
    public static function flushCache(): void
    {
        self::all()->each(function($setting) {
            Cache::forget("setting.{$setting->key}");
        });

        foreach(['business', 'appointment', 'notification', 'payment'] as $group) {
            Cache::forget("settings.group.{$group}");
        }
    }
}