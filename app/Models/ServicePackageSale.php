<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackageSale extends Model
{

    protected $fillable = [
        'sale_item_id', 'service_package_id', 'sessions_used', 'sessions_remaining', 'expires_at', 'status'
    ];

    protected $casts = [
        'sessions_used' => 'integer',
        'sessions_remaining' => 'integer',
        'expires_at' => 'datetime',
        'status' => 'string'
    ];

    // Relationships
    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function servicePackage()
    {
        return $this->belongsTo(ServicePackage::class);
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    public function getIsFullyUsedAttribute()
    {
        return $this->sessions_remaining <= 0;
    }

    // Business Methods
    public function useSession()
    {
        if ($this->is_fully_used || $this->is_expired) {
            throw new \Exception('Cannot use session: package is fully used or expired');
        }

        $this->sessions_used++;
        $this->sessions_remaining--;

        if ($this->sessions_remaining <= 0) {
            $this->status = 'used';
        }

        $this->save();

        return $this;
    }

    public function addSessions(int $count)
    {
        $this->sessions_remaining += $count;
        if ($this->status === 'used') {
            $this->status = 'active'; // Reactivate if it was marked as used
        }
        $this->save();

        return $this;
    }

    public function expirePackage()
    {
        $this->status = 'expired';
        $this->save();

        return $this;
    }

    public function isValid()
    {
        return $this->is_active && !$this->is_fully_used;
    }
}