<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Setting;
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

        $query = Appointment::with(['customer', 'user', 'service'])
            ->orderBy('appointment_date', 'desc');

        // Apply filters
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->filled('staff') && $request->staff !== 'All') {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', $request->staff);
            });
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

        $customers = Customer::orderBy('first_name')->get();
        $services = Service::where('is_active', true)->whereHas('appointments')->get();
        $staff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['administrator', 'staff']);
        })->get();

        $businessHours = Setting::get('business.hours');

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

        $query = Appointment::with(['customer', 'user', 'service']);

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
        $staff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['administrator', 'staff']);
        })->get();

        return view('appointments.create', compact('customers', 'services', 'staff'));
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
            'appointment_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ], [
            'appointment_date.after_or_equal' => 'Appointment date must be today or in the future.',
        ]);

        $service = Service::find($validated['service_id']);
        $duration = $service->duration ?? 60;
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts
        if ($this->hasConflict($validated['user_id'], $appointmentDate, $duration)) {
             return redirect()->back()
                ->withInput()
                ->with('error', 'This staff member already has an appointment at this time, with potential overlap.');
        }

        // Check business hours
        if (!$this->isWithinBusinessHours($appointmentDate, $validated['service_id'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Appointment time must be within business hours and duration must fit before closing time.');
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
            return redirect()->route('appointments.show', $appointment->slug)
                ->with('error', 'This appointment cannot be modified.');
        }

        $customers = Customer::orderBy('first_name')->get();
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
            return redirect()->route('appointments.show', $appointment->slug)
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

        $service = Service::find($validated['service_id']);
        $duration = $service->duration ?? 60;
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts
        if ($this->hasConflict($validated['user_id'], $appointmentDate, $duration, $appointment->id)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This staff member already has an appointment at this time, with potential overlap.');
        }

        // Check business hours
        if (!$this->isWithinBusinessHours($appointmentDate, $validated['service_id'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Appointment time must be within business hours and duration must fit before closing time.');
        }

        $appointment->update($validated);

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
                'appointment' => $appointment->load(['customer', 'user', 'service'])
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
        $duration = $service->duration ?? 60;
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts
        if ($this->hasConflict($appointment->user_id, $appointmentDate, $duration, $appointment->id)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff member already has an appointment at this time, with potential overlap.'
            ]);
        }

        // Check business hours
        if (!$this->isWithinBusinessHours($appointmentDate, $appointment->service_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment time must be within business hours and duration must fit before closing time.'
            ]);
        }

        $appointment->update(['appointment_date' => $validated['appointment_date']]);
        
        // Reload for fresh data
        $appointment->refresh();
        $appointment->load(['customer', 'user', 'service']);

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
            'service_id' => 'required|exists:services,id',
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'appointment_date' => 'required|date'
        ]);

        $service = Service::find($validated['service_id']);
        $duration = $service->duration ?? 60;
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts
        if ($this->hasConflict($validated['user_id'], $appointmentDate, $duration, $appointment->id)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff member already has an appointment at this time, with potential overlap.'
            ]);
        }

        // Check business hours
        if (!$this->isWithinBusinessHours($appointmentDate, $validated['service_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment time must be within business hours and duration must fit before closing time.'
            ]);
        }

        $appointment->update($validated);
        
        // Reload for fresh data
        $appointment->refresh();
        $appointment->load(['customer', 'user', 'service']);

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
            'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service = Service::find($validated['service_id']);
        $duration = $service->duration ?? 60;
        $appointmentDate = Carbon::parse($validated['appointment_date']);

        // Check for conflicts
        if ($this->hasConflict($validated['user_id'], $appointmentDate, $duration)) {
             return response()->json([
                'success' => false,
                'message' => 'This staff member already has an appointment at this time, with potential overlap.'
            ]);
        }

        // Check business hours
        if (!$this->isWithinBusinessHours($appointmentDate, $validated['service_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment time must be within business hours and duration must fit before closing time.'
            ]);
        }

        $appointment = Appointment::create($validated);
        $appointment->load(['customer', 'user', 'service']);

        return response()->json([
            'success' => true,
            'message' => 'Appointment created successfully.',
            'appointment' => $appointment->getCalendarEventData()
        ]);
    }

    /**
     * Check if the appointment time is within business hours and duration fits before closing time
     */
    private function isWithinBusinessHours(Carbon $appointmentDate, $serviceId = null)
    {
        // Get business hours from settings
        $businessHours = Setting::get('business.hours');

        if (!$businessHours) {
            // Default hours
            $businessHours = [
                'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'saturday' => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
                'sunday' => ['open' => null, 'close' => null, 'closed' => true],
            ];
        }

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

        $service = Service::find($serviceId);
        $duration = $service ? $service->duration : 60;

        $appointmentEndTime = $appointmentDate->copy()->addMinutes($duration);
        $endTimeString = $appointmentEndTime->format('H:i');

        // Allow ending exactly at closing time
        return $endTimeString <= $closeTime;
    }

    /**
     * Check for appointment conflicts
     */
    private function hasConflict($userId, Carbon $startDateTime, $duration, $excludeId = null)
    {
        $bufferTime = Setting::get('appointment.buffer_time', 15);
        
        $endDateTime = $startDateTime->copy()->addMinutes($duration);
        
        // Add buffer
        $startWithBuffer = $startDateTime->copy()->subMinutes($bufferTime);
        $endWithBuffer = $endDateTime->copy()->addMinutes($bufferTime);

        $query = Appointment::where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startWithBuffer, $endWithBuffer, $bufferTime) {
                // Check if any existing appointment overlaps with the new one
                $q->whereBetween('appointment_date', [$startWithBuffer, $endWithBuffer])
                  ->orWhere(function ($sub) use ($startWithBuffer, $bufferTime) {
                      // Check for appointments that start before the new one but end after it starts
                      // We need to calculate the end time of existing appointments dynamically
                      $sub->where('appointment_date', '<=', $startWithBuffer)
                          ->whereRaw('DATE_ADD(appointment_date, INTERVAL COALESCE((SELECT duration FROM services WHERE id = appointments.service_id), 60) + ? MINUTE) > ?', [$bufferTime * 2, $startWithBuffer]);
                  });
            });


        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}