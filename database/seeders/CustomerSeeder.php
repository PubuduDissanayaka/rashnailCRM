<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'first_name' => 'Emma',
                'last_name' => 'Johnson',
                'email' => 'emma.johnson@example.com',
                'phone' => '+1 555-0101',
                'date_of_birth' => '1990-05-15',
                'gender' => 'Female',
                'address' => '123 Main St, New York, NY',
                'notes' => 'VIP customer, prefers gel manicures',
                'status' => 'vip',
            ],
            [
                'first_name' => 'Olivia',
                'last_name' => 'Williams',
                'email' => 'olivia.williams@example.com',
                'phone' => '+1 555-0102',
                'date_of_birth' => '1985-08-22',
                'gender' => 'Female',
                'address' => '456 Oak Ave, Los Angeles, CA',
                'notes' => 'Regular customer, likes French tips',
                'status' => 'active',
            ],
            [
                'first_name' => 'Sophia',
                'last_name' => 'Brown',
                'email' => 'sophia.brown@example.com',
                'phone' => '+1 555-0103',
                'date_of_birth' => '1992-12-03',
                'gender' => 'Female',
                'address' => '789 Pine Rd, Chicago, IL',
                'notes' => 'New customer, interested in acrylic nails',
                'status' => 'active',
            ],
            [
                'first_name' => 'Ava',
                'last_name' => 'Jones',
                'email' => 'ava.jones@example.com',
                'phone' => '+1 555-0104',
                'date_of_birth' => '1988-03-17',
                'gender' => 'Female',
                'address' => '321 Elm St, Miami, FL',
                'notes' => 'Frequent buyer, often books pedicures',
                'status' => 'active',
            ],
            [
                'first_name' => 'Isabella',
                'last_name' => 'Garcia',
                'email' => 'isabella.garcia@example.com',
                'phone' => '+1 555-0105',
                'date_of_birth' => '1995-11-08',
                'gender' => 'Female',
                'address' => '654 Maple Dr, Seattle, WA',
                'notes' => 'Loyal customer, referrals from friends',
                'status' => 'active',
            ],
            [
                'first_name' => 'Mia',
                'last_name' => 'Miller',
                'email' => 'mia.miller@example.com',
                'phone' => '+1 555-0106',
                'date_of_birth' => '1991-07-30',
                'gender' => 'Female',
                'address' => '987 Cedar Ln, Denver, CO',
                'notes' => 'Prefers organic nail products',
                'status' => 'active',
            ],
            [
                'first_name' => 'Charlotte',
                'last_name' => 'Davis',
                'email' => 'charlotte.davis@example.com',
                'phone' => '+1 555-0107',
                'date_of_birth' => '1987-04-12',
                'gender' => 'Female',
                'address' => '147 Birch Way, Austin, TX',
                'notes' => 'Booked for wedding prep next month',
                'status' => 'active',
            ],
            [
                'first_name' => 'Amelia',
                'last_name' => 'Rodriguez',
                'email' => 'amelia.rodriguez@example.com',
                'phone' => '+1 555-0108',
                'date_of_birth' => '1993-09-25',
                'gender' => 'Female',
                'address' => '258 Spruce St, Portland, OR',
                'notes' => 'Student discount applied',
                'status' => 'active',
            ],
            [
                'first_name' => 'Harper',
                'last_name' => 'Martinez',
                'email' => 'harper.martinez@example.com',
                'phone' => '+1 555-0109',
                'date_of_birth' => '1989-01-14',
                'gender' => 'Female',
                'address' => '369 Willow Ave, Boston, MA',
                'notes' => 'Referral from Olivia W., first visit',
                'status' => 'active',
            ],
            [
                'first_name' => 'Evelyn',
                'last_name' => 'Hernandez',
                'email' => 'evelyn.hernandez@example.com',
                'phone' => '+1 555-0110',
                'date_of_birth' => '1994-06-19',
                'gender' => 'Female',
                'address' => '741 Redwood Blvd, San Francisco, CA',
                'notes' => 'VIP customer with special appointments',
                'status' => 'vip',
            ],
        ];

        foreach ($customers as $customerData) {
            // Create a unique slug based on the customer's name and a random string to avoid conflicts
            $slug = Str::slug($customerData['first_name'] . '-' . $customerData['last_name'] . '-' . rand(1000, 9999));
            
            Customer::create([
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'date_of_birth' => $customerData['date_of_birth'],
                'gender' => $customerData['gender'],
                'address' => $customerData['address'],
                'notes' => $customerData['notes'],
                'status' => $customerData['status'] ?? 'active',
                'slug' => $slug,
            ]);
        }
    }
}