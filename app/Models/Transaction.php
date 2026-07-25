<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated v2.0 — Legacy model. No controller or route uses this.
 *             Kept for Customer::transactions() and Appointment::transaction()
 *             ORM relationship references. The POS system uses Sale / Payment / Refund
 *             for transaction tracking. This model is no longer actively maintained
 *             and may be removed in a future version.
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'user_id',
        'appointment_id',
        'amount',
        'payment_method',
        'transaction_type',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the customer that owns the transaction.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user (staff) that processed the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the appointment associated with the transaction.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
