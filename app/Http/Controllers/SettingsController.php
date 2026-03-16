<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\NotificationProvider;
use App\Mail\TestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;

class SettingsController extends Controller
{
    use \App\Traits\ConfiguresEmailProvider;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage system');
    }

    /**
     * Display settings page
     */
    public function index()
    {
        $this->authorize('manage system');

        $business = Setting::getGroup('business');
        $appointment = Setting::getGroup('appointment');
        $notification = Setting::getGroup('notification');
        $payment = Setting::getGroup('payment');

        // Get email providers
        $emailProviders = NotificationProvider::where('channel', 'email')
            ->orderBy('priority')
            ->get();

        return view('settings.index', compact('business', 'appointment', 'notification', 'payment', 'emailProviders'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $this->authorize('manage system');

        $validated = $request->validate([
            'group' => 'required|in:business,appointment,notification,payment',
            'settings' => 'nullable|array',
            'business_hours' => 'nullable|array',
            'payment_methods' => 'nullable|array',
        ]);

        // Update each setting
        if (isset($validated['settings'])) {
            foreach ($validated['settings'] as $key => $value) {
                // Determine type based on value
                $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
                Setting::set($key, $value, $type);
            }
        }

        // Handle specific complex settings
        if (isset($validated['business_hours'])) {
            $hours = [];
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            foreach ($days as $day) {
                $dayEnabled = isset($validated['business_hours'][$day]['enabled']) && $validated['business_hours'][$day]['enabled'];
                $hours[$day] = [
                    'open' => $dayEnabled ? ($validated['business_hours'][$day]['open'] ?? null) : null,
                    'close' => $dayEnabled ? ($validated['business_hours'][$day]['close'] ?? null) : null,
                    'closed' => !$dayEnabled
                ];
            }

            Setting::set('business.hours', $hours, 'json');
        }

        if (isset($validated['payment_methods'])) {
            Setting::set('payment.methods', $validated['payment_methods'], 'json');
        }

        // Handle file uploads
        if ($request->hasFile('logo')) {
            $this->handleFileUpload($request, 'logo', 'business.logo');
        }

        if ($request->hasFile('favicon')) {
            $this->handleFileUpload($request, 'favicon', 'business.favicon');
        }

        if ($request->hasFile('auth_bg_image')) {
            $this->handleFileUpload($request, 'auth_bg_image', 'business.auth_bg_image');
        } elseif ($request->input('settings.business.auth_bg_image_remove') === '1') {
            $oldSetting = Setting::where('key', 'business.auth_bg_image')->first();
            if ($oldSetting && $oldSetting->value) {
                Storage::disk('public')->delete($oldSetting->value);
                $oldSetting->delete();
            }
        }

        // Clear cache
        Setting::flushCache();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully.'
            ]);
        }

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload(Request $request, string $field, string $settingKey)
    {
        $maxSize = $field === 'auth_bg_image' ? 4096 : 2048;
        $request->validate([
            $field => "required|image|max:{$maxSize}",
        ]);

        // Delete old file if exists
        $oldSetting = Setting::where('key', $settingKey)->first();
        if ( $oldSetting && $oldSetting->value) {
            Storage::disk('public')->delete($oldSetting->value);
        }

        // Store new file
        $path = $request->file($field)->store('settings', 'public');
        Setting::set($settingKey, $path, 'file');
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $this->authorize('manage system');

        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            // Get the active email provider
            $provider = $this->getActiveEmailProvider();

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active email provider configured. Please add and activate an email provider first.'
                ], 400);
            }

            // Configure mailer with provider settings
            $this->configureMailerForProvider($provider);

            // Send test email
            Mail::mailer('dynamic_smtp')->to($request->email)->send(new TestEmail());

            // Mark provider as tested
            $provider->update([
                'last_test_at' => now(),
                'last_test_status' => 'success'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully using ' . $provider->name . '!'
            ]);
        } catch (\Exception $e) {
            if (isset($provider)) {
                $provider->update([
                    'last_test_at' => now(),
                    'last_test_status' => 'failed'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new email provider
     */
    public function storeEmailProvider(Request $request)
    {
        $this->authorize('manage system');

        // Validate basic fields
        $validated = $request->validate([
            'provider' => 'required|in:smtp,cpanel,mailgun,sendgrid,ses',
            'name' => 'required|string|max:255',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'daily_limit' => 'nullable|integer|min:0',
            'monthly_limit' => 'nullable|integer|min:0',
        ]);

        // Provider-specific validation
        $configRules = $this->getProviderConfigRules($request->provider, false);
        $validatedConfig = $request->validate($configRules);

        // Prepare config data with encryption for sensitive fields
        $inputConfig = $validatedConfig['config'] ?? [];
        $config = [];
        
        foreach ($inputConfig as $key => $value) {
            // Encrypt sensitive fields
            if (in_array($key, ['password', 'secret', 'api_key'])) {
                $config[$key] = Crypt::encryptString($value);
            } else {
                $config[$key] = $value;
            }
        }

        // Create provider
        $provider = NotificationProvider::create([
            'channel' => 'email',
            'provider' => $validated['provider'],
            'name' => $validated['name'],
            'priority' => $validated['priority'] ?? 0,
            'config' => $config,
            'is_active' => $request->boolean('is_active', true),
            'daily_limit' => $validated['daily_limit'] ?? null,
            'monthly_limit' => $validated['monthly_limit'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email provider created successfully.',
            'provider' => $provider
        ]);
    }

    /**
     * Update an existing email provider
     */
    public function updateEmailProvider(Request $request, NotificationProvider $provider)
    {
        $this->authorize('manage system');

        // Validate basic fields
        $validated = $request->validate([
            'provider' => 'required|in:smtp,cpanel,mailgun,sendgrid,ses',
            'name' => 'required|string|max:255',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'daily_limit' => 'nullable|integer|min:0',
            'monthly_limit' => 'nullable|integer|min:0',
        ]);

        // Provider-specific validation (passwords optional for updates)
        $configRules = $this->getProviderConfigRules($request->provider, true);
        $validatedConfig = $request->validate($configRules);

        // Prepare config data
        $inputConfig = $validatedConfig['config'] ?? [];
        $config = $provider->config ?? [];
        
        foreach ($inputConfig as $key => $value) {
            // Skip if password field is empty (keeping existing)
            if (in_array($key, ['password', 'secret', 'api_key']) && empty($value)) {
                continue;
            }

            // Encrypt sensitive fields
            if (in_array($key, ['password', 'secret', 'api_key'])) {
                $config[$key] = Crypt::encryptString($value);
            } else {
                $config[$key] = $value;
            }
        }

        // Update provider
        $provider->update([
            'provider' => $validated['provider'],
            'name' => $validated['name'],
            'priority' => $validated['priority'] ?? 0,
            'config' => $config,
            'is_active' => $request->boolean('is_active', true),
            'daily_limit' => $validated['daily_limit'] ?? null,
            'monthly_limit' => $validated['monthly_limit'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email provider updated successfully.',
            'provider' => $provider->fresh()
        ]);
    }

    /**
     * Test a specific email provider
     */
    public function testEmailProvider(Request $request, NotificationProvider $provider)
    {
        $this->authorize('manage system');

        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            // Configure mailer with this provider's settings
            $this->configureMailerForProvider($provider);

            // Send test email
            Mail::mailer('dynamic_smtp')->to($request->email)->send(new TestEmail());

            // Mark provider as tested
            $provider->update([
                'last_test_at' => now(),
                'last_test_status' => 'success'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $request->email
            ]);
        } catch (\Exception $e) {
            $provider->update([
                'last_test_at' => now(),
                'last_test_status' => 'failed'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an email provider
     */
    public function deleteEmailProvider(NotificationProvider $provider)
    {
        $this->authorize('manage system');

        // Prevent deletion of the last active provider
        if ($provider->is_active) {
            $activeCount = NotificationProvider::where('channel', 'email')
                ->where('is_active', true)
                ->count();

            if ($activeCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last active email provider. Please activate another provider first.'
                ], 400);
            }
        }

        $provider->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email provider deleted successfully.'
        ]);
    }

    /**
     * Get a single email provider
     */
    public function showEmailProvider(NotificationProvider $provider)
    {
        $this->authorize('manage system');

        return response()->json([
            'success' => true,
            'provider' => $provider
        ]);
    }

    /**
     * Toggle email provider active status
     */
    public function toggleEmailProvider(NotificationProvider $provider)
    {
        $this->authorize('manage system');

        // If deactivating, ensure there's at least one other active provider
        if ($provider->is_active) {
            $activeCount = NotificationProvider::where('channel', 'email')
                ->where('is_active', true)
                ->count();

            if ($activeCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate the last active email provider. Please activate another provider first.'
                ], 400);
            }
        }

        $provider->update([
            'is_active' => !$provider->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email provider ' . ($provider->is_active ? 'activated' : 'deactivated') . ' successfully.'
        ]);
    }

    /**
     * Get validation rules for provider-specific config
     */
    private function getProviderConfigRules(string $provider, bool $isUpdate): array
    {
        $passwordRule = $isUpdate ? 'nullable|string' : 'required|string';

        $smtpRules = [
            'config.host' => 'required|string|max:255',
            'config.port' => 'required|integer|between:1,65535',
            'config.username' => 'required|string|max:255',
            'config.password' => $passwordRule,
            'config.encryption' => 'required|in:tls,ssl,none',
            'config.timeout' => 'nullable|integer|min:1|max:120',
            'config.local_domain' => 'nullable|string|max:255',
            'config.from_address' => 'required|email|max:255',
            'config.from_name' => 'required|string|max:255',
            'config.imap_host' => 'nullable|string|max:255',
            'config.imap_port' => 'nullable|integer|between:1,65535',
            'config.imap_encryption' => 'nullable|in:ssl,tls,none',
        ];

        return match($provider) {
            'smtp' => $smtpRules,
            'cpanel' => $smtpRules, // cPanel is SMTP — same fields, different defaults
            'mailgun' => [
                'config.domain' => 'required|string|max:255',
                'config.secret' => $passwordRule,
                'config.endpoint' => 'nullable|url|max:255',
                'config.from_address' => 'required|email|max:255',
                'config.from_name' => 'required|string|max:255',
            ],
            'sendgrid' => [
                'config.api_key' => $passwordRule,
                'config.from_address' => 'required|email|max:255',
                'config.from_name' => 'required|string|max:255',
            ],
            'ses' => [
                'config.key' => 'required|string|max:255',
                'config.secret' => $passwordRule,
                'config.region' => 'required|string|in:us-east-1,us-west-2,eu-west-1,ap-southeast-1,ap-southeast-2',
                'config.from_address' => 'required|email|max:255',
                'config.from_name' => 'required|string|max:255',
            ],
            default => [],
        };
    }

}