<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Setting;
use App\Services\BusinessHoursService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments (List View)
     */
    public function index(Request $request)
    {
        $this->authorize('view appointments');

        $query = Appointment::with(['customer', 'user', 'service', 'services'])
            ->orderBy('appointment_date', 'desc');

        // Apply filters
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->filled('staff') && $request->staff !== 'All') {
            $query->where('user_id', $request->staff);
        }

        if ($request->filled('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        $appointments = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => Appointment::count(),
            'today' => Appointment::today()->count(),
            'upcoming' => Appointment::upcoming()->count(),
            'completed' => Appointment::completed()->count(),
        ];

        $staff = User::withStaffRole()->get();

        return view('appointments.index', compact('appointments', 'stats', 'staff'));
    }

    /**
     * Display calendar view of appointments
     */
    public function calendar()
    {
        $this->authorize('view appointments');

        $customers = Customer::orderBy('first_name')->get();
        $services = Service::where('is_active', true)->get();
        $staff = User::withStaffRole()->get();

        $businessHours = app(BusinessHoursService::class)->getWeeklyHoursForBooking();

        // Stats
        $stats = [
            'total' => Appointment::today()->count(),
            'scheduled' => Appointment::today()->where('status', 'scheduled')->count(),
            'in_progress' => Appointment::today()->where('status', 'in_progress')->count(),
            'completed' => Appointment::today()->where('status', 'completed')->count(),
        ];

        return view('appointments.calendar', compact('customers', 'services', 'staff', 'businessHours', 'stats'));
    }

    /**
     * Get appointments for calendar (AJAX endpoint)
     */
    public function getCalendarEvents(Request $request)
    {
        $this->authorize('view appointments');

        $query = Appointment::with(['customer', 'user', 'service', 'services']);

        // Filter by date range
        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('appointment_date', [
                Carbon::parse($request->start),
                Carbon::parse($request->end)
            ]);
        }

        // Filter by staff
        if ($request->filled('staff_id')) {
            $query->where('user_id', $request->staff_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        $customers = Customer::orderBy('first_name')->get();
        $services = Service::where('is_active', true)->get();
        $staff = User::withStaffRole()->get();
        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        return view('appointments.create', compact('customers', 'services', 'staff', 'currencySymbol'));
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
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ], [
            'appointment_date.after_or_equal' => 'Appointment date must be today or in the future.',
            'service_ids.required' => 'Please select at least one service.',
        ]);

        $serviceIds = $validated['service_ids'];
        $primaryServiceId = $serviceIds[0];
        $services = Service::whereIn('id', $serviceIds)->get();
        $totalDuration = $services->sum('duration');
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts using total duration
        if ($this->hasConflict($validated['user_id'], $appointmentDate, $totalDuration)) {
             return redirect()->back()
                ->withInput()
                ->with('error', 'This staff member already has an appointment at this time, with potential overlap.');
        }

        // Check business hours using the total duration of all selected services
        if (!$this->isWithinBusinessHours($appointmentDate, $totalDuration)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Appointment time must be within business hours and duration must fit before closing time.');
        }

        $this->enforceAppointmentLimits($appointmentDate, $validated['user_id'] ?? null);

        // Create appointment with primary service_id for backward compat
        $appointment = Appointment::create([
            'customer_id' => $validated['customer_id'],
            'user_id' => $validated['user_id'],
            'service_id' => $primaryServiceId,
            'appointment_date' => $validated['appointment_date'],
            'status' => 'scheduled',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Sync pivot table
        $pivotData = [];
        foreach ($services as $service) {
            $pivotData[$service->id] = [
                'quantity' => 1,
                'unit_price' => $service->price,
            ];
        }
        $appointment->services()->sync($pivotData);

        return redirect()->route('appointments.calendar')
            ->with('success', 'Appointment created successfully.');
    }

    /**
     * Display the specified appointment
     */
    public function show(Appointment $appointment)
    {
        $this->authorize('view appointments');

        $appointment->load(['customer', 'user', 'service', 'transaction', 'services']);
        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        return view('appointments.show', compact('appointment', 'currencySymbol'));
    }

    /**
     * Show the form for editing the specified appointment
     */
    public function edit(Appointment $appointment)
    {
        $this->authorize('edit appointments');

        if (!$appointment->canBeModified()) {
            return redirect()->route('appointments.show', $appointment->slug)
                ->with('error', 'This appointment cannot be modified.');
        }

        $customers = Customer::orderBy('first_name')->get();
        $services = Service::where('is_active', true)->get();
        $staff = User::withStaffRole()->get();
        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        return view('appointments.edit', compact('appointment', 'customers', 'services', 'staff', 'currencySymbol'));
    }

    /**
     * Update the specified appointment
     */
    public function update(Request $request, Appointment $appointment)
    {
        $this->authorize('edit appointments');

        if (!$appointment->canBeModified()) {
            return redirect()->route('appointments.show', $appointment->slug)
                ->with('error', 'This appointment cannot be modified.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'appointment_date' => 'required|date',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $serviceIds = $validated['service_ids'];
        $primaryServiceId = $serviceIds[0];
        $services = Service::whereIn('id', $serviceIds)->get();
        $totalDuration = $services->sum('duration');
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts
        if ($this->hasConflict($validated['user_id'], $appointmentDate, $totalDuration, $appointment->id)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This staff member already has an appointment at this time, with potential overlap.');
        }

        // Check business hours using the total duration of all selected services
        if (!$this->isWithinBusinessHours($appointmentDate, $totalDuration)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Appointment time must be within business hours and duration must fit before closing time.');
        }

        $this->enforceAppointmentLimits($appointmentDate, $validated['user_id'] ?? null);

        // Update appointment
        $appointment->update([
            'customer_id' => $validated['customer_id'],
            'user_id' => $validated['user_id'],
            'service_id' => $primaryServiceId,
            'appointment_date' => $validated['appointment_date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Sync pivot table
        $pivotData = [];
        foreach ($services as $service) {
            $pivotData[$service->id] = ['quantity' => 1, 'unit_price' => $service->price];
        }
        $appointment->services()->sync($pivotData);

        return redirect()->route('appointments.show', $appointment->slug)
            ->with('success', 'Appointment updated successfully.');
    }

    /**
     * Cancel the appointment
     */
    public function destroy(Appointment $appointment, Request $request)
    {
        $this->authorize('delete appointments');

        $appointment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Appointment deleted successfully.'
            ]);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment deleted successfully.');
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Appointment status updated successfully.',
                'appointment' => $appointment->load(['customer', 'user', 'service', 'services'])
            ]);
        }

        return redirect()->route('appointments.show', $appointment->slug)
            ->with('success', 'Appointment status updated successfully.');
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
        $duration = $service->duration ?? 60;
        $appointmentDate = Carbon::parse($validated['date']);

        $conflict = $this->hasConflict($validated['staff_id'], $appointmentDate, $duration);

        return response()->json([
            'available' => !$conflict,
            'message' => $conflict ? 'Staff member is not available at this time.' : 'Staff member is available.'
        ]);
    }

    /**
     * Update appointment date/time via AJAX
     */
    public function updateDatetime(Appointment $appointment, Request $request)
    {
        $this->authorize('edit appointments');

        if (!$appointment->canBeModified()) {
            return response()->json([
                'success' => false,
                'message' => 'This appointment cannot be modified.'
            ]);
        }

        $validated = $request->validate([
            'appointment_date' => 'required|date',
        ]);

        $service = $appointment->service;
        $duration = $appointment->services->sum(fn($s) => ($s->duration ?? 60) * ($s->pivot->quantity ?? 1)) ?: ($service->duration ?? 60);

        // The JS drag-drop sends an ISO-8601 string in UTC (e.g.
        // "2026-07-27T08:00:00.000Z").  Convert to the app's timezone
        // so the business-hours check compares against local time
        // (e.g. 13:30 Colombo, not 08:00 UTC).
        $appointmentDate = Carbon::parse($validated['appointment_date'])
            ->setTimezone(config('app.timezone'));

        // Reject if dragged to a past date
        if ($appointmentDate->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment cannot be rescheduled to a past date or time.'
            ]);
        }

        // Check for conflicts
        if ($this->hasConflict($appointment->user_id, $appointmentDate, $duration, $appointment->id)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff member already has an appointment at this time, with potential overlap.'
            ]);
        }

        // Check business hours using the total duration of all selected services
        if (!$this->isWithinBusinessHours($appointmentDate, $duration)) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment time must be within business hours and duration must fit before closing time.'
            ]);
        }

        $appointment->update(['appointment_date' => $appointmentDate]);

        // Reload for fresh data
        $appointment->refresh();
        $appointment->load(['customer', 'user', 'service', 'services']);

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully.',
            'appointment' => $appointment->getCalendarEventData()
        ]);
    }

    /**
     * Update appointment via AJAX
     */
    public function updateViaAjax(Appointment $appointment, Request $request)
    {
        $this->authorize('edit appointments');

        \Illuminate\Support\Facades\Log::info('UpdateViaAjax: appt id ' . $appointment->id . ' status: ' . $appointment->status . ' canBeModified: ' . ($appointment->canBeModified() ? 'true' : 'false'));

        if (!$appointment->canBeModified()) {
            return response()->json([
                'success' => false,
                'message' => 'This appointment cannot be modified.'
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'appointment_date' => 'required|date'
        ]);

        $serviceIds = $validated['service_ids'];
        $primaryServiceId = $serviceIds[0];
        $services = Service::whereIn('id', $serviceIds)->get();
        $totalDuration = $services->sum('duration');
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts
        if ($this->hasConflict($validated['user_id'], $appointmentDate, $totalDuration, $appointment->id)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff member already has an appointment at this time, with potential overlap.'
            ]);
        }

        // Check business hours using the total duration of all selected services
        if (!$this->isWithinBusinessHours($appointmentDate, $totalDuration)) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment time must be within business hours and duration must fit before closing time.'
            ]);
        }

        $this->enforceAppointmentLimits($appointmentDate, $validated['user_id'] ?? null);

        $appointment->update([
            'customer_id' => $validated['customer_id'],
            'user_id' => $validated['user_id'],
            'service_id' => $primaryServiceId,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'appointment_date' => $validated['appointment_date'],
        ]);

        // Sync pivot table
        $pivotData = [];
        foreach ($services as $service) {
            $pivotData[$service->id] = ['quantity' => 1, 'unit_price' => $service->price];
        }
        $appointment->services()->sync($pivotData);

        // Reload for fresh data
        $appointment->refresh();
        $appointment->load(['customer', 'user', 'service', 'services']);

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully.',
            'appointment' => $appointment->getCalendarEventData()
        ]);
    }

    /**
     * Create appointment via AJAX
     */
    public function createViaAjax(Request $request)
    {
        $this->authorize('create appointments');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $serviceIds = $validated['service_ids'];
        $primaryServiceId = $serviceIds[0];
        $services = Service::whereIn('id', $serviceIds)->get();
        $totalDuration = $services->sum('duration');
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts
        if ($this->hasConflict($validated['user_id'], $appointmentDate, $totalDuration)) {
             return response()->json([
                'success' => false,
                'message' => 'This staff member already has an appointment at this time, with potential overlap.'
            ]);
        }

        // Check business hours using the total duration of all selected services
        if (!$this->isWithinBusinessHours($appointmentDate, $totalDuration)) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment time must be within business hours and duration must fit before closing time.'
            ]);
        }

        $this->enforceAppointmentLimits($appointmentDate, $validated['user_id'] ?? null);

        $appointment = Appointment::create([
            'customer_id' => $validated['customer_id'],
            'user_id' => $validated['user_id'],
            'service_id' => $primaryServiceId,
            'appointment_date' => $validated['appointment_date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Sync pivot table
        $pivotData = [];
        foreach ($services as $service) {
            $pivotData[$service->id] = ['quantity' => 1, 'unit_price' => $service->price];
        }
        $appointment->services()->sync($pivotData);

        $appointment->load(['customer', 'user', 'service', 'services']);

        return response()->json([
            'success' => true,
            'message' => 'Appointment created successfully.',
            'appointment' => $appointment->getCalendarEventData()
        ]);
    }

    /**
     * Check if the appointment time is within business hours and the total
     * duration of all selected services fits before closing time.
     *
     * Business hours are sourced from BusinessHoursService, which reflects
     * the Weekly Schedule configured in Settings > Business — the same
     * schedule used for staff attendance, so there is a single source of
     * truth for "when are we open".
     */
    private function isWithinBusinessHours(Carbon $appointmentDate, int $durationMinutes = 60)
    {
        $businessHours = app(BusinessHoursService::class)->getWeeklyHoursForBooking();

        $dayOfWeek = strtolower($appointmentDate->format('l'));
        $appointmentTime = $appointmentDate->format('H:i');

        // Check if the business is closed that day
        if (isset($businessHours[$dayOfWeek]) && $businessHours[$dayOfWeek]['closed']) {
            return false;
        }

        // Get open and close times for the day
        $openTime = $businessHours[$dayOfWeek]['open'] ?? null;
        $closeTime = $businessHours[$dayOfWeek]['close'] ?? null;

        if (!$openTime || !$closeTime) {
            return false;
        }

        if ($appointmentTime < $openTime) {
            return false;
        }

        $appointmentEndTime = $appointmentDate->copy()->addMinutes($durationMinutes);
        $endTimeString = $appointmentEndTime->format('H:i');

        // Allow ending exactly at closing time
        return $endTimeString <= $closeTime;
    }

    /**
     * Check appointment capacity limits — enforces settings from the Settings page
     * Also validates the default_duration setting
     */
    private function enforceAppointmentLimits(Carbon $appointmentDate, ?int $userId = null, ?int $serviceId = null): void
    {
        $maxPerDay = (int) Setting::get('appointment.max_per_day', 20);
        $advanceDays = (int) Setting::get('appointment.advance_booking_days', 30);
        $minHours = (int) Setting::get('appointment.min_advance_hours', 2);
        $defaultDuration = (int) Setting::get('appointment.default_duration', 60);

        // Check advance booking limit
        $maxDate = now()->addDays($advanceDays);
        if ($appointmentDate->gt($maxDate)) {
            abort(422, __('Appointments can only be booked :days days in advance.', ['days' => $advanceDays]));
        }

        // Check minimum advance notice
        if ($appointmentDate->diffInHours(now(), true) < $minHours) {
            abort(422, __('Appointments must be booked at least :hours hours in advance.', ['hours' => $minHours]));
        }

        // Check max per day
        $count = Appointment::whereDate('appointment_date', $appointmentDate->toDateString());
        if ($userId) {
            $count->where('user_id', $userId);
        }
        if ($count->count() >= $maxPerDay) {
            abort(422, __('Maximum :max appointments allowed per day.', ['max' => $maxPerDay]));
        }
    }

    /**
     * Check for appointment conflicts
     */
    private function hasConflict($userId, Carbon $startDateTime, $duration, $excludeId = null)
    {
        $bufferTime = Setting::get('appointment.buffer_time', 15);

        $startWithBuffer = $startDateTime->copy()->subMinutes($bufferTime);
        $endWithBuffer = $startDateTime->copy()->addMinutes($duration + $bufferTime);

        // Pull candidate appointments for this staff member in a window wide
        // enough to catch any existing appointment that could still be running
        // (no appointment is expected to run longer than 24 hours).
        $query = Appointment::with('services', 'service')
            ->where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('appointment_date', [
                $startWithBuffer->copy()->subDay(),
                $endWithBuffer,
            ]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        foreach ($query->get() as $existing) {
            // Use the total duration across all of the existing appointment's
            // services (via the pivot table), not just its primary service —
            // otherwise multi-service appointments under-report their real end time.
            $existingDuration = $existing->services->sum(fn($s) => ($s->duration ?? 60) * ($s->pivot->quantity ?? 1))
                ?: ($existing->service->duration ?? 60);

            $existingStartWithBuffer = $existing->appointment_date->copy()->subMinutes($bufferTime);
            $existingEndWithBuffer = $existing->appointment_date->copy()->addMinutes($existingDuration + $bufferTime);

            if ($startWithBuffer->lt($existingEndWithBuffer) && $existingStartWithBuffer->lt($endWithBuffer)) {
                return true;
            }
        }

        return false;
    }
}