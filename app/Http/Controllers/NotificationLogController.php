<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationLogController extends Controller
{
    /**
     * The notification service.
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create a new controller instance.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
        $this->middleware('can:manage system');
    }

    /**
     * Display a listing of notification logs.
     */
    public function index(Request $request)
    {
        $query = NotificationLog::with('notification');

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by channel
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by provider
        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by recipient
        if ($request->has('recipient')) {
            $query->where('recipient', 'like', "%{$request->recipient}%");
        }

        // Search by subject or content
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('recipient', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);

        // Get log statistics
        $stats = $this->getLogStatistics($request);

        // Get filter options
        $filterOptions = [
            'statuses' => NotificationLog::distinct('status')->pluck('status')->filter(),
            'channels' => NotificationLog::distinct('channel')->pluck('channel')->filter(),
            'providers' => NotificationLog::distinct('provider')->pluck('provider')->filter(),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $logs,
                'stats' => $stats,
                'filter_options' => $filterOptions,
            ]);
        }

        return view('notifications.logs.index', compact('logs', 'stats', 'filterOptions'));
    }

    /**
     * Display the specified notification log.
     */
    public function show(string $id)
    {
        $log = NotificationLog::with(['notification.notifiable'])->findOrFail($id);

        // Get related logs for the same notification
        $relatedLogs = NotificationLog::where('notification_id', $log->notification_id)
            ->where('id', '!=', $log->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get delivery attempts timeline
        $timeline = $this->getDeliveryTimeline($log);

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $log,
                'related_logs' => $relatedLogs,
                'timeline' => $timeline,
            ]);
        }

        return view('notifications.logs.show', compact('log', 'relatedLogs', 'timeline'));
    }

    /**
     * Retry a failed notification log.
     */
    public function retry(string $id)
    {
        $log = NotificationLog::findOrFail($id);

        if (!$log->canRetry()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Log cannot be retried. Either it is not failed or retry limit reached.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Log cannot be retried. Either it is not failed or retry limit reached.');
        }

        try {
            // Get the notification
            $notification = $log->notification;
            
            if (!$notification) {
                throw new \Exception('Associated notification not found.');
            }

            // Retry the notification via the service
            $result = $this->notificationService->retryFailed($notification->uuid);

            if ($result) {
                $log->incrementRetry();
                
                if (request()->expectsJson()) {
                    return response()->json([
                        'message' => 'Retry initiated successfully',
                        'success' => true,
                    ]);
                }
                
                return redirect()->back()->with('success', 'Retry initiated successfully');
            } else {
                throw new \Exception('Retry failed.');
            }
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Retry failed: ' . $e->getMessage(),
                    'success' => false,
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Retry failed: ' . $e->getMessage());
        }
    }

    /**
     * Export notification logs.
     */
    public function export(Request $request)
    {
        $query = NotificationLog::with('notification');

        // Apply filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $format = $request->get('format', 'csv');
        
        if ($format === 'csv') {
            return $this->exportToCsv($logs);
        } elseif ($format === 'json') {
            return $this->exportToJson($logs);
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unsupported export format',
                ], 422);
            }
            return redirect()->back()->with('error', 'Unsupported export format');
        }
    }

    /**
     * Get notification log statistics.
     */
    private function getLogStatistics(Request $request)
    {
        $query = NotificationLog::query();

        // Apply same filters as index
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        $total = $query->count();
        
        $statusCounts = NotificationLog::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $channelCounts = NotificationLog::select('channel', DB::raw('count(*) as count'))
            ->groupBy('channel')
            ->get()
            ->pluck('count', 'channel')
            ->toArray();

        $dailyStats = NotificationLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "sent" then 1 else 0 end) as sent'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed'),
                DB::raw('sum(case when status = "delivered" then 1 else 0 end) as delivered')
            )
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return [
            'total' => $total,
            'status_counts' => $statusCounts,
            'channel_counts' => $channelCounts,
            'daily_stats' => $dailyStats,
            'success_rate' => $total > 0 ? round((($statusCounts['sent'] ?? 0) + ($statusCounts['delivered'] ?? 0)) / $total * 100, 2) : 0,
        ];
    }

    /**
     * Get delivery timeline for a log.
     */
    private function getDeliveryTimeline(NotificationLog $log)
    {
        $timeline = [];

        // Created
        $timeline[] = [
            'time' => $log->created_at,
            'event' => 'Log Created',
            'description' => 'Notification log entry created',
            'status' => 'info',
        ];

        // Sent
        if ($log->sent_at) {
            $timeline[] = [
                'time' => $log->sent_at,
                'event' => 'Sent to Provider',
                'description' => 'Notification sent to ' . ($log->provider ?: $log->channel) . ' provider',
                'status' => 'success',
            ];
        }

        // Delivered
        if ($log->delivered_at) {
            $timeline[] = [
                'time' => $log->delivered_at,
                'event' => 'Delivered',
                'description' => 'Notification delivered to recipient',
                'status' => 'success',
            ];
        }

        // Failed
        if ($log->status === 'failed' && $log->error_message) {
            $timeline[] = [
                'time' => $log->updated_at,
                'event' => 'Failed',
                'description' => 'Delivery failed: ' . $log->error_message,
                'status' => 'danger',
            ];
        }

        // Retries
        if ($log->retry_count > 0) {
            $timeline[] = [
                'time' => $log->updated_at,
                'event' => 'Retry Attempts',
                'description' => $log->retry_count . ' retry attempt(s) made',
                'status' => 'warning',
            ];
        }

        // Sort by time
        usort($timeline, function ($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        return $timeline;
    }

    /**
     * Export logs to CSV.
     */
    private function exportToCsv($logs)
    {
        $filename = 'notification-logs-' . date('Y-m-d-H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Notification ID',
                'Channel',
                'Provider',
                'Recipient',
                'Subject',
                'Status',
                'Error Message',
                'Sent At',
                'Delivered At',
                'Retry Count',
                'Created At',
            ]);

            // Add data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->notification_id,
                    $log->channel,
                    $log->provider,
                    $log->recipient,
                    $log->subject,
                    $log->status,
                    $log->error_message,
                    $log->sent_at ? $log->sent_at->format('Y-m-d H:i:s') : '',
                    $log->delivered_at ? $log->delivered_at->format('Y-m-d H:i:s') : '',
                    $log->retry_count,
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export logs to JSON.
     */
    private function exportToJson($logs)
    {
        $filename = 'notification-logs-' . date('Y-m-d-H-i-s') . '.json';
        
        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'notification_id' => $log->notification_id,
                'channel' => $log->channel,
                'provider' => $log->provider,
                'recipient' => $log->recipient,
                'subject' => $log->subject,
                'status' => $log->status,
                'error_message' => $log->error_message,
                'sent_at' => $log->sent_at ? $log->sent_at->format('Y-m-d H:i:s') : null,
                'delivered_at' => $log->delivered_at ? $log->delivered_at->format('Y-m-d H:i:s') : null,
                'retry_count' => $log->retry_count,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'metadata' => $log->metadata,
            ];
        });

        return response()->json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Bulk retry failed logs.
     */
    public function bulkRetry(Request $request)
    {
        $request->validate([
            'log_ids' => 'required|array',
            'log_ids.*' => 'integer|exists:notification_logs,id',
        ]);

        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($request->log_ids as $logId) {
            try {
                $log = NotificationLog::find($logId);
                
                if ($log && $log->canRetry()) {
                    $notification = $log->notification;
                    
                    if ($notification) {
                        $result = $this->notificationService->retryFailed($notification->uuid);
                        
                        if ($result) {
                            $log->incrementRetry();
                            $successCount++;
                            $results[] = [
                                'id' => $logId,
                                'success' => true,
                                'message' => 'Retry initiated',
                            ];
                        } else {
                            $failedCount++;
                            $results[] = [
                                'id' => $logId,
                                'success' => false,
                                'message' => 'Retry failed',
                            ];
                        }
                    } else {
                        $failedCount++;
                        $results[] = [
                            'id' => $logId,
                            'success' => false,
                            'message' => 'Notification not found',
                        ];
                    }
                } else {
                    $failedCount++;
                    $results[] = [
                        'id' => $logId,
                        'success' => false,
                        'message' => 'Cannot retry this log',
                    ];
                }
            } catch (\Exception $e) {
                $failedCount++;
                $results[] = [
                    'id' => $logId,
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Bulk retry completed. Success: {$successCount}, Failed: {$failedCount}",
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'results' => $results,
            ]);
        }

        return redirect()->back()->with(
            $successCount > 0 ? 'success' : 'warning',
            "Bulk retry completed. Success: {$successCount}, Failed: {$failedCount}"
        );
    }
}
