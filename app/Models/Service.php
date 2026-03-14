<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'is_active',
        'slug',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get appointments for this service.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the route key for route model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Scope a query to only include active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Boot the model and set up slug generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($service) {
            $service->slug = \Illuminate\Support\Str::slug($service->name . '-' . \Illuminate\Support\Str::random(4));
        });

        static::updating(function ($service) {
            if ($service->isDirty('name')) {
                $service->slug = \Illuminate\Support\Str::slug($service->name . '-' . $service->id . '-' . \Illuminate\Support\Str::random(4));
            }
        });
    }

    /**
     * Many-to-many relationship with service packages
     */
    public function packages()
    {
        return $this->belongsToMany(ServicePackage::class, 'package_service', 'service_id', 'package_id')
                    ->withPivot(['quantity', 'sort_order'])
                    ->withTimestamps();
    }

    /**
     * Many-to-many relationship with supplies
     */
    public function supplies()
    {
        return $this->belongsToMany(Supply::class, 'service_supplies')
                    ->withPivot('quantity_required', 'is_optional')
                    ->withTimestamps();
    }
}
