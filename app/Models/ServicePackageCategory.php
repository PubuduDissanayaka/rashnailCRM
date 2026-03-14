<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServicePackageCategory extends Model
{

    protected $fillable = [
        'name', 'description', 'slug', 'order'
    ];

    protected $casts = [
        'order' => 'integer'
    ];

    // Relationship
    public function servicePackages()
    {
        return $this->hasMany(ServicePackage::class);
    }

    // Auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Route key binding
    public function getRouteKeyName()
    {
        return 'slug';
    }
}