<?php

namespace App\Services;

use App\Models\Supply;
use App\Models\SupplyAlert;
use App\Models\Setting;
use App\Mail\SupplyAlertMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * Generate low stock alerts
     */
    public function generateLowStockAlerts(): array
    {
        $alerts = [];
        $lowStockSupplies = Supply::active()->lowStock()->get();

        foreach ($lowStockSupplies as $supply) {
            $alert = $this->checkAndCreateAlert(
                $supply,
                'low_stock',
                'warning',
                "Supply '{$supply->name}' is low on stock. Current: {$supply->current_stock}, Minimum: {$supply->min_stock_level}"
            );
            
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Generate out of stock alerts
     */
    public function generateOutOfStockAlerts(): array
    {
        $alerts = [];
        $outOfStockSupplies = Supply::active()->outOfStock()->get();

        foreach ($outOfStockSupplies as $supply) {
            $alert = $this->checkAndCreateAlert(
                $supply,
                'out_of_stock',
                'critical',
                "Supply '{$supply->name}' is out of stock. Current: {$supply->current_stock}"
            );
            
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Generate expiry alerts
     */
    public function generateExpiryAlerts(): array
    {
        $alerts = [];
        
        // Get supplies with expiry tracking enabled
        $suppliesWithExpiry = Supply::active()
            ->where('track_expiry', true)
            ->whereNotNull('metadata->expiry_date')
            ->get();

        foreach ($suppliesWithExpiry as $supply) {
            $expiryDate = $supply->metadata['expiry_date'] ?? null;
            
            if (!$expiryDate) {
                continue;
            }

            $expiryDate = \Carbon\Carbon::parse($expiryDate);
            $daysUntilExpiry = now()->diffInDays($expiryDate, false);

            if ($daysUntilExpiry <= 0) {
                // Already expired
                $alert = $this->checkAndCreateAlert(
                    $supply,
                    'expired',
                    'critical',
                    "Supply '{$supply->name}' has expired on {$expiryDate->format('Y-m-d')}"
                );
            } elseif ($daysUntilExpiry <= 7) {
                // Expiring soon (within 7 days)
                $alert = $this->checkAndCreateAlert(
                    $supply,
                    'expiring_soon',
                    'warning',
                    "Supply '{$supply->name}' will expire in {$daysUntilExpiry} days on {$expiryDate->format('Y-m-d')}"
                );
            }

            if (isset($alert) && $alert) {
                $alerts[] = $alert;
                unset($alert);
            }
        }

        return $alerts;
    }

    /**
     * Check and create alert if not already exists
     */
    public function checkAndCreateAlert(Supply $supply, string $alertType, string $severity, string $message): ?SupplyAlert
    {
        // Check for existing unresolved alert of same type for this supply
        $existingAlert = SupplyAlert::where('supply_id', $supply->id)
            ->where('alert_type', $alertType)
            ->where('is_resolved', false)
            ->first();

        if ($existingAlert) {
            // Update existing alert with current stock info
            $existingAlert->update([
                'current_stock' => $supply->current_stock,
                'min_stock_level' => $supply->min_stock_level,
                'expiry_date' => $supply->metadata['expiry_date'] ?? null,
            ]);
            return $existingAlert;
        }

        // Create new alert
        $alert = SupplyAlert::create([
            'supply_id' => $supply->id,
            'alert_type' => $alertType,
            'severity' => $severity,
            'message' => $message,
            'current_stock' => $supply->current_stock,
            'min_stock_level' => $supply->min_stock_level,
            'expiry_date' => $supply->metadata['expiry_date'] ?? null,
            'is_resolved' => false,
        ]);

        // Send email notification if enabled
        $this->sendEmailNotifications($alert);

        return $alert;
    }

    /**
     * Send email notifications for alert
     */
    public function sendEmailNotifications(SupplyAlert $alert): void
    {
        try {
            // Check if email notifications are enabled in settings
            $emailEnabled = Setting::where('key', 'inventory.email_alerts_enabled')
                ->value('value') ?? true;

            if (!$emailEnabled) {
                return;
            }

            // Get recipient emails from settings
            $recipients = Setting::where('key', 'inventory.alert_recipients')
                ->value('value') ?? config('mail.admin_email');

            if (!$recipients) {
                return;
            }

            // Convert to array if it's a comma-separated string
            if (is_string($recipients)) {
                $recipients = array_map('trim', explode(',', $recipients));
            }

            // Send email
            foreach ((array)$recipients as $recipient) {
                Mail::to($recipient)->send(new SupplyAlertMail($alert));
            }

            Log::info("Alert email sent for supply alert #{$alert->id} to " . implode(', ', (array)$recipients));
        } catch (\Exception $e) {
            Log::error("Failed to send alert email: " . $e->getMessage());
        }
    }

    /**
     * Get alert statistics for dashboard
     */
    public function getAlertStatistics(): array
    {
        $totalAlerts = SupplyAlert::count();
        $unresolvedAlerts = SupplyAlert::unresolved()->count();
        $criticalAlerts = SupplyAlert::unresolved()->bySeverity('critical')->count();
        $warningAlerts = SupplyAlert::unresolved()->bySeverity('warning')->count();
        $infoAlerts = SupplyAlert::unresolved()->bySeverity('info')->count();

        $alertsByType = SupplyAlert::unresolved()
            ->selectRaw('alert_type, count(*) as count')
            ->groupBy('alert_type')
            ->pluck('count', 'alert_type')
            ->toArray();

        return [
            'total' => $totalAlerts,
            'unresolved' => $unresolvedAlerts,
            'critical' => $criticalAlerts,
            'warning' => $warningAlerts,
            'info' => $infoAlerts,
            'by_type' => $alertsByType,
        ];
    }

    /**
     * Resolve alert
     */
    public function resolveAlert(SupplyAlert $alert, int $userId): bool
    {
        try {
            $alert->resolve($userId);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to resolve alert #{$alert->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk resolve alerts
     */
    public function bulkResolveAlerts(array $alertIds, int $userId): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($alertIds as $alertId) {
            try {
                $alert = SupplyAlert::find($alertId);
                
                if ($alert && !$alert->is_resolved) {
                    $alert->resolve($userId);
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Alert #{$alertId} not found or already resolved";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Alert #{$alertId}: " . $e->getMessage();
            }
        }

        return $results;
    }
}