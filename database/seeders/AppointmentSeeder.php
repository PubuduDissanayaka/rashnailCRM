<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all customers, services, and staff
        $customers = Customer::all();
        $services = Service::where('is_active', true)->get();
        $staff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['administrator', 'staff']);
        })->get();

        // If no customers or services exist, skip seeding
        if ($customers->isEmpty() || $services->isEmpty()) {
            $this->command->warn('Please seed customers and services first!');
            return;
        }

        // If no staff exists, use any user or create a default staff member
        if ($staff->isEmpty()) {
            $staff = User::all();
            if ($staff->isEmpty()) {
                $this->command->warn('No users found. Please seed users first!');
                return;
            }
        }

        // Sample appointments with varied statuses and dates
        $appointments = [
            [
                'appointment_date' => Carbon::now()->addDays(2)->setTime(9, 0),
                'status' => 'scheduled',
                'notes' => 'First appointment of the day',
            ],
            [
                'appointment_date' => Carbon::now()->addDays(3)->setTime(10, 30),
                'status' => 'scheduled',
                'notes' => 'Customer requested specific nail color',
            ],
            [
                'appointment_date' => Carbon::now()->addDays(1)->setTime(14, 0),
                'status' => 'scheduled',
                'notes' => 'VIP customer - prepare special treatment area',
            ],
            [
                'appointment_date' => Carbon::now()->subDays(1)->setTime(11, 0),
                'status' => 'completed',
                'notes' => 'Customer was very satisfied',
            ],
            [
                'appointment_date' => Carbon::now()->subDays(2)->setTime(15, 30),
                'status' => 'completed',
                'notes' => 'Regular customer, prefers gel polish',
            ],
            [
                'appointment_date' => Carbon::now()->setTime(13, 0),
                'status' => 'in_progress',
                'notes' => 'Currently being serviced',
            ],
            [
                'appointment_date' => Carbon::now()->addDays(5)->setTime(16, 0),
                'status' => 'scheduled',
                'notes' => 'Requested French manicure style',
            ],
            [
                'appointment_date' => Carbon::now()->subDays(3)->setTime(10, 0),
                'status' => 'cancelled',
                'notes' => 'Customer called to cancel - family emergency',
            ],
            [
                'appointment_date' => Carbon::now()->addDays(7)->setTime(12, 0),
                'status' => 'scheduled',
                'notes' => 'Wedding preparation appointment',
            ],
            [
                'appointment_date' => Carbon::now()->addDays(4)->setTime(11, 30),
                'status' => 'scheduled',
                'notes' => 'New customer - first visit',
            ],
        ];

        foreach ($appointments as $index => $appointmentData) {
            // Get random customer, service, and staff member
            $customer = $customers->random();
            $service = $services->random();
            $staffMember = $staff->random();

            Appointment::create([
                'customer_id' => $customer->id,
                'user_id' => $staffMember->id,
                'service_id' => $service->id,
                'appointment_date' => $appointmentData['appointment_date'],
                'status' => $appointmentData['status'],
                'notes' => $appointmentData['notes'],
                'slug' => Str::slug('apt-' . Str::random(8)),
            ]);
        }

        $this->command->info('10 sample appointments created successfully!');
    }
}
