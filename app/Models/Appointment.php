<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Appointment extends Model
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
        'service_id',
        'appointment_date',
        'status',
        'notes',
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
            'appointment_date' => 'datetime',
        ];
    }

    /**
     * Boot the model and set up slug generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appointment) {
            $appointment->slug = Str::slug('apt-' . Str::random(8));
        });
    }

    /**
     * Get the route key for route model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the customer that owns the appointment.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user (staff) that owns the appointment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service for the appointment.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the transaction for this appointment if exists.
     */
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    /**
     * Get supply usage logs for this appointment.
     */
    public function supplyUsageLogs()
    {
        return $this->hasMany(SupplyUsageLog::class);
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope a query to only include scheduled appointments.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include in-progress appointments.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed appointments.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled appointments.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include today's appointments.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('appointment_date', today());
    }

    /**
     * Scope a query to only include upcoming appointments.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('appointment_date', '>=', now())
                     ->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include past appointments.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('appointment_date', '<', now());
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('appointment_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by specific staff member.
     */
    public function scopeForStaff(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by specific customer.
     */
    public function scopeForCustomer(Builder $query, $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get formatted appointment date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->appointment_date->format('M d, Y h:i A');
    }

    /**
     * Get formatted date only.
     */
    public function getDateOnlyAttribute(): string
    {
        return $this->appointment_date->format('M d, Y');
    }

    /**
     * Get formatted time only.
     */
    public function getTimeOnlyAttribute(): string
    {
        return $this->appointment_date->format('h:i A');
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'scheduled' => 'primary',
            'in_progress' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    /**
     * Get status color for calendar.
     */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            'scheduled' => '#3e60d5',    // Primary blue
            'in_progress' => '#f7b84b',  // Warning yellow
            'completed' => '#0acf97',    // Success green
            'cancelled' => '#fa5c7c',    // Danger red
        ];
        return $colors[$this->status] ?? '#6c757d';
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Get formatted date and time.
     */
    public function getFormattedDateTimeAttribute(): string
    {
        return $this->appointment_date->format('M d, Y h:i A');
    }

    /**
     * Check if appointment is today.
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->appointment_date->isToday();
    }

    /**
     * Check if appointment is upcoming.
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->appointment_date->isFuture() && $this->status === 'scheduled';
    }

    /**
     * Check if appointment is past.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->appointment_date->isPast();
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Check if appointment can be modified.
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, ['scheduled', 'in_progress', 'completed', 'cancelled']);
    }

    /**
     * Check if appointment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['scheduled', 'in_progress']);
    }

    /**
     * Mark appointment as in progress.
     */
    public function markInProgress(): void
    {
        if ($this->status === 'scheduled') {
            $this->update(['status' => 'in_progress']);
        }
    }

    /**
     * Mark appointment as completed.
     */
    public function markComplete(): void
    {
        if (in_array($this->status, ['scheduled', 'in_progress'])) {
            $this->update(['status' => 'completed']);
        }
    }

    /**
     * Cancel the appointment.
     */
    public function cancel(): void
    {
        if ($this->canBeCancelled()) {
            $this->update(['status' => 'cancelled']);
        }
    }

    /**
     * Get calendar event data for FullCalendar.
     */
    public function getCalendarEventData(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->customer->name . ' - ' . $this->service->name,
            'start' => $this->appointment_date->toIso8601String(),
            'end' => $this->appointment_date->copy()->addMinutes($this->service->duration ?? 60)->toIso8601String(),
            'backgroundColor' => $this->status_color,
            'borderColor' => $this->status_color,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'slug' => $this->slug,
                'customer' => $this->customer->name,
                'staff' => $this->user->name,
                'service' => $this->service->name,
                'status' => $this->status,
                'statusLabel' => $this->status_label,
                'notes' => $this->notes,
                'phone' => $this->customer->phone ?? 'N/A',
                'customer_id' => $this->customer_id,
                'user_id' => $this->user_id,
                'service_id' => $this->service_id,
            ],
        ];
    }

    /**
     * Get duration of appointment based on service.
     */
    public function getDurationAttribute(): int
    {
        return $this->service->duration ?? 60;
    }

    /**
     * Get end time of appointment.
     */
    public function getEndTimeAttribute(): \Carbon\Carbon
    {
        return $this->appointment_date->copy()->addMinutes($this->duration);
    }

    /**
     * Check if appointment conflicts with another appointment for the same staff.
     */
    public function hasConflict(?int $excludeId = null): bool
    {
        $query = static::where('user_id', $this->user_id)
                       ->where('status', '!=', 'cancelled')
                       ->where(function ($q) {
                           $q->whereBetween('appointment_date', [$this->appointment_date, $this->end_time])
                             ->orWhere(function ($q2) {
                                 $q2->where('appointment_date', '<=', $this->appointment_date)
                                    ->whereRaw('DATE_ADD(appointment_date, INTERVAL (SELECT duration FROM services WHERE id = appointments.service_id) MINUTE) > ?', [$this->appointment_date]);
                             });
                       });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Auto-deduct supplies when appointment is completed.
     */
    public function deductSupplies(): void
    {
        if ($this->status !== 'completed') {
            return;
        }

        // Check if supplies have already been deducted for this appointment
        if ($this->supplyUsageLogs()->exists()) {
            return;
        }

        // Load service with supplies
        $this->load(['service.supplies']);

        if (!$this->service || !$this->service->supplies->count()) {
            return;
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($this->service->supplies as $supply) {
                $quantityRequired = $supply->pivot->quantity_required ?? 1;
                
                // Create usage log
                \App\Models\SupplyUsageLog::create([
                    'supply_id' => $supply->id,
                    'appointment_id' => $this->id,
                    'service_id' => $this->service_id,
                    'quantity_used' => $quantityRequired,
                    'unit_cost' => $supply->unit_cost,
                    'total_cost' => $supply->unit_cost * $quantityRequired,
                    'used_by' => $this->user_id,
                    'customer_id' => $this->customer_id,
                    'used_at' => now(),
                ]);

                // Deduct stock
                $supply->removeStock(
                    $quantityRequired,
                    $this,
                    "Used in appointment #{$this->id}"
                );
            }
            
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            throw $e;
        }
    }
}
