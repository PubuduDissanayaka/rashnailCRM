<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\SupplyUsageLog;
use App\Models\Supply;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

class UsageLogController extends Controller
{
    /**
     * Display a listing of the usage logs.
     */
    public function index(Request $request)
    {
        // Check permission
        if (!Gate::allows('inventory.view')) {
            abort(403, 'You do not have permission to view usage logs.');
        }

        if ($request->ajax()) {
            $query = SupplyUsageLog::with(['supply', 'appointment', 'service', 'user', 'customer'])
                ->latest();

            // Apply filters
            if ($request->has('supply_id') && $request->supply_id) {
                $query->where('supply_id', $request->supply_id);
            }

            if ($request->has('appointment_id') && $request->appointment_id) {
                $query->where('appointment_id', $request->appointment_id);
            }

            if ($request->has('service_id') && $request->service_id) {
                $query->where('service_id', $request->service_id);
            }

            if ($request->has('used_by') && $request->used_by) {
                $query->where('used_by', $request->used_by);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('used_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('used_at', '<=', $request->date_to);
            }

            return DataTables::eloquent($query)
                ->addColumn('supply_name', function ($log) {
                    return $log->supply->name ?? 'N/A';
                })
                ->addColumn('appointment_reference', function ($log) {
                    if ($log->appointment) {
                        return '<a href="' . route('appointments.show', $log->appointment) . '">' . $log->appointment->slug . '</a>';
                    }
                    return 'N/A';
                })
                ->addColumn('service_name', function ($log) {
                    return $log->service->name ?? 'N/A';
                })
                ->addColumn('staff_name', function ($log) {
                    return $log->user->name ?? 'N/A';
                })
                ->addColumn('customer_name', function ($log) {
                    return $log->customer->name ?? 'N/A';
                })
                ->addColumn('total_cost_formatted', function ($log) {
                    return '$' . number_format($log->total_cost, 2);
                })
                ->addColumn('used_at_formatted', function ($log) {
                    return $log->used_at->format('M d, Y h:i A');
                })
                ->addColumn('actions', function ($log) {
                    $actions = '<a href="' . route('inventory.usage-logs.show', $log) . '" class="btn btn-sm btn-info">View</a>';
                    return $actions;
                })
                ->rawColumns(['appointment_reference', 'actions'])
                ->toJson();
        }

        // Get filter data
        $supplies = Supply::active()->orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $staff = User::whereHas('roles', function ($q) {
            $q->where('name', 'staff');
        })->orderBy('name')->get();

        return view('inventory.usage-logs.index', compact('supplies', 'services', 'staff'));
    }

    /**
     * Show the form for creating a new usage log.
     */
    public function create()
    {
        // Check permission
        if (!Gate::allows('inventory.usage.create')) {
            abort(403, 'You do not have permission to create usage logs.');
        }

        $supplies = Supply::active()->orderBy('name')->get();
        $appointments = Appointment::where('status', 'completed')
            ->whereDoesntHave('supplyUsageLogs')
            ->orderBy('appointment_date', 'desc')
            ->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $staff = User::whereHas('roles', function ($q) {
            $q->where('name', 'staff');
        })->orderBy('name')->get();
        $customers = User::whereHas('roles', function ($q) {
            $q->where('name', 'customer');
        })->orderBy('name')->get();

        return view('inventory.usage-logs.create', compact('supplies', 'appointments', 'services', 'staff', 'customers'));
    }

    /**
     * Store a newly created usage log in storage.
     */
    public function store(Request $request)
    {
        // Check permission
        if (!Gate::allows('inventory.usage.create')) {
            abort(403, 'You do not have permission to create usage logs.');
        }

        $validated = $request->validate([
            'supply_id' => 'required|exists:supplies,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'service_id' => 'nullable|exists:services,id',
            'quantity_used' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'used_by' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:users,id',
            'batch_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'used_at' => 'nullable|date',
        ]);

        // Get supply to calculate costs if not provided
        $supply = Supply::findOrFail($validated['supply_id']);
        
        // Use supply's current unit cost if not provided
        if (!isset($validated['unit_cost']) || $validated['unit_cost'] == 0) {
            $validated['unit_cost'] = $supply->unit_cost;
        }
        
        // Calculate total cost
        $validated['total_cost'] = $validated['quantity_used'] * $validated['unit_cost'];
        
        // Set used_at to now if not provided
        if (!isset($validated['used_at'])) {
            $validated['used_at'] = now();
        }

        DB::beginTransaction();
        try {
            // Create usage log
            $usageLog = SupplyUsageLog::create($validated);
            
            // Deduct stock from supply
            $supply->removeStock(
                $validated['quantity_used'],
                $usageLog,
                'Manual usage log #' . $usageLog->id . ($validated['notes'] ? ': ' . $validated['notes'] : '')
            );
            
            DB::commit();
            
            return redirect()->route('inventory.usage-logs.index')
                ->with('success', 'Usage log created successfully and stock deducted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create usage log: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified usage log.
     */
    public function show(SupplyUsageLog $usageLog)
    {
        // Check permission
        if (!Gate::allows('inventory.view')) {
            abort(403, 'You do not have permission to view usage logs.');
        }

        $usageLog->load(['supply', 'appointment', 'service', 'user', 'customer']);
        
        return view('inventory.usage-logs.show', compact('usageLog'));
    }

    /**
     * Get usage logs for a specific appointment (for AJAX requests).
     */
    public function getAppointmentUsageLogs($appointmentId)
    {
        $logs = SupplyUsageLog::with(['supply', 'service'])
            ->where('appointment_id', $appointmentId)
            ->get();
            
        return response()->json($logs);
    }
}