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

        // ── POS permissions ───────────────────────────────────
        $this->seed([
            'view pos', 'create pos transactions', 'manage pos',
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

        // ── Create administrator role with ALL permissions ────
        $administrator = Role::firstOrCreate(
            ['name' => 'administrator', 'guard_name' => 'web']
        );
        $administrator->syncPermissions(Permission::all());

        // ── Do NOT pre-create "staff" role ────────────────────
        // Admin creates custom roles (Manager, Staff L1, Reception, etc.)
        // via the Roles & Permissions UI at /users/roles

        // ── Ensure default admin user has administrator role ──
        $adminUser = User::where('email', 'admin@rashnail.com')->first();
        if ($adminUser && !$adminUser->hasRole('administrator')) {
            $adminUser->assignRole('administrator');
            $adminUser->update(['role' => 'administrator']);
        }
    }

    private function seed(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
