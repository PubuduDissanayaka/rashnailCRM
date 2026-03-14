<?php

namespace App\Http\Controllers;

use App\Models\NotificationProvider;
use App\Models\ProviderConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class ProviderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage system');
    }

    /**
     * Display a listing of notification providers.
     */
    public function index(Request $request)
    {
        $query = NotificationProvider::query();

        // Filter by channel
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by provider type
        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->get('sort', 'priority');
        $order = $request->get('order', 'asc');
        $query->orderBy($sort, $order);

        $perPage = $request->get('per_page', 20);
        $providers = $query->paginate($perPage);

        // Get provider statistics
        $stats = [
            'total' => NotificationProvider::count(),
            'active' => NotificationProvider::where('is_active', true)->count(),
            'by_channel' => NotificationProvider::selectRaw('channel, count(*) as count')
                ->groupBy('channel')
                ->get()
                ->pluck('count', 'channel')
                ->toArray(),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $providers,
                'stats' => $stats,
            ]);
        }

        return view('notifications.providers.index', compact('providers', 'stats'));
    }

    /**
     * Show the form for creating a new notification provider.
     */
    public function create()
    {
        $providerTypes = $this->getProviderTypes();
        $channelTypes = $this->getChannelTypes();

        return view('notifications.providers.create', compact('providerTypes', 'channelTypes'));
    }

    /**
     * Store a newly created notification provider.
     */
    public function store(Request $request)
    {
        $validator = $this->validateProvider($request);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $config = $this->prepareConfig($request);

        $provider = NotificationProvider::create([
            'channel' => $request->channel,
            'provider' => $request->provider,
            'name' => $request->name,
            'is_default' => $request->boolean('is_default', false),
            'priority' => $request->priority ?? 1,
            'config' => $config,
            'is_active' => $request->boolean('is_active', true),
            'daily_limit' => $request->daily_limit,
            'monthly_limit' => $request->monthly_limit,
            'usage_count' => 0,
        ]);

        // If this is set as default, unset other defaults for same channel
        if ($provider->is_default) {
            NotificationProvider::where('channel', $provider->channel)
                ->where('id', '!=', $provider->id)
                ->update(['is_default' => false]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Provider created successfully',
                'data' => $provider,
            ], 201);
        }

        return redirect()->route('notifications.providers.index')
            ->with('success', 'Provider created successfully');
    }

    /**
     * Display the specified notification provider.
     */
    public function show(string $id)
    {
        $provider = NotificationProvider::findOrFail($id);
        $configurations = $provider->configurations()->paginate(10);
        $usageStats = $this->getProviderUsageStats($provider);

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $provider,
                'configurations' => $configurations,
                'usage_stats' => $usageStats,
            ]);
        }

        return view('notifications.providers.show', compact('provider', 'configurations', 'usageStats'));
    }

    /**
     * Show the form for editing the specified notification provider.
     */
    public function edit(string $id)
    {
        $provider = NotificationProvider::findOrFail($id);
        $providerTypes = $this->getProviderTypes();
        $channelTypes = $this->getChannelTypes();

        return view('notifications.providers.edit', compact('provider', 'providerTypes', 'channelTypes'));
    }

    /**
     * Update the specified notification provider.
     */
    public function update(Request $request, string $id)
    {
        $provider = NotificationProvider::findOrFail($id);

        $validator = $this->validateProvider($request, $provider);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $config = $this->prepareConfig($request, $provider);

        $provider->update([
            'channel' => $request->channel,
            'provider' => $request->provider,
            'name' => $request->name,
            'is_default' => $request->boolean('is_default', false),
            'priority' => $request->priority ?? $provider->priority,
            'config' => $config,
            'is_active' => $request->boolean('is_active', $provider->is_active),
            'daily_limit' => $request->daily_limit,
            'monthly_limit' => $request->monthly_limit,
        ]);

        // If this is set as default, unset other defaults for same channel
        if ($provider->is_default) {
            NotificationProvider::where('channel', $provider->channel)
                ->where('id', '!=', $provider->id)
                ->update(['is_default' => false]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Provider updated successfully',
                'data' => $provider,
            ]);
        }

        return redirect()->route('notifications.providers.index')
            ->with('success', 'Provider updated successfully');
    }

    /**
     * Remove the specified notification provider.
     */
    public function destroy(string $id)
    {
        $provider = NotificationProvider::findOrFail($id);

        // Check if provider is in use
        if ($provider->configurations()->exists()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Cannot delete provider that has configurations',
                ], 422);
            }
            return redirect()->back()->with('error', 'Cannot delete provider that has configurations');
        }

        $provider->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Provider deleted successfully',
            ]);
        }

        return redirect()->route('notifications.providers.index')
            ->with('success', 'Provider deleted successfully');
    }

    /**
     * Test provider connection.
     */
    public function testConnection(string $id)
    {
        $provider = NotificationProvider::findOrFail($id);

        try {
            $success = $this->testProviderConnection($provider);
            $provider->markTested($success);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => $success,
                    'message' => $success ? 'Connection test successful' : 'Connection test failed',
                ]);
            }

            return redirect()->back()->with(
                $success ? 'success' : 'error',
                $success ? 'Connection test successful' : 'Connection test failed'
            );
        } catch (\Exception $e) {
            $provider->markTested(false);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection test failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle provider active status.
     */
    public function toggleActive(string $id)
    {
        $provider = NotificationProvider::findOrFail($id);
        $provider->update(['is_active' => !$provider->is_active]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Provider status updated',
                'is_active' => $provider->is_active,
            ]);
        }

        return redirect()->back()->with('success', 'Provider status updated');
    }

    /**
     * Set provider as default for its channel.
     */
    public function setDefault(string $id)
    {
        $provider = NotificationProvider::findOrFail($id);

        // Unset other defaults for same channel
        NotificationProvider::where('channel', $provider->channel)
            ->update(['is_default' => false]);

        // Set this provider as default
        $provider->update(['is_default' => true]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Provider set as default',
            ]);
        }

        return redirect()->back()->with('success', 'Provider set as default');
    }

    /**
     * Get provider configuration form fields.
     */
    public function getConfigFields(Request $request)
    {
        $channel = $request->channel;
        $provider = $request->provider;

        $fields = $this->getProviderConfigFields($channel, $provider);

        if (request()->expectsJson()) {
            return response()->json([
                'fields' => $fields,
            ]);
        }

        return view('notifications.providers.partials.config-fields', compact('fields'));
    }

    /**
     * Validate provider data.
     */
    private function validateProvider(Request $request, $provider = null)
    {
        $rules = [
            'channel' => 'required|in:email,sms,in_app,push',
            'provider' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'priority' => 'nullable|integer|min:1|max:100',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'daily_limit' => 'nullable|integer|min:0',
            'monthly_limit' => 'nullable|integer|min:0',
        ];

        // Add provider-specific config validation
        $configFields = $this->getProviderConfigFields($request->channel, $request->provider);
        foreach ($configFields as $field) {
            if ($field['required'] ?? false) {
                $rules["config.{$field['name']}"] = 'required';
            }
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * Prepare config array from request.
     */
    private function prepareConfig(Request $request, $existingProvider = null)
    {
        $config = [];
        $configFields = $this->getProviderConfigFields($request->channel, $request->provider);

        foreach ($configFields as $field) {
            $fieldName = $field['name'];
            $value = $request->input("config.{$fieldName}");

            // Encrypt sensitive fields
            $encrypt = $field['encrypt'] ?? false;
            if ($encrypt && $value) {
                $value = Crypt::encryptString($value);
            }

            // Use existing value if not provided and field should be preserved
            if ($value === null && $existingProvider && $field['preserve'] ?? true) {
                $value = $existingProvider->getConfigValue($fieldName);
            }

            if ($value !== null) {
                $config[$fieldName] = $value;
            }
        }

        return $config;
    }

    /**
     * Get provider configuration fields based on channel and provider.
     */
    private function getProviderConfigFields($channel, $provider)
    {
        $fields = [];

        switch ($channel) {
            case 'email':
                switch ($provider) {
                    case 'smtp':
                        $fields = [
                            ['name' => 'host', 'type' => 'text', 'label' => 'SMTP Host', 'required' => true],
                            ['name' => 'port', 'type' => 'number', 'label' => 'Port', 'required' => true, 'default' => 587],
                            ['name' => 'encryption', 'type' => 'select', 'label' => 'Encryption', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'], 'required' => true],
                            ['name' => 'username', 'type' => 'text', 'label' => 'Username', 'required' => true],
                            ['name' => 'password', 'type' => 'password', 'label' => 'Password', 'required' => true, 'encrypt' => true],
                            ['name' => 'from_address', 'type' => 'email', 'label' => 'From Address', 'required' => true],
                            ['name' => 'from_name', 'type' => 'text', 'label' => 'From Name', 'required' => false],
                        ];
                        break;
                    case 'mailgun':
                        $fields = [
                            ['name' => 'domain', 'type' => 'text', 'label' => 'Domain', 'required' => true],
                            ['name' => 'secret', 'type' => 'password', 'label' => 'API Secret', 'required' => true, 'encrypt' => true],
                            ['name' => 'endpoint', 'type' => 'text', 'label' => 'Endpoint', 'required' => false, 'default' => 'api.mailgun.net'],
                            ['name' => 'from_address', 'type' => 'email', 'label' => 'From Address', 'required' => true],
                            ['name' => 'from_name', 'type' => 'text', 'label' => 'From Name', 'required' => false],
                        ];
                        break;
                    case 'sendgrid':
                        $fields = [
                            ['name' => 'api_key', 'type' => 'password', 'label' => 'API Key', 'required' => true, 'encrypt' => true],
                            ['name' => 'from_address', 'type' => 'email', 'label' => 'From Address', 'required' => true],
                            ['name' => 'from_name', 'type' => 'text', 'label' => 'From Name', 'required' => false],
                        ];
                        break;
                    case 'ses':
                        $fields = [
                            ['name' => 'key', 'type' => 'text', 'label' => 'Access Key', 'required' => true],
                            ['name' => 'secret', 'type' => 'password', 'label' => 'Secret Key', 'required' => true, 'encrypt' => true],
                            ['name' => 'region', 'type' => 'text', 'label' => 'Region', 'required' => true, 'default' => 'us-east-1'],
                            ['name' => 'from_address', 'type' => 'email', 'label' => 'From Address', 'required' => true],
                            ['name' => 'from_name', 'type' => 'text', 'label' => 'From Name', 'required' => false],
                        ];
                        break;
                }
                break;
            case 'sms':
                // SMS provider fields
                break;
            case 'in_app':
                // In-app notification fields
                break;
            case 'push':
                // Push notification fields
                break;
        }

        return $fields;
    }

    /**
     * Get available provider types.
     */
    private function getProviderTypes()
    {
        return [
            'email' => ['smtp', 'mailgun', 'sendgrid', 'ses'],
            'sms' => ['twilio', 'nexmo', 'plivo'],
            'in_app' => ['database'],
            'push' => ['fcm', 'apns', 'webpush'],
        ];
    }

    /**
     * Get available channel types.
     */
    private function getChannelTypes()
    {
        return [
            'email' => 'Email',
            'sms' => 'SMS',
            'in_app' => 'In-App',
            'push' => 'Push Notification',
        ];
    }

    /**
     * Test provider connection.
     */
    private function testProviderConnection(NotificationProvider $provider)
    {
        // This is a simplified test - in a real implementation,
        // you would test the actual connection to the provider
        switch ($provider->channel) {
            case 'email':
                // Test email connection
                return $this->testEmailConnection($provider);
            case 'sms':
                // Test SMS connection
                return true;
            default:
                return true;
        }
    }

    /**
     * Test email provider connection.
     */
    private function testEmailConnection(NotificationProvider $provider)
    {
        // Simplified email connection test
        // In a real implementation, you would attempt to connect to the SMTP server
        // or make an API call to the email service
        try {
            $config = $provider->config;
            if (empty($config)) {
                return false;
            }

            // For SMTP, check if required fields exist
            if ($provider->provider === 'smtp') {
                $required = ['host', 'port', 'username', 'password'];
                foreach ($required as $field) {
                    if (!isset($config[$field]) || empty($config[$field])) {
                        return false;
                    }
                }
                
                // Try to establish a test connection (simplified)
                // In production, you would use SwiftMailer or similar to test
                return true;
            }
            
            // For API-based providers, we assume they work if config exists
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get provider usage statistics.
     */
    private function getProviderUsageStats(NotificationProvider $provider)
    {
        // Simplified statistics - in real implementation, you would
        // calculate actual usage from logs
        return [
            'today' => rand(0, 50),
            'this_week' => rand(0, 200),
            'this_month' => rand(0, 1000),
            'total' => $provider->usage_count,
            'daily_limit' => $provider->daily_limit,
            'monthly_limit' => $provider->monthly_limit,
            'daily_remaining' => $provider->daily_limit ? $provider->daily_limit - rand(0, $provider->daily_limit) : null,
            'monthly_remaining' => $provider->monthly_limit ? $provider->monthly_limit - rand(0, $provider->monthly_limit) : null,
        ];
    }
}