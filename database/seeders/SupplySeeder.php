<?php

namespace Database\Seeders;

use App\Models\Supply;
use App\Models\SupplyCategory;
use Illuminate\Database\Seeder;

class SupplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find categories by slug
        $polishCategory = SupplyCategory::where('slug', 'polish-lacquer')->first();
        $removerCategory = SupplyCategory::where('slug', 'nail-care')->first(); // parent category for remover
        $disposablesCategory = SupplyCategory::where('slug', 'disposables')->first();

        $supplies = [
            [
                'name' => 'Nail Polish Red',
                'slug' => 'nail-polish-red',
                'description' => 'Classic red nail polish, 15ml bottle',
                'sku' => 'NP-RED-001',
                'barcode' => '123456789012',
                'category_id' => $polishCategory->id ?? null,
                'brand' => 'BeautyPro',
                'supplier_name' => 'Nail Supply Co.',
                'unit_type' => 'bottle',
                'unit_size' => 15,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'current_stock' => 45,
                'unit_cost' => 5.99,
                'retail_value' => 12.99,
                'is_active' => true,
                'track_expiry' => true,
                'track_batch' => true,
                'usage_per_service' => 1,
                'location' => 'Shelf A1',
                'storage_location' => 'Nail Station',
                'notes' => 'Popular color, reorder when below 15',
            ],
            [
                'name' => 'Acetone',
                'slug' => 'acetone',
                'description' => 'Professional acetone for nail polish removal, 1 liter',
                'sku' => 'AC-REG-001',
                'barcode' => '123456789013',
                'category_id' => $removerCategory->id ?? null,
                'brand' => 'SalonPure',
                'supplier_name' => 'Beauty Distributors',
                'unit_type' => 'liter',
                'unit_size' => 1,
                'min_stock_level' => 2,
                'max_stock_level' => 20,
                'current_stock' => 3.5,
                'unit_cost' => 8.50,
                'retail_value' => 18.99,
                'is_active' => true,
                'track_expiry' => false,
                'track_batch' => false,
                'usage_per_service' => 0.05,
                'location' => 'Storage Room',
                'storage_location' => 'Chemical Shelf',
                'notes' => 'Flammable, store in cool place',
            ],
            [
                'name' => 'Cotton Pads',
                'slug' => 'cotton-pads',
                'description' => '100-count cotton pads for makeup and nail removal',
                'sku' => 'CP-100-001',
                'barcode' => '123456789014',
                'category_id' => $disposablesCategory->id ?? null,
                'brand' => 'SoftTouch',
                'supplier_name' => 'General Supplies Inc.',
                'unit_type' => 'piece',
                'unit_size' => 100,
                'min_stock_level' => 200,
                'max_stock_level' => 1000,
                'current_stock' => 850,
                'unit_cost' => 0.05,
                'retail_value' => 0.15,
                'is_active' => true,
                'track_expiry' => false,
                'track_batch' => false,
                'usage_per_service' => 2,
                'location' => 'Supply Cabinet',
                'storage_location' => 'Drawer 3',
                'notes' => 'High usage item',
            ],
            [
                'name' => 'Gel Top Coat',
                'slug' => 'gel-top-coat',
                'description' => 'UV/LED gel top coat, 30ml',
                'sku' => 'GC-TOP-001',
                'barcode' => '123456789015',
                'category_id' => $polishCategory->id ?? null,
                'brand' => 'GelNails',
                'supplier_name' => 'Nail Supply Co.',
                'unit_type' => 'bottle',
                'unit_size' => 30,
                'min_stock_level' => 5,
                'max_stock_level' => 50,
                'current_stock' => 12,
                'unit_cost' => 9.99,
                'retail_value' => 24.99,
                'is_active' => true,
                'track_expiry' => true,
                'track_batch' => true,
                'usage_per_service' => 1,
                'location' => 'Shelf A2',
                'storage_location' => 'Gel Station',
                'notes' => 'Expires 2 years from manufacture',
            ],
            [
                'name' => 'Shampoo (Moisturizing)',
                'slug' => 'shampoo-moisturizing',
                'description' => 'Moisturizing shampoo for dry hair, 500ml',
                'sku' => 'SH-MOIS-001',
                'barcode' => '123456789016',
                'category_id' => SupplyCategory::where('slug', 'shampoos')->first()->id ?? null,
                'brand' => 'HairCare Plus',
                'supplier_name' => 'Hair Products Ltd.',
                'unit_type' => 'bottle',
                'unit_size' => 500,
                'min_stock_level' => 3,
                'max_stock_level' => 30,
                'current_stock' => 8,
                'unit_cost' => 12.50,
                'retail_value' => 28.00,
                'is_active' => true,
                'track_expiry' => true,
                'track_batch' => true,
                'usage_per_service' => 15,
                'location' => 'Shampoo Station',
                'storage_location' => 'Shelf B1',
                'notes' => 'For dry and damaged hair',
            ],
        ];

        foreach ($supplies as $supplyData) {
            Supply::create($supplyData);
        }

        $this->command->info('Supplies seeded successfully.');
    }
}