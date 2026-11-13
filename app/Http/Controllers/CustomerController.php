<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index(Request $request)
    {
        $this->authorize('view customers');

        $query = Customer::withCount('appointments')
            ->withSum('transactions', 'amount');

        // Profile completion filter
        if ($request->filled('profile')) {
            if ($request->profile === 'complete') {
                $query->complete();
            } elseif ($request->profile === 'incomplete') {
                $query->incomplete();
            }
        }

        $customers = $query->latest()->paginate(10);

        $stats = [
            'total' => Customer::count(),
            'new_this_month' => Customer::whereMonth('created_at', now()->month)->count(),
            'active' => Customer::whereHas('appointments', function($q) {
                $q->where('appointment_date', '>=', now()->subDays(30));
            })->count(),
            'incomplete' => Customer::incomplete()->count(),
        ];

        return view('customers.index', compact('customers', 'stats'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        $this->authorize('create customers');

        $countries = config('countries');
        $defaultCountryCode = \App\Models\Setting::get('business.country_code', '94');

        return view('customers.create', compact('countries', 'defaultCountryCode'));
    }

    /**
     * Store a newly created customer.
     */
    /**
     * Quick-create a customer from the appointment modal (returns JSON).
     */
    public function quickStore(Request $request)
    {
        $this->authorize('create customers');

        $data = $request->validate([
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'phone'       => 'required|string|max:30',
            'email'       => 'nullable|email|unique:customers',
            'joined_date' => 'nullable|date|before_or_equal:today',
        ]);

        $data['phone'] = $this->formatPhoneNumberForStorage($data['phone']);

        // Default joined_date to today if not provided
        if (empty($data['joined_date'])) {
            $data['joined_date'] = now()->toDateString();
        }

        $customer = Customer::create($data);

        return response()->json([
            'success' => true,
            'customer' => [
                'id'        => $customer->id,
                'full_name' => $customer->full_name,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create customers');

        // Normalise BEFORE validating so 'unique' compares the value we store
        if ($request->filled('local_phone')) {
            $request->merge(['phone' => $this->formatPhoneNumberForStorage($request->country_code . $request->local_phone)]);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'country_code' => 'required|string|max:5',
            'local_phone' => 'required|string|max:20',
            'phone' => 'required|string|unique:customers',
            'email' => 'nullable|email|unique:customers',
            'date_of_birth' => 'nullable|date|before:today',
            'joined_date' => 'nullable|date|before_or_equal:today',
            'gender' => 'nullable|in:'.implode(',', ['male', 'female', 'other', 'prefer_not_to_say']),
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ], [
            'local_phone.required' => "Please enter the customer's mobile number.",
            'phone.unique' => 'That mobile number is already saved for another customer. Open that customer instead of adding a new one.',
            'email.unique' => 'That email is already saved for another customer.',
        ]);

        // Prepare the validated data from request
        $validatedData = $request->only([
            'first_name', 'last_name', 'phone', 'email', 'date_of_birth',
            'joined_date', 'gender', 'address', 'notes'
        ]);

        // Format phone number using country code and local phone from request
        $validatedData['phone'] = $this->formatPhoneNumberForStorage($request->country_code . $request->local_phone);

        // Default joined_date to today if not provided
        if (empty($validatedData['joined_date'])) {
            $validatedData['joined_date'] = now()->toDateString();
        }

        // Remove helper fields that are not in the database
        unset($validatedData['country_code']);
        unset($validatedData['local_phone']);

        $customer = Customer::create($validatedData);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Format phone number for WhatsApp-compatible storage (numbers only, no +)
     */
    private function formatPhoneNumberForStorage($phoneNumber)
    {
        return \App\Helpers\PhoneHelper::formatForStorage($phoneNumber);
    }

    /**
     * Extract country code and local number from stored phone
     */
    private function extractPhoneNumber($phone)
    {
        if (!$phone) {
            return ['country_code' => '94', 'local_number' => ''];
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Check against known country codes (check longer codes first)
        $countries = config('countries');

        // Sort by code length descending to match longer codes first (e.g., 971 before 91)
        usort($countries, function($a, $b) {
            return strlen($b['code']) - strlen($a['code']);
        });

        foreach ($countries as $country) {
            if (strpos($phone, $country['code']) === 0) {
                return [
                    'country_code' => $country['code'],
                    'local_number' => substr($phone, strlen($country['code']))
                ];
            }
        }

        // Default: assume Sri Lanka
        return [
            'country_code' => '94',
            'local_number' => $phone
        ];
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        $this->authorize('view customers');

        // Load customer with comprehensive history data
        $customer->load([
            'appointments.service',
            'appointments.user',
        ]);

        // Calculate comprehensive stats
        $stats = [
            'total_appointments' => $customer->totalAppointments(),
            'total_spent' => $customer->totalSpent(),
            'last_visit' => $customer->lastVisit(),
            'favorite_service' => $customer->favoriteService(),
            'total_sales' => $customer->totalSalesCount(),
            'total_bills_paid' => $customer->totalBillsPaid(),
            'last_transaction_date' => $customer->getLastTransactionDate(),
        ];

        // Load appointments, sales, and transactions separately for detailed history
        $appointments = $customer->appointments()
            ->with(['service', 'user'])
            ->orderByDesc('appointment_date')
            ->take(10)
            ->get();

        $sales = $customer->sales()
            ->with(['items.sellable', 'payments'])
            ->orderByDesc('sale_date')
            ->take(10)
            ->get();

        $transactions = $customer->transactions()
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('customers.show', compact('customer', 'stats', 'appointments', 'sales', 'transactions'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        $this->authorize('edit customers');

        $countries = config('countries');
        $defaultCountryCode = \App\Models\Setting::get('business.country_code', '94');

        // Extract country code and local number from stored phone
        $phoneData = $this->extractPhoneNumber($customer->phone);

        return view('customers.edit', compact('customer', 'countries', 'defaultCountryCode', 'phoneData'));
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('edit customers');

        // Normalise BEFORE validating so 'unique' compares the value we store
        if ($request->filled('local_phone')) {
            $request->merge(['phone' => $this->formatPhoneNumberForStorage($request->country_code . $request->local_phone)]);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'country_code' => 'required|string|max:5',
            'local_phone' => 'required|string|max:20',
            'phone' => 'required|string|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'date_of_birth' => 'nullable|date|before:today',
            'joined_date' => 'nullable|date|before_or_equal:today',
            'gender' => 'nullable|in:'.implode(',', ['male', 'female', 'other', 'prefer_not_to_say']),
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ], [
            'local_phone.required' => "Please enter the customer's mobile number.",
            'phone.unique' => 'That mobile number is already saved for another customer. Use a different number or open that customer.',
            'email.unique' => 'That email is already saved for another customer.',
        ]);

        // Prepare the validated data from request
        $validatedData = $request->only([
            'first_name', 'last_name', 'phone', 'email', 'date_of_birth',
            'joined_date', 'gender', 'address', 'notes'
        ]);

        // Format phone number using country code and local phone from request
        $validatedData['phone'] = $this->formatPhoneNumberForStorage($request->country_code . $request->local_phone);

        // Remove helper fields that are not in the database
        unset($validatedData['country_code']);
        unset($validatedData['local_phone']);

        $customer->update($validatedData);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer — permanently.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete customers');

        // Live bookings block deletion — cancel or complete them first
        $pendingCount = $customer->appointments()
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->count();

        if ($pendingCount > 0) {
            return redirect()->back()->with('error', "Cannot delete this customer — they still have {$pendingCount} pending appointment(s). Cancel or complete those first.");
        }

        $appointmentCount = $customer->appointments()->count();
        $name = $customer->full_name;

        // Permanent delete. A soft-deleted row kept its mobile in the unique index, which then
        // blocked re-adding the same number and surfaced as a duplicate-key error at the till.
        $customer->forceDelete();

        $message = "{$name} was deleted permanently.";
        if ($appointmentCount > 0) {
            $message .= " Their {$appointmentCount} past appointment(s) were removed with them.";
        }

        return redirect()->route('customers.index')->with('success', $message);
    }
}