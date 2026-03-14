<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for the nail studio using firstOrCreate to avoid conflicts
        Permission::firstOrCreate(['name' => 'view customers'], ['name' => 'view customers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create customers'], ['name' => 'create customers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit customers'], ['name' => 'edit customers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete customers'], ['name' => 'delete customers', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view appointments'], ['name' => 'view appointments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create appointments'], ['name' => 'create appointments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit appointments'], ['name' => 'edit appointments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete appointments'], ['name' => 'delete appointments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage all appointments'], ['name' => 'manage all appointments', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view services'], ['name' => 'view services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create services'], ['name' => 'create services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit services'], ['name' => 'edit services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete services'], ['name' => 'delete services', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'process transactions'], ['name' => 'process transactions', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view transactions'], ['name' => 'view transactions', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete transactions'], ['name' => 'delete transactions', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view users'], ['name' => 'view users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create users'], ['name' => 'create users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit users'], ['name' => 'edit users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete users'], ['name' => 'delete users', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view reports'], ['name' => 'view reports', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage system'], ['name' => 'manage system', 'guard_name' => 'web']);

        // Service permissions
        Permission::firstOrCreate(['name' => 'view services'], ['name' => 'view services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create services'], ['name' => 'create services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit services'], ['name' => 'edit services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete services'], ['name' => 'delete services', 'guard_name' => 'web']);

        // Appointment permissions
        Permission::firstOrCreate(['name' => 'view appointments'], ['name' => 'view appointments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create appointments'], ['name' => 'create appointments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit appointments'], ['name' => 'edit appointments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete appointments'], ['name' => 'delete appointments', 'guard_name' => 'web']);

        // Attendance permissions
        Permission::firstOrCreate(['name' => 'view attendances'], ['name' => 'view attendances', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage attendances'], ['name' => 'manage attendances', 'guard_name' => 'web']);

        // Create roles and assign permissions using firstOrCreate to avoid conflicts
        $administrator = Role::firstOrCreate(['name' => 'administrator'], ['name' => 'administrator', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff'], ['name' => 'staff', 'guard_name' => 'web']);

        // Administrator can do everything
        $administrator->givePermissionTo(Permission::all());

        // Staff has limited permissions
        $staff->givePermissionTo([
            'view customers',
            'create customers',
            'edit customers',
            'view appointments',
            'create appointments',
            'edit appointments',
            'view services',
            'process transactions',
            'view transactions',
            'view attendances'  // Staff need to view their own attendance and clock in/out
        ]);

        // Add service management permissions to staff
        $staff->givePermissionTo(['view services', 'create services', 'edit services']);

        // Service package permissions
        Permission::firstOrCreate(['name' => 'view service packages'], ['name' => 'view service packages', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create service packages'], ['name' => 'create service packages', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit service packages'], ['name' => 'edit service packages', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete service packages'], ['name' => 'delete service packages', 'guard_name' => 'web']);

        // Add service package management permissions to administrator
        $administrator->givePermissionTo(['view service packages', 'create service packages', 'edit service packages', 'delete service packages']);

        // Add limited service package permissions to staff
        $staff->givePermissionTo(['view service packages', 'create service packages', 'edit service packages']);

        // Add POS permissions
        Permission::firstOrCreate(['name' => 'view pos'], ['name' => 'view pos', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create pos transactions'], ['name' => 'create pos transactions', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage pos'], ['name' => 'manage pos', 'guard_name' => 'web']);

        // Give POS permissions to administrator
        $administrator->givePermissionTo(['view pos', 'create pos transactions', 'manage pos']);

        // Give POS permissions to staff as well (based on business requirements)
        $staff->givePermissionTo(['view pos', 'create pos transactions']);

        // Inventory permissions
        Permission::firstOrCreate(['name' => 'inventory.view'], ['name' => 'inventory.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.manage'], ['name' => 'inventory.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.supplies.create'], ['name' => 'inventory.supplies.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.supplies.edit'], ['name' => 'inventory.supplies.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.supplies.delete'], ['name' => 'inventory.supplies.delete', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.supplies.adjust'], ['name' => 'inventory.supplies.adjust', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.purchase.create'], ['name' => 'inventory.purchase.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.purchase.approve'], ['name' => 'inventory.purchase.approve', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.purchase.receive'], ['name' => 'inventory.purchase.receive', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.reports.view'], ['name' => 'inventory.reports.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.alerts.manage'], ['name' => 'inventory.alerts.manage', 'guard_name' => 'web']);

        // Expense permissions
        Permission::firstOrCreate(['name' => 'expenses.view'], ['name' => 'expenses.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'expenses.create'], ['name' => 'expenses.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'expenses.edit'], ['name' => 'expenses.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'expenses.delete'], ['name' => 'expenses.delete', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'expenses.approve'], ['name' => 'expenses.approve', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'expenses.manage'], ['name' => 'expenses.manage', 'guard_name' => 'web']);

        // Give all inventory permissions to administrator
        $administrator->givePermissionTo([
            'inventory.view',
            'inventory.manage',
            'inventory.supplies.create',
            'inventory.supplies.edit',
            'inventory.supplies.delete',
            'inventory.supplies.adjust',
            'inventory.purchase.create',
            'inventory.purchase.approve',
            'inventory.purchase.receive',
            'inventory.reports.view',
            'inventory.alerts.manage',
        ]);

        // Give limited inventory permissions to staff (view, adjust stock, create usage logs)
        $staff->givePermissionTo([
            'inventory.view',
            'inventory.supplies.adjust',
        ]);

        // Give limited expense permissions to staff (view, create, edit own expenses)
        $staff->givePermissionTo([
            'expenses.view',
            'expenses.create',
            'expenses.edit',
        ]);

        // Update the default admin user to have the administrator role
        $adminUser = User::where('email', 'admin@rashnail.com')->first();
        if ($adminUser) {
            $adminUser->assignRole('administrator');
        }
    }
}
