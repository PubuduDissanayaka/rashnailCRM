<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BusinessHoursService;
use Carbon\Carbon;

class BusinessHoursServiceTest extends TestCase
{
    protected BusinessHoursService $service;
    protected array $businessHoursConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new BusinessHoursService();
        
        // Default business hours configuration for testing
        $this->businessHoursConfig = [
            'grace_period_minutes' => 15,
            'overtime_start_after_hours' => 1,
            'minimum_shift_hours' => 4,
            'maximum_shift_hours' => 12,
            'half_day_threshold_hours' => 4,
            'weekdays' => [
                'monday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                'tuesday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                'wednesday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                'thursday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                'friday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                'saturday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
                'sunday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
            ]
        ];
    }

    /** @test */
    public function it_can_get_business_hours_for_a_specific_day()
    {
        // Test Monday (enabled day)
        $monday = Carbon::parse('2025-12-22'); // Monday
        $hours = $this->service->getBusinessHoursForDate($monday, $this->businessHoursConfig);
        
        $this->assertTrue($hours['enabled']);
        $this->assertEquals('09:00', $hours['open']);
        $this->assertEquals('17:00', $hours['close']);
        
        // Test Saturday (disabled day)
        $saturday = Carbon::parse('2025-12-27'); // Saturday
        $hours = $this->service->getBusinessHoursForDate($saturday, $this->businessHoursConfig);
        
        $this->assertFalse($hours['enabled']);
        $this->assertNull($hours['open']);
        $this->assertNull($hours['close']);
    }

    /** @test */
    public function it_can_determine_if_a_date_is_a_business_day()
    {
        // Monday should be a business day
        $monday = Carbon::parse('2025-12-22');
        $this->assertTrue($this->service->isBusinessDay($monday, $this->businessHoursConfig));
        
        // Saturday should not be a business day
        $saturday = Carbon::parse('2025-12-27');
        $this->assertFalse($this->service->isBusinessDay($saturday, $this->businessHoursConfig));
    }

    /** @test */
    public function it_can_calculate_late_arrival_status()
    {
        $checkInTime = Carbon::parse('2025-12-22 09:20:00'); // 20 minutes after 9:00
        $businessOpen = Carbon::parse('2025-12-22 09:00:00');
        $gracePeriod = 15;
        
        $status = $this->service->calculateLateArrivalStatus($checkInTime, $businessOpen, $gracePeriod);
        
        $this->assertEquals('late', $status);
        $this->assertEquals(20, $status['minutes_late']);
        
        // Test on-time arrival (within grace period)
        $checkInTime = Carbon::parse('2025-12-22 09:10:00'); // 10 minutes after 9:00
        $status = $this->service->calculateLateArrivalStatus($checkInTime, $businessOpen, $gracePeriod);
        
        $this->assertEquals('on_time', $status);
        $this->assertEquals(10, $status['minutes_late']);
        
        // Test early arrival
        $checkInTime = Carbon::parse('2025-12-22 08:45:00'); // 15 minutes before 9:00
        $status = $this->service->calculateLateArrivalStatus($checkInTime, $businessOpen, $gracePeriod);
        
        $this->assertEquals('early', $status);
        $this->assertEquals(0, $status['minutes_late']);
    }

    /** @test */
    public function it_can_calculate_early_departure_status()
    {
        $checkOutTime = Carbon::parse('2025-12-22 16:30:00'); // 30 minutes before 17:00
        $businessClose = Carbon::parse('2025-12-22 17:00:00');
        
        $status = $this->service->calculateEarlyDepartureStatus($checkOutTime, $businessClose);
        
        $this->assertEquals('early', $status);
        $this->assertEquals(30, $status['minutes_early']);
        
        // Test on-time departure
        $checkOutTime = Carbon::parse('2025-12-22 17:00:00'); // Exactly at closing
        $status = $this->service->calculateEarlyDepartureStatus($checkOutTime, $businessClose);
        
        $this->assertEquals('on_time', $status);
        $this->assertEquals(0, $status['minutes_early']);
        
        // Test late departure
        $checkOutTime = Carbon::parse('2025-12-22 17:30:00'); // 30 minutes after closing
        $status = $this->service->calculateEarlyDepartureStatus($checkOutTime, $businessClose);
        
        $this->assertEquals('late', $status);
        $this->assertEquals(0, $status['minutes_early']);
    }

    /** @test */
    public function it_can_calculate_overtime_hours()
    {
        $checkOutTime = Carbon::parse('2025-12-22 18:30:00'); // 1.5 hours after 17:00
        $businessClose = Carbon::parse('2025-12-22 17:00:00');
        $overtimeStartAfter = 1; // Overtime starts 1 hour after closing
        
        $overtime = $this->service->calculateOvertimeHours($checkOutTime, $businessClose, $overtimeStartAfter);
        
        $this->assertEquals(0.5, $overtime); // 0.5 hours of overtime (18:30 - 18:00)
        
        // Test no overtime (departure within overtime buffer)
        $checkOutTime = Carbon::parse('2025-12-22 17:45:00'); // 45 minutes after closing
        $overtime = $this->service->calculateOvertimeHours($checkOutTime, $businessClose, $overtimeStartAfter);
        
        $this->assertEquals(0, $overtime);
        
        // Test exact overtime start
        $checkOutTime = Carbon::parse('2025-12-22 18:00:00'); // Exactly 1 hour after closing
        $overtime = $this->service->calculateOvertimeHours($checkOutTime, $businessClose, $overtimeStartAfter);
        
        $this->assertEquals(0, $overtime);
    }

    /** @test */
    public function it_can_determine_attendance_status_based_on_hours_worked()
    {
        $hoursWorked = 3.5;
        $minimumShiftHours = 4;
        $halfDayThreshold = 4;
        
        $status = $this->service->determineAttendanceStatusByHours(
            $hoursWorked, 
            $minimumShiftHours, 
            $halfDayThreshold
        );
        
        $this->assertEquals('half_day', $status);
        
        // Test full day
        $hoursWorked = 8;
        $status = $this->service->determineAttendanceStatusByHours(
            $hoursWorked, 
            $minimumShiftHours, 
            $halfDayThreshold
        );
        
        $this->assertEquals('full_day', $status);
        
        // Test insufficient hours
        $hoursWorked = 2;
        $status = $this->service->determineAttendanceStatusByHours(
            $hoursWorked, 
            $minimumShiftHours, 
            $halfDayThreshold
        );
        
        $this->assertEquals('insufficient', $status);
    }

    /** @test */
    public function it_can_validate_shift_duration()
    {
        $hoursWorked = 10;
        $minimumShiftHours = 4;
        $maximumShiftHours = 12;
        
        $validation = $this->service->validateShiftDuration(
            $hoursWorked, 
            $minimumShiftHours, 
            $maximumShiftHours
        );
        
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        
        // Test too short shift
        $hoursWorked = 3;
        $validation = $this->service->validateShiftDuration(
            $hoursWorked, 
            $minimumShiftHours, 
            $maximumShiftHours
        );
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('Shift duration is below minimum required hours', $validation['errors']);
        
        // Test too long shift
        $hoursWorked = 13;
        $validation = $this->service->validateShiftDuration(
            $hoursWorked, 
            $minimumShiftHours, 
            $maximumShiftHours
        );
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('Shift duration exceeds maximum allowed hours', $validation['errors']);
    }

    /** @test */
    public function it_can_calculate_effective_working_hours()
    {
        $checkIn = Carbon::parse('2025-12-22 09:15:00');
        $checkOut = Carbon::parse('2025-12-22 17:30:00');
        $businessOpen = Carbon::parse('2025-12-22 09:00:00');
        $businessClose = Carbon::parse('2025-12-22 17:00:00');
        
        $effectiveHours = $this->service->calculateEffectiveWorkingHours(
            $checkIn, 
            $checkOut, 
            $businessOpen, 
            $businessClose
        );
        
        // Should be 7.75 hours (9:00-17:00 = 8 hours, but started 15 minutes late)
        $this->assertEquals(7.75, $effectiveHours);
        
        // Test early check-in
        $checkIn = Carbon::parse('2025-12-22 08:45:00');
        $effectiveHours = $this->service->calculateEffectiveWorkingHours(
            $checkIn, 
            $checkOut, 
            $businessOpen, 
            $businessClose
        );
        
        // Should still be 8 hours (early check-in doesn't count extra)
        $this->assertEquals(8, $effectiveHours);
        
        // Test late check-out
        $checkOut = Carbon::parse('2025-12-22 18:00:00');
        $effectiveHours = $this->service->calculateEffectiveWorkingHours(
            $checkIn, 
            $checkOut, 
            $businessOpen, 
            $businessClose
        );
        
        // Should be 8 hours (late check-out doesn't count extra within business hours)
        $this->assertEquals(8, $effectiveHours);
    }

    /** @test */
    public function it_can_generate_attendance_summary()
    {
        $checkIn = Carbon::parse('2025-12-22 09:20:00');
        $checkOut = Carbon::parse('2025-12-22 17:30:00');
        $date = Carbon::parse('2025-12-22');
        
        $summary = $this->service->generateAttendanceSummary(
            $date,
            $checkIn,
            $checkOut,
            $this->businessHoursConfig
        );
        
        $this->assertArrayHasKey('status', $summary);
        $this->assertArrayHasKey('hours_worked', $summary);
        $this->assertArrayHasKey('overtime_hours', $summary);
        $this->assertArrayHasKey('late_minutes', $summary);
        $this->assertArrayHasKey('early_minutes', $summary);
        $this->assertArrayHasKey('effective_hours', $summary);
        
        // With 20 minutes late arrival, should be marked as late
        $this->assertEquals('late', $summary['status']);
        $this->assertEquals(20, $summary['late_minutes']);
        $this->assertEquals(0.5, $summary['overtime_hours']); // 30 minutes after closing, 1 hour buffer
    }

    /** @test */
    public function it_handles_holidays_correctly()
    {
        $holidayDate = Carbon::parse('2025-12-25'); // Christmas
        $holidays = [$holidayDate->format('Y-m-d')];
        
        // Test with holiday
        $this->assertTrue($this->service->isHoliday($holidayDate, $holidays));
        
        // Test with non-holiday
        $regularDate = Carbon::parse('2025-12-24');
        $this->assertFalse($this->service->isHoliday($regularDate, $holidays));
    }

    /** @test */
    public function it_can_get_default_business_hours_config()
    {
        $defaultConfig = $this->service->getDefaultBusinessHoursConfig();
        
        $this->assertArrayHasKey('grace_period_minutes', $defaultConfig);
        $this->assertArrayHasKey('overtime_start_after_hours', $defaultConfig);
        $this->assertArrayHasKey('minimum_shift_hours', $defaultConfig);
        $this->assertArrayHasKey('maximum_shift_hours', $defaultConfig);
        $this->assertArrayHasKey('half_day_threshold_hours', $defaultConfig);
        $this->assertArrayHasKey('weekdays', $defaultConfig);
        
        // Check default values
        $this->assertEquals(15, $defaultConfig['grace_period_minutes']);
        $this->assertEquals(1, $defaultConfig['overtime_start_after_hours']);
        $this->assertEquals(4, $defaultConfig['minimum_shift_hours']);
        $this->assertEquals(12, $defaultConfig['maximum_shift_hours']);
        $this->assertEquals(4, $defaultConfig['half_day_threshold_hours']);
    }
}