<?php

namespace App\Http\Controllers;

use App\Models\WorkSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view work schedules');

        $staffMembers = User::withStaffRole()->with('workSchedules')->get();

        $stats = [
            'total_staff' => $staffMembers->count(),
            'with_schedules' => $staffMembers->filter(fn($u) => $u->workSchedules->where('is_working_day', true)->count() > 0)->count(),
            'total_entries' => WorkSchedule::count(),
            'working_days' => WorkSchedule::where('is_working_day', true)->count(),
        ];

        return view('schedules.index', compact('staffMembers', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('manage work schedules');

        $staffMembers = User::withStaffRole()->get();

        return view('schedules.create', compact('staffMembers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('manage work schedules');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'days' => 'required|array|min:1',
            'days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'grace_period_minutes' => 'nullable|integer|min:0|max:60',
            'is_working_day' => 'required|boolean'
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($validated['days'] as $day) {
            // Check if schedule already exists for this staff member and day
            $existing = WorkSchedule::where('user_id', $validated['user_id'])
                ->where('day_of_week', $day)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            WorkSchedule::create([
                'user_id' => $validated['user_id'],
                'day_of_week' => $day,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'grace_period_minutes' => $validated['grace_period_minutes'] ?? 15,
                'is_working_day' => $validated['is_working_day']
            ]);
            $created++;
        }

        $msg = "{$created} schedule(s) created.";
        if ($skipped > 0) $msg .= " {$skipped} skipped (already exist).";

        return redirect()->route('schedules.index')
            ->with('success', $msg);
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkSchedule $workSchedule)
    {
        $this->authorize('view work schedules');

        $schedule = $workSchedule->load('user');

        return view('schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WorkSchedule $schedule)
    {
        $this->authorize('manage work schedules');

        if (!$schedule->exists) {
            return redirect()->route('schedules.index')
                ->with('error', 'Schedule not found.');
        }

        $staffMembers = User::withStaffRole()->get();

        return view('schedules.edit', compact('schedule', 'staffMembers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkSchedule $schedule)
    {
        $this->authorize('manage work schedules');

        if (!$schedule->exists) {
            return redirect()->route('schedules.index')
                ->with('error', 'Schedule not found.');
        }

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

        if (!$schedule->exists) {
            return redirect()->route('schedules.index')
                ->with('error', 'Schedule not found.');
        }

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

    /**
     * Quick toggle a schedule on/off (set is_working_day or swap times)
     */
    public function quickToggle(Request $request, WorkSchedule $schedule)
    {
        $this->authorize('manage work schedules');

        if (!$schedule->exists) {
            return response()->json(['success' => false, 'message' => 'Schedule not found.'], 404);
        }

        $validated = $request->validate([
            'is_working_day' => 'required|boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $updateData = ['is_working_day' => $validated['is_working_day']];
        if ($validated['is_working_day']) {
            $updateData['start_time'] = $validated['start_time'] ?? $schedule->start_time;
            $updateData['end_time'] = $validated['end_time'] ?? $schedule->end_time;
        }

        $schedule->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated.',
            'schedule' => $schedule->fresh()
        ]);
    }

    /**
     * Copy week schedule from one staff to another
     */
    public function copyWeek(Request $request)
    {
        $this->authorize('manage work schedules');

        $validated = $request->validate([
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id',
        ]);

        if ($validated['from_user_id'] == $validated['to_user_id']) {
            return response()->json(['success' => false, 'message' => 'Source and target must be different.'], 422);
        }

        $sourceSchedules = WorkSchedule::where('user_id', $validated['from_user_id'])->get();

        DB::beginTransaction();
        try {
            WorkSchedule::where('user_id', $validated['to_user_id'])->delete();

            foreach ($sourceSchedules as $sched) {
                WorkSchedule::create([
                    'user_id' => $validated['to_user_id'],
                    'day_of_week' => $sched->day_of_week,
                    'start_time' => $sched->start_time,
                    'end_time' => $sched->end_time,
                    'grace_period_minutes' => $sched->grace_period_minutes,
                    'is_working_day' => $sched->is_working_day,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Week schedule copied.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Copy failed: ' . $e->getMessage()], 500);
        }
    }
}