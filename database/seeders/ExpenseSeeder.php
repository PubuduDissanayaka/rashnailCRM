<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@rashnail.com')->first();

        if (!$admin) {
            $this->command->warn('Admin user not found. Run AdminUserSeeder first.');
            return;
        }

        $categories = ExpenseCategory::all()->keyBy('slug');
        $expenseCounter = 1;

        $expenses = [
            // Recurring monthly fixed costs — last 6 months
            ...array_map(fn($m) => [
                'title' => 'Monthly Rent',
                'category_slug' => 'rent',
                'amount' => 3500.00,
                'payment_method' => 'bank_transfer',
                'status' => 'paid',
                'expense_date' => now()->subMonths($m)->startOfMonth()->toDateString(),
                'paid_date' => now()->subMonths($m)->startOfMonth()->addDays(2)->toDateString(),
            ], range(0, 5)),

            ...array_map(fn($m) => [
                'title' => 'Electricity Bill',
                'category_slug' => 'utilities',
                'amount' => round(rand(120, 250) + rand(0, 99) / 100, 2),
                'payment_method' => 'cash',
                'status' => 'paid',
                'expense_date' => now()->subMonths($m)->startOfMonth()->addDays(5)->toDateString(),
                'paid_date' => now()->subMonths($m)->startOfMonth()->addDays(7)->toDateString(),
            ], range(0, 5)),

            ...array_map(fn($m) => [
                'title' => 'Internet & Wi-Fi',
                'category_slug' => 'utilities',
                'amount' => 45.00,
                'payment_method' => 'bank_transfer',
                'status' => 'paid',
                'expense_date' => now()->subMonths($m)->startOfMonth()->addDays(3)->toDateString(),
                'paid_date' => now()->subMonths($m)->startOfMonth()->addDays(4)->toDateString(),
            ], range(0, 5)),

            // Variable expenses
            [
                'title' => 'Nail Polish Stock Replenishment',
                'category_slug' => 'office-supplies',
                'amount' => 280.00,
                'payment_method' => 'cash',
                'status' => 'paid',
                'expense_date' => now()->subMonths(1)->toDateString(),
                'paid_date' => now()->subMonths(1)->toDateString(),
            ],
            [
                'title' => 'Gel & UV Products',
                'category_slug' => 'office-supplies',
                'amount' => 350.00,
                'payment_method' => 'card',
                'status' => 'paid',
                'expense_date' => now()->subWeeks(3)->toDateString(),
                'paid_date' => now()->subWeeks(3)->toDateString(),
            ],
            [
                'title' => 'Staff Uniforms',
                'category_slug' => 'office-supplies',
                'amount' => 150.00,
                'payment_method' => 'cash',
                'status' => 'paid',
                'expense_date' => now()->subMonths(2)->toDateString(),
                'paid_date' => now()->subMonths(2)->toDateString(),
            ],
            [
                'title' => 'UV Lamp Replacement',
                'category_slug' => 'equipment',
                'amount' => 85.00,
                'payment_method' => 'card',
                'status' => 'paid',
                'expense_date' => now()->subMonths(3)->toDateString(),
                'paid_date' => now()->subMonths(3)->toDateString(),
            ],
            [
                'title' => 'Nail Drill Machine',
                'category_slug' => 'equipment',
                'amount' => 220.00,
                'payment_method' => 'card',
                'status' => 'paid',
                'expense_date' => now()->subMonths(4)->toDateString(),
                'paid_date' => now()->subMonths(4)->toDateString(),
            ],
            [
                'title' => 'Social Media Advertising',
                'category_slug' => 'marketing',
                'amount' => 150.00,
                'payment_method' => 'card',
                'status' => 'paid',
                'expense_date' => now()->subMonth()->toDateString(),
                'paid_date' => now()->subMonth()->toDateString(),
            ],
            [
                'title' => 'Promotional Flyers Printing',
                'category_slug' => 'marketing',
                'amount' => 80.00,
                'payment_method' => 'cash',
                'status' => 'paid',
                'expense_date' => now()->subMonths(2)->toDateString(),
                'paid_date' => now()->subMonths(2)->toDateString(),
            ],
            [
                'title' => 'Nail Technician Training Workshop',
                'category_slug' => 'training',
                'amount' => 400.00,
                'payment_method' => 'bank_transfer',
                'status' => 'paid',
                'expense_date' => now()->subMonths(3)->toDateString(),
                'paid_date' => now()->subMonths(3)->toDateString(),
            ],
            [
                'title' => 'Salon Cleaning Service',
                'category_slug' => 'maintenance',
                'amount' => 120.00,
                'payment_method' => 'cash',
                'status' => 'paid',
                'expense_date' => now()->subWeeks(2)->toDateString(),
                'paid_date' => now()->subWeeks(2)->toDateString(),
            ],
            [
                'title' => 'Accounting Software Subscription',
                'category_slug' => 'software',
                'amount' => 29.00,
                'payment_method' => 'card',
                'status' => 'paid',
                'expense_date' => now()->subMonth()->toDateString(),
                'paid_date' => now()->subMonth()->toDateString(),
            ],
            // Pending expenses (current month)
            [
                'title' => 'New Reception Chair',
                'category_slug' => 'equipment',
                'amount' => 350.00,
                'payment_method' => null,
                'status' => 'pending',
                'expense_date' => now()->toDateString(),
            ],
            [
                'title' => 'Business Insurance Premium',
                'category_slug' => 'insurance',
                'amount' => 200.00,
                'payment_method' => null,
                'status' => 'pending',
                'expense_date' => now()->addDays(5)->toDateString(),
            ],
            [
                'title' => 'Staff Team Lunch',
                'category_slug' => 'entertainment',
                'amount' => 95.00,
                'payment_method' => 'cash',
                'status' => 'approved',
                'expense_date' => now()->subDays(3)->toDateString(),
            ],
        ];

        foreach ($expenses as $data) {
            $cat = isset($data['category_slug']) ? ($categories[$data['category_slug']] ?? null) : null;
            $amount = $data['amount'];
            $totalAmount = $amount; // no tax for simplicity

            $expenseNumber = 'EXP-' . now()->format('Y') . '-' . str_pad($expenseCounter++, 5, '0', STR_PAD_LEFT);

            Expense::create([
                'expense_number' => $expenseNumber,
                'title' => $data['title'],
                'category_id' => $cat?->id,
                'amount' => $amount,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'LKR',
                'payment_method' => $data['payment_method'] ?? null,
                'expense_date' => $data['expense_date'],
                'paid_date' => $data['paid_date'] ?? null,
                'status' => $data['status'],
                'created_by' => $admin->id,
                'approved_by' => in_array($data['status'], ['approved', 'paid']) ? $admin->id : null,
                'approved_at' => in_array($data['status'], ['approved', 'paid']) ? now()->subDays(rand(1, 5)) : null,
            ]);
        }

        $this->command->info("Expenses seeded successfully. Total: {$expenseCounter} expenses.");
    }
}
