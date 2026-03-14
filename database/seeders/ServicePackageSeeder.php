<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServicePackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServicePackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Manicure & Pedicure Combo',
                'description' => 'Complete hand and foot care package. Includes basic manicure and basic pedicure at a discounted rate.',
                'price' => 60.00,
                'base_price' => 65.00,
                'discounted_price' => 60.00,
                'total_duration' => 75,
                'is_active' => true,
            ],
            [
                'name' => 'Monthly Gel Bundle',
                'description' => '4 gel manicure sessions to keep your nails looking fresh all month long. Best value for regular clients.',
                'price' => 160.00,
                'base_price' => 180.00,
                'discounted_price' => 160.00,
                'total_duration' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Bridal Prep Package',
                'description' => 'Complete bridal nail preparation. Includes deluxe manicure, gel pedicure, and nail art for the special day.',
                'price' => 120.00,
                'base_price' => 140.00,
                'discounted_price' => 120.00,
                'total_duration' => 135,
                'is_active' => true,
            ],
            [
                'name' => 'Spa Day Treat',
                'description' => 'Full luxury spa package. Deluxe manicure, deluxe pedicure, and a nail art add-on — perfect for a relaxing day.',
                'price' => 100.00,
                'base_price' => 115.00,
                'discounted_price' => 100.00,
                'total_duration' => 125,
                'is_active' => true,
            ],
            [
                'name' => 'Acrylic Maintenance Plan',
                'description' => '3 acrylic fill or full-set sessions. Ideal for clients maintaining long-term acrylic nails.',
                'price' => 155.00,
                'base_price' => 180.00,
                'discounted_price' => 155.00,
                'total_duration' => 90,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $data) {
            $slug = Str::slug($data['name']);
            $original = $slug;
            $i = 1;
            while (ServicePackage::where('slug', $slug)->exists()) {
                $slug = $original . '-' . $i++;
            }
            $data['slug'] = $slug;
            ServicePackage::firstOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command->info('Service packages seeded successfully.');
    }
}
