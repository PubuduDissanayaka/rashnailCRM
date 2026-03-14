<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkHourReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user with permissions
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'administrator',
        ]);
        $this->adminUser->assignRole('administrator');

        // Create staff user
        $this->staffUser = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'role' => 'staff',
        ]);
        $this->staffUser->assignRole('staff');

        // Create sample attendance data
        $this->createSampleAttendanceData();
    }

    private function createSampleAttendanceData(): void
    {
        // Create attendance records for the last 7 days
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            Attendance::create([
                'user_id' => $this->staffUser->id,
                'date' => $date->format('Y-m-d'),
                'check_in' => $date->copy()->setTime(9, 0),
                'check_out' => $date->copy()->setTime(17, 0),
                'hours_worked' => 8.0,
                'expected_hours' => 8.0,
                'overtime_hours' => 0.0,
                'total_break_minutes' => 60,
                'status' => 'present',
                'late_arrival_minutes' => 0,
                'early_departure_minutes' => 0,
                'attendance_type' => 'regular',
                'business_hours_type' => 'standard',
                'calculated_using_business_hours' => true,
            ]);
        }
    }

    /** @test */
    public function admin_can_access_work_hour_reports_page()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/reports/work-hours');

        $response->assertStatus(200);
        $response->assertSee('Work Hour Reports');
        $response->assertSee('Comprehensive staff work hour analytics');
    }

    /** @test */
    public function staff_user_cannot_access_work_hour_reports_without_permission()
    {
        $this->actingAs($this->staffUser);

        $response = $this->get('/reports/work-hours');

        $response->assertStatus(403);
    }

    /** @test */
    public function can_get_staff_summary_via_api()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/reports/work-hours/summary?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'staff_id',
                    'staff_name',
                    'email',
                    'total_days',
                    'total_hours',
                    'total_expected_hours',
                    'total_overtime',
                    'attendance_rate',
                    'compliance_rate',
                ]
            ],
            'summary' => [
                'total_staff',
                'total_hours',
                'overall_attendance_rate',
                'total_overtime',
            ]
        ]);
    }

    /** @test */
    public function can_get_detailed_report_via_api()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/reports/work-hours/detail?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'per_page' => 10,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'check_in',
                        'check_out',
                        'hours_worked',
                        'expected_hours',
                        'overtime_hours',
                        'status',
                        'staff_name',
                    ]
                ],
                'total',
            ]
        ]);
    }

    /** @test */
    public function can_get_filter_options()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/reports/work-hours/filter-options');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'staff_users',
                'roles',
                'date_ranges',
                'status_options',
            ]
        ]);
    }

    /** @test */
    public function can_export_csv_with_valid_filters()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/reports/work-hours/export/csv', [
            'start_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition');
    }

    /** @test */
    public function export_fails_with_invalid_date_range()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/reports/work-hours/export/csv', [
            'start_date' => Carbon::now()->subDays(400)->format('Y-m-d'), // More than 1 year
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors',
        ]);
    }

    /** @test */
    public function rate_limiting_works_for_export_endpoints()
    {
        $this->actingAs($this->adminUser);

        // Make multiple export requests
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/reports/work-hours/export/csv', [
                'start_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
                'end_date' => Carbon::now()->format('Y-m-d'),
            ]);
        }

        // 6th request should be rate limited
        $response->assertStatus(429);
        $response->assertJsonStructure([
            'success',
            'message',
            'retry_after',
        ]);
    }

    /** @test */
    public function can_view_staff_detail_report()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/reports/work-hours/staff/' . $this->staffUser->id . '?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'staff_info',
                'summary',
                'detailed_records',
            ]
        ]);
    }

    /** @test */
    public function filters_work_correctly()
    {
        $this->actingAs($this->adminUser);

        // Test staff filter
        $response = $this->get('/reports/work-hours/summary?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'staff_id' => $this->staffUser->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals($this->staffUser->id, $data['data'][0]['staff_id']);

        // Test status filter
        $response = $this->get('/reports/work-hours/detail?' . http_build_query([
            'start_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'present',
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThan(0, $data['data']['total']);
    }
}