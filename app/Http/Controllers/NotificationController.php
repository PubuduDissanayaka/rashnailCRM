<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
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
    }

    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Notification::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id);
        
        // Filter by read status
        if ($request->has('read')) {
            $query->whereNotNull('read_at');
        }
        
        if ($request->has('unread')) {
            $query->whereNull('read_at');
        }
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Sort
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);
        
        $perPage = $request->get('per_page', 20);
        $notifications = $query->paginate($perPage);
        
        return response()->json([
            'data' => $notifications,
            'meta' => [
                'total' => $notifications->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:100',
            'data' => 'required|array',
            'channels' => 'array',
            'channels.*' => 'in:email,in_app,sms',
        ]);
        
        $user = Auth::user();
        
        $notification = $this->notificationService->sendToUser(
            $user,
            $request->type,
            $request->data,
            $request->channels
        );
        
        return response()->json([
            'message' => 'Notification sent successfully',
            'data' => $notification,
        ], 201);
    }

    /**
     * Display the specified notification.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        
        $notification = Notification::where('uuid', $id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->firstOrFail();
        
        // Mark as read when viewing
        if ($notification->isUnread()) {
            $notification->markAsRead();
        }
        
        $logs = $notification->logs()->get();
        
        return response()->json([
            'data' => $notification,
            'logs' => $logs,
        ]);
    }

    /**
     * Update the specified notification (mark as read/unread).
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'read' => 'boolean',
        ]);
        
        $user = Auth::user();
        
        $notification = Notification::where('uuid', $id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->firstOrFail();
        
        if ($request->has('read')) {
            if ($request->read) {
                $notification->markAsRead();
            } else {
                $notification->markAsUnread();
            }
        }
        
        return response()->json([
            'message' => 'Notification updated successfully',
            'data' => $notification,
        ]);
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        
        $notification = Notification::where('uuid', $id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->firstOrFail();
        
        $notification->delete();
        
        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        $count = Notification::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return response()->json([
            'message' => "{$count} notifications marked as read",
        ]);
    }

    /**
     * Get notification status.
     */
    public function status(string $id)
    {
        $user = Auth::user();
        
        $notification = Notification::where('uuid', $id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->firstOrFail();
        
        $status = $this->notificationService->getStatus($id);
        
        return response()->json($status);
    }

    /**
     * Retry failed notification.
     */
    public function retry(string $id)
    {
        $user = Auth::user();

        $notification = Notification::where('uuid', $id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->firstOrFail();

        $result = $this->notificationService->retryFailed($id);

        return response()->json([
            'message' => 'Retry initiated',
            'success' => $result,
        ]);
    }

    /**
     * Get recent notifications for the topbar dropdown inbox.
     */
    public function inbox(Request $request)
    {
        $user = Auth::user();

        $notifications = Notification::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $unreadCount = Notification::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success'      => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(string $id)
    {
        $user = Auth::user();

        $notification = Notification::where('uuid', $id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->firstOrFail();

        if ($notification->isUnread()) {
            $notification->markAsRead();
        }

        $unreadCount = Notification::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Return the current unread notification count (for polling).
     */
    public function checkNew(Request $request)
    {
        $user = Auth::user();

        $unreadCount = Notification::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications as read (alias used by topbar).
     */
    public function clearAll(Request $request)
    {
        $user = Auth::user();

        Notification::where('notifiable_type', 'App\Models\User')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }
}
