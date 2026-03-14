<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'description' => 'Business travel expenses including flights, hotels, and transportation',
                'icon' => 'fas fa-plane',
                'color' => '#3b82f6', // Blue
                'is_active' => true,
                'sort_order' => 1,
                'budget_amount' => 1000.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Office Supplies',
                'slug' => 'office-supplies',
                'description' => 'Office stationery, paper, pens, and other consumables',
                'icon' => 'fas fa-pencil-alt',
                'color' => '#10b981', // Green
                'is_active' => true,
                'sort_order' => 2,
                'budget_amount' => 500.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Software',
                'slug' => 'software',
                'description' => 'Software subscriptions, licenses, and digital tools',
                'icon' => 'fas fa-laptop-code',
                'color' => '#8b5cf6', // Purple
                'is_active' => true,
                'sort_order' => 3,
                'budget_amount' => 300.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'description' => 'Advertising, promotions, and marketing campaigns',
                'icon' => 'fas fa-bullhorn',
                'color' => '#f59e0b', // Amber
                'is_active' => true,
                'sort_order' => 4,
                'budget_amount' => 2000.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Training',
                'slug' => 'training',
                'description' => 'Employee training, courses, and professional development',
                'icon' => 'fas fa-graduation-cap',
                'color' => '#ef4444', // Red
                'is_active' => true,
                'sort_order' => 5,
                'budget_amount' => 800.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Utilities',
                'slug' => 'utilities',
                'description' => 'Electricity, water, internet, and other utility bills',
                'icon' => 'fas fa-bolt',
                'color' => '#06b6d4', // Cyan
                'is_active' => true,
                'sort_order' => 6,
                'budget_amount' => 1500.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Rent',
                'slug' => 'rent',
                'description' => 'Office space rent and lease payments',
                'icon' => 'fas fa-building',
                'color' => '#f97316', // Orange
                'is_active' => true,
                'sort_order' => 7,
                'budget_amount' => 5000.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Equipment',
                'slug' => 'equipment',
                'description' => 'Office equipment, computers, and hardware purchases',
                'icon' => 'fas fa-desktop',
                'color' => '#6366f1', // Indigo
                'is_active' => true,
                'sort_order' => 8,
                'budget_amount' => 3000.00,
                'budget_period' => 'quarterly',
            ],
            [
                'name' => 'Maintenance',
                'slug' => 'maintenance',
                'description' => 'Office maintenance, repairs, and cleaning services',
                'icon' => 'fas fa-tools',
                'color' => '#14b8a6', // Teal
                'is_active' => true,
                'sort_order' => 9,
                'budget_amount' => 1000.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Professional Services',
                'slug' => 'professional-services',
                'description' => 'Legal, accounting, consulting, and other professional fees',
                'icon' => 'fas fa-user-tie',
                'color' => '#8b5cf6', // Purple
                'is_active' => true,
                'sort_order' => 10,
                'budget_amount' => 2000.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Entertainment',
                'slug' => 'entertainment',
                'description' => 'Client entertainment, team building, and business meals',
                'icon' => 'fas fa-utensils',
                'color' => '#ec4899', // Pink
                'is_active' => true,
                'sort_order' => 11,
                'budget_amount' => 800.00,
                'budget_period' => 'monthly',
            ],
            [
                'name' => 'Insurance',
                'slug' => 'insurance',
                'description' => 'Business insurance premiums',
                'icon' => 'fas fa-shield-alt',
                'color' => '#84cc16', // Lime
                'is_active' => true,
                'sort_order' => 12,
                'budget_amount' => 1200.00,
                'budget_period' => 'monthly',
            ],
        ];

        foreach ($categories as $categoryData) {
            ExpenseCategory::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $this->command->info('Expense categories seeded successfully.');
    }
}