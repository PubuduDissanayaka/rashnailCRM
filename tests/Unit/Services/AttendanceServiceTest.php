<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AttendanceService;
use App\Services\BusinessHoursService;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceService $service;
    protected BusinessHoursService $businessHoursService;
    protected User $user;
    protected array $businessHoursConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->businessHoursService = new BusinessHoursService();
        $this->service = new AttendanceService($this->businessHoursService);
        
        $this->user = User::factory()->create();
        
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
    public function it_can_clock_in_a_user()
    {
        $now = Carbon::parse('2025-12-22 09:00:00');
        Carbon::setTestNow($now);
        
        $result = $this->service->clockIn($this->user->id, [], $this->businessHoursConfig);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Clocked in successfully', $result['message']);
        $this->assertArrayHasKey('attendance', $result);
        
        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', $now->toDateString())
            ->first();
            
        $this->assertNotNull($attendance);
        $this->assertEquals($now->format('H:i:s'), $attendance->check_in->format('H:i:s'));
        $this->assertEquals('present', $attendance->status);
    }

    /** @test */
    public function it_can_clock_out_a_user()
    {
        // First clock in
        $clockInTime = Carbon::parse('2025-12-22 09:00:00');
        Carbon::setTestNow($clockInTime);
        
        $this->service->clockIn($this->user->id, [], $this->businessHoursConfig);
        
        // Then clock out
        $clockOutTime = Carbon::parse('2025-12-22 17:00:00');
        Carbon::setTestNow($clockOutTime);
        
        $result = $this->service->clockOut($this->user->id, [], $this->businessHoursConfig);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Clocked out successfully', $result['message']);
        $this->assertArrayHasKey('hours_worked', $result);
        
        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', $clockInTime->toDateString())
            ->first();
            
        $this->assertNotNull($attendance->check_out);
        $this->assertEquals($clockOutTime->format('H:i:s'), $attendance->check_out->format('H:i:s'));
        $this->assertEquals(8, $attendance->hours_worked);
    }

    /** @test */
    public function it_cannot_clock_in_twice_on_same_day()
    {
        $now = Carbon::parse('2025-12-22 09:00:00');
        Carbon::setTestNow($now);
        
        // First clock in
        $this->service->clockIn($this->user->id, [], $this->businessHoursConfig);
        
        // Try to clock in again
        $result = $this->service->clockIn($this->user->id, [], $this->businessHoursConfig);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Already clocked in today', $result['message']);
    }

    /** @test */
    public function it_cannot_clock_out_without_clocking_in()
    {
        $now = Carbon::parse('2025-12-22 17:00:00');
        Carbon::setTestNow($now);
        
        $result = $this->service->clockOut($this->user->id, [], $this->businessHoursConfig);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('No active clock-in found', $result['message']);
    }

    /** @test */
    public function it_can_calculate_daily_attendance_summary()
    {
        // Create some attendance records
        $date = Carbon::parse('2025-12-22');
        
        // User 1: Present
        $user1 = User::factory()->create();
        Attendance::create([
            'user_id' => $user1->id,
            'date' => $date,
            'check_in' => $date->copy()->setTime(9, 0),
            'check_out' => $date->copy()->setTime(17, 0),
            'status' => 'present',
            'hours_worked' => 8,
        ]);
        
        // User 2: Late
        $user2 = User::factory()->create();
        Attendance::create([
            'user_id' => $user2->id,
            'date' => $date,
            'check_in' => $date->copy()->setTime(9, 30),
            'check_out' => $date->copy()->setTime(17, 0),
            'status' => 'late',
            'hours_worked' => 7.5,
        ]);
        
        // User 3: Absent (no record)
        User::factory()->create();
        
        $summary = $this->service->getDailyAttendanceSummary($date);
        
        $this->assertEquals(1, $summary['present']);
        $this->assertEquals(1, $summary['late']);
        $this->assertEquals(1, $summary['absent']);
        $this->assertEquals(0, $summary['on_leave']);
        $this->assertEquals(3, $summary['total_staff']);
    }

    /** @test */
    public function it_can_get_user_attendance_for_period()
    {
        $startDate = Carbon::parse('2025-12-01');
        $endDate = Carbon::parse('2025-12-31');
        
        // Create attendance records for the user
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-15'),
            'check_in' => Carbon::parse('2025-12-15 09:00'),
            'check_out' => Carbon::parse('2025-12-15 17:00'),
            'status' => 'present',
            'hours_worked' => 8,
        ]);
        
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-16'),
            'check_in' => Carbon::parse('2025-12-16 09:30'),
            'check_out' => Carbon::parse('2025-12-16 17:00'),
            'status' => 'late',
            'hours_worked' => 7.5,
        ]);
        
        $attendance = $this->service->getUserAttendanceForPeriod(
            $this->user->id, 
            $startDate, 
            $endDate
        );
        
        $this->assertCount(2, $attendance);
        $this->assertEquals('present', $attendance[0]->status);
        $this->assertEquals('late', $attendance[1]->status);
    }

    /** @test */
    public function it_can_calculate_user_attendance_stats()
    {
        // Create attendance records for the user
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-15'),
            'check_in' => Carbon::parse('2025-12-15 09:00'),
            'check_out' => Carbon::parse('2025-12-15 17:00'),
            'status' => 'present',
            'hours_worked' => 8,
        ]);
        
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-16'),
            'check_in' => Carbon::parse('2025-12-16 09:30'),
            'check_out' => Carbon::parse('2025-12-16 17:00'),
            'status' => 'late',
            'hours_worked' => 7.5,
        ]);
        
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-17'),
            'check_in' => Carbon::parse('2025-12-17 09:00'),
            'check_out' => Carbon::parse('2025-12-17 13:00'),
            'status' => 'half_day',
            'hours_worked' => 4,
        ]);
        
        $startDate = Carbon::parse('2025-12-01');
        $endDate = Carbon::parse('2025-12-31');
        
        $stats = $this->service->getUserAttendanceStats(
            $this->user->id, 
            $startDate, 
            $endDate
        );
        
        $this->assertEquals(3, $stats['total_days']);
        $this->assertEquals(1, $stats['present_days']);
        $this->assertEquals(1, $stats['late_days']);
        $this->assertEquals(1, $stats['half_day_days']);
        $this->assertEquals(0, $stats['absent_days']);
        $this->assertEquals(19.5, $stats['total_hours']); // 8 + 7.5 + 4
        $this->assertEquals(6.5, $stats['average_hours_per_day']); // 19.5 / 3
    }

    /** @test */
    public function it_can_validate_clock_in_time_against_business_hours()
    {
        $date = Carbon::parse('2025-12-22'); // Monday
        $checkInTime = Carbon::parse('2025-12-22 08:00:00'); // Before business hours
        
        $result = $this->service->validateClockInTime(
            $checkInTime, 
            $this->businessHoursConfig
        );
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Cannot clock in before business hours', $result['errors']);
        
        // Test valid clock-in time
        $checkInTime = Carbon::parse('2025-12-22 09:00:00'); // At business opening
        $result = $this->service->validateClockInTime(
            $checkInTime, 
            $this->businessHoursConfig
        );
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        
        // Test non-business day
        $date = Carbon::parse('2025-12-27'); // Saturday
        $checkInTime = Carbon::parse('2025-12-27 09:00:00');
        $result = $this->service->validateClockInTime(
            $checkInTime, 
            $this->businessHoursConfig
        );
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Cannot clock in on non-business day', $result['errors']);
    }

    /** @test */
    public function it_can_validate_clock_out_time_against_business_hours()
    {
        $checkInTime = Carbon::parse('2025-12-22 09:00:00');
        $checkOutTime = Carbon::parse('2025-12-22 16:00:00'); // Before business closing
        
        $result = $this->service->validateClockOutTime(
            $checkInTime,
            $checkOutTime,
            $this->businessHoursConfig
        );
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        
        // Test clock-out before clock-in
        $checkOutTime = Carbon::parse('2025-12-22 08:00:00'); // Before clock-in
        $result = $this->service->validateClockOutTime(
            $checkInTime,
            $checkOutTime,
            $this->businessHoursConfig
        );
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Clock-out time cannot be before clock-in time', $result['errors']);
        
        // Test maximum shift duration
        $checkOutTime = Carbon::parse('2025-12-22 22:00:00'); // 13 hours after clock-in
        $result = $this->service->validateClockOutTime(
            $checkInTime,
            $checkOutTime,
            $this->businessHoursConfig
        );
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Shift duration exceeds maximum allowed hours', $result['errors']);
    }

    /** @test */
    public function it_can_generate_attendance_report()
    {
        // Create multiple attendance records
        $users = User::factory()->count(3)->create();
        
        foreach ($users as $index => $user) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => Carbon::parse('2025-12-15'),
                'check_in' => Carbon::parse('2025-12-15 09:00'),
                'check_out' => Carbon::parse('2025-12-15 17:00'),
                'status' => $index === 0 ? 'present' : ($index === 1 ? 'late' : 'half_day'),
                'hours_worked' => $index === 2 ? 4 : 8,
            ]);
        }
        
        $startDate = Carbon::parse('2025-12-01');
        $endDate = Carbon::parse('2025-12-31');
        
        $report = $this->service->generateAttendanceReport($startDate, $endDate);
        
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('data', $report);
        $this->assertArrayHasKey('stats', $report);
        
        $this->assertCount(3, $report['data']);
        $this->assertEquals(3, $report['summary']['total_records']);
        $this->assertEquals(1, $report['summary']['present_count']);
        $this->assertEquals(1, $report['summary']['late_count']);
        $this->assertEquals(1, $report['summary']['half_day_count']);
    }

    /** @test */
    public function it_can_handle_manual_attendance_entry()
    {
        $date = Carbon::parse('2025-12-22');
        $checkIn = Carbon::parse('2025-12-22 09:00:00');
        $checkOut = Carbon::parse('2025-12-22 17:00:00');
        
        $data = [
            'user_id' => $this->user->id,
            'date' => $date->toDateString(),
            'check_in' => $checkIn->format('H:i'),
            'check_out' => $checkOut->format('H:i'),
            'notes' => 'Manual entry test',
        ];
        
        $result = $this->service->createManualAttendance($data, $this->businessHoursConfig);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Attendance record created successfully', $result['message']);
        
        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', $date)
            ->first();
            
        $this->assertNotNull($attendance);
        $this->assertEquals($checkIn->format('H:i:s'), $attendance->check_in->format('H:i:s'));
        $this->assertEquals($checkOut->format('H:i:s'), $attendance->check_out->format('H:i:s'));
        $this->assertEquals('present', $attendance->status);
        $this->assertEquals(8, $attendance->hours_worked);
    }

    /** @test */
    public function it_can_update_attendance_record()
    {
        // Create an attendance record
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-22'),
            'check_in' => Carbon::parse('2025-12-22 09:00'),
            'check_out' => Carbon::parse('2025-12-22 17:00'),
            'status' => 'present',
            'hours_worked' => 8,
            'notes' => 'Original notes',
        ]);
        
        $updateData = [
            'check_in' => '09:30',
            'check_out' => '17:30',
            'notes' => 'Updated notes',
        ];
        
        $result = $this->service->updateAttendance($attendance->id, $updateData, $this->businessHoursConfig);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Attendance record updated successfully', $result['message']);
        
        $attendance->refresh();
        $this->assertEquals('09:30:00', $attendance->check_in->format('H:i:s'));
        $this->assertEquals('17:30:00', $attendance->check_out->format('H:i:s'));
        $this->assertEquals('Updated notes', $attendance->notes);
        $this->assertEquals(8.5, $attendance->hours_worked); // 9:30 to 17:30 = 8 hours
    }

    /** @test */
    public function it_can_delete_attendance_record()
    {
        // Create an attendance record
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-22'),
            'check_in' => Carbon::parse('2025-12-22 09:00'),
            'check_out' => Carbon::parse('2025-12-22 17:00'),
            'status' => 'present',
            'hours_worked' => 8,
        ]);
        
        $result = $this->service->deleteAttendance($attendance->id);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Attendance record deleted successfully', $result['message']);
        
        $this->assertNull(Attendance::find($attendance->id));
    }

    /** @test */
    public function it_can_get_attendance_by_id()
    {
        // Create an attendance record
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-22'),
            'check_in' => Carbon::parse('2025-12-22 09:00'),
            'check_out' => Carbon::parse('2025-12-22 17:00'),
            'status' => 'present',
            'hours_worked' => 8,
        ]);
        
        $result = $this->service->getAttendanceById($attendance->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($attendance->id, $result->id);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertEquals('present', $result->status);
    }

    /** @test */
    public function it_can_get_todays_attendance_for_user()
    {
        $today = Carbon::today();
        
        // Create today's attendance record
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => $today,
            'check_in' => $today->copy()->setTime(9, 0),
            'check_out' => null,
            'status' => 'present',
            'hours_worked' => 0,
        ]);
        
        $result = $this->service->getTodaysAttendanceForUser($this->user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($attendance->id, $result->id);
        $this->assertNotNull($result->check_in);
        $this->assertNull($result->check_out);
    }

    /** @test */
    public function it_can_get_attendance_for_date_range()
    {
        $startDate = Carbon::parse('2025-12-01');
        $endDate = Carbon::parse('2025-12-31');
        
        // Create attendance records
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-15'),
            'check_in' => Carbon::parse('2025-12-15 09:00'),
            'check_out' => Carbon::parse('2025-12-15 17:00'),
            'status' => 'present',
            'hours_worked' => 8,
        ]);
        
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-16'),
            'check_in' => Carbon::parse('2025-12-16 09:30'),
            'check_out' => Carbon::parse('2025-12-16 17:00'),
            'status' => 'late',
            'hours_worked' => 7.5,
        ]);
        
        $result = $this->service->getAttendanceForDateRange($startDate, $endDate);
        
        $this->assertCount(2, $result);
        $this->assertEquals('present', $result[0]->status);
        $this->assertEquals('late', $result[1]->status);
    }

    /** @test */
    public function it_can_calculate_monthly_attendance_summary()
    {
        $year = 2025;
        $month = 12;
        
        // Create attendance records for the month
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-15'),
            'check_in' => Carbon::parse('2025-12-15 09:00'),
            'check_out' => Carbon::parse('2025-12-15 17:00'),
            'status' => 'present',
            'hours_worked' => 8,
        ]);
        
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::parse('2025-12-16'),
            'check_in' => Carbon::parse('2025-12-16 09:30'),
            'check_out' => Carbon::parse('2025-12-16 17:00'),
            'status' => 'late',
            'hours_worked' => 7.5,
        ]);
        
        // Another user
        $user2 = User::factory()->create();
        Attendance::create([
            'user_id' => $user2->id,
            'date' => Carbon::parse('2025-12-15'),
            'check_in' => Carbon::parse('2025-12-15 09:00'),
            'check_out' => Carbon::parse('2025-12-15 13:00'),
            'status' => 'half_day',
            'hours_worked' => 4,
        ]);
        
        $summary = $this->service->getMonthlyAttendanceSummary($year, $month);
        
        $this->assertEquals(2, $summary['present']);
        $this->assertEquals(1, $summary['late']);
        $this->assertEquals(1, $summary['half_day']);
        $this->assertEquals(0, $summary['absent']);
        $this->assertEquals(0, $summary['on_leave']);
        $this->assertEquals(19.5, $summary['total_hours']); // 8 + 7.5 + 4
    }
}