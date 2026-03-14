<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('manage leave balances');

        $staffMembers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['staff']);
        })->get();

        return view('leave-balances.index', compact('staffMembers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('manage leave balances');

        $staffMembers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['staff']);
        })->get();

        return view('leave-balances.create', compact('staffMembers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('manage leave balances');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer|min:2000|max:2100',
            'leave_type' => 'required|in:sick,vacation,personal,unpaid,emergency',
            'total_days' => 'required|integer|min:0|max:365'
        ]);

        // Check if balance already exists for this user, year, and leave type
        $existing = LeaveBalance::where('user_id', $request->user_id)
            ->where('year', $request->year)
            ->where('leave_type', $request->leave_type)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'Leave balance already exists for this user, year, and leave type.')
                ->withInput();
        }

        LeaveBalance::create([
            'user_id' => $request->user_id,
            'year' => $request->year,
            'leave_type' => $request->leave_type,
            'total_days' => $request->total_days,
            'used_days' => 0,
            'remaining_days' => $request->total_days
        ]);

        return redirect()->route('leave-balances.index')
            ->with('success', 'Leave balance created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $balance = LeaveBalance::with('user')->findOrFail($id);

        $this->authorize('view leave balances');

        return view('leave-balances.show', compact('balance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveBalance $leaveBalance)
    {
        $this->authorize('manage leave balances');

        return view('leave-balances.edit', compact('leaveBalance'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveBalance $leaveBalance)
    {
        $this->authorize('manage leave balances');

        $request->validate([
            'total_days' => 'required|integer|min:0|max:365',
            'used_days' => 'required|integer|min:0|max:' . $request->total_days
        ]);

        $leaveBalance->total_days = $request->total_days;
        $leaveBalance->used_days = $request->used_days;
        $leaveBalance->remaining_days = $request->total_days - $request->used_days;

        $leaveBalance->save();

        return redirect()->route('leave-balances.show', $leaveBalance->id)
            ->with('success', 'Leave balance updated successfully.');
    }

    /**
     * Adjust leave balance
     */
    public function adjust(Request $request, LeaveBalance $leaveBalance)
    {
        $this->authorize('manage leave balances');

        $request->validate([
            'adjustment' => 'required|integer', // Can be positive or negative
            'reason' => 'required|string|max:500'
        ]);

        $adjustment = (int)$request->adjustment;

        // Calculate new values
        $newTotalDays = $leaveBalance->total_days + $adjustment;
        $newRemainingDays = $leaveBalance->remaining_days + $adjustment;

        // Ensure we don't go below zero for remaining days
        if ($newRemainingDays < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Adjustment would result in negative remaining days.'
            ], 400);
        }

        $leaveBalance->total_days = $newTotalDays;
        $leaveBalance->remaining_days = $newRemainingDays;
        $leaveBalance->save();

        // Log the adjustment
        \Log::info("Leave balance adjusted for user {$leaveBalance->user_id}, type {$leaveBalance->leave_type}, year {$leaveBalance->year}. Adjustment: {$adjustment}. Reason: {$request->reason}");

        return response()->json([
            'success' => true,
            'message' => 'Leave balance adjusted successfully.',
            'balance' => $leaveBalance->refresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveBalance $leaveBalance)
    {
        $this->authorize('manage leave balances');

        $leaveBalance->delete();

        return redirect()->route('leave-balances.index')
            ->with('success', 'Leave balance deleted successfully.');
    }

    /**
     * Get leave balance for a user via AJAX
     */
    public function getUserBalance($userId)
    {
        $this->authorize('view leave balances');

        $user = User::findOrFail($userId);
        $balances = $user->leaveBalances()->where('year', now()->year)->get();

        return response()->json([
            'success' => true,
            'balances' => $balances
        ]);
    }
}