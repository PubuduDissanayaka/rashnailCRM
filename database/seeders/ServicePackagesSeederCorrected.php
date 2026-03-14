<?php

namespace Database\Seeders;

use App\Models\ServicePackage as ServicePackageModel;
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
        // Create some service packages based on the available services
        $packages = [
            [
                'name' => 'Basic Spa Package',
                'description' => 'Includes Basic Manicure and Basic Pedicure',
                'price' => 65.00, // Combined price with small discount
                'duration' => 75, // Sum of individual durations
                'included_services' => [1, 4], // Basic Manicure and Basic Pedicure (assumes services with IDs 1 and 4)
                'session_count' => 1,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Deluxe Spa Package',
                'description' => 'Includes Deluxe Manicure and Deluxe Pedicure',
                'price' => 85.00, // Combined price with small discount
                'duration' => 105, // Sum of individual durations
                'included_services' => [2, 5], // Deluxe Manicure and Deluxe Pedicure (assumes services with IDs 2 and 5)
                'session_count' => 1,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Gel Treatment Package',
                'description' => 'Includes Gel Manicure and Gel Pedicure',
                'price' => 105.00, // Combined price with small discount
                'duration' => 135, // Sum of individual durations
                'included_services' => [3, 6], // Gel Manicure and Gel Pedicure (assumes services with IDs 3 and 6)
                'session_count' => 1,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Acrylic Full Set Special',
                'description' => 'Acrylic Full Set with Nail Art Design',
                'price' => 70.00, // Combined price with small discount
                'duration' => 110, // Sum of individual durations
                'included_services' => [7, 8], // Acrylic Full Set and Nail Art (assumes services with IDs 7 and 8)
                'session_count' => 1,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Express Treatment',
                'description' => 'Choose any 3 services from our menu',
                'price' => 95.00, // Flexible package
                'duration' => 120, // Average duration for 3 services
                'included_services' => [], // Will be populated when purchased
                'session_count' => 1,
                'validity_days' => 45,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Monthly Manicure Club',
                'description' => '4 Manicures per month for a set price',
                'price' => 120.00, // 4 basic manicures at discounted rate
                'duration' => 30, // Each session is 30 mins
                'included_services' => [1], // Basic Manicure (assumes service with ID 1)
                'session_count' => 4,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Pedicure Monthly Plan',
                'description' => '4 Pedicures per month for a set price',
                'price' => 180.00, // 4 basic pedicures at discounted rate
                'duration' => 45, // Each session is 45 mins
                'included_services' => [4], // Basic Pedicure (assumes service with ID 4)
                'session_count' => 4,
                'validity_days' => 30,
                'is_available_for_sale' => true,
                'is_active' => true,
            ]
        ];

        foreach ($packages as $packageData) {
            ServicePackageModel::create([
                'name' => $packageData['name'],
                'description' => $packageData['description'],
                'price' => $packageData['price'],
                'duration' => $packageData['duration'],
                'included_services' => json_encode($packageData['included_services']),
                'session_count' => $packageData['session_count'],
                'validity_days' => $packageData['validity_days'],
                'is_available_for_sale' => $packageData['is_available_for_sale'],
                'is_active' => $packageData['is_active'],
                'image' => null, // Could add images later
                'slug' => Str::slug($packageData['name']) . '-' . Str::random(6),
                'category_id' => null, // Could assign to categories later
            ]);
        }

        echo "Service Packages seeder completed!\n";
    }
}