<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Services\BroadcastService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BroadcastController extends Controller
{
    /**
     * The broadcast service.
     *
     * @var BroadcastService
     */
    protected $broadcastService;

    /**
     * The notification service.
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create a new controller instance.
     *
     * @param BroadcastService $broadcastService
     * @param NotificationService $notificationService
     */
    public function __construct(BroadcastService $broadcastService, NotificationService $notificationService)
    {
        $this->broadcastService = $broadcastService;
        $this->notificationService = $notificationService;
        $this->middleware('auth');
        $this->middleware('can:manage system');
    }

    /**
     * Display a listing of broadcast notifications.
     */
    public function index(Request $request)
    {
        $query = Notification::where('type', 'like', 'broadcast_%');

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

        // Search by subject or data
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('data->subject', 'like', "%{$search}%")
                  ->orWhere('data->message', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        $perPage = $request->get('per_page', 20);
        $broadcasts = $query->paginate($perPage);

        // Get broadcast statistics
        $stats = $this->getBroadcastStatistics($request);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $broadcasts,
                'stats' => $stats,
            ]);
        }

        return view('notifications.broadcasts.index', compact('broadcasts', 'stats'));
    }

    /**
     * Show the form for creating a new broadcast notification.
     */
    public function create()
    {
        $templates = EmailTemplate::active()->orderBy('name')->get();
        $userGroups = $this->getUserGroups();
        $channels = $this->getAvailableChannels();

        return view('notifications.broadcasts.create', compact('templates', 'userGroups', 'channels'));
    }

    /**
     * Store a newly created broadcast notification.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'recipient_type' => 'required|in:all_users,selected_users,by_role,by_department',
            'recipients' => 'nullable|array',
            'recipients.*' => 'integer|exists:users,id',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
            'departments' => 'nullable|array',
            'departments.*' => 'string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,in_app,sms',
            'template_id' => 'nullable|exists:email_templates,id',
            'template_variables' => 'nullable|array',
            'schedule_type' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'nullable|date|after:now',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        try {
            // Prepare broadcast data
            $broadcastData = [
                'subject' => $request->subject,
                'message' => $request->message,
                'recipient_type' => $request->recipient_type,
                'recipients' => $request->recipients,
                'roles' => $request->roles,
                'departments' => $request->departments,
                'channels' => $request->channels,
                'template_id' => $request->template_id,
                'template_variables' => $request->template_variables,
                'schedule_type' => $request->schedule_type,
                'scheduled_at' => $request->scheduled_at,
                'priority' => $request->priority ?? 'normal',
                'created_by' => Auth::id(),
            ];

            // Create broadcast
            $broadcast = $this->broadcastService->createBroadcast($broadcastData);

            if ($request->schedule_type === 'immediate') {
                // Send immediately
                $result = $this->broadcastService->sendBroadcast($broadcast->id);
                
                if ($result['success']) {
                    $message = 'Broadcast sent successfully to ' . $result['recipient_count'] . ' recipients';
                } else {
                    $message = 'Broadcast created but sending failed: ' . $result['message'];
                }
            } else {
                // Scheduled for later
                $message = 'Broadcast scheduled for ' . $request->scheduled_at;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'data' => $broadcast,
                    'result' => $result ?? null,
                ], 201);
            }

            return redirect()->route('notifications.broadcasts.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to create broadcast: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create broadcast: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified broadcast notification.
     */
    public function show(string $id)
    {
        $broadcast = Notification::where('uuid', $id)
            ->where('type', 'like', 'broadcast_%')
            ->firstOrFail();

        // Get broadcast statistics
        $stats = $this->getBroadcastDetailStatistics($broadcast);

        // Get recipient list
        $recipients = $this->broadcastService->getBroadcastRecipients($broadcast->id);

        // Get delivery logs
        $logs = NotificationLog::where('notification_id', $broadcast->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $broadcast,
                'stats' => $stats,
                'recipients' => $recipients,
                'logs' => $logs,
            ]);
        }

        return view('notifications.broadcasts.show', compact('broadcast', 'stats', 'recipients', 'logs'));
    }

    /**
     * Cancel a scheduled broadcast.
     */
    public function cancel(string $id)
    {
        $broadcast = Notification::where('uuid', $id)
            ->where('type', 'like', 'broadcast_%')
            ->firstOrFail();

        if ($broadcast->status !== 'scheduled') {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Only scheduled broadcasts can be cancelled',
                ], 422);
            }
            return redirect()->back()->with('error', 'Only scheduled broadcasts can be cancelled');
        }

        $cancelled = $this->broadcastService->cancelBroadcast($broadcast->id);

        if ($cancelled) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Broadcast cancelled successfully',
                ]);
            }
            return redirect()->back()->with('success', 'Broadcast cancelled successfully');
        } else {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to cancel broadcast',
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to cancel broadcast');
        }
    }

    /**
     * Retry a failed broadcast.
     */
    public function retry(string $id)
    {
        $broadcast = Notification::where('uuid', $id)
            ->where('type', 'like', 'broadcast_%')
            ->firstOrFail();

        if ($broadcast->status !== 'failed') {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Only failed broadcasts can be retried',
                ], 422);
            }
            return redirect()->back()->with('error', 'Only failed broadcasts can be retried');
        }

        $result = $this->broadcastService->retryBroadcast($broadcast->id);

        if ($result['success']) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Broadcast retry initiated successfully',
                    'result' => $result,
                ]);
            }
            return redirect()->back()->with('success', 'Broadcast retry initiated successfully');
        } else {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to retry broadcast: ' . $result['message'],
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to retry broadcast: ' . $result['message']);
        }
    }

    /**
     * Get recipient options for broadcast.
     */
    public function getRecipientOptions(Request $request)
    {
        $type = $request->get('type', 'all_users');
        
        $options = [];

        switch ($type) {
            case 'all_users':
                $options['total'] = User::count();
                $options['description'] = 'All active users in the system';
                break;
                
            case 'selected_users':
                $users = User::active()
                    ->select('id', 'name', 'email', 'role')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                        ];
                    });
                $options['users'] = $users;
                $options['total'] = $users->count();
                break;
                
            case 'by_role':
                $roles = DB::table('roles')
                    ->select('name', DB::raw('count(users.id) as user_count'))
                    ->leftJoin('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->leftJoin('users', 'model_has_roles.model_id', '=', 'users.id')
                    ->where('users.is_active', true)
                    ->groupBy('roles.name')
                    ->get();
                $options['roles'] = $roles;
                $options['total'] = $roles->sum('user_count');
                break;
                
            case 'by_department':
                $departments = User::active()
                    ->select('department', DB::raw('count(*) as user_count'))
                    ->whereNotNull('department')
                    ->groupBy('department')
                    ->get();
                $options['departments'] = $departments;
                $options['total'] = $departments->sum('user_count');
                break;
        }

        if ($request->expectsJson()) {
            return response()->json($options);
        }

        return view('notifications.broadcasts.partials.recipient-options', compact('options', 'type'));
    }

    /**
     * Preview broadcast with template.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'template_id' => 'nullable|exists:email_templates,id',
            'template_variables' => 'nullable|array',
        ]);

        $preview = [
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        // If template is selected, render it
        if ($request->template_id) {
            $template = EmailTemplate::find($request->template_id);
            if ($template) {
                try {
                    $variables = $request->template_variables ?? [];
                    $rendered = $this->broadcastService->renderTemplate($template, $variables);
                    $preview['subject'] = $rendered['subject'];
                    $preview['message'] = $rendered['body_html'];
                    $preview['is_template'] = true;
                    $preview['template_name'] = $template->name;
                } catch (\Exception $e) {
                    $preview['error'] = 'Template rendering failed: ' . $e->getMessage();
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'preview' => $preview,
            ]);
        }

        return view('notifications.broadcasts.partials.preview', compact('preview'));
    }

    /**
     * Get broadcast statistics.
     */
    private function getBroadcastStatistics(Request $request)
    {
        $query = Notification::where('type', 'like', 'broadcast_%');

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $total = $query->count();
        
        $statusCounts = Notification::where('type', 'like', 'broadcast_%')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $typeCounts = Notification::where('type', 'like', 'broadcast_%')
            ->select(DB::raw('SUBSTRING_INDEX(type, "_", -1) as broadcast_type'), DB::raw('count(*) as count'))
            ->groupBy('broadcast_type')
            ->get()
            ->pluck('count', 'broadcast_type')
            ->toArray();

        $recentBroadcasts = Notification::where('type', 'like', 'broadcast_%')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total' => $total,
            'status_counts' => $statusCounts,
            'type_counts' => $typeCounts,
            'recent_broadcasts' => $recentBroadcasts,
            'success_rate' => $total > 0 ? round((($statusCounts['sent'] ?? 0) + ($statusCounts['delivered'] ?? 0)) / $total * 100, 2) : 0,
        ];
    }

    /**
     * Get broadcast detail statistics.
     */
    private function getBroadcastDetailStatistics(Notification $broadcast)
    {
        $logs = NotificationLog::where('notification_id', $broadcast->id)->get();
        
        $total = $logs->count();
        $sent = $logs->where('status', 'sent')->count();
        $delivered = $logs->where('status', 'delivered')->count();
        $failed = $logs->where('status', 'failed')->count();
        
        $channelStats = $logs->groupBy('channel')->map(function ($channelLogs) {
            return [
                'total' => $channelLogs->count(),
                'sent' => $channelLogs->where('status', 'sent')->count(),
                'delivered' => $channelLogs->where('status', 'delivered')->count(),
                'failed' => $channelLogs->where('status', 'failed')->count(),
            ];
        });

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($sent + $delivered) / $total * 100, 2) : 0,
            'channel_stats' => $channelStats,
        ];
    }

    /**
     * Get user groups for recipient selection.
     */
    private function getUserGroups()
    {
        return [
            'all_users' => 'All Users',
            'selected_users' => 'Selected Users',
            'by_role' => 'By Role',
            'by_department' => 'By Department',
        ];
    }

    /**
     * Get available channels.
     */
    private function getAvailableChannels()
    {
        return [
            'email' => 'Email',
            'in_app' => 'In-App Notification',
            'sms' => 'SMS',
        ];
    }
}
