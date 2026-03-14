<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'date_of_birth',
        'address',
        'gender',
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
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Get the full name attribute.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get appointments for this customer.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get transactions for this customer.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get total number of appointments for this customer
     */
    public function totalAppointments(): int
    {
        return $this->appointments()->count();
    }

    /**
     * Get total amount spent by this customer across all sources
     */
    public function totalSpent(): float
    {
        // Total from appointment transactions
        $appointmentRevenue = $this->transactions()
            ->where('transaction_type', 'sale')
            ->where('status', 'completed')
            ->sum('amount') ?? 0.0;

        // Total from POS sales
        $saleRevenue = $this->sales()
            ->where('status', 'completed')
            ->sum('total_amount') ?? 0.0;

        return $appointmentRevenue + $saleRevenue;
    }

    /**
     * Get the last visit date (considering both appointments and sales)
     */
    public function lastVisit()
    {
        $lastAppointment = $this->appointments()
            ->where('status', 'completed')
            ->latest('appointment_date')
            ->first();

        $lastSale = $this->sales()
            ->where('status', 'completed')
            ->latest('sale_date')
            ->first();

        if ($lastAppointment && $lastSale) {
            // Compare the dates and return the more recent one
            if ($lastSale->sale_date && $lastAppointment->appointment_date) {
                return $lastSale->sale_date > $lastAppointment->appointment_date ? $lastSale : $lastAppointment;
            } elseif ($lastSale->sale_date) {
                return $lastSale;
            } else {
                return $lastAppointment;
            }
        }

        // Return whichever exists
        return $lastAppointment ?: $lastSale;
    }

    /**
     * Get the datetime of the last visit (most recent between appointments and sales)
     */
    public function getLastVisitDate()
    {
        $lastAppointment = $this->appointments()
            ->where('status', 'completed')
            ->latest('appointment_date')
            ->first();

        $lastSale = $this->sales()
            ->where('status', 'completed')
            ->latest('sale_date')
            ->first();

        if ($lastAppointment && $lastSale) {
            if ($lastSale->sale_date && $lastAppointment->appointment_date) {
                return $lastSale->sale_date > $lastAppointment->appointment_date ? $lastSale->sale_date : $lastAppointment->appointment_date;
            } elseif ($lastSale->sale_date) {
                return $lastSale->sale_date;
            } else {
                return $lastAppointment->appointment_date;
            }
        }

        // Return whichever date exists
        if ($lastAppointment) {
            return $lastAppointment->appointment_date;
        }

        if ($lastSale) {
            return $lastSale->sale_date;
        }

        return null;
    }

    /**
     * Get the customer's favorite service
     */
    public function favoriteService()
    {
        return $this->appointments()
            ->with('service')
            ->selectRaw('service_id, count(*) as count')
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->first()?->service;
    }

    /**
     * Get the route key for route model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get initials for the customer
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) .
            substr($this->last_name, 0, 1)
        );
    }

    /**
     * Get the phone number in international format for WhatsApp
     */
    public function getWhatsAppPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $this->phone);

        if (empty($phone)) {
            return null;
        }

        // Get country code from settings
        $countryCode = Setting::get('business.country_code', '94');
        $countryCode = ltrim($countryCode, '+');

        // Handle the different cases:
        if (substr($phone, 0, strlen($countryCode)) === $countryCode) {
            // Already has country code
            return $phone;
        } elseif ($phone[0] === '0') {
            // Starts with 0, replace with country code
            return $countryCode . substr($phone, 1);
        } else {
            // Assume local number, add country code
            return $countryCode . $phone;
        }
    }

    /**
     * Get sales for this customer
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get total number of sales for this customer
     */
    public function totalSalesCount(): int
    {
        return $this->sales()->count();
    }

    /**
     * Get total number of bills paid by this customer
     */
    public function totalBillsPaid(): float
    {
        return $this->sales()
            ->where('status', 'completed')
            ->sum('total_amount') ?? 0.0;
    }

    /**
     * Get the date of the last transaction (sale or appointment)
     */
    public function getLastTransactionDate()
    {
        $lastSale = $this->sales()
            ->orderByDesc('sale_date')
            ->first();

        $lastAppointment = $this->appointments()
            ->orderByDesc('appointment_date')
            ->first();

        if ($lastSale && $lastAppointment) {
            return $lastSale->sale_date > $lastAppointment->appointment_date ?
                   $lastSale->sale_date : $lastAppointment->appointment_date;
        }

        if ($lastSale) {
            return $lastSale->sale_date;
        }

        if ($lastAppointment) {
            return $lastAppointment->appointment_date;
        }

        return null;
    }

    /**
     * Generate a unique slug based on the customer's name and ID
     */
    public function generateSlug()
    {
        $baseSlug = \Illuminate\Support\Str::slug($this->first_name . '-' . $this->last_name);
        $slug = $baseSlug;

        // Ensure uniqueness by appending the ID if needed
        $counter = 1;
        while (static::where('slug', $slug)->where('id', '!=', $this->id)->first()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $this->slug = $slug;
        return $this;
    }

    /**
     * Boot the model and set up slug generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->slug)) {
                $customer->generateSlug();
            }
        });

        static::updating(function ($customer) {
            if (empty($customer->slug)) {
                $customer->generateSlug();
            }
        });
    }

    /**
     * Get total revenue from this customer (both appointments and sales)
     */
    public function getTotalRevenue(): float
    {
        $appointmentRevenue = $this->transactions()->sum('amount') ?? 0.0;
        $saleRevenue = $this->sales()->sum('total_amount') ?? 0.0;

        return $appointmentRevenue + $saleRevenue;
    }

    /**
     * Get all history events for timeline view (combines appointments and sales)
     */
    public function getAllHistory($limit = 20)
    {
        // Get appointments with their details
        $appointmentHistory = $this->appointments()
            ->with(['service', 'user'])
            ->select(['id', 'customer_id', 'appointment_date as date', 'status', 'notes', 'created_at', 'updated_at'])
            ->get()
            ->map(function ($apt) {
                return [
                    'type' => 'appointment',
                    'id' => $apt->id,
                    'date' => $apt->appointment_date,
                    'status' => $apt->status,
                    'service' => $apt->service ? $apt->service->name : null,
                    'staff' => $apt->user ? $apt->user->name : null,
                    'notes' => $apt->notes,
                    'amount' => $apt->service ? $apt->service->price : 0
                ];
            });

        // Get sales with their details
        $saleHistory = $this->sales()
            ->with(['items'])
            ->select(['id', 'customer_id', 'sale_date as date', 'status', 'notes', 'created_at', 'updated_at', 'total_amount'])
            ->get()
            ->map(function ($sale) {
                return [
                    'type' => 'sale',
                    'id' => $sale->id,
                    'date' => $sale->sale_date,
                    'status' => $sale->status,
                    'service' => 'POS Sale',
                    'staff' => null, // Could include user who processed this
                    'notes' => $sale->notes,
                    'amount' => $sale->total_amount
                ];
            });

        // Combine, sort by date, and limit results
        $combined = collect($appointmentHistory)->merge($saleHistory);

        return $combined->sortByDesc('date')->take($limit);
    }
}
