<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Business & Branding Settings
            ['key' => 'business.name', 'value' => 'Rash Nail Studio', 'type' => 'string', 'group' => 'business', 'description' => 'Business name displayed throughout the application', 'order' => 1],
            ['key' => 'business.tagline', 'value' => 'Where Beauty Meets Expertise', 'type' => 'string', 'group' => 'business', 'description' => 'Business tagline or slogan', 'order' => 2],
            ['key' => 'business.logo', 'value' => null, 'type' => 'file', 'group' => 'business', 'description' => 'Business logo (recommended: 200x60px)', 'order' => 3],
            ['key' => 'business.favicon', 'value' => null, 'type' => 'file', 'group' => 'business', 'description' => 'Website favicon (recommended: 32x32px)', 'order' => 4],
            ['key' => 'business.phone', 'value' => '+94 11 223 4567', 'type' => 'string', 'group' => 'business', 'description' => 'Primary business phone number', 'order' => 5],
            ['key' => 'business.email', 'value' => 'info@rashnail.lk', 'type' => 'string', 'group' => 'business', 'description' => 'Primary business email address', 'order' => 6],
            ['key' => 'business.website', 'value' => 'https://rashnail.lk', 'type' => 'string', 'group' => 'business', 'description' => 'Business website URL', 'order' => 7],
            ['key' => 'business.address', 'value' => '45 Galle Face Center', 'type' => 'string', 'group' => 'business', 'description' => 'Street address', 'order' => 8],
            ['key' => 'business.city', 'value' => 'Colombo', 'type' => 'string', 'group' => 'business', 'description' => 'City', 'order' => 9],
            ['key' => 'business.state', 'value' => 'WP', 'type' => 'string', 'group' => 'business', 'description' => 'State or province', 'order' => 10],
            ['key' => 'business.zip', 'value' => '00300', 'type' => 'string', 'group' => 'business', 'description' => 'ZIP or postal code', 'order' => 11],
            ['key' => 'business.timezone', 'value' => 'Asia/Colombo', 'type' => 'string', 'group' => 'business', 'description' => 'Business timezone', 'order' => 12],
            ['key' => 'business.about', 'value' => 'Premium nail salon offering exceptional services', 'type' => 'text', 'group' => 'business', 'description' => 'About business description', 'order' => 13],

            // Social Media
            ['key' => 'business.social.facebook', 'value' => null, 'type' => 'string', 'group' => 'business', 'description' => 'Facebook page URL', 'order' => 20],
            ['key' => 'business.social.instagram', 'value' => null, 'type' => 'string', 'group' => 'business', 'description' => 'Instagram profile URL', 'order' => 21],
            ['key' => 'business.social.twitter', 'value' => null, 'type' => 'string', 'group' => 'business', 'description' => 'Twitter profile URL', 'order' => 22],
            ['key' => 'business.social.linkedin', 'value' => null, 'type' => 'string', 'group' => 'business', 'description' => 'LinkedIn profile URL', 'order' => 23],

            // Business Hours (JSON)
            ['key' => 'business.hours', 'value' => json_encode([
                'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'saturday' => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
                'sunday' => ['open' => null, 'close' => null, 'closed' => true],
            ]), 'type' => 'json', 'group' => 'business', 'description' => 'Business operating hours per day', 'order' => 30],

            // Appointment Settings
            ['key' => 'appointment.default_duration', 'value' => '60', 'type' => 'integer', 'group' => 'appointment', 'description' => 'Default appointment duration in minutes', 'order' => 1],
            ['key' => 'appointment.buffer_time', 'value' => '15', 'type' => 'integer', 'group' => 'appointment', 'description' => 'Buffer time between appointments (minutes)', 'order' => 2],
            ['key' => 'appointment.max_per_day', 'value' => '20', 'type' => 'integer', 'group' => 'appointment', 'description' => 'Maximum appointments per day', 'order' => 3],
            ['key' => 'appointment.advance_booking_days', 'value' => '30', 'type' => 'integer', 'group' => 'appointment', 'description' => 'How far in advance customers can book (days)', 'order' => 4],
            ['key' => 'appointment.min_advance_hours', 'value' => '2', 'type' => 'integer', 'group' => 'appointment', 'description' => 'Minimum advance notice for booking (hours)', 'order' => 5],
            ['key' => 'appointment.cancellation_hours', 'value' => '24', 'type' => 'integer', 'group' => 'appointment', 'description' => 'Cancellation deadline (hours before appointment)', 'order' => 6],
            ['key' => 'appointment.cancellation_policy', 'value' => 'Appointments must be cancelled at least 24 hours in advance to avoid cancellation fees.', 'type' => 'text', 'group' => 'appointment', 'description' => 'Cancellation policy text shown to customers', 'order' => 7],
            ['key' => 'appointment.online_booking', 'value' => '0', 'type' => 'boolean', 'group' => 'appointment', 'description' => 'Enable online booking (future feature)', 'order' => 10],
            ['key' => 'appointment.require_confirmation', 'value' => '1', 'type' => 'boolean', 'group' => 'appointment', 'description' => 'Require customer confirmation for bookings', 'order' => 11],

            // Notification Settings
            ['key' => 'notification.email_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Enable email notifications', 'order' => 1],
            ['key' => 'notification.sms_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Enable SMS notifications', 'order' => 2],
            ['key' => 'notification.email_address', 'value' => 'notifications@rashnail.lk', 'type' => 'string', 'group' => 'notification', 'description' => 'Email address for system notifications', 'order' => 3],
            ['key' => 'notification.email_signature', 'value' => "Best Regards,\nRash Nail Studio Team", 'type' => 'text', 'group' => 'notification', 'description' => 'Email signature for outgoing emails', 'order' => 4],
            ['key' => 'notification.reminder_hours', 'value' => '24', 'type' => 'integer', 'group' => 'notification', 'description' => 'Send appointment reminder (hours before)', 'order' => 5],
            ['key' => 'notification.staff_new_appointment', 'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Notify staff of new appointments', 'order' => 10],
            ['key' => 'notification.staff_cancellation', 'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Notify staff of cancellations', 'order' => 11],
            ['key' => 'notification.customer_confirmation', 'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Send customer booking confirmation', 'order' => 12],
            ['key' => 'notification.customer_reminder', 'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Send customer appointment reminder', 'order' => 13],

            // Payment Settings (Future POS)
            ['key' => 'payment.currency_code', 'value' => 'LKR', 'type' => 'string', 'group' => 'payment', 'description' => 'Currency code (ISO 4217)', 'order' => 1],
            ['key' => 'payment.currency_symbol', 'value' => 'Rs.', 'type' => 'string', 'group' => 'payment', 'description' => 'Currency symbol', 'order' => 2],
            ['key' => 'payment.tax_rate', 'value' => '0', 'type' => 'integer', 'group' => 'payment', 'description' => 'Tax rate percentage', 'order' => 3],
            ['key' => 'payment.methods', 'value' => json_encode(['cash', 'card']), 'type' => 'json', 'group' => 'payment', 'description' => 'Accepted payment methods', 'order' => 4],
            ['key' => 'payment.require_deposit', 'value' => '0', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Require deposit for bookings', 'order' => 5],
            ['key' => 'payment.deposit_percentage', 'value' => '50', 'type' => 'integer', 'group' => 'payment', 'description' => 'Deposit percentage required', 'order' => 6],
            ['key' => 'payment.refund_policy', 'value' => 'Refunds are processed within 5-7 business days. Cancellation fees may apply.', 'type' => 'text', 'group' => 'payment', 'description' => 'Refund policy text', 'order' => 7],
            ['key' => 'payment.invoice_prefix', 'value' => 'INV', 'type' => 'string', 'group' => 'payment', 'description' => 'Invoice number prefix', 'order' => 10],
            ['key' => 'payment.next_invoice_number', 'value' => '1', 'type' => 'integer', 'group' => 'payment', 'description' => 'Next invoice number', 'order' => 11],

            // POS Payment Modal Settings
            ['key' => 'payment.pos.quick_amounts_mode', 'value' => 'smart', 'type' => 'string', 'group' => 'payment', 'description' => 'Quick amount calculation: smart, percentage, or fixed', 'order' => 20],
            ['key' => 'payment.pos.quick_amounts_fixed', 'value' => json_encode([20, 50, 100]), 'type' => 'json', 'group' => 'payment', 'description' => 'Fixed quick amount buttons', 'order' => 21],
            ['key' => 'payment.pos.quick_amounts_percentages', 'value' => json_encode([105, 110, 120]), 'type' => 'json', 'group' => 'payment', 'description' => 'Percentage-based quick amounts (e.g., 105 = 5% more)', 'order' => 22],
            ['key' => 'payment.pos.enable_sound_effects', 'value' => '1', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Enable keypad sound effects', 'order' => 23],
            ['key' => 'payment.pos.max_payment_amount', 'value' => '100000', 'type' => 'integer', 'group' => 'payment', 'description' => 'Maximum single payment amount', 'order' => 24],
            ['key' => 'payment.pos.require_reference_card', 'value' => '1', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Require reference number for card payments', 'order' => 25],
            ['key' => 'payment.pos.require_reference_check', 'value' => '1', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Require check number for check payments', 'order' => 26],
            ['key' => 'payment.pos.require_reference_bank_transfer', 'value' => '1', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Require reference for bank transfers', 'order' => 27],
            ['key' => 'payment.pos.require_reference_mobile', 'value' => '1', 'type' => 'boolean', 'group' => 'payment', 'description' => 'Require transaction ID for mobile payments', 'order' => 28],

            // Attendance Settings
            ['key' => 'attendance.general.enable_geolocation', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable geolocation tracking for check-in/check-out', 'order' => 1],
            ['key' => 'attendance.general.geofence_radius', 'value' => '100', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Geofence radius in meters', 'order' => 2],
            ['key' => 'attendance.general.require_photo_verification', 'value' => '0', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Require photo verification for check-in', 'order' => 3],
            ['key' => 'attendance.general.auto_approval_threshold', 'value' => '15', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Auto-approval threshold for late arrivals (minutes)', 'order' => 4],
            ['key' => 'attendance.general.default_shift_id', 'value' => null, 'type' => 'integer', 'group' => 'attendance', 'description' => 'Default shift ID for new staff', 'order' => 5],
            ['key' => 'attendance.general.enable_break_tracking', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable break tracking functionality', 'order' => 6],
            ['key' => 'attendance.general.enable_overtime_calculation', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable automatic overtime calculation', 'order' => 7],
            ['key' => 'attendance.general.require_manager_approval', 'value' => '0', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Require manager approval for attendance records', 'order' => 8],
            ['key' => 'attendance.general.max_manual_entries_per_month', 'value' => '3', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Maximum manual attendance entries per staff per month', 'order' => 9],

            // Shift Management Settings
            ['key' => 'attendance.shifts.default_start_time', 'value' => '09:00', 'type' => 'string', 'group' => 'attendance', 'description' => 'Default shift start time', 'order' => 20],
            ['key' => 'attendance.shifts.default_end_time', 'value' => '17:00', 'type' => 'string', 'group' => 'attendance', 'description' => 'Default shift end time', 'order' => 21],
            ['key' => 'attendance.shifts.default_grace_period', 'value' => '15', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Default grace period for late arrivals (minutes)', 'order' => 22],
            ['key' => 'attendance.shifts.break_duration', 'value' => '60', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Default break duration (minutes)', 'order' => 23],
            ['key' => 'attendance.shifts.overtime_threshold', 'value' => '60', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Overtime threshold after scheduled end time (minutes)', 'order' => 24],
            ['key' => 'attendance.shifts.overtime_rate_multiplier', 'value' => '1.5', 'type' => 'string', 'group' => 'attendance', 'description' => 'Overtime rate multiplier (e.g., 1.5 for time and a half)', 'order' => 25],

            // Location Settings
            ['key' => 'attendance.locations.enable_geofencing', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable geofencing for attendance', 'order' => 30],
            ['key' => 'attendance.locations.max_distance', 'value' => '500', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Maximum allowed distance from location (meters)', 'order' => 31],
            ['key' => 'attendance.locations.require_location', 'value' => '0', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Require location for check-in/check-out', 'order' => 32],
            ['key' => 'attendance.locations.default_location_id', 'value' => null, 'type' => 'integer', 'group' => 'attendance', 'description' => 'Default location for attendance', 'order' => 33],

            // Notification Settings
            ['key' => 'attendance.notifications.late_checkin_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable notifications for late check-ins', 'order' => 40],
            ['key' => 'attendance.notifications.missed_checkin_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable notifications for missed check-ins', 'order' => 41],
            ['key' => 'attendance.notifications.overtime_alert_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable notifications for overtime', 'order' => 42],
            ['key' => 'attendance.notifications.daily_summary_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable daily attendance summary notifications', 'order' => 43],
            ['key' => 'attendance.notifications.approval_required_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable notifications when approval is required', 'order' => 44],
            ['key' => 'attendance.notifications.break_reminder_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable break reminder notifications', 'order' => 45],

            // Compliance Settings
            ['key' => 'attendance.compliance.audit_log_retention', 'value' => '365', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Audit log retention period (days)', 'order' => 50],
            ['key' => 'attendance.compliance.max_working_hours_per_day', 'value' => '12', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Maximum working hours per day', 'order' => 51],
            ['key' => 'attendance.compliance.min_break_duration', 'value' => '30', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Minimum break duration (minutes)', 'order' => 52],
            ['key' => 'attendance.compliance.max_break_duration', 'value' => '120', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Maximum break duration (minutes)', 'order' => 53],
            ['key' => 'attendance.compliance.require_break_after_hours', 'value' => '5', 'type' => 'integer', 'group' => 'attendance', 'description' => 'Require break after working hours', 'order' => 54],
            ['key' => 'attendance.compliance.data_encryption_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance', 'description' => 'Enable encryption for sensitive attendance data', 'order' => 55],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Cache all settings
        Setting::flushCache();
        foreach(['business', 'appointment', 'notification', 'payment', 'attendance'] as $group) {
            Setting::getGroup($group);
        }

        $this->command->info('Settings seeded successfully!');
    }
}