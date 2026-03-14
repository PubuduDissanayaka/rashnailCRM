<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServicePackageController extends Controller
{
    /**
     * Display a listing of the service packages.
     */
    public function index()
    {
        $this->authorize('view service packages');

        $packages = ServicePackage::with(['services'])->withCount('services')->get();

        $stats = [
            'total' => ServicePackage::count(),
            'active' => ServicePackage::where('is_active', true)->count(),
            'inactive' => ServicePackage::where('is_active', false)->count(),
            'total_savings' => ServicePackage::sum(DB::raw('base_price - discounted_price')),
        ];

        return view('service-packages.index', compact('packages', 'stats'));
    }

    /**
     * Show the form for creating a new service package.
     */
    public function create()
    {
        $this->authorize('create service packages');

        $services = Service::where('is_active', true)->get();

        return view('service-packages.create', compact('services'));
    }

    /**
     * Store a newly created service package in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create service packages');

        $request->validate([
            'name' => 'required|string|max:255|unique:service_packages,name',
            'description' => 'nullable|string',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'base_price' => 'required|numeric|min:0',
            'discounted_price' => 'required|numeric|min:0|lte:base_price',
            'is_active' => 'boolean',
        ]);

        // Validate quantities if provided
        if ($request->has('quantities')) {
            $request->validate([
                'quantities.*' => 'integer|min:1',
            ]);
        }

        DB::beginTransaction();
        try {
            $package = ServicePackage::create([
                'name' => $request->name,
                'description' => $request->description,
                'base_price' => $request->base_price,
                'discounted_price' => $request->discounted_price,
                'discount_percentage' => $request->base_price > 0 ?
                    round((($request->base_price - $request->discounted_price) / $request->base_price) * 100, 2) : 0,
                'total_duration' => $this->calculateTotalDuration($request->services, $request->quantities),
                'is_active' => $request->filled('is_active') ? $request->is_active : true,
            ]);

            // Attach services to the package with quantities
            $servicesData = [];
            foreach ($request->services as $serviceId) {
                $quantity = 1; // Default quantity

                // improved quantity resolution
                if ($request->has('quantities')) {
                    if (isset($request->quantities[$serviceId])) {
                         $quantity = intval($request->quantities[$serviceId]);
                    }
                }

                $servicesData[$serviceId] = [
                    'quantity' => $quantity,
                    'sort_order' => 0 
                ];
            }

            $package->services()->attach($servicesData);

            DB::commit();

            return redirect()->route('service-packages.index')->with('success', 'Service package created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Failed to create service package: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified service package.
     */
    public function show(ServicePackage $servicePackage)
    {
        $this->authorize('view service packages');

        // Since we're using route model binding with slug, $servicePackage is already the correct instance
        $servicePackage->load('services');

        return view('service-packages.show', compact('servicePackage'));
    }

    /**
     * Show the form for editing the specified service package.
     */
    public function edit(ServicePackage $servicePackage)
    {
        $this->authorize('edit service packages');

        $services = Service::where('is_active', true)->get();
        $selectedServices = $servicePackage->services->pluck('id')->toArray();
        $serviceQuantities = [];

        foreach ($servicePackage->services as $service) {
            $serviceQuantities[$service->id] = $service->pivot->quantity;
        }

        return view('service-packages.edit', compact('servicePackage', 'services', 'selectedServices', 'serviceQuantities'));
    }

    /**
     * Update the specified service package in storage.
     */
    public function update(Request $request, ServicePackage $servicePackage)
    {
        $this->authorize('edit service packages');

        $request->validate([
            'name' => 'required|string|max:255|unique:service_packages,name,' . $servicePackage->id,
            'description' => 'nullable|string',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'base_price' => 'required|numeric|min:0',
            'discounted_price' => 'required|numeric|min:0|lte:base_price',
            'is_active' => 'boolean',
        ]);

        // Validate quantities if provided
        if ($request->has('quantities')) {
            $request->validate([
                'quantities.*' => 'integer|min:1',
            ]);
        }

        DB::beginTransaction();
        try {
            $servicePackage->update([
                'name' => $request->name,
                'description' => $request->description,
                'base_price' => $request->base_price,
                'discounted_price' => $request->discounted_price,
                'discount_percentage' => $request->base_price > 0 ?
                    round((($request->base_price - $request->discounted_price) / $request->base_price) * 100, 2) : 0,
                'total_duration' => $this->calculateTotalDuration($request->services, $request->quantities),
                'is_active' => $request->filled('is_active') ? $request->is_active : $servicePackage->is_active,
            ]);

            // Sync services with quantities
            $servicesData = [];
            foreach ($request->services as $serviceId) {
                $quantity = 1; // Default quantity

                // improved quantity resolution
                if ($request->has('quantities')) {
                    if (isset($request->quantities[$serviceId])) {
                         $quantity = intval($request->quantities[$serviceId]);
                    }
                }

                $servicesData[$serviceId] = [
                    'quantity' => $quantity,
                    'sort_order' => 0
                ];
            }

            $servicePackage->services()->sync($servicesData);

            DB::commit();

            return redirect()->route('service-packages.index')->with('success', 'Service package updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Failed to update service package: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified service package from storage.
     */
    public function destroy(ServicePackage $servicePackage)
    {
        $this->authorize('delete service packages');

        $servicePackage->delete();

        return redirect()->route('service-packages.index')->with('success', 'Service package deleted successfully.');
    }

    /**
     * Calculate total duration for services in a package
     */
    private function calculateTotalDuration($serviceIds, $quantities)
    {
        $totalDuration = 0;
        
        if (!is_array($serviceIds)) {
            return 0;
        }

        foreach ($serviceIds as $serviceId) {
            $service = Service::find($serviceId);
            if ($service) {
                $quantity = 1;
                if (isset($quantities) && isset($quantities[$serviceId])) {
                    $quantity = intval($quantities[$serviceId]);
                }
                $totalDuration += $service->duration * $quantity;
            }
        }
        
        return $totalDuration;
    }
}