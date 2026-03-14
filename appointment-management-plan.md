# Appointment Management System - Full Implementation Plan

## Executive Summary

Comprehensive appointment management system for **Rash Nail Lounge** with calendar-based scheduling, leveraging the existing UBold admin theme and FullCalendar.js implementation for design consistency.

**Estimated Implementation Time:** 10-12 hours
**Priority:** HIGH (Core business workflow)

---

## System Overview

### Core Features
1. **Calendar View** - FullCalendar integration for visual scheduling
2. **List View** - DataTable-based appointment list
3. **Quick Booking** - Fast walk-in appointment creation
4. **Appointment CRUD** - Complete create, read, update, delete operations
5. **Status Management** - Track appointment lifecycle
6. **Staff Assignment** - Assign stylists to appointments
7. **Service Selection** - Link services/packages to appointments
8. **Availability Checking** - Prevent double-booking
9. **Customer Integration** - Link to existing customer records

### Design Principles
- ✅ Use existing UBold calendar template as base
- ✅ Maintain Bootstrap 5 styling consistency
- ✅ Follow existing CRUD patterns from Services/Customers
- ✅ Use existing UI components (DataTables, Choices.js, SweetAlert2)
- ✅ Implement permission-based access control
- ✅ Responsive design (mobile-friendly)

---

## Database Schema (Already Exists ✅)

```sql
appointments table:
- id (bigint, PK)
- customer_id (FK → customers)
- user_id (FK → users) -- Staff/Stylist
- service_id (FK → services)
- appointment_date (datetime)
- status (enum: 'scheduled', 'in_progress', 'completed', 'cancelled')
- notes (text, nullable)
- created_at, updated_at
```

### Future Enhancement (Optional)
```sql
-- For service packages support
- package_id (FK → service_packages, nullable)
```

---

## Architecture

### File Structure

```
app/
├── Http/Controllers/
│   └── AppointmentController.php (NEW)
├── Models/
│   └── Appointment.php (EXISTS - needs enhancement)

resources/
├── views/appointments/
│   ├── index.blade.php (NEW - List view with DataTable)
│   ├── calendar.blade.php (NEW - Calendar view)
│   ├── create.blade.php (NEW - Booking form)
│   ├── edit.blade.php (NEW - Edit form)
│   ├── show.blade.php (NEW - Appointment details)
│   └── partials/
│       ├── quick-book-modal.blade.php (NEW - Walk-in modal)
│       └── appointment-details-modal.blade.php (NEW - Quick view)
├── js/pages/
│   ├── appointments-calendar.js (NEW - Calendar integration)
│   └── appointments-list.js (NEW - DataTable)

routes/
└── web.php (MODIFY - add appointment routes)

database/
└── seeders/
    ├── RoleSeeder.php (MODIFY - add permissions)
    └── AppointmentSeeder.php (NEW - sample data)
```

---

## Implementation Plan

---

## Phase 1: Model & Database Enhancement (1 hour)

### 1.1 Enhance Appointment Model
**File:** `app/Models/Appointment.php`

**Add the following methods:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'service_id',
        'appointment_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    // ==================== QUERY SCOPES ====================

    public function scopeScheduled(Builder $query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress(Builder $query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled(Builder $query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeToday(Builder $query)
    {
        return $query->whereDate('appointment_date', today());
    }

    public function scopeUpcoming(Builder $query)
    {
        return $query->where('appointment_date', '>=', now())
                     ->where('status', 'scheduled');
    }

    public function scopePast(Builder $query)
    {
        return $query->where('appointment_date', '<', now());
    }

    public function scopeForStaff(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ==================== ACCESSORS ====================

    public function getFormattedDateAttribute(): string
    {
        return $this->appointment_date->format('M d, Y');
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->appointment_date->format('h:i A');
    }

    public function getFormattedDateTimeAttribute(): string
    {
        return $this->appointment_date->format('M d, Y h:i A');
    }

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

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'scheduled' => 'bg-primary-subtle text-primary border-primary',
            'in_progress' => 'bg-warning-subtle text-warning border-warning',
            'completed' => 'bg-success-subtle text-success border-success',
            'cancelled' => 'bg-danger-subtle text-danger border-danger',
        ];

        return $colors[$this->status] ?? 'bg-secondary-subtle text-secondary border-secondary';
    }

    public function getDurationAttribute(): int
    {
        return $this->service->duration ?? 0;
    }

    // ==================== BUSINESS LOGIC ====================

    public function canBeModified(): bool
    {
        return in_array($this->status, ['scheduled']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['scheduled', 'in_progress']);
    }

    public function markInProgress(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function markComplete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isPast(): bool
    {
        return $this->appointment_date->isPast();
    }

    public function isToday(): bool
    {
        return $this->appointment_date->isToday();
    }

    public function isTomorrow(): bool
    {
        return $this->appointment_date->isTomorrow();
    }

    // ==================== HELPERS ====================

    public static function getStatusOptions(): array
    {
        return [
            'scheduled' => 'Scheduled',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function getUpcomingCount(): int
    {
        return static::upcoming()->count();
    }

    public static function getTodayCount(): int
    {
        return static::today()->count();
    }

    public function getCalendarEventData(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->customer->name . ' - ' . $this->service->name,
            'start' => $this->appointment_date->toIso8601String(),
            'end' => $this->appointment_date->addMinutes($this->duration)->toIso8601String(),
            'className' => $this->status_color,
            'extendedProps' => [
                'customer' => $this->customer->name,
                'service' => $this->service->name,
                'staff' => $this->user->name,
                'status' => $this->status,
                'phone' => $this->customer->phone,
                'notes' => $this->notes,
            ],
        ];
    }
}
```

**Key Enhancements:**
- Comprehensive query scopes for filtering
- Formatted date/time accessors
- Status badge colors matching UBold theme
- Business logic methods for state transitions
- Calendar event data formatter

---

## Phase 2: Controller Implementation (2-3 hours)

### 2.1 Create AppointmentController
**File:** `app/Http/Controllers/AppointmentController.php`

**Command:** `php artisan make:controller AppointmentController`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments (List View)
     */
    public function index(Request $request)
    {
        $this->authorize('view appointments');

        $query = Appointment::with(['customer', 'user', 'service'])
            ->orderBy('appointment_date', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('staff')) {
            $query->where('user_id', $request->staff);
        }

        if ($request->filled('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        $appointments = $query->get();

        // Stats
        $stats = [
            'total' => Appointment::count(),
            'today' => Appointment::today()->count(),
            'upcoming' => Appointment::upcoming()->count(),
            'completed' => Appointment::completed()->count(),
        ];

        $staff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['administrator', 'staff']);
        })->get();

        return view('appointments.index', compact('appointments', 'stats', 'staff'));
    }

    /**
     * Display calendar view of appointments
     */
    public function calendar()
    {
        $this->authorize('view appointments');

        $staff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['administrator', 'staff']);
        })->get();

        return view('appointments.calendar', compact('staff'));
    }

    /**
     * Get appointments for calendar (AJAX endpoint)
     */
    public function getCalendarEvents(Request $request)
    {
        $this->authorize('view appointments');

        $query = Appointment::with(['customer', 'user', 'service']);

        // Filter by date range
        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('appointment_date', [
                $request->start,
                $request->end
            ]);
        }

        // Filter by staff
        if ($request->filled('staff_id')) {
            $query->where('user_id', $request->staff_id);
        }

        $appointments = $query->get();

        return response()->json(
            $appointments->map(fn($apt) => $apt->getCalendarEventData())
        );
    }

    /**
     * Show the form for creating a new appointment
     */
    public function create()
    {
        $this->authorize('create appointments');

        $customers = Customer::orderBy('name')->get();
        $services = Service::where('is_active', true)->get();
        $staff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['administrator', 'staff']);
        })->get();
        $packages = ServicePackage::where('is_active', true)->get();

        return view('appointments.create', compact('customers', 'services', 'staff', 'packages'));
    }

    /**
     * Store a newly created appointment
     */
    public function store(Request $request)
    {
        $this->authorize('create appointments');

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ], [
            'appointment_date.after' => 'Appointment date must be in the future.',
        ]);

        // Check for conflicts (optional business rule)
        $conflict = Appointment::where('user_id', $validated['user_id'])
            ->where('status', 'scheduled')
            ->where(function ($query) use ($validated) {
                $query->whereBetween('appointment_date', [
                    $validated['appointment_date'],
                    date('Y-m-d H:i:s', strtotime($validated['appointment_date'] . ' +2 hours'))
                ]);
            })
            ->exists();

        if ($conflict) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This staff member already has an appointment at this time.');
        }

        $validated['status'] = 'scheduled';

        $appointment = Appointment::create($validated);

        return redirect()->route('appointments.calendar')
            ->with('success', 'Appointment created successfully.');
    }

    /**
     * Display the specified appointment
     */
    public function show(Appointment $appointment)
    {
        $this->authorize('view appointments');

        $appointment->load(['customer', 'user', 'service', 'transaction']);

        return view('appointments.show', compact('appointment'));
    }

    /**
     * Show the form for editing the specified appointment
     */
    public function edit(Appointment $appointment)
    {
        $this->authorize('edit appointments');

        if (!$appointment->canBeModified()) {
            return redirect()->route('appointments.show', $appointment)
                ->with('error', 'This appointment cannot be modified.');
        }

        $customers = Customer::orderBy('name')->get();
        $services = Service::where('is_active', true)->get();
        $staff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['administrator', 'staff']);
        })->get();

        return view('appointments.edit', compact('appointment', 'customers', 'services', 'staff'));
    }

    /**
     * Update the specified appointment
     */
    public function update(Request $request, Appointment $appointment)
    {
        $this->authorize('edit appointments');

        if (!$appointment->canBeModified()) {
            return redirect()->route('appointments.show', $appointment)
                ->with('error', 'This appointment cannot be modified.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointment->update($validated);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appointment updated successfully.');
    }

    /**
     * Update appointment status (AJAX endpoint)
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $this->authorize('edit appointments');

        $validated = $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed,cancelled'
        ]);

        $appointment->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Appointment status updated successfully.',
            'appointment' => $appointment->load(['customer', 'user', 'service'])
        ]);
    }

    /**
     * Cancel/delete the appointment
     */
    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete appointments');

        if (!$appointment->canBeCancelled()) {
            return redirect()->back()
                ->with('error', 'This appointment cannot be cancelled.');
        }

        // Soft cancel instead of delete
        $appointment->cancel();

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * Check staff availability (AJAX endpoint)
     */
    public function checkAvailability(Request $request)
    {
        $this->authorize('view appointments');

        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'service_id' => 'required|exists:services,id',
        ]);

        $service = Service::find($validated['service_id']);
        $requestedTime = $validated['date'];
        $duration = $service->duration;

        // Check if staff has overlapping appointments
        $conflict = Appointment::where('user_id', $validated['staff_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($requestedTime, $duration) {
                $endTime = date('Y-m-d H:i:s', strtotime($requestedTime . " +{$duration} minutes"));

                $query->where(function ($q) use ($requestedTime, $endTime) {
                    $q->where('appointment_date', '>=', $requestedTime)
                      ->where('appointment_date', '<', $endTime);
                });
            })
            ->exists();

        return response()->json([
            'available' => !$conflict,
            'message' => $conflict ? 'Staff member is not available at this time.' : 'Staff member is available.'
        ]);
    }
}
```

**Key Features:**
- Authorization checks on all methods
- Conflict detection for double-booking prevention
- Calendar event data endpoint
- AJAX status updates
- Availability checking
- Comprehensive validation

---

## Phase 3: Routes Configuration (30 minutes)

### 3.1 Add Appointment Routes
**File:** `routes/web.php`

**Add after customer routes:**

```php
use App\Http\Controllers\AppointmentController;

// Appointment Management Routes
Route::middleware(['auth', 'can:view appointments'])->group(function () {
    // Main views
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');

    // CRUD routes
    Route::get('/appointments/create', [AppointmentController::class, 'create'])
        ->middleware('can:create appointments')
        ->name('appointments.create');

    Route::post('/appointments', [AppointmentController::class, 'store'])
        ->middleware('can:create appointments')
        ->name('appointments.store');

    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');

    Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])
        ->middleware('can:edit appointments')
        ->name('appointments.edit');

    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])
        ->middleware('can:edit appointments')
        ->name('appointments.update');

    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])
        ->middleware('can:delete appointments')
        ->name('appointments.destroy');

    // AJAX endpoints
    Route::get('/api/appointments/calendar-events', [AppointmentController::class, 'getCalendarEvents'])
        ->name('appointments.calendar-events');

    Route::post('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
        ->middleware('can:edit appointments')
        ->name('appointments.update-status');

    Route::post('/appointments/check-availability', [AppointmentController::class, 'checkAvailability'])
        ->name('appointments.check-availability');
});
```

---

## Phase 4: Permissions Setup (30 minutes)

### 4.1 Add Appointment Permissions
**File:** `database/seeders/RoleSeeder.php`

**Add to the seeder:**

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Inside run() method, add:

// Appointment Permissions
Permission::firstOrCreate(['name' => 'view appointments'], ['guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'create appointments'], ['guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'edit appointments'], ['guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'delete appointments'], ['guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'manage all appointments'], ['guard_name' => 'web']);

// Assign to roles
$administrator = Role::findByName('administrator');
$staff = Role::findByName('staff');

$administrator->givePermissionTo([
    'view appointments',
    'create appointments',
    'edit appointments',
    'delete appointments',
    'manage all appointments'
]);

$staff->givePermissionTo([
    'view appointments',
    'create appointments',
    'edit appointments'
]);
```

**Run seeder:**
```bash
php artisan db:seed --class=RoleSeeder
```

---

## Phase 5: View Implementation (5-6 hours)

### 5.1 Calendar View (Priority 1)
**File:** `resources/views/appointments/calendar.blade.php`

Based on UBold's `apps/calendar.blade.php` but adapted for appointments:

```blade
@extends('layouts.vertical', ['title' => 'Appointments Calendar'])

@section('css')
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Appointments', 'title' => 'Calendar View'])

    <div class="d-flex mb-3 gap-1">
        <!-- Sidebar with filters and quick actions -->
        <div class="card h-100 mb-0 d-none d-lg-flex rounded-end-0" style="min-width: 280px;">
            <div class="card-body">
                <button class="btn btn-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#quick-book-modal">
                    <i class="ti ti-plus me-2 align-middle"></i>
                    Quick Book
                </button>

                <!-- Staff Filter -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Filter by Staff</label>
                    <select class="form-select" id="staff-filter" data-choices>
                        <option value="">All Staff</option>
                        @foreach($staff as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Legend -->
                <div class="mt-4">
                    <p class="text-muted fw-semibold mb-2">Status</p>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-primary-subtle text-primary me-2" style="width: 20px; height: 20px;"></span>
                        <span class="fs-sm">Scheduled</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-warning-subtle text-warning me-2" style="width: 20px; height: 20px;"></span>
                        <span class="fs-sm">In Progress</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-success-subtle text-success me-2" style="width: 20px; height: 20px;"></span>
                        <span class="fs-sm">Completed</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-danger-subtle text-danger me-2" style="width: 20px; height: 20px;"></span>
                        <span class="fs-sm">Cancelled</span>
                    </div>
                </div>

                <!-- Today's Stats -->
                <div class="mt-4 pt-3 border-top">
                    <p class="text-muted fw-semibold mb-2">Today's Overview</p>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fs-sm">Total:</span>
                        <span class="fw-semibold" id="today-total">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fs-sm">Completed:</span>
                        <span class="fw-semibold text-success" id="today-completed">0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="fs-sm">Upcoming:</span>
                        <span class="fw-semibold text-primary" id="today-upcoming">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="card h-100 mb-0 rounded-start-0 flex-grow-1 border-start-0">
            <div class="d-lg-none d-inline-flex card-header">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quick-book-modal">
                    <i class="ti ti-plus me-2 align-middle"></i>
                    Quick Book
                </button>
            </div>
            <div class="card-body" style="height: calc(100vh - 280px);">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- Quick Book Modal -->
    @include('appointments.partials.quick-book-modal')

    <!-- Appointment Details Modal -->
    @include('appointments.partials.appointment-details-modal')
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js', 'resources/js/pages/appointments-calendar.js'])
@endsection
```

### 5.2 List View
**File:** `resources/views/appointments/index.blade.php`

```blade
@extends('layouts.vertical', ['title' => 'Appointments'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Appointments', 'title' => 'All Appointments'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Appointment List</h4>
                        <p class="text-muted mb-0">Manage all salon appointments</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search appointments..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('create appointments')
                        <a href="{{ route('appointments.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Book Appointment
                        </a>
                        <a href="{{ route('appointments.calendar') }}" class="btn btn-secondary">
                            <i class="ti ti-calendar me-1"></i> Calendar
                        </a>
                        @endcan
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="card-body border-top border-light">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['today'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Today</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['upcoming'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Upcoming</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['completed'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Completed</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">ID</th>
                                <th data-table-sort="sort-customer">Customer</th>
                                <th data-table-sort="sort-service">Service</th>
                                <th data-table-sort="sort-staff">Staff</th>
                                <th data-table-sort="sort-date">Date & Time</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $appointment)
                            <tr>
                                <td class="ps-3">{{ $appointment->id }}</td>
                                <td data-sort="sort-customer">
                                    <div>
                                        <h5 class="fs-base mb-0">{{ $appointment->customer->name }}</h5>
                                        <small class="text-muted">{{ $appointment->customer->phone }}</small>
                                    </div>
                                </td>
                                <td data-sort="sort-service">{{ $appointment->service->name }}</td>
                                <td data-sort="sort-staff">{{ $appointment->user->name }}</td>
                                <td data-sort="sort-date">
                                    <div>
                                        <span class="fw-semibold">{{ $appointment->formatted_date }}</span><br>
                                        <small class="text-muted">{{ $appointment->formatted_time }}</small>
                                    </div>
                                </td>
                                <td data-sort="sort-status">
                                    <span class="badge bg-{{ $appointment->status_badge }}-subtle text-{{ $appointment->status_badge }}">
                                        <i class="ti ti-circle-filled fs-xs"></i> {{ ucfirst($appointment->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('appointments.show', $appointment) }}" title="View Details">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('edit appointments')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('appointments.edit', $appointment) }}" title="Edit">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        @endcan
                                        @can('delete appointments')
                                        <form id="delete-form-{{ $appointment->id }}" action="{{ route('appointments.destroy', $appointment) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                onclick="confirmCancel('{{ $appointment->id }}', '{{ addslashes($appointment->customer->name) }}')"
                                                title="Cancel">
                                            <i class="ti ti-x fs-lg"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-calendar-off fs-24 mb-2 d-block"></i>
                                        No appointments found. <a href="{{ route('appointments.create') }}">Create the first appointment</a>.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="appointments"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmCancel(appointmentId, customerName) {
            Swal.fire({
                title: 'Cancel Appointment',
                text: `Are you sure you want to cancel the appointment for "${customerName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${appointmentId}`).submit();
                }
            });
        }
    </script>
@endsection
```

### 5.3 Create/Edit Forms
**File:** `resources/views/appointments/create.blade.php`

```blade
@extends('layouts.vertical', ['title' => 'Book Appointment'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/flatpickr/dist/flatpickr.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Appointments', 'title' => 'Book New Appointment'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('appointments.store') }}" id="appointment-form">
                        @csrf

                        <div class="row">
                            <!-- Customer Selection -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                    <select class="form-control" id="customer_id" name="customer_id" data-choices required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }} ({{ $customer->phone }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <span class="text-danger"><small>{{ $message }}</small></span>
                                    @enderror
                                    <div class="form-text">
                                        <a href="{{ route('customers.create') }}" target="_blank">+ Add New Customer</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Staff Selection -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Staff/Stylist <span class="text-danger">*</span></label>
                                    <select class="form-control" id="user_id" name="user_id" data-choices required>
                                        <option value="">Select Staff</option>
                                        @foreach($staff as $member)
                                            <option value="{{ $member->id }}" {{ old('user_id') == $member->id ? 'selected' : '' }}>
                                                {{ $member->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <span class="text-danger"><small>{{ $message }}</small></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Service Selection -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                                    <select class="form-control" id="service_id" name="service_id" data-choices required>
                                        <option value="">Select Service</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}"
                                                    data-duration="{{ $service->duration }}"
                                                    data-price="{{ $service->price }}"
                                                    {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                                {{ $service->name }} (${{ number_format($service->price, 2) }}) - {{ $service->duration }} min
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('service_id')
                                        <span class="text-danger"><small>{{ $message }}</small></span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Date & Time -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="appointment_date" class="form-label">Date & Time <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="appointment_date" name="appointment_date"
                                           value="{{ old('appointment_date') }}"
                                           placeholder="Select date and time" required>
                                    @error('appointment_date')
                                        <span class="text-danger"><small>{{ $message }}</small></span>
                                    @enderror
                                    <div id="availability-message" class="mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <span class="text-danger"><small>{{ $message }}</small></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Service Summary -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Appointment Summary</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p class="mb-1"><strong>Duration:</strong> <span id="summary-duration">-</span></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-1"><strong>Price:</strong> <span id="summary-price">-</span></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-1"><strong>End Time:</strong> <span id="summary-end-time">-</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="ti ti-check me-1"></i> Book Appointment
                            </button>
                            <a href="{{ route('appointments.calendar') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js'])
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize flatpickr for date/time selection
            const appointmentDatePicker = flatpickr("#appointment_date", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                minuteIncrement: 15,
                time_24hr: false,
                onChange: function(selectedDates, dateStr, instance) {
                    checkAvailability();
                    updateSummary();
                }
            });

            // Update summary when service changes
            document.getElementById('service_id').addEventListener('change', function() {
                updateSummary();
                checkAvailability();
            });

            document.getElementById('user_id').addEventListener('change', function() {
                checkAvailability();
            });

            function updateSummary() {
                const serviceSelect = document.getElementById('service_id');
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

                if (selectedOption && selectedOption.value) {
                    const duration = selectedOption.getAttribute('data-duration');
                    const price = selectedOption.getAttribute('data-price');
                    const appointmentDate = document.getElementById('appointment_date').value;

                    document.getElementById('summary-duration').textContent = duration + ' minutes';
                    document.getElementById('summary-price').textContent = '$' + parseFloat(price).toFixed(2);

                    if (appointmentDate) {
                        const endTime = new Date(appointmentDate);
                        endTime.setMinutes(endTime.getMinutes() + parseInt(duration));
                        document.getElementById('summary-end-time').textContent = endTime.toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } else {
                        document.getElementById('summary-end-time').textContent = '-';
                    }
                } else {
                    document.getElementById('summary-duration').textContent = '-';
                    document.getElementById('summary-price').textContent = '-';
                    document.getElementById('summary-end-time').textContent = '-';
                }
            }

            function checkAvailability() {
                const staffId = document.getElementById('user_id').value;
                const serviceId = document.getElementById('service_id').value;
                const date = document.getElementById('appointment_date').value;
                const messageDiv = document.getElementById('availability-message');
                const submitBtn = document.getElementById('submit-btn');

                if (!staffId || !serviceId || !date) {
                    messageDiv.innerHTML = '';
                    submitBtn.disabled = false;
                    return;
                }

                // AJAX call to check availability
                fetch('{{ route('appointments.check-availability') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        staff_id: staffId,
                        service_id: serviceId,
                        date: date
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        messageDiv.innerHTML = '<small class="text-success"><i class="ti ti-check-circle me-1"></i>' + data.message + '</small>';
                        submitBtn.disabled = false;
                    } else {
                        messageDiv.innerHTML = '<small class="text-danger"><i class="ti ti-alert-circle me-1"></i>' + data.message + '</small>';
                        submitBtn.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    messageDiv.innerHTML = '';
                    submitBtn.disabled = false;
                });
            }
        });
    </script>
@endsection
```

### 5.4 Show View
**File:** `resources/views/appointments/show.blade.php`

```blade
@extends('layouts.vertical', ['title' => 'Appointment Details'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Appointments', 'title' => 'Appointment #' . $appointment->id])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h4 class="mb-1">{{ $appointment->customer->name }}</h4>
                            <p class="text-muted mb-0">{{ $appointment->service->name }}</p>
                        </div>
                        <span class="badge bg-{{ $appointment->status_badge }}-subtle text-{{ $appointment->status_badge }} fs-base">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </div>

                    <div class="mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Customer</label>
                                    <div>
                                        <p class="mb-0">{{ $appointment->customer->name }}</p>
                                        <small class="text-muted">{{ $appointment->customer->phone }}</small><br>
                                        <small class="text-muted">{{ $appointment->customer->email }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Staff/Stylist</label>
                                    <div>
                                        <p class="mb-0">{{ $appointment->user->name }}</p>
                                        <small class="text-muted">{{ $appointment->user->email }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Service</label>
                                    <div>
                                        <p class="mb-0">{{ $appointment->service->name }}</p>
                                        <small class="text-muted">${{ number_format($appointment->service->price, 2) }} • {{ $appointment->service->duration }} minutes</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Date & Time</label>
                                    <div>
                                        <p class="mb-0">{{ $appointment->formatted_date_time }}</p>
                                        @if($appointment->isToday())
                                            <span class="badge bg-primary-subtle text-primary">Today</span>
                                        @elseif($appointment->isTomorrow())
                                            <span class="badge bg-info-subtle text-info">Tomorrow</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($appointment->notes)
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Notes</label>
                                    <p class="text-muted mb-0">{{ $appointment->notes }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Created At</label>
                                    <p class="mb-0">{{ $appointment->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Last Updated</label>
                                    <p class="mb-0">{{ $appointment->updated_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <a href="{{ route('appointments.calendar') }}" class="btn btn-light">
                            <i class="ti ti-arrow-left me-1"></i> Back to Calendar
                        </a>
                        @can('edit appointments')
                            @if($appointment->canBeModified())
                                <a href="{{ route('appointments.edit', $appointment) }}" class="btn btn-primary">
                                    <i class="ti ti-edit me-1"></i> Edit
                                </a>
                            @endif
                        @endcan
                        @can('delete appointments')
                            @if($appointment->canBeCancelled())
                                <form id="cancel-form" action="{{ route('appointments.destroy', $appointment) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger" onclick="confirmCancel()">
                                        <i class="ti ti-x me-1"></i> Cancel Appointment
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            @if($appointment->transaction)
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Payment Information</h5>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0">Amount: <strong>${{ number_format($appointment->transaction->amount, 2) }}</strong></p>
                            <p class="mb-0">Method: {{ ucfirst($appointment->transaction->payment_method) }}</p>
                        </div>
                        <span class="badge bg-{{ $appointment->transaction->status === 'completed' ? 'success' : 'warning' }}-subtle text-{{ $appointment->transaction->status === 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($appointment->transaction->status) }}
                        </span>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="col-lg-4">
            @if($appointment->status === 'scheduled')
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Quick Actions</h5>
                    @can('edit appointments')
                        <button class="btn btn-warning w-100 mb-2" onclick="updateStatus('in_progress')">
                            <i class="ti ti-player-play me-1"></i> Start Service
                        </button>
                        <button class="btn btn-success w-100 mb-2" onclick="updateStatus('completed')">
                            <i class="ti ti-check me-1"></i> Mark Complete
                        </button>
                    @endcan
                </div>
            </div>
            @elseif($appointment->status === 'in_progress')
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Quick Actions</h5>
                    @can('edit appointments')
                        <button class="btn btn-success w-100 mb-2" onclick="updateStatus('completed')">
                            <i class="ti ti-check me-1"></i> Mark Complete
                        </button>
                    @endcan
                </div>
            </div>
            @endif

            <!-- Customer Info -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Customer History</h5>
                    <p class="mb-1"><small class="text-muted">Total Appointments:</small></p>
                    <p class="fw-semibold">{{ $appointment->customer->appointments()->count() }}</p>
                    <p class="mb-1"><small class="text-muted">Last Visit:</small></p>
                    <p class="fw-semibold">
                        @if($lastAppointment = $appointment->customer->appointments()->where('id', '!=', $appointment->id)->completed()->latest('appointment_date')->first())
                            {{ $lastAppointment->appointment_date->diffForHumans() }}
                        @else
                            First visit
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function updateStatus(newStatus) {
            Swal.fire({
                title: 'Update Status',
                text: `Change appointment status to "${newStatus.replace('_', ' ')}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, update it!',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('appointments.update-status', $appointment) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonClass: 'btn btn-primary'
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to update status',
                            icon: 'error',
                            confirmButtonClass: 'btn btn-primary'
                        });
                    });
                }
            });
        }

        function confirmCancel() {
            Swal.fire({
                title: 'Cancel Appointment',
                text: 'Are you sure you want to cancel this appointment?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, cancel it!',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cancel-form').submit();
                }
            });
        }
    </script>
@endsection
```

### 5.5 Quick Book Modal
**File:** `resources/views/appointments/partials/quick-book-modal.blade.php`

```blade
<!-- Quick Book Modal -->
<div class="modal fade" id="quick-book-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="quick-book-form" method="POST" action="{{ route('appointments.store') }}">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title">Quick Book Appointment</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Customer</label>
                                <select class="form-control" name="customer_id" id="quick-customer" required>
                                    <option value="">Select Customer</option>
                                    @foreach(App\Models\Customer::orderBy('name')->get() as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Staff</label>
                                <select class="form-control" name="user_id" id="quick-staff" required>
                                    <option value="">Select Staff</option>
                                    @foreach(App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', ['administrator', 'staff']))->get() as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Service</label>
                                <select class="form-control" name="service_id" id="quick-service" required>
                                    <option value="">Select Service</option>
                                    @foreach(App\Models\Service::where('is_active', true)->get() as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date & Time</label>
                                <input type="text" class="form-control" name="appointment_date" id="quick-datetime" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Book Now</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

### 5.6 Appointment Details Modal
**File:** `resources/views/appointments/partials/appointment-details-modal.blade.php`

```blade
<!-- Appointment Details Modal -->
<div class="modal fade" id="appointment-details-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appointment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="appointment-details-content">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="appointment-details-view-btn" class="btn btn-primary">View Full Details</a>
            </div>
        </div>
    </div>
</div>
```

---

## Phase 6: JavaScript Implementation (2-3 hours)

### 6.1 Calendar JavaScript
**File:** `resources/js/pages/appointments-calendar.js`

```javascript
/**
 * Appointments Calendar Integration
 * Based on UBold Calendar Template
 */
import { Modal } from 'bootstrap/dist/js/bootstrap.bundle.min';
import { Calendar } from '@fullcalendar/core';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import Choices from 'choices.js';
import flatpickr from 'flatpickr';

class AppointmentsCalendar {
    constructor() {
        this.calendarEl = document.getElementById('calendar');
        this.quickBookModal = new Modal(document.getElementById('quick-book-modal'));
        this.detailsModal = new Modal(document.getElementById('appointment-details-modal'));
        this.calendar = null;
        this.staffFilter = null;
        this.init();
    }

    init() {
        // Initialize calendar
        this.calendar = new Calendar(this.calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            slotDuration: '00:15:00',
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            slotLabelInterval: '01:00',
            height: 'auto',
            contentHeight: 'auto',
            aspectRatio: 2,
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            weekends: true,
            nowIndicator: true,
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5, 6], // Monday - Saturday
                startTime: '09:00',
                endTime: '18:00'
            },

            // Load events from backend
            events: (fetchInfo, successCallback, failureCallback) => {
                this.fetchAppointments(fetchInfo, successCallback, failureCallback);
            },

            // Event click - show details
            eventClick: (info) => {
                this.showAppointmentDetails(info.event);
            },

            // Date select - create new appointment
            select: (info) => {
                this.openQuickBook(info.start);
            },

            // Event drag & drop
            eventDrop: (info) => {
                this.updateAppointmentTime(info.event);
            },

            // Event resize
            eventResize: (info) => {
                this.updateAppointmentTime(info.event);
            }
        });

        this.calendar.render();

        // Initialize staff filter
        this.initStaffFilter();

        // Initialize quick book form
        this.initQuickBookForm();

        // Update today's stats
        this.updateTodayStats();
    }

    fetchAppointments(fetchInfo, successCallback, failureCallback) {
        const staffId = this.staffFilter ? this.staffFilter.getValue(true) : '';

        fetch(`/api/appointments/calendar-events?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}&staff_id=${staffId}`)
            .then(response => response.json())
            .then(data => {
                successCallback(data);
                this.updateTodayStats();
            })
            .catch(error => {
                console.error('Error fetching appointments:', error);
                failureCallback(error);
            });
    }

    initStaffFilter() {
        const staffFilterEl = document.getElementById('staff-filter');
        if (staffFilterEl) {
            this.staffFilter = new Choices(staffFilterEl, {
                searchEnabled: false,
                itemSelectText: '',
            });

            staffFilterEl.addEventListener('change', () => {
                this.calendar.refetchEvents();
            });
        }
    }

    initQuickBookForm() {
        const form = document.getElementById('quick-book-form');
        if (form) {
            // Initialize Choices.js for selects
            new Choices(document.getElementById('quick-customer'));
            new Choices(document.getElementById('quick-staff'));
            new Choices(document.getElementById('quick-service'));

            // Initialize flatpickr for datetime
            flatpickr('#quick-datetime', {
                enableTime: true,
                dateFormat: 'Y-m-d H:i',
                minDate: 'today',
                minuteIncrement: 15,
                time_24hr: false
            });
        }
    }

    openQuickBook(date) {
        const datetimeInput = document.getElementById('quick-datetime');
        if (datetimeInput && datetimeInput._flatpickr) {
            datetimeInput._flatpickr.setDate(date);
        }
        this.quickBookModal.show();
    }

    showAppointmentDetails(event) {
        const props = event.extendedProps;
        const content = `
            <div class="mb-3">
                <h6 class="fw-semibold">Customer</h6>
                <p class="mb-0">${props.customer}</p>
                <small class="text-muted">${props.phone || ''}</small>
            </div>
            <div class="mb-3">
                <h6 class="fw-semibold">Service</h6>
                <p class="mb-0">${props.service}</p>
            </div>
            <div class="mb-3">
                <h6 class="fw-semibold">Staff</h6>
                <p class="mb-0">${props.staff}</p>
            </div>
            <div class="mb-3">
                <h6 class="fw-semibold">Time</h6>
                <p class="mb-0">${event.start.toLocaleString()}</p>
            </div>
            <div class="mb-3">
                <h6 class="fw-semibold">Status</h6>
                <span class="badge bg-${this.getStatusBadge(props.status)}-subtle text-${this.getStatusBadge(props.status)}">
                    ${props.status}
                </span>
            </div>
            ${props.notes ? `<div class="mb-3"><h6 class="fw-semibold">Notes</h6><p class="mb-0">${props.notes}</p></div>` : ''}
        `;

        document.getElementById('appointment-details-content').innerHTML = content;
        document.getElementById('appointment-details-view-btn').href = `/appointments/${event.id}`;
        this.detailsModal.show();
    }

    updateAppointmentTime(event) {
        // AJAX call to update appointment time
        const appointmentId = event.id;
        const newDate = event.start.toISOString();

        fetch(`/appointments/${appointmentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                appointment_date: newDate,
                _method: 'PUT'
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Appointment updated');
        })
        .catch(error => {
            console.error('Error updating appointment:', error);
            event.revert();
        });
    }

    updateTodayStats() {
        // Fetch today's stats
        const today = new Date().toISOString().split('T')[0];

        fetch(`/api/appointments/calendar-events?start=${today}&end=${today}`)
            .then(response => response.json())
            .then(data => {
                const total = data.length;
                const completed = data.filter(apt => apt.extendedProps.status === 'completed').length;
                const upcoming = data.filter(apt => apt.extendedProps.status === 'scheduled').length;

                document.getElementById('today-total').textContent = total;
                document.getElementById('today-completed').textContent = completed;
                document.getElementById('today-upcoming').textContent = upcoming;
            });
    }

    getStatusBadge(status) {
        const badges = {
            'scheduled': 'primary',
            'in_progress': 'warning',
            'completed': 'success',
            'cancelled': 'danger'
        };
        return badges[status] || 'secondary';
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new AppointmentsCalendar();
});
```

---

## Phase 7: Navigation & Final Integration (1 hour)

### 7.1 Add Menu Items
**File:** `resources/views/layouts/partials/menu.blade.php` (or sidenav.blade.php)

**Add after Services section:**

```blade
<!-- Appointments Section -->
<li class="menu-title mt-2">Appointments</li>

@can('view appointments')
<li class="menu-item">
    <a href="{{ route('appointments.calendar') }}" class="menu-link">
        <span class="menu-icon"><i data-lucide="calendar"></i></span>
        <span class="menu-text">Calendar</span>
    </a>
</li>
<li class="menu-item">
    <a href="{{ route('appointments.index') }}" class="menu-link">
        <span class="menu-icon"><i data-lucide="list"></i></span>
        <span class="menu-text">All Appointments</span>
    </a>
</li>
@endcan

@can('create appointments')
<li class="menu-item">
    <a href="{{ route('appointments.create') }}" class="menu-link">
        <span class="menu-icon"><i data-lucide="calendar-plus"></i></span>
        <span class="menu-text">Book Appointment</span>
    </a>
</li>
@endcan
```

### 7.2 Add Dashboard Widgets (Optional)
**File:** `resources/views/dashboard/index.blade.php` (if exists)

```blade
<!-- Today's Appointments Widget -->
<div class="col-md-6 col-xl-3">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="avatar-sm">
                        <span class="avatar-title bg-primary-subtle rounded">
                            <i class="ti ti-calendar fs-24 text-primary"></i>
                        </span>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <p class="text-muted mb-1 text-truncate">Today's Appointments</p>
                    <h4 class="mb-0">{{ \App\Models\Appointment::today()->count() }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## Phase 8: Package.json Dependencies (15 minutes)

### 8.1 Add FullCalendar Dependencies
**File:** `package.json`

**Add to dependencies:**

```json
{
  "dependencies": {
    "@fullcalendar/core": "^6.1.8",
    "@fullcalendar/daygrid": "^6.1.8",
    "@fullcalendar/timegrid": "^6.1.8",
    "@fullcalendar/list": "^6.1.8",
    "@fullcalendar/interaction": "^6.1.8",
    "flatpickr": "^4.6.13"
  }
}
```

**Run:**
```bash
npm install
npm run build
```

---

## Testing Checklist

### Functional Testing
- [ ] Calendar loads with existing appointments
- [ ] Can create appointment via form
- [ ] Can create appointment via quick book modal
- [ ] Can create appointment by clicking on calendar
- [ ] Calendar displays correct colors for status
- [ ] Can drag & drop appointments to reschedule
- [ ] Can click appointment to view details
- [ ] Staff filter works correctly
- [ ] Availability checking prevents double-booking
- [ ] Can edit appointment from form
- [ ] Can update status via quick actions
- [ ] Can cancel appointment
- [ ] List view displays all appointments correctly
- [ ] Search/filter in list view works
- [ ] Status badges display correctly
- [ ] Permissions are enforced (staff vs admin)

### UI/UX Testing
- [ ] Calendar is responsive (mobile/desktop)
- [ ] Matches UBold theme styling
- [ ] Loading states display properly
- [ ] Error messages are clear
- [ ] Success messages appear
- [ ] Modals open/close smoothly
- [ ] Forms validate correctly
- [ ] Date picker works intuitively

### Performance Testing
- [ ] Calendar loads quickly with many appointments
- [ ] AJAX calls don't block UI
- [ ] No console errors
- [ ] Assets load efficiently

---

## Optional Enhancements

### Phase 9: Advanced Features (Future)
1. **Email/SMS Reminders**
   - Send appointment reminders
   - Confirmation emails

2. **Recurring Appointments**
   - Weekly/monthly bookings
   - Series management

3. **Service Packages Integration**
   - Book package instead of single service
   - Track package usage

4. **Staff Availability Management**
   - Set working hours
   - Block out time
   - Vacation scheduling

5. **Customer Portal**
   - Online booking
   - View appointment history
   - Cancel/reschedule

6. **Waiting List**
   - Queue management
   - Auto-fill cancellations

7. **Analytics Dashboard**
   - Booking trends
   - Staff utilization
   - Revenue by service

---

## Deployment Steps

1. **Run Migrations** (Already done)
   ```bash
   php artisan migrate
   ```

2. **Seed Permissions**
   ```bash
   php artisan db:seed --class=RoleSeeder
   ```

3. **Install NPM Dependencies**
   ```bash
   npm install
   npm run build
   ```

4. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

5. **Test in Browser**
   - Navigate to `/appointments/calendar`
   - Create test appointment
   - Verify all features work

---

## Success Criteria

✅ **Core Functionality**
- Appointments can be created, viewed, edited, and cancelled
- Calendar displays appointments visually
- Status management works correctly
- Double-booking prevention works
- Staff assignment functions properly

✅ **Design Consistency**
- Matches UBold theme perfectly
- Uses existing UI components
- Follows Bootstrap 5 patterns
- Responsive on all devices

✅ **Business Logic**
- Prevents scheduling conflicts
- Links customers, staff, and services correctly
- Status transitions follow business rules
- Permissions are enforced

✅ **User Experience**
- Intuitive calendar interface
- Quick booking for walk-ins
- Clear appointment details
- Easy status updates
- Helpful error messages

---

## Next Steps After Completion

1. **Implement POS System** - Process payments for completed appointments
2. **Add Reporting** - Analytics and insights
3. **Email Notifications** - Reminders and confirmations
4. **Customer Portal** - Online booking
5. **Staff Commissions** - Track earnings

---

## Estimated Timeline

- **Phase 1:** Model Enhancement - 1 hour
- **Phase 2:** Controller - 2-3 hours
- **Phase 3:** Routes & Permissions - 1 hour
- **Phase 4:** Views (Calendar + List) - 3-4 hours
- **Phase 5:** JavaScript Integration - 2-3 hours
- **Phase 6:** Testing & Polish - 1-2 hours

**Total: 10-14 hours**

---

## Conclusion

This implementation provides a comprehensive appointment management system that:
- Leverages existing UBold calendar template for design consistency
- Integrates seamlessly with existing customer/service data
- Provides both calendar and list views for flexibility
- Implements proper business logic (conflict detection, status management)
- Maintains permission-based access control
- Offers excellent user experience for both staff and administrators

The system establishes the foundation for POS integration and future enhancements like online booking, reminders, and analytics.
