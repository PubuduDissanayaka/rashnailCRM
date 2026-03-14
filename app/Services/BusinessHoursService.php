<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class BusinessHoursService
{
    /**
     * Get business hours configuration
     */
    public function getConfig(): array
    {
        // Try to get configuration from multiple possible keys for backward compatibility
        // Priority: 1. UI config (attendance.business_hours), 2. Migration config (attendance.business_hours.config)
        $config = null;
        
        // First try the UI key (used by settings form)
        $config = Setting::get('attendance.business_hours');
        
        // If not found, try the migration config key
        if (!$config || $config === []) {
            $config = Setting::get('attendance.business_hours.config');
        }
        
        // Ensure config is an array if it was stored as a JSON string
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }
        
        // If still not found, use defaults
        if (!$config || $config === []) {
            $config = [
                'business_hours' => [
                    'monday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                    'tuesday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                    'wednesday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                    'thursday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                    'friday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                    'saturday' => ['open' => null, 'close' => null, 'enabled' => false],
                    'sunday' => ['open' => null, 'close' => null, 'enabled' => false],
                ],
                'grace_period_minutes' => 15,
                'overtime_threshold_minutes' => 0,
                'minimum_shift_hours' => 0,
                'maximum_shift_hours' => 12,
                'half_day_threshold_hours' => 4,
                'break_duration_minutes' => 60, // Default 1 hour break
            ];
        }

        // Transform configuration for backward compatibility
        $config = $this->transformConfigForCompatibility($config);

        return $config;
    }

    /**
     * Transform configuration for backward compatibility
     * - Converts 'weekdays' array to 'business_hours' array
     * - Maps 'overtime_start_after_hours' to 'overtime_threshold_minutes'
     * - Ensures 'break_duration_minutes' exists with default
     */
    private function transformConfigForCompatibility(array $config): array
    {
        // If config uses 'weekdays' instead of 'business_hours', rename it
        if (isset($config['weekdays']) && !isset($config['business_hours'])) {
            $config['business_hours'] = $config['weekdays'];
            unset($config['weekdays']);
        }

        // Map overtime_start_after_hours (hours) to overtime_threshold_minutes (minutes)
        if (isset($config['overtime_start_after_hours']) && !isset($config['overtime_threshold_minutes'])) {
            $config['overtime_threshold_minutes'] = (int) ($config['overtime_start_after_hours'] * 60);
        }

        // Ensure break_duration_minutes exists with default
        if (!isset($config['break_duration_minutes'])) {
            $config['break_duration_minutes'] = 60;
        }

        // Ensure all required fields have defaults
        $defaults = [
            'grace_period_minutes' => 15,
            'overtime_threshold_minutes' => 0,
            'minimum_shift_hours' => 0,
            'maximum_shift_hours' => 12,
            'half_day_threshold_hours' => 4,
            'break_duration_minutes' => 60,
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($config[$key])) {
                $config[$key] = $default;
            }
        }

        return $config;
    }

    /**
     * Get business hours for a specific date
     */
    public function getHoursForDate(CarbonInterface $date): ?array
    {
        $config = $this->getConfig();
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
        
        if (!isset($config['business_hours'][$dayOfWeek])) {
            return null;
        }
        
        $dayConfig = $config['business_hours'][$dayOfWeek];
        
        if (!$dayConfig['enabled']) {
            return null;
        }
        
        return [
            'open' => $dayConfig['open'] ? Carbon::parse($date->format('Y-m-d') . ' ' . $dayConfig['open']) : null,
            'close' => $dayConfig['close'] ? Carbon::parse($date->format('Y-m-d') . ' ' . $dayConfig['close']) : null,
            'enabled' => $dayConfig['enabled'],
            'day_of_week' => $dayOfWeek,
        ];
    }

    /**
     * Check if a date is a business day
     */
    public function isBusinessDay(CarbonInterface $date): bool
    {
        // Check if it's a holiday
        if (Holiday::isHoliday($date)) {
            return false;
        }
        
        $hours = $this->getHoursForDate($date);
        return $hours !== null && $hours['enabled'] && $hours['open'] !== null && $hours['close'] !== null;
    }

    /**
     * Get business day type
     */
    public function getBusinessDayType(CarbonInterface $date): string
    {
        if (Holiday::isHoliday($date)) {
            return 'holiday';
        }
        
        if (!$this->isBusinessDay($date)) {
            return 'weekend';
        }
        
        return 'regular';
    }

    /**
     * Get expected hours for a date
     */
    public function getExpectedHoursForDate(CarbonInterface $date): float
    {
        $hours = $this->getHoursForDate($date);
        
        if (!$hours || !$hours['open'] || !$hours['close']) {
            return 0;
        }
        
        $open = $hours['open'];
        $close = $hours['close'];
        
        // Calculate expected hours (excluding breaks)
        $totalMinutes = $open->diffInMinutes($close);
        $config = $this->getConfig();
        
        // If there's a standard break duration, subtract it
        $breakMinutes = $config['break_duration_minutes'] ?? 60;
        $netMinutes = max(0, $totalMinutes - $breakMinutes);
        
        return round($netMinutes / 60, 2);
    }

    /**
     * Check if a time is within business hours
     */
    public function isWithinBusinessHours(CarbonInterface $time): bool
    {
        $hours = $this->getHoursForDate($time);
        
        if (!$hours || !$hours['open'] || !$hours['close']) {
            return false;
        }
        
        return $time->between($hours['open'], $hours['close']);
    }

    /**
     * Check if check-in is late
     */
    public function isLateCheckIn(CarbonInterface $checkInTime): bool
    {
        $hours = $this->getHoursForDate($checkInTime);
        
        if (!$hours || !$hours['open']) {
            return false;
        }
        
        $config = $this->getConfig();
        $gracePeriod = $config['grace_period_minutes'] ?? 15;
        $graceEnd = $hours['open']->copy()->addMinutes($gracePeriod);
        
        return $checkInTime->greaterThan($graceEnd);
    }

    /**
     * Calculate late arrival minutes
     */
    public function calculateLateArrivalMinutes(CarbonInterface $checkInTime): int
    {
        if (!$this->isLateCheckIn($checkInTime)) {
            return 0;
        }
        
        $hours = $this->getHoursForDate($checkInTime);
        $config = $this->getConfig();
        $gracePeriod = $config['grace_period_minutes'] ?? 15;
        $graceEnd = $hours['open']->copy()->addMinutes($gracePeriod);
        
        return max(0, $graceEnd->diffInMinutes($checkInTime));
    }

    /**
     * Check if check-out is early departure
     */
    public function isEarlyDeparture(CarbonInterface $checkOutTime): bool
    {
        $hours = $this->getHoursForDate($checkOutTime);
        
        if (!$hours || !$hours['close']) {
            return false;
        }
        
        return $checkOutTime->lessThan($hours['close']);
    }

    /**
     * Calculate early departure minutes
     */
    public function calculateEarlyDepartureMinutes(CarbonInterface $checkOutTime): int
    {
        if (!$this->isEarlyDeparture($checkOutTime)) {
            return 0;
        }
        
        $hours = $this->getHoursForDate($checkOutTime);
        // Use absolute difference to ensure positive value when check-out is before close
        return abs($hours['close']->diffInMinutes($checkOutTime, false));
    }

    /**
     * Calculate overtime minutes
     */
    public function calculateOvertimeMinutes(CarbonInterface $checkOutTime): int
    {
        $hours = $this->getHoursForDate($checkOutTime);
        
        if (!$hours || !$hours['close']) {
            return 0;
        }
        
        $config = $this->getConfig();
        $overtimeThreshold = $config['overtime_threshold_minutes'] ?? 0;
        $overtimeStart = $hours['close']->copy()->addMinutes($overtimeThreshold);
        
        if ($checkOutTime->lessThanOrEqualTo($overtimeStart)) {
            return 0;
        }
        
        return $overtimeStart->diffInMinutes($checkOutTime);
    }

    /**
     * Get next business day
     */
    public function getNextBusinessDay(CarbonInterface $fromDate): CarbonInterface
    {
        $date = $fromDate->copy()->addDay();
        
        while (!$this->isBusinessDay($date)) {
            $date->addDay();
        }
        
        return $date;
    }

    /**
     * Get previous business day
     */
    public function getPreviousBusinessDay(CarbonInterface $fromDate): CarbonInterface
    {
        $date = $fromDate->copy()->subDay();
        
        while (!$this->isBusinessDay($date)) {
            $date->subDay();
        }
        
        return $date;
    }

    /**
     * Get grace period in minutes
     */
    public function getGracePeriodMinutes(): int
    {
        $config = $this->getConfig();
        return $config['grace_period_minutes'] ?? 15;
    }

    /**
     * Get overtime threshold in minutes
     */
    public function getOvertimeThresholdMinutes(): int
    {
        $config = $this->getConfig();
        return $config['overtime_threshold_minutes'] ?? 0;
    }

    /**
     * Get half day threshold in hours
     */
    public function getHalfDayThresholdHours(): float
    {
        $config = $this->getConfig();
        return $config['half_day_threshold_hours'] ?? 4;
    }

    /**
     * Determine attendance status based on hours worked
     */
    public function determineStatus(float $hoursWorked, bool $isLate = false): string
    {
        $config = $this->getConfig();
        $halfDayThreshold = $config['half_day_threshold_hours'] ?? 4;
        
        if ($hoursWorked <= 0) {
            return 'absent';
        }
        
        if ($hoursWorked < $halfDayThreshold) {
            return 'half_day';
        }
        
        return $isLate ? 'late' : 'present';
    }

    /**
     * Validate if hours worked are within acceptable range
     */
    public function validateHoursWorked(float $hoursWorked): bool
    {
        $config = $this->getConfig();
        $minimum = $config['minimum_shift_hours'] ?? 0;
        $maximum = $config['maximum_shift_hours'] ?? 12;
        
        return $hoursWorked >= $minimum && $hoursWorked <= $maximum;
    }
}