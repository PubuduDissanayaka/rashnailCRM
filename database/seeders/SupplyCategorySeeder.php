<?php

namespace Database\Seeders;

use App\Models\SupplyCategory;
use Illuminate\Database\Seeder;

class SupplyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Nail Care',
                'slug' => 'nail-care',
                'description' => 'Products for nail treatments and manicures',
                'icon' => 'fas fa-hand-sparkles',
                'sort_order' => 1,
                'children' => [
                    [
                        'name' => 'Polish & Lacquer',
                        'slug' => 'polish-lacquer',
                        'description' => 'Nail polishes and lacquers',
                        'icon' => 'fas fa-paint-brush',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Gel Products',
                        'slug' => 'gel-products',
                        'description' => 'Gel nails and UV/LED products',
                        'icon' => 'fas fa-sun',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Acrylics',
                        'slug' => 'acrylics',
                        'description' => 'Acrylic nail products',
                        'icon' => 'fas fa-hard-hat',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Nail Art Supplies',
                        'slug' => 'nail-art-supplies',
                        'description' => 'Decals, gems, and art tools',
                        'icon' => 'fas fa-gem',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name' => 'Hair Care',
                'slug' => 'hair-care',
                'description' => 'Products for hair treatments and styling',
                'icon' => 'fas fa-cut',
                'sort_order' => 2,
                'children' => [
                    [
                        'name' => 'Shampoos',
                        'slug' => 'shampoos',
                        'description' => 'Hair cleansing products',
                        'icon' => 'fas fa-soap',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Conditioners',
                        'slug' => 'conditioners',
                        'description' => 'Hair conditioning products',
                        'icon' => 'fas fa-spa',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Styling Products',
                        'slug' => 'styling-products',
                        'description' => 'Gels, sprays, and mousses',
                        'icon' => 'fas fa-wind',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Color Products',
                        'slug' => 'color-products',
                        'description' => 'Hair dyes and color treatments',
                        'icon' => 'fas fa-fill-drip',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name' => 'Skin Care',
                'slug' => 'skin-care',
                'description' => 'Products for facial and skin treatments',
                'icon' => 'fas fa-spa',
                'sort_order' => 3,
                'children' => [
                    [
                        'name' => 'Cleansers',
                        'slug' => 'cleansers',
                        'description' => 'Face and skin cleansers',
                        'icon' => 'fas fa-bath',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Moisturizers',
                        'slug' => 'moisturizers',
                        'description' => 'Hydrating creams and lotions',
                        'icon' => 'fas fa-tint',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Masks',
                        'slug' => 'masks',
                        'description' => 'Face masks and treatments',
                        'icon' => 'fas fa-mask',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Treatments',
                        'slug' => 'treatments',
                        'description' => 'Specialized skin treatments',
                        'icon' => 'fas fa-prescription-bottle',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name' => 'Salon Supplies',
                'slug' => 'salon-supplies',
                'description' => 'General salon consumables and equipment',
                'icon' => 'fas fa-store',
                'sort_order' => 4,
                'children' => [
                    [
                        'name' => 'Towels & Linens',
                        'slug' => 'towels-linens',
                        'description' => 'Salon towels and linens',
                        'icon' => 'fas fa-tshirt',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Disposables',
                        'slug' => 'disposables',
                        'description' => 'Single-use items',
                        'icon' => 'fas fa-trash-alt',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Cleaning Products',
                        'slug' => 'cleaning-products',
                        'description' => 'Sanitizers and cleaners',
                        'icon' => 'fas fa-pump-soap',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Tools & Equipment',
                        'slug' => 'tools-equipment',
                        'description' => 'Salon tools and equipment',
                        'icon' => 'fas fa-tools',
                        'sort_order' => 4,
                    ],
                ],
            ],
        ];

        foreach ($categories as $parentData) {
            $children = $parentData['children'];
            unset($parentData['children']);

            $parent = SupplyCategory::create($parentData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                SupplyCategory::create($childData);
            }
        }

        $this->command->info('Supply categories seeded successfully.');
    }
}