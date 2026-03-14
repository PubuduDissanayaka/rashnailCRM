<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Roles & Permissions (must be first)
            RoleSeeder::class,

            // Users
            AdminUserSeeder::class,
            StaffSeeder::class,

            // Settings
            SettingsSeeder::class,

            // Customers
            CustomerSeeder::class,

            // Services & Packages
            ServiceSeeder::class,
            ServicePackageSeeder::class,

            // Inventory
            SupplyCategorySeeder::class,
            SupplySeeder::class,

            // Expenses
            ExpenseCategorySeeder::class,
            ExpenseSeeder::class,

            // Appointments
            AppointmentSeeder::class,

            // Sales & Payments (after customers, services, packages, staff)
            SaleSeeder::class,

            // Coupon System (after sales, customers)
            CouponBatchSeeder::class,
            CustomerGroupSeeder::class,
            CouponSeeder::class,
            CouponRedemptionSeeder::class,

            // Attendance (after staff)
            AttendanceSeeder::class,

            // Notifications
            NotificationSeeder::class,
        ]);
    }
}
