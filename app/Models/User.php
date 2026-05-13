<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Scope a query to include all staff-role users.
     * Matches: any Spatie role (administrator + all custom roles),
     * or legacy role column with admin/staff/manager in the name.
     */
    public function scopeWithStaffRole($query)
    {
        return $query->where(function($q) {
            // Has any Spatie role (administrator or any custom staff role)
            $q->whereHas('roles')
            // OR legacy role column fallback
            ->orWhere(function ($sub) {
                $sub->whereRaw('LOWER(role) LIKE ?', ['%admin%'])
                    ->orWhereRaw('LOWER(role) LIKE ?', ['%staff%'])
                    ->orWhereRaw('LOWER(role) LIKE ?', ['%manager%'])
                    ->orWhereRaw('LOWER(role) LIKE ?', ['%reception%']);
            });
        });
    }

    /**
     * Check if the user has the administrator role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('administrator');
    }

    /**
     * Check if the user has any staff-type role.
     * True if the user has ANY Spatie role that is not 'administrator',
     * or has a role column value indicating staff.
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        // Has any Spatie role AND it's not administrator
        $spatieRoles = $this->getRoleNames();
        if ($spatieRoles->isNotEmpty() && !$spatieRoles->contains('administrator')) {
            return true;
        }

        // Fallback: role column check
        $role = strtolower($this->role ?? '');
        return $role !== '' && $role !== 'administrator';
    }

    /**
     * Get the route key for route model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Boot the model and set up slug generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if ($user->isDirty('name') || empty($user->slug)) {
                $user->slug = \Illuminate\Support\Str::slug($user->name) . '-' . \Illuminate\Support\Str::random(6);
            }
        });
    }

    /**
     * User has many attendance records
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    /**
     * Get today's attendance for the user
     */
    public function todaysAttendance()
    {
        return $this->attendances()->whereDate('date', today())->first();
    }

    /**
     * Get attendance records for a specific date
     */
    public function attendanceForDate($date)
    {
        return $this->attendances()->whereDate('date', $date)->first();
    }

    /**
     * Check if user has checked in today
     */
    public function hasCheckedInToday()
    {
        $attendance = $this->todaysAttendance();
        return $attendance && $attendance->check_in;
    }

    /**
     * Check if user has checked out today
     */
    public function hasCheckedOutToday()
    {
        $attendance = $this->todaysAttendance();
        return $attendance && $attendance->check_out;
    }

    /**
     * Get attendance records for a specific month
     */
    public function attendanceForMonth($year, $month)
    {
        return $this->attendances()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();
    }

    /**
     * Get monthly attendance summary
     */
    public function getAttendanceSummaryForMonth($year, $month)
    {
        $attendances = $this->attendanceForMonth($year, $month);

        return [
            'total_days' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
            'total_hours' => $attendances->sum('hours_worked'),
        ];
    }

    /**
     * Get user's work schedules
     */
    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class, 'user_id');
    }

    /**
     * Get user's leave requests
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'user_id');
    }

    /**
     * Get user's leave balances
     */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'user_id');
    }

    /**
     * Get the user's notifications.
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get the user's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Get the user's read notifications.
     */
    public function readNotifications()
    {
        return $this->notifications()->whereNotNull('read_at');
    }
}
