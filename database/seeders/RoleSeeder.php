<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Customer permissions ──────────────────────────────
        $this->seed([
            'view customers', 'create customers', 'edit customers', 'delete customers',
        ]);

        // ── Appointment permissions ───────────────────────────
        $this->seed([
            'view appointments', 'create appointments', 'edit appointments',
            'delete appointments', 'manage all appointments',
        ]);

        // ── Service permissions ───────────────────────────────
        $this->seed([
            'view services', 'create services', 'edit services', 'delete services',
        ]);

        // ── Service package permissions ───────────────────────
        $this->seed([
            'view service packages', 'create service packages',
            'edit service packages', 'delete service packages',
        ]);

        // ── Transaction permissions ───────────────────────────
        $this->seed([
            'process transactions', 'view transactions', 'delete transactions',
        ]);

        // ── Invoice permissions ────────────────────────────
        $this->seed([
            'view invoices', 'create invoices', 'edit invoices', 'delete invoices',
        ]);

        // ── POS permissions ───────────────────────────────────
        $this->seed([
            'view pos', 'create pos transactions', 'edit pos transactions', 'manage pos',
        ]);

        // ── User management permissions ───────────────────────
        $this->seed([
            'view users', 'create users', 'edit users', 'delete users',
        ]);

        // ── Attendance permissions ────────────────────────────
        $this->seed([
            'view attendances', 'edit attendances', 'manage attendances',
        ]);

        // ── Work schedule permissions ─────────────────────────
        $this->seed([
            'view work schedules', 'manage work schedules',
        ]);

        // ── Work hour report permissions ──────────────────────
        $this->seed([
            'view work hour reports', 'export work hour reports',
        ]);

        // ── Leave management permissions ──────────────────────
        $this->seed([
            'view leave requests', 'create leave requests', 'approve leave requests',
            'view leave balances', 'manage leave balances',
        ]);

        // ── Coupon permissions ────────────────────────────────
        $this->seed([
            'view coupons', 'create coupons', 'edit coupons',
            'delete coupons', 'manage coupon batches',
        ]);

        // ── Inventory permissions ─────────────────────────────
        $this->seed([
            'inventory.view', 'inventory.manage',
            'inventory.supplies.create', 'inventory.supplies.edit',
            'inventory.supplies.delete', 'inventory.supplies.adjust',
            'inventory.usage.create',
            'inventory.purchase.create', 'inventory.purchase.approve',
            'inventory.purchase.receive',
            'inventory.reports.view', 'inventory.alerts.manage',
        ]);

        // ── Expense permissions ───────────────────────────────
        $this->seed([
            'expenses.view', 'expenses.create', 'expenses.edit',
            'expenses.delete', 'expenses.approve', 'expenses.manage',
        ]);

        // ── Reporting & system permissions ────────────────────
        $this->seed([
            'view reports', 'export reports', 'manage system',
        ]);

        // ─────────────────────────────────────────────────────────
        // ROLE: Administrator — full system access
        // ─────────────────────────────────────────────────────────
        $administrator = Role::firstOrCreate(
            ['name' => 'administrator', 'guard_name' => 'web']
        );
        $administrator->syncPermissions(Permission::all());

        // ─────────────────────────────────────────────────────────
        // ROLE: Manager — day-to-day operations, staff oversight
        //      Everything except 'manage system' (settings, themes)
        // ─────────────────────────────────────────────────────────
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            // Customers
            'view customers', 'create customers', 'edit customers', 'delete customers',
            // Appointments
            'view appointments', 'create appointments', 'edit appointments',
            'delete appointments', 'manage all appointments',
            // Services & Packages
            'view services', 'create services', 'edit services', 'delete services',
            'view service packages', 'create service packages',
            'edit service packages', 'delete service packages',
            // Transactions & POS
            'process transactions', 'view transactions', 'delete transactions',
            'view pos', 'create pos transactions', 'edit pos transactions', 'manage pos',
            // Invoices
            'view invoices', 'create invoices', 'edit invoices', 'delete invoices',
            // Users & Staff
            'view users', 'create users', 'edit users', 'delete users',
            // Attendance
            'view attendances', 'edit attendances', 'manage attendances',
            // Work Schedules
            'view work schedules', 'manage work schedules',
            // Work Hour Reports
            'view work hour reports', 'export work hour reports',
            // Leave Management
            'view leave requests', 'create leave requests', 'approve leave requests',
            'view leave balances', 'manage leave balances',
            // Coupons
            'view coupons', 'create coupons', 'edit coupons',
            'delete coupons', 'manage coupon batches',
            // Inventory
            'inventory.view', 'inventory.manage',
            'inventory.supplies.create', 'inventory.supplies.edit',
            'inventory.supplies.delete', 'inventory.supplies.adjust',
            'inventory.usage.create',
            'inventory.purchase.create', 'inventory.purchase.approve',
            'inventory.purchase.receive',
            'inventory.reports.view', 'inventory.alerts.manage',
            // Expenses
            'expenses.view', 'expenses.create', 'expenses.edit',
            'expenses.delete', 'expenses.approve', 'expenses.manage',
            // Reports
            'view reports', 'export reports',
            // System — deliberately excluded
        ]);

        // ─────────────────────────────────────────────────────────
        // ROLE: Receptionist — front desk operations
        //      Manage appointments, customers, process POS sales
        //      View services/packages. Read-only on inventory.
        // ─────────────────────────────────────────────────────────
        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $receptionist->syncPermissions([
            // Customers (CRUD except delete)
            'view customers', 'create customers', 'edit customers',
            // Appointments (CRUD except delete)
            'view appointments', 'create appointments', 'edit appointments',
            // Services (read-only for dropdowns)
            'view services',
            // Service Packages (read-only)
            'view service packages',
            // POS (process sales, view transactions)
            'view pos', 'create pos transactions', 'view transactions',
            // Attendance (clock in/out)
            'view attendances',
            // Leave (request own leave)
            'view leave requests', 'create leave requests',
            'view leave balances',
            // Inventory (read-only view of stock)
            'inventory.view',
            // Reports (view basic)
            'view reports',
            // Work Schedules (view)
            'view work schedules',
        ]);

        // ─────────────────────────────────────────────────────────
        // ROLE: Nail Technician (Staff) — service delivery
        //      View own appointments, clock in/out, request leave
        //      View customer/service dropdowns for appointment creation
        //      No inventory/expense/user/report access
        // ─────────────────────────────────────────────────────────
        $technician = Role::firstOrCreate(['name' => 'nail technician', 'guard_name' => 'web']);
        $technician->syncPermissions([
            // Appointments (view all, create new, edit own)
            'view appointments', 'create appointments', 'edit appointments',
            // Customers (read-only for dropdowns)
            'view customers',
            // Services (read-only for dropdowns)
            'view services',
            // Service Packages (read-only)
            'view service packages',
            // POS (basic POS terminal access)
            'view pos',
            // Attendance (clock in/out)
            'view attendances',
            // Work Schedules (view own schedule)
            'view work schedules',
            // Leave (request own leave, view own balances)
            'view leave requests', 'create leave requests',
            'view leave balances',
        ]);

        // ─────────────────────────────────────────────────────────
        // ROLE: Viewer — read-only access
        //      Can view dashboards, appointments, customers, services
        //      No create/edit/delete on anything
        // ─────────────────────────────────────────────────────────
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'view appointments',
            'view customers',
            'view services',
            'view service packages',
            'view pos',
            'view attendances',
            'view work schedules',
            'view leave requests', 'view leave balances',
            'inventory.view',
            'view reports',
        ]);

        // ── Also keep the generic 'staff' role for backward compat ──
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staffRole->syncPermissions([
            'view appointments', 'create appointments', 'edit appointments',
            'view customers',
            'view services',
            'view pos', 'create pos transactions',
            'view attendances',
            'view leave requests', 'create leave requests',
            'view leave balances',
        ]);

        // ── Ensure default admin user has administrator role ──
        $adminUser = User::where('email', 'admin@rashnail.com')->first();
        if ($adminUser && !$adminUser->hasRole('administrator')) {
            $adminUser->assignRole('administrator');
            $adminUser->update(['role' => 'administrator']);
        }

        // ── Assign seeded staff users to 'nail technician' role ──
        // Note: the 'role' column is a legacy enum ('administrator'/'staff') for backward compat.
        // Actual authorization uses Spatie roles stored in model_has_roles pivot table.
        $staffEmails = ['nimal@rashnail.com', 'kamani@rashnail.com', 'dilani@rashnail.com', 'priya@rashnail.com'];
        foreach ($staffEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles(['nail technician']);
                // Legacy column kept at 'staff' since the SQLite enum only allows 'administrator' or 'staff'
                $user->update(['role' => 'staff']);
            }
        }
    }

    private function seed(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
