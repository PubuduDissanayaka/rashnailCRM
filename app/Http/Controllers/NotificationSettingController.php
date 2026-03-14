<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationSettingController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the user's notification settings.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user's notification settings
        $settings = NotificationSetting::where('user_id', $user->id)
            ->orderBy('notification_type')
            ->orderBy('channel')
            ->get();

        // Group settings by notification type
        $groupedSettings = $settings->groupBy('notification_type');

        // Get available notification types and channels
        $notificationTypes = $this->getAvailableNotificationTypes();
        $channels = $this->getAvailableChannels();

        // Get system default settings
        $systemDefaults = NotificationSetting::systemDefaults()->get();

        if ($request->expectsJson()) {
            return response()->json([
                'settings' => $settings,
                'grouped_settings' => $groupedSettings,
                'notification_types' => $notificationTypes,
                'channels' => $channels,
                'system_defaults' => $systemDefaults,
            ]);
        }

        return view('notifications.settings.index', compact(
            'groupedSettings',
            'notificationTypes',
            'channels',
            'systemDefaults',
            'user'
        ));
    }

    /**
     * Update the user's notification settings.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $settings = $request->input('settings', []);

        // Validate the settings structure
        $request->validate([
            'settings' => 'array',
            'settings.*.notification_type' => 'required|string|max:100',
            'settings.*.channel' => 'required|in:email,in_app,sms',
            'settings.*.is_enabled' => 'boolean',
            'settings.*.preferences' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            foreach ($settings as $settingData) {
                $notificationType = $settingData['notification_type'];
                $channel = $settingData['channel'];

                // Find or create the setting
                $setting = NotificationSetting::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'notification_type' => $notificationType,
                        'channel' => $channel,
                    ],
                    [
                        'is_enabled' => $settingData['is_enabled'] ?? true,
                        'preferences' => $settingData['preferences'] ?? [],
                    ]
                );

                // Update the setting
                $setting->update([
                    'is_enabled' => $settingData['is_enabled'] ?? $setting->is_enabled,
                    'preferences' => array_merge($setting->preferences ?? [], $settingData['preferences'] ?? []),
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Notification settings updated successfully',
                    'settings' => NotificationSetting::where('user_id', $user->id)->get(),
                ]);
            }

            return redirect()->route('notifications.settings.index')
                ->with('success', 'Notification settings updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to update notification settings: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update notification settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update a specific notification setting.
     */
    public function updateSetting(Request $request, string $notificationType, string $channel)
    {
        $user = $request->user();

        $request->validate([
            'is_enabled' => 'boolean',
            'preferences' => 'nullable|array',
        ]);

        $setting = NotificationSetting::firstOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
                'channel' => $channel,
            ],
            [
                'is_enabled' => $request->get('is_enabled', true),
                'preferences' => $request->get('preferences', []),
            ]
        );

        $setting->update([
            'is_enabled' => $request->get('is_enabled', $setting->is_enabled),
            'preferences' => array_merge($setting->preferences ?? [], $request->get('preferences', [])),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Setting updated successfully',
                'setting' => $setting,
            ]);
        }

        return redirect()->route('notifications.settings.index')
            ->with('success', 'Setting updated successfully');
    }

    /**
     * Reset user's notification settings to system defaults.
     */
    public function resetToDefaults(Request $request)
    {
        $user = $request->user();

        // Delete all user-specific settings
        NotificationSetting::where('user_id', $user->id)->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Notification settings reset to system defaults',
            ]);
        }

        return redirect()->route('notifications.settings.index')
            ->with('success', 'Notification settings reset to system defaults');
    }

    /**
     * Update user's "Do Not Disturb" settings.
     */
    public function updateDoNotDisturb(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'enabled' => 'boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'days' => 'nullable|array',
            'days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'exceptions' => 'nullable|array',
            'exceptions.*' => 'date',
        ]);

        // Get or create the DND setting
        $setting = NotificationSetting::firstOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => 'system',
                'channel' => 'do_not_disturb',
            ],
            [
                'is_enabled' => false,
                'preferences' => [],
            ]
        );

        $preferences = [
            'enabled' => $request->get('enabled', false),
            'start_time' => $request->get('start_time', '22:00'),
            'end_time' => $request->get('end_time', '08:00'),
            'days' => $request->get('days', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'exceptions' => $request->get('exceptions', []),
        ];

        $setting->update([
            'is_enabled' => $preferences['enabled'],
            'preferences' => $preferences,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Do Not Disturb settings updated successfully',
                'setting' => $setting,
            ]);
        }

        return redirect()->route('notifications.settings.index')
            ->with('success', 'Do Not Disturb settings updated successfully');
    }

    /**
     * Get user's notification preferences for a specific type.
     */
    public function getUserPreferences(Request $request, string $notificationType)
    {
        $user = $request->user();

        $settings = NotificationSetting::where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->get();

        $preferences = [
            'channels' => [],
            'do_not_disturb' => $this->getDoNotDisturbStatus($user),
        ];

        foreach ($settings as $setting) {
            $preferences['channels'][$setting->channel] = [
                'enabled' => $setting->is_enabled,
                'preferences' => $setting->preferences,
            ];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'notification_type' => $notificationType,
                'preferences' => $preferences,
            ]);
        }

        return view('notifications.settings.partials.preferences', compact('preferences', 'notificationType'));
    }

    /**
     * Check if user is currently in "Do Not Disturb" mode.
     */
    public function checkDoNotDisturb(Request $request)
    {
        $user = $request->user();
        $status = $this->getDoNotDisturbStatus($user);

        if ($request->expectsJson()) {
            return response()->json($status);
        }

        return view('notifications.settings.partials.do-not-disturb-status', compact('status'));
    }

    /**
     * Get available notification types.
     */
    private function getAvailableNotificationTypes()
    {
        return [
            'attendance_check_in' => 'Attendance Check-In',
            'attendance_check_out' => 'Attendance Check-Out',
            'late_check_in' => 'Late Check-In',
            'report_generated' => 'Report Generated',
            'appointment_reminder' => 'Appointment Reminder',
            'appointment_confirmation' => 'Appointment Confirmation',
            'appointment_cancellation' => 'Appointment Cancellation',
            'payment_received' => 'Payment Received',
            'payment_due' => 'Payment Due',
            'system_announcement' => 'System Announcement',
            'broadcast_message' => 'Broadcast Message',
            'security_alert' => 'Security Alert',
            'password_reset' => 'Password Reset',
            'welcome_message' => 'Welcome Message',
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

    /**
     * Get Do Not Disturb status for a user.
     */
    private function getDoNotDisturbStatus(User $user)
    {
        $setting = NotificationSetting::where('user_id', $user->id)
            ->where('notification_type', 'system')
            ->where('channel', 'do_not_disturb')
            ->first();

        if (!$setting || !$setting->is_enabled) {
            return [
                'enabled' => false,
                'active' => false,
                'message' => 'Do Not Disturb is disabled',
            ];
        }

        $preferences = $setting->preferences;
        $now = now();
        $currentDay = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        // Check if today is in the enabled days
        if (!in_array($currentDay, $preferences['days'] ?? [])) {
            return [
                'enabled' => true,
                'active' => false,
                'message' => 'Do Not Disturb is enabled but not active for today',
                'preferences' => $preferences,
            ];
        }

        // Check if current time is within DND hours
        $startTime = $preferences['start_time'] ?? '22:00';
        $endTime = $preferences['end_time'] ?? '08:00';

        $active = false;
        if ($startTime <= $endTime) {
            // Normal time range (e.g., 22:00 to 08:00)
            $active = $currentTime >= $startTime && $currentTime <= $endTime;
        } else {
            // Overnight time range (e.g., 22:00 to 08:00 next day)
            $active = $currentTime >= $startTime || $currentTime <= $endTime;
        }

        // Check for exceptions
        $currentDate = $now->format('Y-m-d');
        if (in_array($currentDate, $preferences['exceptions'] ?? [])) {
            $active = false;
        }

        return [
            'enabled' => true,
            'active' => $active,
            'message' => $active ? 'Do Not Disturb is active' : 'Do Not Disturb is enabled but not active',
            'preferences' => $preferences,
            'current_time' => $currentTime,
            'current_day' => $currentDay,
        ];
    }

    /**
     * Bulk update notification settings for multiple users (admin only).
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'settings' => 'required|array',
            'settings.*.notification_type' => 'required|string|max:100',
            'settings.*.channel' => 'required|in:email,in_app,sms',
            'settings.*.is_enabled' => 'boolean',
            'settings.*.preferences' => 'nullable|array',
        ]);

        $userIds = $request->user_ids;
        $settings = $request->settings;
        $updatedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($userIds as $userId) {
                foreach ($settings as $settingData) {
                    $setting = NotificationSetting::firstOrCreate(
                        [
                            'user_id' => $userId,
                            'notification_type' => $settingData['notification_type'],
                            'channel' => $settingData['channel'],
                        ],
                        [
                            'is_enabled' => $settingData['is_enabled'] ?? true,
                            'preferences' => $settingData['preferences'] ?? [],
                        ]
                    );

                    $setting->update([
                        'is_enabled' => $settingData['is_enabled'] ?? $setting->is_enabled,
                        'preferences' => array_merge($setting->preferences ?? [], $settingData['preferences'] ?? []),
                    ]);

                    $updatedCount++;
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Updated {$updatedCount} settings for " . count($userIds) . " users",
                    'updated_count' => $updatedCount,
                ]);
            }

            return redirect()->back()
                ->with('success', "Updated {$updatedCount} settings for " . count($userIds) . " users");
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to bulk update settings: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to bulk update settings: ' . $e->getMessage());
        }
    }

    /**
     * Update system-wide notification settings (admin only).
     */
    public function updateSystemSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.*.is_enabled' => 'boolean',
            'settings.*.preferences' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $settings = $request->settings;
            $notificationTypes = $this->getAvailableNotificationTypes();
            $channels = $this->getAvailableChannels();

            foreach ($notificationTypes as $type => $label) {
                foreach ($channels as $channel => $channelLabel) {
                    $isEnabled = $settings[$type][$channel]['is_enabled'] ?? true;
                    $preferences = $settings[$type]['preferences'] ?? [];

                    // Update or create system default setting
                    $setting = NotificationSetting::firstOrCreate(
                        [
                            'user_id' => null,
                            'notification_type' => $type,
                            'channel' => $channel,
                        ],
                        [
                            'is_enabled' => $isEnabled,
                            'preferences' => $preferences,
                        ]
                    );

                    $setting->update([
                        'is_enabled' => $isEnabled,
                        'preferences' => $preferences,
                    ]);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'System notification settings updated successfully',
                ]);
            }

            return redirect()->route('notifications.settings.index')
                ->with('success', 'System notification settings updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to update system settings: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update system settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update rate limiting settings (admin only).
     */
    public function updateRateLimiting(Request $request)
    {
        $request->validate([
            'rate_limit_hourly' => 'required|integer|min:1|max:10000',
            'rate_limit_daily' => 'required|integer|min:1|max:100000',
            'user_limit_daily' => 'required|integer|min:1|max:1000',
            'retry_attempts' => 'required|integer|min:0|max:10',
        ]);

        // Store rate limiting settings in the settings table or a dedicated table
        $settings = [
            'notification_rate_limit_hourly' => $request->rate_limit_hourly,
            'notification_rate_limit_daily' => $request->rate_limit_daily,
            'notification_user_limit_daily' => $request->user_limit_daily,
            'notification_retry_attempts' => $request->retry_attempts,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Rate limiting settings updated successfully',
                'settings' => $settings,
            ]);
        }

        return redirect()->route('notifications.settings.index')
            ->with('success', 'Rate limiting settings updated successfully');
    }

    /**
     * Update blacklist/whitelist settings (admin only).
     */
    public function updateBlacklistWhitelist(Request $request)
    {
        $request->validate([
            'blacklisted_domains' => 'nullable|string',
            'whitelisted_ips' => 'nullable|string',
        ]);

        $settings = [
            'notification_blacklisted_domains' => $request->blacklisted_domains,
            'notification_whitelisted_ips' => $request->whitelisted_ips,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Blacklist/whitelist settings updated successfully',
                'settings' => $settings,
            ]);
        }

        return redirect()->route('notifications.settings.index')
            ->with('success', 'Blacklist/whitelist settings updated successfully');
    }

    /**
     * Reset system settings to defaults (admin only).
     */
    public function resetSystemToDefaults(Request $request)
    {
        // Delete all system default settings
        NotificationSetting::whereNull('user_id')->delete();

        // Run the notification seeder to restore defaults
        $seeder = new \Database\Seeders\NotificationSeeder();
        $seeder->run();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'System notification settings reset to defaults',
            ]);
        }

        return redirect()->route('notifications.settings.index')
            ->with('success', 'System notification settings reset to defaults');
    }

    /**
     * Apply system settings to all users (admin only).
     */
    public function applyToAllUsers(Request $request)
    {
        $systemDefaults = NotificationSetting::systemDefaults()->get();
        $users = User::where('id', '!=', $request->user()->id)->get();

        DB::beginTransaction();

        try {
            foreach ($users as $user) {
                // Delete existing user settings
                NotificationSetting::where('user_id', $user->id)->delete();

                // Create new settings based on system defaults
                foreach ($systemDefaults as $default) {
                    NotificationSetting::create([
                        'user_id' => $user->id,
                        'notification_type' => $default->notification_type,
                        'channel' => $default->channel,
                        'is_enabled' => $default->is_enabled,
                        'preferences' => $default->preferences,
                    ]);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'System settings applied to ' . $users->count() . ' users',
                    'user_count' => $users->count(),
                ]);
            }

            return redirect()->route('notifications.settings.index')
                ->with('success', 'System settings applied to ' . $users->count() . ' users');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to apply system settings: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to apply system settings: ' . $e->getMessage());
        }
    }
}
