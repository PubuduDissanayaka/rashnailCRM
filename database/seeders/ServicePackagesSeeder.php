<?php

namespace Database\Seeders;

use App\Models\ServicePackage as ServicePackageModel; // Use alias to avoid confusion
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServicePackagesSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        // First, make sure we have services in the database
        $services = Service::all();
        if ($services->count() == 0) {
            echo "No services found in database. Please seed services first.\n";
            return;
        }

        // Create service packages using the current schema
        $packages = [
            [
                'name' => 'Basic Spa Package',
                'description' => 'Includes Basic Manicure and Basic Pedicure',
                'price' => 65.00,
                'base_price' => 70.00, // Combined individual price would be higher
                'discounted_price' => 65.00,
                'discount_percentage' => 7.14,
                'duration' => 75, // 30 (manicure) + 45 (pedicure)
                'total_duration' => 75, // Legacy field
                'included_services' => json_encode([1, 4]), // Basic Manicure and Basic Pedicure
                'session_count' => 1,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Deluxe Spa Experience',
                'description' => 'Includes Deluxe Manicure and Deluxe Pedicure',
                'price' => 85.00,
                'base_price' => 90.00, // Combined individual price would be higher
                'discounted_price' => 85.00,
                'discount_percentage' => 5.56,
                'duration' => 105, // 45 (deluxe manicure) + 60 (deluxe pedicure)
                'total_duration' => 105, // Legacy field
                'included_services' => json_encode([2, 5]), // Deluxe Manicure and Deluxe Pedicure
                'session_count' => 1,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Gel Treatment Special',
                'description' => 'Includes Gel Manicure and Gel Pedicure',
                'price' => 105.00,
                'base_price' => 110.00, // Combined individual price would be higher
                'discounted_price' => 105.00,
                'discount_percentage' => 4.55,
                'duration' => 135, // 60 (gel manicure) + 75 (gel pedicure)
                'total_duration' => 135, // Legacy field
                'included_services' => json_encode([3, 6]), // Gel Manicure and Gel Pedicure
                'session_count' => 1,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Acrylic Full Set Special',
                'description' => 'Acrylic Full Set with Nail Art Design',
                'price' => 70.00,
                'base_price' => 75.00, // Combined individual price would be higher
                'discounted_price' => 70.00,
                'discount_percentage' => 6.67,
                'duration' => 110, // 90 (acrylic set) + 20 (nail art)
                'total_duration' => 110, // Legacy field
                'included_services' => json_encode([7, 8]), // Acrylic Full Set and Nail Art
                'session_count' => 1,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Express Treatment Package',
                'description' => 'Choose any 3 services from our menu',
                'price' => 95.00,
                'base_price' => 100.00, // Flexible package base price
                'discounted_price' => 95.00,
                'discount_percentage' => 5.00,
                'duration' => 120, // Average duration for 3 services
                'total_duration' => 120, // Legacy field
                'included_services' => json_encode([]), // Will be populated when purchased
                'session_count' => 1,
                'validity_days' => 45,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Monthly Manicure Club',
                'description' => '4 Basic Manicures per month for a set price',
                'price' => 100.00, // Discounted monthly rate for 4 manicures
                'base_price' => 120.00, // Regular price for 4 basic manicures (4 x $30)
                'discounted_price' => 100.00,
                'discount_percentage' => 16.67,
                'duration' => 30, // Each session is 30 minutes
                'total_duration' => 30, // Legacy field
                'included_services' => json_encode([1]), // Basic Manicure (service ID 1)
                'session_count' => 4,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Pedicure Monthly Plan',
                'description' => '4 Pedicures per month for a set price',
                'price' => 180.00, // Discounted monthly rate for 4 pedicures
                'base_price' => 220.00, // Regular price for 4 basic pedicures (4 x $55)
                'discounted_price' => 180.00,
                'discount_percentage' => 18.18,
                'duration' => 45, // Each session is 45 minutes
                'total_duration' => 45, // Legacy field
                'included_services' => json_encode([4]), // Basic Pedicure (service ID 4)
                'session_count' => 4,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ]
        ];

        foreach ($packages as $packageData) {
            // Calculate discount percentage if not already provided
            if (!isset($packageData['discount_percentage']) && $packageData['base_price'] > 0) {
                $packageData['discount_percentage'] = round(((($packageData['base_price'] - $packageData['discounted_price']) / $packageData['base_price']) * 100), 2);
            }

            ServicePackageModel::updateOrCreate(
                ['name' => $packageData['name']], // Unique identifier for update
                [
                    'name' => $packageData['name'],
                    'description' => $packageData['description'],
                    'price' => $packageData['price'],
                    'base_price' => $packageData['base_price'],
                    'discounted_price' => $packageData['discounted_price'],
                    'discount_percentage' => $packageData['discount_percentage'],
                    'duration' => $packageData['duration'], // New schema field
                    'total_duration' => $packageData['total_duration'], // Legacy field
                    'included_services' => $packageData['included_services'], // New schema field
                    'session_count' => $packageData['session_count'], // New schema field
                    'validity_days' => $packageData['validity_days'], // New schema field
                    'is_available_for_sale' => $packageData['is_available_for_sale'], // New schema field
                    'is_active' => $packageData['is_active'],
                    'image' => null, // Could add images later
                    'slug' => Str::slug($packageData['name']) . '-' . Str::random(6),
                    'category_id' => null, // Could assign to categories later
                ]
            );
        }

        echo "Service Packages seeder completed!\n";
    }
}