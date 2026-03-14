<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. VIP Customers (high-value customers)
        $vipGroup = CustomerGroup::factory()->create([
            'name' => 'VIP Customers',
            'description' => 'High-value customers with significant spending history',
            'criteria' => [
                'min_total_spent' => 1000,
                'min_orders' => 10,
                'has_membership' => true,
            ],
            'is_active' => true,
        ]);
        
        // 2. New Customers (first-time buyers)
        $newGroup = CustomerGroup::factory()->create([
            'name' => 'New Customers',
            'description' => 'First-time buyers within the last 30 days',
            'criteria' => [
                'max_days_since_first_order' => 30,
                'max_orders' => 1,
            ],
            'is_active' => true,
        ]);
        
        // 3. Regular Customers (repeat buyers)
        $regularGroup = CustomerGroup::factory()->create([
            'name' => 'Regular Customers',
            'description' => 'Repeat customers with 3+ purchases',
            'criteria' => [
                'min_orders' => 3,
                'max_days_since_last_order' => 90,
            ],
            'is_active' => true,
        ]);
        
        // 4. Student Discount (with student ID criteria)
        $studentGroup = CustomerGroup::factory()->create([
            'name' => 'Student Discount',
            'description' => 'Customers with valid student ID verification',
            'criteria' => [
                'requires_student_id' => true,
                'max_age' => 30,
            ],
            'is_active' => true,
        ]);
        
        // 5. Birthday Club (for birthday promotions)
        $birthdayGroup = CustomerGroup::factory()->create([
            'name' => 'Birthday Club',
            'description' => 'Customers who have provided their birth date for birthday promotions',
            'criteria' => [
                'has_birthday' => true,
                'days_before_birthday' => 7,
            ],
            'is_active' => true,
        ]);
        
        // Additional generic groups for variety
        CustomerGroup::factory()->create([
            'name' => 'Loyalty Members',
            'description' => 'Customers enrolled in loyalty program',
            'criteria' => [
                'loyalty_tier' => 'gold',
                'points_balance' => 500,
            ],
        ]);
        
        CustomerGroup::factory()->create([
            'name' => 'Inactive Customers',
            'description' => 'Customers with no purchases in last 12 months',
            'criteria' => [
                'min_days_since_last_order' => 365,
            ],
            'is_active' => false,
        ]);
        
        CustomerGroup::factory()->create([
            'name' => 'High Risk',
            'description' => 'Customers with past payment issues',
            'criteria' => [
                'has_payment_failures' => true,
                'max_failures' => 3,
            ],
            'is_active' => true,
        ]);
        
        // Create some random groups
        CustomerGroup::factory()->count(5)->create();
        
        // Attach customers to groups (sample relationships)
        $customers = Customer::inRandomOrder()->take(10)->get();
        
        foreach ($customers as $customer) {
            // Randomly assign to VIP group (30% chance)
            if (rand(1, 100) <= 30) {
                $vipGroup->customers()->syncWithoutDetaching([$customer->id]);
            }
            
            // Assign to New Customers group if customer created recently
            if ($customer->created_at->diffInDays(now()) <= 30 && rand(1, 100) <= 50) {
                $newGroup->customers()->syncWithoutDetaching([$customer->id]);
            }
            
            // Assign to Regular Customers group if customer has purchases
            if ($customer->sales()->count() >= 3 && rand(1, 100) <= 40) {
                $regularGroup->customers()->syncWithoutDetaching([$customer->id]);
            }
        }
        
        $this->command->info('Customer groups seeded successfully.');
    }
}