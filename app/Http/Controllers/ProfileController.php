<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page.
     */
    public function show()
    {
        $user = Auth::user();

        return view('users.profile', compact('user'));
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();

        return view('users.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        // Only update password if a new one was provided
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        $user->update($updateData);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's profile picture.
     */
    public function updateAvatar(Request $request)
    {
        try {
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $user = Auth::user();

            // Handle file upload
            if ($request->hasFile('avatar')) {
                $avatarFile = $request->file('avatar');

                // Validate the uploaded file
                if (!$avatarFile->isValid()) {
                    return redirect()->back()
                        ->with('error', 'File upload failed. Please try again.')
                        ->withInput();
                }

                // Generate a unique filename to avoid conflicts
                $extension = $avatarFile->getClientOriginalExtension();
                $avatarFileName = time() . '_' . $user->id . '_' . uniqid() . '.' . $extension;

                // Ensure the avatars directory exists in both locations
                $publicAvatarPath = public_path('storage/avatars');
                $storageAvatarPath = storage_path('app/public/avatars');

                if (!file_exists($publicAvatarPath)) {
                    mkdir($publicAvatarPath, 0755, true);
                }
                if (!file_exists($storageAvatarPath)) {
                    mkdir($storageAvatarPath, 0755, true);
                }

                // Save to public/storage/avatars (directly accessible)
                $publicFilePath = $publicAvatarPath . '/' . $avatarFileName;

                // Move the uploaded file to the public directory
                $moved = $avatarFile->move($publicAvatarPath, $avatarFileName);

                // Check if file was moved successfully
                if (!$moved) {
                    return redirect()->back()
                        ->with('error', 'Failed to save the image. Please try again.')
                        ->withInput();
                }

                // Verify the file exists
                if (!file_exists($publicFilePath)) {
                    return redirect()->back()
                        ->with('error', 'File was not saved properly. Please try again.')
                        ->withInput();
                }

                // Also copy to storage/app/public/avatars for backup
                $storageFilePath = $storageAvatarPath . '/' . $avatarFileName;
                @copy($publicFilePath, $storageFilePath);

                // Delete old avatar if exists
                if ($user->avatar) {
                    $oldPublicPath = $publicAvatarPath . '/' . $user->avatar;
                    $oldStoragePath = $storageAvatarPath . '/' . $user->avatar;

                    if (file_exists($oldPublicPath)) {
                        @unlink($oldPublicPath);
                    }
                    if (file_exists($oldStoragePath)) {
                        @unlink($oldStoragePath);
                    }
                }

                // Update the user's avatar field in the database
                $updateResult = $user->update([
                    'avatar' => $avatarFileName,
                ]);

                // Force refresh the user model
                $user = $user->fresh();

                // Verify the database was updated
                if (!$updateResult || !$user->avatar) {
                    return redirect()->back()
                        ->with('error', 'Failed to update profile picture in database. Please try again.')
                        ->withInput();
                }

                // Log success with file paths
                \Log::info('Avatar updated successfully', [
                    'user_id' => $user->id,
                    'avatar_filename' => $avatarFileName,
                    'db_avatar' => $user->avatar,
                    'public_path' => $publicFilePath,
                    'storage_path' => $storageFilePath,
                    'public_exists' => file_exists($publicFilePath),
                    'storage_exists' => file_exists($storageFilePath)
                ]);

                return redirect()->route('profile.edit')
                    ->with('success', 'Profile picture updated successfully! File saved to: ' . $avatarFileName);
            }

            return redirect()->back()->with('error', 'No file was uploaded.');

        } catch (\Exception $e) {
            \Log::error('Avatar upload error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while uploading the image: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update the user's notification settings from profile page.
     */
    public function updateNotificationSettings(Request $request)
    {
        $user = Auth::user();
        
        // Get form data
        $channels = $request->input('channels', []);
        $notificationTypes = $request->input('notification_types', []);
        $notificationFrequency = $request->input('notification_frequency', 'immediate');
        $quietHoursStart = $request->input('quiet_hours_start', '22:00');
        $quietHoursEnd = $request->input('quiet_hours_end', '07:00');
        
        // Convert form data to settings array format expected by NotificationSettingController
        $settings = [];
        
        // Define notification types mapping
        $notificationTypeMapping = [
            'attendance_check_in' => 'attendance_check_in',
            'attendance_exceptions' => 'late_check_in', // Map to late_check_in for now
            'report_generated' => 'report_generated',
            'report_generation_failed' => 'report_generation_failed',
            'scheduled_report_ready' => 'scheduled_report_ready',
            'system_announcements' => 'system_announcement',
        ];
        
        // Build settings array
        foreach ($notificationTypeMapping as $formType => $notificationType) {
            foreach (['email', 'in_app'] as $channel) {
                $isEnabled = in_array($channel, $channels) &&
                            isset($notificationTypes[$formType][$channel]) &&
                            $notificationTypes[$formType][$channel] == '1';
                
                $settings[] = [
                    'notification_type' => $notificationType,
                    'channel' => $channel,
                    'is_enabled' => $isEnabled,
                    'preferences' => [
                        'frequency' => $notificationFrequency,
                    ],
                ];
            }
        }
        
        // Add Do Not Disturb setting
        $settings[] = [
            'notification_type' => 'system',
            'channel' => 'do_not_disturb',
            'is_enabled' => !empty($quietHoursStart) && !empty($quietHoursEnd),
            'preferences' => [
                'enabled' => !empty($quietHoursStart) && !empty($quietHoursEnd),
                'start_time' => $quietHoursStart,
                'end_time' => $quietHoursEnd,
                'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                'exceptions' => [],
            ],
        ];
        
        // Call NotificationSettingController's update method
        $notificationSettingController = new \App\Http\Controllers\NotificationSettingController();
        
        // Create a new request with the settings data
        $newRequest = new \Illuminate\Http\Request();
        $newRequest->setMethod('POST');
        $newRequest->request->add(['settings' => $settings]);
        $newRequest->setUserResolver(function () use ($user) {
            return $user;
        });
        
        try {
            // Call the update method
            $response = $notificationSettingController->update($newRequest);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Notification settings updated successfully',
                    'redirect' => route('profile.show') . '#notifications',
                ]);
            }
            
            return redirect()->route('profile.show') . '#notifications'
                ->with('success', 'Notification settings updated successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to update notification settings: ' . $e->getMessage(),
                ], 500);
            }
            
            return redirect()->route('profile.show') . '#notifications'
                ->with('error', 'Failed to update notification settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Method to check if the avatar file exists and is accessible.
     */
    public function checkAvatar(Request $request)
    {
        $user = Auth::user();

        if (!$user->avatar) {
            return response()->json(['error' => 'No avatar set for this user'], 404);
        }

        $avatarPath = storage_path('app/public/avatars/' . $user->avatar);
        $webPath = public_path('storage/avatars/' . $user->avatar);

        $existsInStorage = file_exists($avatarPath);
        $existsInPublic = file_exists($webPath);

        return response()->json([
            'avatar_filename' => $user->avatar,
            'exists_in_storage' => $existsInStorage,
            'exists_in_public' => $existsInPublic,
            'storage_path' => $avatarPath,
            'public_path' => $webPath,
            'avatar_url' => asset('storage/avatars/' . $user->avatar)
        ]);
    }
}
