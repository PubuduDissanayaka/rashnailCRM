<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\LeaveBalance;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('view leave requests');

        $query = LeaveRequest::with(['user', 'approver']);

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('end_date', '<=', $request->end_date);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get staff members for filter dropdown
        $staffMembers = User::whereHas('roles', function ($q) {
            $q->where('name', 'staff');
        })->get();

        return view('leaves.index', compact('leaveRequests', 'staffMembers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only authorized users can create leave requests
        $this->authorize('create leave requests');

        $user = auth()->user();
        
        // Get user's leave balances
        $currentYear = now()->year;
        $leaveBalances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $currentYear)
            ->get();

        return view('leaves.create', compact('leaveBalances'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'leave_type' => 'required|in:sick,vacation,personal,unpaid,emergency',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $daysCount = LeaveRequest::calculateDaysCount($startDate, $endDate);

        // Check if user has sufficient leave balance for paid leaves
        if (!in_array($request->leave_type, ['sick', 'unpaid', 'emergency'])) {
            $leaveBalance = LeaveBalance::where('user_id', $user->id)
                ->where('year', $startDate->year)
                ->where('leave_type', $request->leave_type)
                ->first();

            if (!$leaveBalance || $leaveBalance->remaining_days < $daysCount) {
                return redirect()->back()
                    ->with('error', 'Insufficient leave balance. You have ' . ($leaveBalance ? $leaveBalance->remaining_days : 0) . ' days remaining.')
                    ->withInput();
            }
        }

        // Check for overlapping leave requests
        $overlappingRequest = LeaveRequest::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->first();

        if ($overlappingRequest) {
            return redirect()->back()
                ->with('error', 'You already have a leave request overlapping with these dates.')
                ->withInput();
        }

        // Create the leave request
        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type' => $request->leave_type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $daysCount,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('leaves.my-requests')
            ->with('success', 'Leave request submitted successfully. Awaiting approval.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        // Authorize that the user can view this leave request
        if ($leaveRequest->user_id != auth()->id() && !auth()->user()->can('view leave requests')) {
            abort(403);
        }

        return view('leaves.show', compact('leaveRequest'));
    }

    /**
     * Show form for approval/rejection
     */
    public function showApproval(LeaveRequest $leaveRequest)
    {
        $this->authorize('approve leave requests');

        return view('leaves.approval', compact('leaveRequest'));
    }

    /**
     * Approve the leave request
     */
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('approve leave requests');

        $request->validate([
            'notes' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            // Check if leave balance exists and is sufficient
            $leaveBalance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('year', $leaveRequest->start_date->year)
                ->where('leave_type', $leaveRequest->leave_type)
                ->first();

            if ($leaveBalance && !in_array($leaveRequest->leave_type, ['unpaid', 'emergency'])) {
                if ($leaveBalance->remaining_days < $leaveRequest->days_count) {
                    throw new \Exception('Insufficient leave balance to approve this request.');
                }

                // Deduct days from balance
                $leaveBalance->used_days += $leaveRequest->days_count;
                $leaveBalance->remaining_days -= $leaveRequest->days_count;
                $leaveBalance->save();
            }

            $leaveRequest->approve(auth()->id(), $request->notes);

            DB::commit();

            return redirect()->route('leaves.index')
                ->with('success', 'Leave request approved successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to approve leave request: ' . $e->getMessage());
        }
    }

    /**
     * Reject the leave request
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('approve leave requests');

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $leaveRequest->reject(auth()->id(), $request->rejection_reason);

        return redirect()->route('leaves.index')
            ->with('success', 'Leave request rejected successfully.');
    }

    /**
     * Cancel a leave request (before approval)
     */
    public function cancel(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->user_id !== auth()->id()) {
            abort(403, 'Unauthorized to cancel this leave request.');
        }

        if ($leaveRequest->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Cannot cancel leave request that is not pending.');
        }

        if ($leaveRequest->start_date->isPast()) {
            return redirect()->back()
                ->with('error', 'Cannot cancel leave request that has already started.');
        }

        $leaveRequest->status = 'cancelled';
        $leaveRequest->save();

        return redirect()->route('leaves.my-requests')
            ->with('success', 'Leave request cancelled successfully.');
    }

    /**
     * Display user's leave requests
     */
    public function myRequests()
    {
        $user = auth()->user();
        $leaveRequests = LeaveRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('leaves.my-requests', compact('leaveRequests'));
    }

    /**
     * Display calendar view of leave requests
     */
    public function calendar()
    {
        $this->authorize('view leave requests');

        $leaveRequests = LeaveRequest::with('user')
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_date', '<=', now()->addMonths(3))
            ->where('end_date', '>=', now()->subMonths(1))
            ->get();

        return view('leaves.calendar', compact('leaveRequests'));
    }
}