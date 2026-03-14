<?php

namespace App\Http\Controllers;

use App\Models\WorkSchedule;
use App\Models\User;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('manage work schedules');

        $staffMembers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['staff', 'administrator']);
        })->get();

        return view('schedules.index', compact('staffMembers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('manage work schedules');

        $staffMembers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['staff', 'administrator']);
        })->get();

        return view('schedules.create', compact('staffMembers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('manage work schedules');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'grace_period_minutes' => 'nullable|integer|min:0|max:60',
            'is_working_day' => 'required|boolean'
        ]);

        // Check if schedule already exists for this staff member and day
        $existing = WorkSchedule::where('user_id', $request->user_id)
            ->where('day_of_week', $request->day_of_week)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'Work schedule already exists for this staff member and day.')
                ->withInput();
        }

        WorkSchedule::create([
            'user_id' => $request->user_id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'grace_period_minutes' => $request->grace_period_minutes ?? 15,
            'is_working_day' => $request->is_working_day
        ]);

        return redirect()->route('schedules.index')
            ->with('success', 'Work schedule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $schedule = WorkSchedule::with('user')->findOrFail($id);
        
        $this->authorize('view work schedules');
        
        return view('schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WorkSchedule $schedule)
    {
        $this->authorize('manage work schedules');

        $staffMembers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['staff', 'administrator']);
        })->get();

        return view('schedules.edit', compact('schedule', 'staffMembers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkSchedule $schedule)
    {
        $this->authorize('manage work schedules');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'grace_period_minutes' => 'nullable|integer|min:0|max:60',
            'is_working_day' => 'required|boolean'
        ]);

        // Check if schedule already exists for this staff member and day (excluding current record)
        $existing = WorkSchedule::where('user_id', $request->user_id)
            ->where('day_of_week', $request->day_of_week)
            ->where('id', '!=', $schedule->id)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'Work schedule already exists for this staff member and day.')
                ->withInput();
        }

        $schedule->update([
            'user_id' => $request->user_id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'grace_period_minutes' => $request->grace_period_minutes ?? 15,
            'is_working_day' => $request->is_working_day
        ]);

        return redirect()->route('schedules.index')
            ->with('success', 'Work schedule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkSchedule $schedule)
    {
        $this->authorize('manage work schedules');

        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Work schedule deleted successfully.');
    }

    /**
     * Get work schedule for a staff member via AJAX
     */
    public function getSchedule($userId)
    {
        $this->authorize('view work schedules');

        $user = User::findOrFail($userId);
        $schedules = $user->workSchedules()->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")->get();

        return response()->json([
            'success' => true,
            'schedules' => $schedules,
            'user' => $user
        ]);
    }

    /**
     * Bulk update work schedules for a user
     */
    public function bulkUpdate(Request $request, $userId)
    {
        $this->authorize('manage work schedules');

        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.start_time' => 'required_if:schedules.*.is_working_day,true|date_format:H:i',
            'schedules.*.end_time' => 'required_if:schedules.*.is_working_day,true|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.grace_period_minutes' => 'nullable|integer|min:0|max:60',
            'schedules.*.is_working_day' => 'boolean'
        ]);

        $userId = (int)$userId;
        $user = User::findOrFail($userId);

        DB::beginTransaction();

        try {
            // Delete existing schedules for this user
            $user->workSchedules()->delete();

            // Create new schedules
            foreach ($request->schedules as $scheduleData) {
                WorkSchedule::create([
                    'user_id' => $userId,
                    'day_of_week' => $scheduleData['day_of_week'],
                    'start_time' => $scheduleData['is_working_day'] ? $scheduleData['start_time'] : null,
                    'end_time' => $scheduleData['is_working_day'] ? $scheduleData['end_time'] : null,
                    'grace_period_minutes' => $scheduleData['grace_period_minutes'] ?? 15,
                    'is_working_day' => $scheduleData['is_working_day'] ?? false
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work schedules updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update work schedules: ' . $e->getMessage()
            ], 500);
        }
    }
}