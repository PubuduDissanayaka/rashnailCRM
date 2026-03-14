<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Supply;
use App\Models\SupplyUsageLog;
use Illuminate\Support\Facades\DB;

echo "Testing auto-deduction on appointment completion...\n";

// Create test data if needed
try {
    // Get an existing appointment that's not completed
    $appointment = Appointment::where('status', '!=', 'completed')->first();
    
    if (!$appointment) {
        echo "No non-completed appointments found. Creating a test appointment...\n";
        
        // Create a test service with linked supplies
        $service = Service::first();
        if (!$service) {
            $service = Service::create([
                'name' => 'Test Service for Auto-Deduction',
                'description' => 'Test service',
                'price' => 50,
                'duration' => 60,
                'is_active' => true,
            ]);
        }
        
        // Get or create a supply
        $supply = Supply::first();
        if (!$supply) {
            $supply = Supply::create([
                'name' => 'Test Supply',
                'sku' => 'TEST001',
                'description' => 'Test supply for auto-deduction',
                'category_id' => 1,
                'unit_type' => 'pcs',
                'current_stock' => 100,
                'min_stock' => 10,
                'unit_cost' => 5.00,
                'is_active' => true,
            ]);
        }
        
        // Link supply to service
        $service->supplies()->sync([$supply->id => [
            'quantity_required' => 2,
            'is_optional' => false,
        ]]);
        
        // Create a test appointment
        $appointment = Appointment::create([
            'slug' => 'TEST-' . time(),
            'customer_id' => 1,
            'service_id' => $service->id,
            'staff_id' => 1,
            'appointment_date' => now(),
            'duration' => 60,
            'status' => 'scheduled',
            'notes' => 'Test appointment for auto-deduction',
        ]);
        
        echo "Created test appointment ID: {$appointment->id}\n";
        echo "Service ID: {$service->id} with supply ID: {$supply->id}\n";
        echo "Initial supply stock: {$supply->current_stock}\n";
    } else {
        echo "Using existing appointment ID: {$appointment->id}\n";
        echo "Current status: {$appointment->status}\n";
        
        // Check if service has linked supplies
        $service = $appointment->service;
        if ($service) {
            $supplies = $service->supplies;
            if ($supplies->count() > 0) {
                echo "Service has {$supplies->count()} linked supplies.\n";
                foreach ($supplies as $supply) {
                    echo " - Supply: {$supply->name}, Quantity required: " . ($supply->pivot->quantity_required ?? 1) . "\n";
                    echo "   Current stock: {$supply->current_stock}\n";
                }
            } else {
                echo "Service has no linked supplies. Auto-deduction won't happen.\n";
                echo "Creating a test supply and linking it...\n";
                
                $supply = Supply::first();
                if (!$supply) {
                    $supply = Supply::create([
                        'name' => 'Test Supply',
                        'sku' => 'TEST001',
                        'description' => 'Test supply for auto-deduction',
                        'category_id' => 1,
                        'unit_type' => 'pcs',
                        'current_stock' => 100,
                        'min_stock' => 10,
                        'unit_cost' => 5.00,
                        'is_active' => true,
                    ]);
                }
                
                $service->supplies()->sync([$supply->id => [
                    'quantity_required' => 2,
                    'is_optional' => false,
                ]]);
                
                echo "Linked supply ID: {$supply->id} to service.\n";
                echo "Initial supply stock: {$supply->current_stock}\n";
            }
        } else {
            echo "Appointment has no service. Cannot test auto-deduction.\n";
            exit(1);
        }
    }
    
    // Get current usage logs count
    $initialLogsCount = SupplyUsageLog::where('appointment_id', $appointment->id)->count();
    echo "Initial usage logs for appointment: {$initialLogsCount}\n";
    
    // Update appointment status to 'completed' to trigger auto-deduction
    echo "\nUpdating appointment status to 'completed'...\n";
    $appointment->status = 'completed';
    $appointment->save();
    
    // Check if usage logs were created
    $newLogsCount = SupplyUsageLog::where('appointment_id', $appointment->id)->count();
    echo "New usage logs for appointment: {$newLogsCount}\n";
    
    if ($newLogsCount > $initialLogsCount) {
        echo "SUCCESS: Auto-deduction triggered! Created " . ($newLogsCount - $initialLogsCount) . " usage log(s).\n";
        
        // Display created logs
        $newLogs = SupplyUsageLog::where('appointment_id', $appointment->id)
            ->where('id', '>', 0) // Get all logs
            ->get();
            
        foreach ($newLogs as $log) {
            echo "\nUsage Log Details:\n";
            echo " - ID: {$log->id}\n";
            echo " - Supply: " . ($log->supply->name ?? 'N/A') . "\n";
            echo " - Quantity Used: {$log->quantity_used}\n";
            echo " - Unit Cost: $" . number_format($log->unit_cost, 2) . "\n";
            echo " - Total Cost: $" . number_format($log->total_cost, 2) . "\n";
            
            // Check if stock was deducted
            $supply = $log->supply;
            if ($supply) {
                echo " - Supply Stock After Deduction: {$supply->current_stock}\n";
            }
        }
    } else {
        echo "WARNING: No new usage logs created. Auto-deduction may not have triggered.\n";
        
        // Check if observer is registered
        echo "Checking AppointmentObserver registration...\n";
        $observers = \App\Models\Appointment::getObservableEvents();
        echo "Observable events: " . implode(', ', $observers) . "\n";
    }
    
    echo "\nTest completed.\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}