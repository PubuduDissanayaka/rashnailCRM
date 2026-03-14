<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
    public function index()
    {
        $this->authorize('view services');

        $services = Service::select('id', 'name', 'slug', 'description', 'price', 'duration', 'is_active', 'created_at', 'updated_at')
            ->get();

        $stats = [
            'total' => Service::count(),
            'active' => Service::where('is_active', true)->count(),
            'inactive' => Service::where('is_active', false)->count(),
        ];

        return view('services.index', compact('services', 'stats'));
    }

    /**
     * Show the form for creating a new service.
     */
    public function create()
    {
        $this->authorize('create services');

        $supplies = Supply::active()->orderBy('name')->get();

        return view('services.create', compact('supplies'));
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create services');

        $request->validate([
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'supplies' => 'nullable|array',
            'supplies.*.id' => 'nullable|exists:supplies,id',
            'supplies.*.quantity_required' => 'nullable|numeric|min:0.01',
            'supplies.*.is_optional' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'duration' => $request->duration,
                'is_active' => $request->filled('is_active') ? $request->is_active : true,
            ]);

            // Attach supplies if provided
            if ($request->has('supplies')) {
                $supplyData = [];
                foreach ($request->supplies as $supply) {
                    if (!empty($supply['id'])) {
                        $supplyData[$supply['id']] = [
                            'quantity_required' => $supply['quantity_required'] ?? 1,
                            'is_optional' => $supply['is_optional'] ?? false,
                        ];
                    }
                }
                $service->supplies()->sync($supplyData);
            }

            DB::commit();
            return redirect()->route('services.index')->with('success', 'Service created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to create service: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service)
    {
        $this->authorize('view services');

        $service->load('supplies');

        return view('services.show', compact('service'));
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Service $service)
    {
        $this->authorize('edit services');

        $supplies = Supply::active()->orderBy('name')->get();
        $service->load('supplies');

        return view('services.edit', compact('service', 'supplies'));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, Service $service)
    {
        $this->authorize('edit services');

        $request->validate([
            'name' => 'required|string|max:255|unique:services,name,' . $service->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'supplies' => 'nullable|array',
            'supplies.*.id' => 'nullable|exists:supplies,id',
            'supplies.*.quantity_required' => 'nullable|numeric|min:0.01',
            'supplies.*.is_optional' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $service->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'duration' => $request->duration,
                'is_active' => $request->filled('is_active') ? $request->is_active : false,
            ]);

            // Update supplies if provided
            if ($request->has('supplies')) {
                $supplyData = [];
                foreach ($request->supplies as $supply) {
                    if (!empty($supply['id'])) {
                        $supplyData[$supply['id']] = [
                            'quantity_required' => $supply['quantity_required'] ?? 1,
                            'is_optional' => $supply['is_optional'] ?? false,
                        ];
                    }
                }
                $service->supplies()->sync($supplyData);
            } else {
                // If no supplies provided, detach all
                $service->supplies()->detach();
            }

            DB::commit();
            return redirect()->route('services.index')->with('success', 'Service updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to update service: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Service $service)
    {
        $this->authorize('delete services');

        // Check if service is used in any appointments
        if ($service->appointments()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete service that is used in appointments.');
        }

        $service->delete();

        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }
}