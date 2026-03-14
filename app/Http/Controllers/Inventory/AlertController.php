<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\SupplyAlert;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlertController extends Controller
{
    /**
     * Display a listing of the alerts.
     */
    public function index(Request $request, AlertService $alertService)
    {
        $this->authorize('inventory.alerts.manage');

        $query = SupplyAlert::with(['supply', 'resolver'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('alert_type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            if ($request->status === 'resolved') {
                $query->where('is_resolved', true);
            } elseif ($request->status === 'unresolved') {
                $query->where('is_resolved', false);
            }
        }

        if ($request->filled('supply_id')) {
            $query->where('supply_id', $request->supply_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $alerts = $query->paginate(20);

        // Get statistics
        $stats = $alertService->getAlertStatistics();

        // Get filter options
        $alertTypes = SupplyAlert::select('alert_type')
            ->distinct()
            ->pluck('alert_type')
            ->mapWithKeys(fn($type) => [$type => ucfirst(str_replace('_', ' ', $type))])
            ->toArray();

        $severities = SupplyAlert::select('severity')
            ->distinct()
            ->pluck('severity')
            ->mapWithKeys(fn($severity) => [$severity => ucfirst($severity)])
            ->toArray();

        return view('inventory.alerts.index', compact(
            'alerts',
            'stats',
            'alertTypes',
            'severities'
        ));
    }

    /**
     * Display the specified alert.
     */
    public function show(SupplyAlert $alert)
    {
        $this->authorize('inventory.alerts.manage');

        $alert->load(['supply', 'resolver', 'supply.category']);

        // Get related alerts for the same supply
        $relatedAlerts = SupplyAlert::where('supply_id', $alert->supply_id)
            ->where('id', '!=', $alert->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('inventory.alerts.show', compact('alert', 'relatedAlerts'));
    }

    /**
     * Resolve the specified alert.
     */
    public function resolve(Request $request, SupplyAlert $alert, AlertService $alertService)
    {
        $this->authorize('inventory.alerts.manage');

        $request->validate([
            'resolution_notes' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();
        $success = $alertService->resolveAlert($alert, $userId);

        if ($success) {
            if ($request->filled('resolution_notes')) {
                // Store resolution notes in metadata or separate table
                // For now, we'll update the alert message
                $alert->update([
                    'message' => $alert->message . ' [Resolved: ' . $request->resolution_notes . ']',
                ]);
            }

            return redirect()->route('inventory.alerts.index')
                ->with('success', 'Alert resolved successfully.');
        }

        return redirect()->back()
            ->with('error', 'Failed to resolve alert. Please try again.');
    }

    /**
     * Bulk resolve alerts.
     */
    public function bulkResolve(Request $request, AlertService $alertService)
    {
        $this->authorize('inventory.alerts.manage');

        $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'exists:supply_alerts,id',
            'resolution_notes' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();
        $results = $alertService->bulkResolveAlerts($request->alert_ids, $userId);

        if ($results['success'] > 0) {
            $message = "Successfully resolved {$results['success']} alert(s).";
            
            if ($results['failed'] > 0) {
                $message .= " Failed to resolve {$results['failed']} alert(s).";
            }

            return redirect()->route('inventory.alerts.index')
                ->with('success', $message);
        }

        return redirect()->back()
            ->with('error', 'Failed to resolve any alerts. Please try again.');
    }

    /**
     * Get alert statistics for dashboard.
     */
    public function statistics(AlertService $alertService)
    {
        $this->authorize('inventory.view');

        $stats = $alertService->getAlertStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get recent alerts for dashboard.
     */
    public function recent()
    {
        $this->authorize('inventory.view');

        $recentAlerts = SupplyAlert::with(['supply'])
            ->where('is_resolved', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->alert_type,
                    'severity' => $alert->severity,
                    'message' => $alert->message,
                    'supply_name' => $alert->supply->name ?? 'Unknown',
                    'created_at' => $alert->created_at->diffForHumans(),
                    'is_critical' => $alert->isCritical(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $recentAlerts,
        ]);
    }

    /**
     * Export alerts to CSV.
     */
    public function export(Request $request)
    {
        $this->authorize('inventory.alerts.manage');

        $query = SupplyAlert::with(['supply', 'resolver'])
            ->orderBy('created_at', 'desc');

        // Apply filters same as index
        if ($request->filled('type')) {
            $query->where('alert_type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            if ($request->status === 'resolved') {
                $query->where('is_resolved', true);
            } elseif ($request->status === 'unresolved') {
                $query->where('is_resolved', false);
            }
        }

        $alerts = $query->get();

        $filename = 'alerts-export-' . date('Y-m-d-H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($alerts) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Alert Type',
                'Severity',
                'Message',
                'Supply',
                'Current Stock',
                'Min Stock Level',
                'Expiry Date',
                'Status',
                'Resolved By',
                'Resolved At',
                'Created At',
            ]);

            // Add data rows
            foreach ($alerts as $alert) {
                fputcsv($file, [
                    $alert->id,
                    $alert->alert_type,
                    $alert->severity,
                    $alert->message,
                    $alert->supply->name ?? 'Unknown',
                    $alert->current_stock,
                    $alert->min_stock_level,
                    $alert->expiry_date ? $alert->expiry_date->format('Y-m-d') : '',
                    $alert->is_resolved ? 'Resolved' : 'Unresolved',
                    $alert->resolver->name ?? '',
                    $alert->resolved_at ? $alert->resolved_at->format('Y-m-d H:i:s') : '',
                    $alert->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}