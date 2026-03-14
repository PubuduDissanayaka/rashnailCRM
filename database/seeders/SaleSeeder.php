<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $services = Service::where('is_active', true)->get();
        $packages = ServicePackage::where('is_active', true)->get();
        $staff = User::role('staff')->get();
        $admin = User::where('email', 'admin@rashnail.com')->first();

        if ($customers->isEmpty() || $services->isEmpty()) {
            $this->command->warn('No customers or services found. Run CustomerSeeder and ServiceSeeder first.');
            return;
        }

        $allStaff = $staff->isNotEmpty() ? $staff : collect([$admin]);
        $paymentMethods = ['cash', 'card', 'mobile'];
        $saleCounter = 1;

        // Generate sales over the last 6 months
        $startDate = now()->subMonths(6)->startOfMonth();
        $endDate = now();

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            // Skip Sundays (closed)
            if ($current->dayOfWeek === Carbon::SUNDAY) {
                $current->addDay();
                continue;
            }

            // 3-8 sales per day
            $salesPerDay = rand(3, 8);
            for ($i = 0; $i < $salesPerDay; $i++) {
                // Pick random customer (nullable 30% of the time)
                $customer = rand(1, 10) > 3 ? $customers->random() : null;
                $staffUser = $allStaff->random();

                // Pick 1-3 random services or 1 package
                $items = [];
                $subtotal = 0;

                $usePackage = $packages->isNotEmpty() && rand(0, 4) === 0;
                if ($usePackage) {
                    $pkg = $packages->random();
                    $items[] = [
                        'type' => 'package',
                        'model' => $pkg,
                        'quantity' => 1,
                        'unit_price' => $pkg->price,
                        'item_name' => $pkg->name,
                    ];
                    $subtotal = $pkg->price;
                } else {
                    $serviceCount = rand(1, 3);
                    $selectedServices = $services->random(min($serviceCount, $services->count()));
                    foreach ($selectedServices as $svc) {
                        $qty = 1;
                        $items[] = [
                            'type' => 'service',
                            'model' => $svc,
                            'quantity' => $qty,
                            'unit_price' => $svc->price,
                            'item_name' => $svc->name,
                        ];
                        $subtotal += $svc->price * $qty;
                    }
                }

                $taxAmount = 0;
                $discountAmount = rand(0, 5) === 0 ? round($subtotal * 0.1, 2) : 0;
                $totalAmount = $subtotal - $discountAmount + $taxAmount;

                // Random time between 9am and 6pm
                $saleTime = $current->copy()->setTime(rand(9, 17), rand(0, 59));

                $saleNumber = 'SALE-' . $saleTime->format('Y') . '-' . str_pad($saleCounter++, 5, '0', STR_PAD_LEFT);

                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
                $amountPaid = $totalAmount + (rand(0, 3) === 0 ? rand(1, 50) : 0);
                $changeAmount = max(0, $amountPaid - $totalAmount);

                $sale = Sale::create([
                    'sale_number' => $saleNumber,
                    'customer_id' => $customer?->id,
                    'user_id' => $staffUser->id,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $changeAmount,
                    'status' => 'completed',
                    'sale_type' => 'walk_in',
                    'sale_date' => $saleTime,
                ]);

                // Create sale items
                foreach ($items as $item) {
                    $lineTotal = $item['unit_price'] * $item['quantity'];
                    $sale->items()->create([
                        'sellable_type' => $item['type'] === 'service'
                            ? Service::class
                            : ServicePackage::class,
                        'sellable_id' => $item['model']->id,
                        'item_name' => $item['item_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'line_total' => $lineTotal,
                    ]);
                }

                // Create payment
                $sale->payments()->create([
                    'payment_method' => $paymentMethod,
                    'amount' => $amountPaid,
                    'reference_number' => $paymentMethod !== 'cash' ? strtoupper(substr($paymentMethod, 0, 2)) . rand(100000, 999999) : null,
                    'payment_date' => $saleTime,
                ]);
            }

            $current->addDay();
        }

        $this->command->info("Sales seeded successfully. Total: {$saleCounter} sales.");
    }
}
