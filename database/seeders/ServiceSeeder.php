<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample nail salon services
        $services = [
            [
                'name' => 'Basic Manicure',
                'description' => 'Classic nail care treatment including filing, shaping, cuticle work, and polish application',
                'price' => 25.00,
                'duration' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Deluxe Manicure',
                'description' => 'Luxurious nail treatment with scrub, mask, and massage in addition to classic manicure services',
                'price' => 35.00,
                'duration' => 45,
                'is_active' => true,
            ],
            [
                'name' => 'Gel Manicure',
                'description' => 'Long-lasting gel polish application with LED curing for chip-resistant results',
                'price' => 45.00,
                'duration' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Basic Pedicure',
                'description' => 'Foot care treatment including soaking, exfoliation, nail shaping, cuticle work, and polish',
                'price' => 40.00,
                'duration' => 45,
                'is_active' => true,
            ],
            [
                'name' => 'Deluxe Pedicure',
                'description' => 'Premium foot treatment with hot stones, callus removal, scrub, mask, massage and polish',
                'price' => 55.00,
                'duration' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Gel Pedicure',
                'description' => 'Long-lasting gel polish for feet with LED curing for chip-resistant results',
                'price' => 65.00,
                'duration' => 75,
                'is_active' => true,
            ],
            [
                'name' => 'Acrylic Full Set',
                'description' => 'Full set of acrylic nails applied over natural nails for length and strength',
                'price' => 60.00,
                'duration' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Nail Art',
                'description' => 'Custom nail art designs including stickers, gems, or hand-painted artwork',
                'price' => 15.00,
                'duration' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Nail Repair',
                'description' => 'Repair of broken or damaged artificial nails',
                'price' => 10.00,
                'duration' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Nail Polish Change',
                'description' => 'Quick polish change without full nail preparation',
                'price' => 12.00,
                'duration' => 20,
                'is_active' => true,
            ]
        ];

        foreach ($services as $serviceData) {
            Service::create($serviceData);
        }
    }
}