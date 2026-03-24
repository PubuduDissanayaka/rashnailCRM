<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServicePackageController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\NotificationLogController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\Inventory\SupplyController;
use App\Http\Controllers\Inventory\SupplyCategoryController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\UsageLogController;
use App\Http\Controllers\Inventory\AlertController;
use App\Http\Controllers\Expense\{ExpenseController, ExpenseCategoryController};
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CouponReportController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SearchController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes group
Route::middleware(['auth'])->group(function () {
    Route::get('/', [RoutingController::class, 'index'])->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::get('/profile/check-avatar', [ProfileController::class, 'checkAvatar'])->name('profile.check-avatar');
    Route::post('/profile/notifications', [ProfileController::class, 'updateNotificationSettings'])->name('profile.notifications.update');

    // Role management routes - MUST be before user routes to avoid conflicts
    // These are restricted to users with 'manage system' permission
    Route::middleware(['can:manage system'])->group(function () {
        Route::get('/users/roles', [RoleController::class, 'index'])->name('users.roles');
        Route::get('/users/permissions', [RoleController::class, 'permissions'])->name('users.permissions');
        Route::get('/users/role-details/{roleName}', [RoleController::class, 'show'])->name('users.role-details');
        Route::post('/users/roles', [RoleController::class, 'store'])->name('users.roles.store');
        Route::put('/users/roles/{role}', [RoleController::class, 'update'])->name('users.roles.update');
        Route::delete('/users/roles/{role}', [RoleController::class, 'destroy'])->name('users.roles.destroy');
        Route::put('/users/permissions/{permission}', [RoleController::class, 'updatePermission'])->name('users.permissions.update');
    });

    // User management routes - restricted to users with 'view users' permission
    // IMPORTANT: These must come AFTER role/permission routes because of {user:slug} parameter
    Route::middleware(['can:view users'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/contacts', [UserController::class, 'contacts'])->name('users.contacts');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        // Parameterized routes MUST be last
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    // Service management routes - restricted to users with 'view services' permission
    Route::middleware(['can:view services'])->group(function () {
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
        Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    });

    // Appointment management routes - authorization will be handled in controller methods
    Route::middleware(['auth'])->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
        Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
        Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

        // Calendar API endpoint
        Route::get('/api/appointments/calendar-events', [AppointmentController::class, 'getCalendarEvents'])->name('appointments.calendar-events');

        // Update status endpoint
        Route::put('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
             ->name('appointments.update-status');

        // AJAX endpoints for calendar
        Route::post('/appointments/{appointment}/update-datetime', [AppointmentController::class, 'updateDatetime'])
             ->name('appointments.update-datetime');
        Route::put('/appointments/{appointment}/ajax', [AppointmentController::class, 'updateViaAjax'])
             ->name('appointments.update-via-ajax');
        Route::post('/appointments/ajax', [AppointmentController::class, 'createViaAjax'])
             ->name('appointments.create-via-ajax');
    });

    // Settings routes - Admin only (MUST be before catch-all routes)
    Route::middleware(['auth', 'can:manage system'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings.index');

        Route::put('/settings', [SettingsController::class, 'update'])
            ->name('settings.update');

        Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])
            ->name('settings.test-email');

        // Email provider routes
        Route::post('/settings/email-providers', [SettingsController::class, 'storeEmailProvider'])
            ->name('settings.email-providers.store');
        Route::get('/settings/email-providers/{provider}', [SettingsController::class, 'showEmailProvider'])
            ->name('settings.email-providers.show');
        Route::put('/settings/email-providers/{provider}', [SettingsController::class, 'updateEmailProvider'])
            ->name('settings.email-providers.update');
        Route::delete('/settings/email-providers/{provider}', [SettingsController::class, 'deleteEmailProvider'])
            ->name('settings.email-providers.delete');
        Route::post('/settings/email-providers/{provider}/test', [SettingsController::class, 'testEmailProvider'])
            ->name('settings.email-providers.test');
        Route::post('/settings/email-providers/{provider}/toggle', [SettingsController::class, 'toggleEmailProvider'])
            ->name('settings.email-providers.toggle');
    });

    // POS routes — accessible to anyone with 'view pos' permission
    Route::middleware(['auth', 'can:view pos'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])
            ->name('pos.index');

        Route::get('/pos/transactions', [PosController::class, 'transactions'])
            ->name('pos.transactions');

        Route::post('/pos/sale', [PosController::class, 'store'])
            ->middleware('can:create pos transactions')
            ->name('pos.store');

        Route::get('/pos/search', [PosController::class, 'searchItems'])
            ->name('pos.search');

        Route::get('/pos/customer/{id}', [PosController::class, 'getCustomerDetails'])
            ->name('pos.customer-details');

        Route::get('/pos/live-search', [PosController::class, 'liveSearch'])
            ->name('pos.live-search');

        Route::post('/pos/customers', [PosController::class, 'storeCustomer'])
            ->name('pos.customers.store');

        Route::get('/pos/receipt/{sale}', [PosController::class, 'showReceipt'])
            ->name('pos.receipt');
    });

    // Coupon management routes — gated by granular coupon permissions
    Route::middleware(['auth', 'can:view coupons'])->group(function () {
        Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::get('/coupons/{coupon}', [CouponController::class, 'show'])->name('coupons.show');

        Route::get('/coupons/create', [CouponController::class, 'create'])->middleware('can:create coupons')->name('coupons.create');
        Route::post('/coupons', [CouponController::class, 'store'])->middleware('can:create coupons')->name('coupons.store');

        Route::get('/coupons/{coupon}/edit', [CouponController::class, 'edit'])->middleware('can:edit coupons')->name('coupons.edit');
        Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->middleware('can:edit coupons')->name('coupons.update');

        Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->middleware('can:delete coupons')->name('coupons.destroy');

        // Bulk coupon generation
        Route::get('/coupons/bulk/create', [CouponController::class, 'createBulk'])->middleware('can:manage coupon batches')->name('coupons.bulk.create');
        Route::post('/coupons/bulk/generate', [CouponController::class, 'generateBulk'])->middleware('can:manage coupon batches')->name('coupons.bulk.generate');

        // Customer groups management (admin-level)
        Route::middleware(['can:manage system'])->group(function () {
            Route::get('/customer-groups', [CouponController::class, 'customerGroups'])->name('customer-groups.index');
            Route::get('/customer-groups/create', [CouponController::class, 'createCustomerGroup'])->name('customer-groups.create');
            Route::post('/customer-groups', [CouponController::class, 'storeCustomerGroup'])->name('customer-groups.store');
            Route::get('/customer-groups/{group}/edit', [CouponController::class, 'editCustomerGroup'])->name('customer-groups.edit');
            Route::put('/customer-groups/{group}', [CouponController::class, 'updateCustomerGroup'])->name('customer-groups.update');
            Route::delete('/customer-groups/{group}', [CouponController::class, 'destroyCustomerGroup'])->name('customer-groups.destroy');
        });

        // Coupon reports
        Route::get('/reports/coupons', [CouponReportController::class, 'index'])->name('reports.coupons.index');
        Route::get('/api/reports/coupons/redemption-analytics', [CouponReportController::class, 'redemptionAnalytics'])->name('reports.coupons.redemption-analytics');
        Route::get('/api/reports/coupons/performance-by-type', [CouponReportController::class, 'performanceByType'])->name('reports.coupons.performance-by-type');
        Route::get('/api/reports/coupons/usage-by-period', [CouponReportController::class, 'usageByPeriod'])->name('reports.coupons.usage-by-period');
        Route::get('/api/reports/coupons/top-coupons', [CouponReportController::class, 'topCoupons'])->name('reports.coupons.top-coupons');
        Route::get('/reports/coupons/export/{type}', [CouponReportController::class, 'export'])->name('reports.coupons.export');
    });

    // POS coupon validation — any authenticated user with POS access
    Route::middleware(['auth', 'can:view pos'])->group(function () {
        Route::post('/api/coupons/validate', [CouponController::class, 'validateCoupon'])->name('coupons.validate');
        Route::get('/api/coupons/report', [CouponController::class, 'report'])->name('coupons.report');
    });

    // Service packages routes - restricted to users with appropriate permissions
    // IMPORTANT: These must be added before catch-all routes to avoid conflicts
    Route::middleware(['can:view service packages'])->group(function () {
        Route::get('/service-packages', [ServicePackageController::class, 'index'])->name('service-packages.index');
        Route::get('/service-packages/create', [ServicePackageController::class, 'create'])->middleware('can:create service packages')->name('service-packages.create');
        Route::post('/service-packages', [ServicePackageController::class, 'store'])->middleware('can:create service packages')->name('service-packages.store');
        Route::get('/service-packages/{servicePackage:slug}', [ServicePackageController::class, 'show'])->name('service-packages.show');
        Route::get('/service-packages/{servicePackage:slug}/edit', [ServicePackageController::class, 'edit'])->middleware('can:edit service packages')->name('service-packages.edit');
        Route::put('/service-packages/{servicePackage:slug}', [ServicePackageController::class, 'update'])->middleware('can:edit service packages')->name('service-packages.update');
        Route::delete('/service-packages/{servicePackage:slug}', [ServicePackageController::class, 'destroy'])->middleware('can:delete service packages')->name('service-packages.destroy');
    });

    // Customer management routes - restricted to users with 'view customers' permission
    Route::middleware(['can:view customers'])->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::post('/customers/quick', [CustomerController::class, 'quickStore'])->name('customers.quick-store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // Attendance index — all authenticated users can access (staff see own records, admins see all)
    Route::get('/attendance', function() { return redirect()->route('attendance.index'); });
    Route::get('/attendance/index', [AttendanceController::class, 'index'])->name('attendance.index');

    // Attendance management routes - restricted to users with 'view attendances' permission
    Route::middleware(['can:view attendances'])->group(function () {
        Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('/attendance/staff', [AttendanceController::class, 'staff'])->name('attendance.staff');
        // Dashboard
        Route::get('/attendance/dashboard', [AttendanceController::class, 'dashboard'])->name('attendance.dashboard');
        // Date-specific attendance
        Route::get('/attendance/date/{date}', [AttendanceController::class, 'showDate'])->name('attendance.date.show');
        // Staff-specific attendance
        Route::get('/attendance/staff/{user}', [AttendanceController::class, 'showStaff'])->name('attendance.staff.show');
        // Manual entry
        Route::get('/attendance/manual/create', [AttendanceController::class, 'createManual'])->name('attendance.manual.create');
        Route::post('/attendance/manual', [AttendanceController::class, 'storeManual'])->name('attendance.manual.store');
        Route::get('/attendance/manual/{attendance}/edit', [AttendanceController::class, 'editManual'])->name('attendance.manual.edit');
        Route::put('/attendance/manual/{attendance}', [AttendanceController::class, 'updateManual'])->name('attendance.manual.update');
        Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    });

    // Work Hour Reports - restricted to users with 'view attendances' permission
    Route::middleware(['can:view attendances'])->group(function () {
        Route::get('/reports/work-hours', [\App\Http\Controllers\WorkHourReportController::class, 'index'])->name('reports.work-hours.index');
        Route::get('/reports/work-hours/summary', [\App\Http\Controllers\WorkHourReportController::class, 'summary'])->name('reports.work-hours.summary');
        Route::get('/reports/work-hours/detail', [\App\Http\Controllers\WorkHourReportController::class, 'detail'])->name('reports.work-hours.detail');
        Route::get('/reports/work-hours/staff/{staffId}', [\App\Http\Controllers\WorkHourReportController::class, 'staffDetail'])->name('reports.work-hours.staff.detail');
        Route::get('/reports/work-hours/filter-options', [\App\Http\Controllers\WorkHourReportController::class, 'filterOptions'])->name('reports.work-hours.filter-options');
        
        // Export routes with rate limiting
        Route::middleware([\App\Http\Middleware\RateLimitReportExports::class])->group(function () {
            Route::post('/reports/work-hours/export/csv', [\App\Http\Controllers\WorkHourReportController::class, 'exportCsv'])->name('reports.work-hours.export.csv');
            Route::post('/reports/work-hours/export/pdf', [\App\Http\Controllers\WorkHourReportController::class, 'exportPdf'])->name('reports.work-hours.export.pdf');
        });
    });

    // Notification management routes - restricted to authenticated users
    Route::middleware(['auth'])->group(function () {
        // Static routes MUST come before wildcard {notification} routes
        Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications', [\App\Http\Controllers\NotificationController::class, 'store'])->name('notifications.store');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::get('/notifications/inbox', [\App\Http\Controllers\NotificationController::class, 'inbox'])->name('notifications.inbox');
        Route::get('/notifications/check-new', [\App\Http\Controllers\NotificationController::class, 'checkNew'])->name('notifications.check-new');
        Route::post('/notifications/clear-all', [\App\Http\Controllers\NotificationController::class, 'clearAll'])->name('notifications.clear-all');

        // Wildcard {notification} routes
        Route::get('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'show'])->name('notifications.show');
        Route::put('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'update'])->name('notifications.update');
        Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/notifications/{notification}/status', [\App\Http\Controllers\NotificationController::class, 'status'])->name('notifications.status');
        Route::post('/notifications/{notification}/retry', [\App\Http\Controllers\NotificationController::class, 'retry'])->name('notifications.retry');
        Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.mark-read');
    });

    // Notification Provider Management routes - restricted to admin users
    Route::middleware(['auth', 'can:manage system'])->group(function () {
        Route::get('/notification-providers', [\App\Http\Controllers\ProviderController::class, 'index'])->name('notification-providers.index');
        Route::get('/notification-providers/create', [\App\Http\Controllers\ProviderController::class, 'create'])->name('notification-providers.create');
        Route::post('/notification-providers', [\App\Http\Controllers\ProviderController::class, 'store'])->name('notification-providers.store');
        Route::get('/notification-providers/{provider}', [\App\Http\Controllers\ProviderController::class, 'show'])->name('notification-providers.show');
        Route::get('/notification-providers/{provider}/edit', [\App\Http\Controllers\ProviderController::class, 'edit'])->name('notification-providers.edit');
        Route::put('/notification-providers/{provider}', [\App\Http\Controllers\ProviderController::class, 'update'])->name('notification-providers.update');
        Route::delete('/notification-providers/{provider}', [\App\Http\Controllers\ProviderController::class, 'destroy'])->name('notification-providers.destroy');
        Route::post('/notification-providers/{provider}/test', [\App\Http\Controllers\ProviderController::class, 'testConnection'])->name('notification-providers.test');
        Route::post('/notification-providers/{provider}/toggle', [\App\Http\Controllers\ProviderController::class, 'toggleStatus'])->name('notification-providers.toggle');
        Route::post('/notification-providers/{provider}/set-default', [\App\Http\Controllers\ProviderController::class, 'setDefault'])->name('notification-providers.set-default');
    });

    // Template Management routes - restricted to admin users
    Route::middleware(['auth', 'can:manage system'])->group(function () {
        Route::get('/templates', [\App\Http\Controllers\TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [\App\Http\Controllers\TemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [\App\Http\Controllers\TemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}', [\App\Http\Controllers\TemplateController::class, 'show'])->name('templates.show');
        Route::get('/templates/{template}/edit', [\App\Http\Controllers\TemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/templates/{template}', [\App\Http\Controllers\TemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [\App\Http\Controllers\TemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('/templates/{template}/duplicate', [\App\Http\Controllers\TemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::post('/templates/{template}/preview', [\App\Http\Controllers\TemplateController::class, 'preview'])->name('templates.preview');
        Route::post('/templates/send-test', [\App\Http\Controllers\TemplateController::class, 'sendTest'])->name('templates.send-test');
    });

    // Notification Log routes - restricted to admin users
    Route::middleware(['auth', 'can:manage system'])->group(function () {
        Route::get('/notification-logs', [\App\Http\Controllers\NotificationLogController::class, 'index'])->name('notification-logs.index');
        Route::get('/notification-logs/{log}', [\App\Http\Controllers\NotificationLogController::class, 'show'])->name('notification-logs.show');
        Route::delete('/notification-logs/{log}', [\App\Http\Controllers\NotificationLogController::class, 'destroy'])->name('notification-logs.destroy');
        Route::post('/notification-logs/{log}/retry', [\App\Http\Controllers\NotificationLogController::class, 'retry'])->name('notification-logs.retry');
        Route::post('/notification-logs/export', [\App\Http\Controllers\NotificationLogController::class, 'export'])->name('notification-logs.export');
        Route::post('/notification-logs/bulk-retry', [\App\Http\Controllers\NotificationLogController::class, 'bulkRetry'])->name('notification-logs.bulk-retry');
        Route::post('/notification-logs/bulk-delete', [\App\Http\Controllers\NotificationLogController::class, 'bulkDelete'])->name('notification-logs.bulk-delete');
    });

    // Broadcast Notification routes - restricted to admin users
    Route::middleware(['auth', 'can:manage system'])->group(function () {
        Route::get('/broadcasts', [\App\Http\Controllers\BroadcastController::class, 'index'])->name('broadcasts.index');
        Route::get('/broadcasts/create', [\App\Http\Controllers\BroadcastController::class, 'create'])->name('broadcasts.create');
        Route::post('/broadcasts', [\App\Http\Controllers\BroadcastController::class, 'store'])->name('broadcasts.store');
        Route::get('/broadcasts/{broadcast}', [\App\Http\Controllers\BroadcastController::class, 'show'])->name('broadcasts.show');
        Route::get('/broadcasts/{broadcast}/edit', [\App\Http\Controllers\BroadcastController::class, 'edit'])->name('broadcasts.edit');
        Route::put('/broadcasts/{broadcast}', [\App\Http\Controllers\BroadcastController::class, 'update'])->name('broadcasts.update');
        Route::delete('/broadcasts/{broadcast}', [\App\Http\Controllers\BroadcastController::class, 'destroy'])->name('broadcasts.destroy');
        Route::post('/broadcasts/{broadcast}/cancel', [\App\Http\Controllers\BroadcastController::class, 'cancel'])->name('broadcasts.cancel');
        Route::post('/broadcasts/{broadcast}/send-now', [\App\Http\Controllers\BroadcastController::class, 'sendNow'])->name('broadcasts.send-now');
        Route::get('/broadcasts/recipient-options', [\App\Http\Controllers\BroadcastController::class, 'recipientOptions'])->name('broadcasts.recipient-options');
    });

    // User Notification Preferences routes - accessible to all authenticated users
    Route::middleware(['auth'])->group(function () {
        Route::get('/notification-settings', [\App\Http\Controllers\NotificationSettingController::class, 'index'])->name('notification-settings.index');
        Route::post('/notification-settings', [\App\Http\Controllers\NotificationSettingController::class, 'update'])->name('notification-settings.update');
        Route::post('/notification-settings/bulk-update', [\App\Http\Controllers\NotificationSettingController::class, 'bulkUpdate'])->name('notification-settings.bulk-update');
        Route::post('/notification-settings/reset-to-defaults', [\App\Http\Controllers\NotificationSettingController::class, 'resetToDefaults'])->name('notification-settings.reset-to-defaults');
        Route::post('/notification-settings/toggle-channel', [\App\Http\Controllers\NotificationSettingController::class, 'toggleChannel'])->name('notification-settings.toggle-channel');
        Route::post('/notification-settings/do-not-disturb', [\App\Http\Controllers\NotificationSettingController::class, 'updateDoNotDisturb'])->name('notification-settings.do-not-disturb');
    });

    // System-wide Notification Settings routes - admin only
    Route::middleware(['auth', 'can:manage system'])->group(function () {
        Route::put('/notification-settings/system', [\App\Http\Controllers\NotificationSettingController::class, 'updateSystemSettings'])->name('notification-settings.system.update');
        Route::post('/notification-settings/system/reset', [\App\Http\Controllers\NotificationSettingController::class, 'resetSystemToDefaults'])->name('notification-settings.system.reset');
        Route::post('/notification-settings/system/apply-to-all', [\App\Http\Controllers\NotificationSettingController::class, 'applyToAllUsers'])->name('notification-settings.system.apply-to-all');
        Route::post('/notification-settings/rate-limiting', [\App\Http\Controllers\NotificationSettingController::class, 'updateRateLimiting'])->name('notification-settings.rate-limiting.update');
        Route::post('/notification-settings/blacklist-whitelist', [\App\Http\Controllers\NotificationSettingController::class, 'updateBlacklistWhitelist'])->name('notification-settings.blacklist-whitelist.update');
    });

    // Notification System Status Dashboard routes - admin only
    Route::middleware(['auth', 'can:manage system'])->group(function () {
        Route::get('/notifications/status', [\App\Http\Controllers\NotificationStatusController::class, 'index'])->name('notifications.status.index');
        Route::get('/notifications/status/health', [\App\Http\Controllers\NotificationStatusController::class, 'health'])->name('notifications.status.health');
        Route::get('/notifications/status/error-statistics', [\App\Http\Controllers\NotificationStatusController::class, 'errorStatistics'])->name('notifications.status.error-statistics');
        Route::get('/notifications/status/queue-statistics', [\App\Http\Controllers\NotificationStatusController::class, 'queueStatistics'])->name('notifications.status.queue-statistics');
        Route::get('/notifications/status/providers', [\App\Http\Controllers\NotificationStatusController::class, 'providerHealth'])->name('notifications.status.providers');
        Route::get('/notifications/status/delivery-statistics', [\App\Http\Controllers\NotificationStatusController::class, 'deliveryStatistics'])->name('notifications.status.delivery-statistics');
        Route::get('/notifications/status/retryable-notifications', [\App\Http\Controllers\NotificationStatusController::class, 'retryableNotifications'])->name('notifications.status.retryable-notifications');
        Route::get('/notifications/status/recommendations', [\App\Http\Controllers\NotificationStatusController::class, 'recommendations'])->name('notifications.status.recommendations');
        Route::get('/notifications/status/historical-health', [\App\Http\Controllers\NotificationStatusController::class, 'historicalHealth'])->name('notifications.status.historical-health');
        Route::get('/notifications/status/alerts', [\App\Http\Controllers\NotificationStatusController::class, 'alerts'])->name('notifications.status.alerts');
        Route::get('/notifications/status/export-report', [\App\Http\Controllers\NotificationStatusController::class, 'exportReport'])->name('notifications.status.export-report');
        
        // Action routes (POST)
        Route::post('/notifications/status/health-check', [\App\Http\Controllers\NotificationStatusController::class, 'runHealthCheck'])->name('notifications.status.health-check');
        Route::post('/notifications/status/retry-failed', [\App\Http\Controllers\NotificationStatusController::class, 'retryFailedNotifications'])->name('notifications.status.retry-failed');
        Route::post('/notifications/status/clear-cache', [\App\Http\Controllers\NotificationStatusController::class, 'clearCache'])->name('notifications.status.clear-cache');
        Route::post('/notifications/status/alerts/{alertId}/acknowledge', [\App\Http\Controllers\NotificationStatusController::class, 'acknowledgeAlert'])->name('notifications.status.alerts.acknowledge');
    });

    // Inventory Management Routes (Protected by auth + inventory permission)
    Route::middleware(['auth', 'can:inventory.view'])->prefix('inventory')->name('inventory.')->group(function () {
        // Supplies
        Route::resource('supplies', SupplyController::class);
        Route::post('supplies/{supply}/adjust-stock', [SupplyController::class, 'adjustStock'])->name('supplies.adjust');
        Route::get('supplies/{supply}/history', [SupplyController::class, 'history'])->name('supplies.history');

        // Categories
        Route::resource('categories', SupplyCategoryController::class)->except(['show']);

        // Purchase Orders
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

        // Usage Logs
        Route::get('usage-logs', [UsageLogController::class, 'index'])->name('usage-logs.index');
        Route::get('usage-logs/create', [UsageLogController::class, 'create'])->name('usage-logs.create');
        Route::post('usage-logs', [UsageLogController::class, 'store'])->name('usage-logs.store');
        Route::get('usage-logs/{usageLog}', [UsageLogController::class, 'show'])->name('usage-logs.show');
        
        // API endpoint for appointment usage logs
        Route::get('api/usage-logs/appointment/{appointmentId}', [UsageLogController::class, 'getAppointmentUsageLogs'])->name('usage-logs.appointment');
        
        // Alerts
        Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::get('alerts/{alert}', [AlertController::class, 'show'])->name('alerts.show');
        Route::post('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
        Route::post('alerts/bulk-resolve', [AlertController::class, 'bulkResolve'])->name('alerts.bulk-resolve');
        Route::get('alerts/export', [AlertController::class, 'export'])->name('alerts.export');
        Route::get('api/alerts/statistics', [AlertController::class, 'statistics'])->name('alerts.statistics');
        Route::get('api/alerts/recent', [AlertController::class, 'recent'])->name('alerts.recent');
    });

    // Expense Management Routes (Protected by auth + expenses permission)
    // Expense Dashboard
    Route::get('/expenses/dashboard', [ExpenseController::class, 'dashboard'])
        ->name('expenses.dashboard')
        ->middleware(['auth', 'can:expenses.view']);

    // Expense Categories CRUD
    Route::middleware(['auth', 'can:expenses.view'])->group(function () {
        Route::resource('expenses/categories', ExpenseCategoryController::class)
            ->names([
                'index' => 'expenses.categories.index',
                'create' => 'expenses.categories.create',
                'store' => 'expenses.categories.store',
                'show' => 'expenses.categories.show',
                'edit' => 'expenses.categories.edit',
                'update' => 'expenses.categories.update',
                'destroy' => 'expenses.categories.destroy',
            ]);
    });

    // Expenses CRUD
    Route::middleware(['auth', 'can:expenses.view'])->group(function () {
        Route::resource('expenses', ExpenseController::class);
    });

    // Expense Workflow Actions
    Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])
        ->name('expenses.approve')
        ->middleware(['auth', 'can:expenses.approve']);
    Route::post('/expenses/{expense}/reject', [ExpenseController::class, 'reject'])
        ->name('expenses.reject')
        ->middleware(['auth', 'can:expenses.approve']);
    Route::post('/expenses/{expense}/mark-as-paid', [ExpenseController::class, 'markAsPaid'])
        ->name('expenses.mark-paid')
        ->middleware(['auth', 'can:expenses.manage']);

    // Attendance API routes (require authentication but not necessarily 'view attendances' permission)
    Route::middleware(['auth'])->group(function () {
        // Check-in/check-out for staff
        Route::post('/api/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('/api/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
        
        // Break management
        Route::post('/api/attendance/break/start', [AttendanceController::class, 'startBreak'])->name('attendance.break.start');
        Route::post('/api/attendance/break/end', [AttendanceController::class, 'endBreak'])->name('attendance.break.end');
        Route::get('/api/attendance/break/status', [AttendanceController::class, 'breakStatus'])->name('attendance.break.status');
        
        // Status endpoints
        Route::get('/api/attendance/today-status', [AttendanceController::class, 'todayStatus'])->name('attendance.today-status');
        Route::get('/api/attendance/current-status', [AttendanceController::class, 'currentStatus'])->name('attendance.current-status');
        Route::get('/api/attendance/today-details', [AttendanceController::class, 'todayDetails'])->name('attendance.today-details');
        Route::get('/api/attendance/user-statistics', [AttendanceController::class, 'userStatistics'])->name('attendance.user-statistics');
        
        // DataTables AJAX endpoint
        Route::get('/api/attendance/datatable-staff', [AttendanceController::class, 'datatableStaff'])->name('attendance.datatable-staff');
        
        // Approval workflow (require 'manage attendances' - authorization handled in controller)
        Route::post('/api/attendance/{attendance}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve');
        Route::post('/api/attendance/{attendance}/reject', [AttendanceController::class, 'reject'])->name('attendance.reject');
        Route::get('/api/attendance/{attendance}/audit-logs', [AttendanceController::class, 'auditLogs'])->name('attendance.audit-logs');
        Route::get('/api/attendance/{attendance}/view', [AttendanceController::class, 'viewRecord'])->name('attendance.view');
        
        // Import/export (require 'manage attendances' - authorization handled in controller)
        Route::post('/api/attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
        Route::get('/api/attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
        // Download export file
        Route::get('/api/attendance/export/download', [AttendanceController::class, 'downloadExport'])->name('attendance.download-export');
    });


    // Handle .well-known paths first to prevent errors
    Route::get('.well-known/{path}', function ($path) {
        // Return 404 for any .well-known paths that don't exist
        return response()->json([], 404);
    })->where('path', '.*');

    // Report Hub + Sub-Reports — requires 'view reports' permission
    Route::middleware(['auth', 'can:view reports'])->group(function () {
        Route::get('/reports',                   [ReportsController::class, 'index'])       ->name('reports.index');
        Route::get('/reports/sales',             [ReportsController::class, 'sales'])       ->name('reports.sales');
        Route::get('/reports/appointments',      [ReportsController::class, 'appointments'])->name('reports.appointments');
        Route::get('/reports/customers',         [ReportsController::class, 'customers'])   ->name('reports.customers');
        Route::get('/reports/expenses',          [ReportsController::class, 'expenses'])    ->name('reports.expenses');
        Route::get('/reports/inventory',         [ReportsController::class, 'inventory'])   ->name('reports.inventory');
        Route::get('/reports/{type}/export',     [ReportsController::class, 'export'])
            ->middleware('can:export reports')
            ->name('reports.export');
    });

    // Search route
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Additional routes
    Route::delete('alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
    Route::get('/expenses/attachments/{attachment}/download', [ExpenseController::class, 'downloadAttachment'])->name('expenses.attachments.download');
    Route::post('/expenses/{expense}/comments', [ExpenseController::class, 'storeComment'])->name('expenses.comments.store');

    // Catch-all routes (must be last) — exclude storage/ and build/ paths
    Route::group(['prefix' => '/'], function () {
        Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])
            ->where('first', '(?!storage|build).*')->name('third');
        Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])
            ->where('first', '(?!storage|build).*')->name('second');
        Route::get('{any}', [RoutingController::class, 'root'])
            ->where('any', '(?!storage|build).*')->name('any');
    });
});
