<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global quick search — returns JSON results.
     */
    public function quickSearch(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json(['results' => []]);
        }

        $term = '%' . $query . '%';

        // Search customers by name or phone
        $customers = Customer::where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('phone', 'like', $term);
            })
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'type' => 'Customer',
                'label' => $c->name,
                'sub'   => $c->phone,
                'url'   => route('customers.show', $c->slug),
                'icon'  => 'ti ti-user',
            ]);

        // Search appointments by customer name or id
        $appointments = Appointment::whereHas('customer', function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('phone', 'like', $term);
            })
            ->with('customer')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'type' => 'Appointment',
                'label' => '#' . $a->id . ' — ' . ($a->customer?->name ?? 'N/A'),
                'sub'   => $a->appointment_date?->format('M d, H:i') ?? '',
                'url'   => route('appointments.show', $a->slug),
                'icon'  => 'ti ti-calendar-event',
            ]);

        // Search services by name
        $services = Service::where('name', 'like', $term)
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'type' => 'Service',
                'label' => $s->name,
                'sub'   => $s->duration . ' min',
                'url'   => route('services.edit', $s->id),
                'icon'  => 'ti ti-scissors',
            ]);

        // Search users by name or email
        $users = User::where('name', 'like', $term)
            ->orWhere('email', 'like', $term)
            ->limit(5)
            ->get()
            ->map(fn ($u) => [
                'type' => 'User',
                'label' => $u->name,
                'sub'   => $u->email,
                'url'   => route('users.edit', $u->id),
                'icon'  => 'ti ti-users',
            ]);

        $results = collect()
            ->merge($customers)
            ->merge($appointments)
            ->merge($services)
            ->merge($users)
            ->take(10);

        return response()->json(['results' => $results]);
    }
}
