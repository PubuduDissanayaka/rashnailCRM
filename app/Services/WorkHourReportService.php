<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\DTOs\FilterCriteria;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class WorkHourReportService
{
    /**
     * Get staff work hour summary for a date range
     *
     * @param FilterCriteria $filters
     * @return Collection
     */
    public function getStaffSummary(FilterCriteria $filters): Collection
    {
        $cacheKey = $this->generateCacheKey('staff_summary', $filters);
        
        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($filters) {
            $query = $this->buildBaseQuery($filters);

            // Select aggregated metrics
            $query->select([
                'users.id as staff_id',
                'users.name as staff_name',
                'users.email',
                DB::raw('COUNT(attendances.id) as total_days'),
                DB::raw('COALESCE(SUM(attendances.hours_worked), 0) as total_hours'),
                DB::raw('COALESCE(SUM(attendances.expected_hours), 0) as total_expected_hours'),
                DB::raw('COALESCE(SUM(attendances.overtime_hours), 0) as total_overtime'),
                DB::raw('COALESCE(AVG(attendances.hours_worked), 0) as avg_hours_per_day'),
                DB::raw('COUNT(CASE WHEN attendances.status IN ("present", "late") THEN 1 END) as present_days'),
                DB::raw('COUNT(CASE WHEN attendances.status = "late" THEN 1 END) as late_days'),
                DB::raw('COUNT(CASE WHEN attendances.status = "absent" THEN 1 END) as absent_days'),
                DB::raw('COUNT(CASE WHEN attendances.status = "leave" THEN 1 END) as leave_days'),
                DB::raw('COUNT(CASE WHEN attendances.status = "half_day" THEN 1 END) as half_day_days'),
                DB::raw('COALESCE(SUM(attendances.total_break_minutes), 0) as total_break_minutes'),
                DB::raw('COALESCE(SUM(attendances.late_arrival_minutes), 0) as total_late_minutes'),
                DB::raw('COALESCE(SUM(attendances.early_departure_minutes), 0) as total_early_departure_minutes'),
            ])
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('users.name');

            $results = $query->get();

            // Calculate derived metrics
            return $results->map(function ($item) {
                $item->attendance_rate = $item->total_days > 0
                    ? round(($item->present_days / $item->total_days) * 100, 2)
                    : 0;
                
                $item->compliance_rate = $item->total_expected_hours > 0
                    ? round(($item->total_hours / $item->total_expected_hours) * 100, 2)
                    : 0;
                
                $item->total_break_hours = round($item->total_break_minutes / 60, 2);
                $item->net_hours = round($item->total_hours - $item->total_break_hours, 2);
                
                return $item;
            });
        });
    }

    /**
     * Get detailed attendance report with pagination
     *
     * @param FilterCriteria $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDetailedReport(FilterCriteria $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);

        // Select detailed columns
        $query->select([
            'attendances.id',
            'attendances.date',
            'attendances.check_in',
            'attendances.check_out',
            'attendances.hours_worked',
            'attendances.expected_hours',
            'attendances.overtime_hours',
            'attendances.total_break_minutes',
            'attendances.status',
            'attendances.late_arrival_minutes',
            'attendances.early_departure_minutes',
            'attendances.attendance_type',
            'attendances.business_hours_type',
            'attendances.calculated_using_business_hours',
            'users.name as staff_name',
            'users.email',
        ])
        ->orderBy('attendances.date', 'desc')
        ->orderBy('users.name');

        return $query->paginate($perPage);
    }

    /**
     * Get filter options for the report
     *
     * @return array
     */
    public function getFilterOptions(): array
    {
        // Get all staff users (users with staff role)
        $staffUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'staff');
        })
        ->select(['id', 'name', 'email'])
        ->orderBy('name')
        ->get();

        // Get available roles for filtering
        $roles = DB::table('roles')
            ->whereIn('name', ['staff', 'manager', 'supervisor']) // Common staff roles
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        // Get date range suggestions
        $dateRanges = [
            'today' => ['label' => 'Today', 'start' => today(), 'end' => today()],
            'this_week' => ['label' => 'This Week', 'start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'this_month' => ['label' => 'This Month', 'start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'last_month' => ['label' => 'Last Month', 'start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()],
            'last_30_days' => ['label' => 'Last 30 Days', 'start' => now()->subDays(30), 'end' => today()],
        ];

        return [
            'staff_users' => $staffUsers,
            'roles' => $roles,
            'date_ranges' => $dateRanges,
            'status_options' => [
                'present' => 'Present',
                'late' => 'Late',
                'absent' => 'Absent',
                'leave' => 'On Leave',
                'half_day' => 'Half Day',
            ],
        ];
    }

    /**
     * Build base query with filters applied
     *
     * @param FilterCriteria $filters
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildBaseQuery(FilterCriteria $filters)
    {
        // Start with users who have staff role
        $query = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('attendances', function ($join) use ($filters) {
                $join->on('users.id', '=', 'attendances.user_id')
                    ->whereBetween('attendances.date', [$filters->startDate, $filters->endDate])
                    ->where('attendances.calculated_using_business_hours', true);
            });

        // Apply role filter if specified
        if ($filters->roleId) {
            $query->where('roles.id', $filters->roleId);
        } else {
            // Default to staff role only
            $query->where('roles.name', 'staff');
        }

        // Apply staff filter if specified
        if ($filters->staffId) {
            $query->where('users.id', $filters->staffId);
        }

        // Apply status filter if specified
        if ($filters->status) {
            $query->where('attendances.status', $filters->status);
        }

        return $query;
    }

    /**
     * Calculate summary statistics for the filtered data
     *
     * @param FilterCriteria $filters
     * @return array
     */
    public function getSummaryStatistics(FilterCriteria $filters): array
    {
        $summary = $this->getStaffSummary($filters);

        if ($summary->isEmpty()) {
            return $this->getEmptySummary();
        }

        $totalStaff = $summary->count();
        $totalDays = $summary->sum('total_days');
        $totalHours = $summary->sum('total_hours');
        $totalExpectedHours = $summary->sum('total_expected_hours');
        $totalOvertime = $summary->sum('total_overtime');
        $totalPresentDays = $summary->sum('present_days');
        $totalLateDays = $summary->sum('late_days');

        return [
            'total_staff' => $totalStaff,
            'total_days' => $totalDays,
            'total_hours' => round($totalHours, 2),
            'total_expected_hours' => round($totalExpectedHours, 2),
            'total_overtime' => round($totalOvertime, 2),
            'total_present_days' => $totalPresentDays,
            'total_late_days' => $totalLateDays,
            'average_hours_per_staff' => $totalStaff > 0 ? round($totalHours / $totalStaff, 2) : 0,
            'average_days_per_staff' => $totalStaff > 0 ? round($totalDays / $totalStaff, 2) : 0,
            'overall_attendance_rate' => $totalDays > 0 ? round(($totalPresentDays / $totalDays) * 100, 2) : 0,
            'overall_compliance_rate' => $totalExpectedHours > 0 ? round(($totalHours / $totalExpectedHours) * 100, 2) : 0,
            'overtime_percentage' => $totalHours > 0 ? round(($totalOvertime / $totalHours) * 100, 2) : 0,
        ];
    }

    /**
     * Get empty summary for when no data is found
     *
     * @return array
     */
    private function getEmptySummary(): array
    {
        return [
            'total_staff' => 0,
            'total_days' => 0,
            'total_hours' => 0,
            'total_expected_hours' => 0,
            'total_overtime' => 0,
            'total_present_days' => 0,
            'total_late_days' => 0,
            'average_hours_per_staff' => 0,
            'average_days_per_staff' => 0,
            'overall_attendance_rate' => 0,
            'overall_compliance_rate' => 0,
            'overtime_percentage' => 0,
        ];
    }

    /**
     * Get date range statistics (daily breakdown)
     *
     * @param FilterCriteria $filters
     * @return Collection
     */
    public function getDateRangeStatistics(FilterCriteria $filters): Collection
    {
        $cacheKey = $this->generateCacheKey('date_range_stats', $filters);
        
        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($filters) {
            $query = DB::table('attendances')
                ->join('users', 'attendances.user_id', '=', 'users.id')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->whereBetween('attendances.date', [$filters->startDate, $filters->endDate])
                ->where('attendances.calculated_using_business_hours', true)
                ->where('roles.name', 'staff');

            if ($filters->roleId) {
                $query->where('roles.id', $filters->roleId);
            }

            if ($filters->staffId) {
                $query->where('users.id', $filters->staffId);
            }

            if ($filters->status) {
                $query->where('attendances.status', $filters->status);
            }

            return $query->select([
                DB::raw('DATE(attendances.date) as date'),
                DB::raw('COUNT(DISTINCT users.id) as staff_count'),
                DB::raw('COUNT(attendances.id) as attendance_count'),
                DB::raw('SUM(attendances.hours_worked) as total_hours'),
                DB::raw('SUM(attendances.overtime_hours) as total_overtime'),
                DB::raw('COUNT(CASE WHEN attendances.status IN ("present", "late") THEN 1 END) as present_count'),
                DB::raw('COUNT(CASE WHEN attendances.status = "late" THEN 1 END) as late_count'),
            ])
            ->groupBy(DB::raw('DATE(attendances.date)'))
            ->orderBy('date')
            ->get();
        });
    }

    /**
     * Generate cache key based on filters
     *
     * @param string $prefix
     * @param FilterCriteria $filters
     * @return string
     */
    private function generateCacheKey(string $prefix, FilterCriteria $filters): string
    {
        $keyParts = [
            $prefix,
            $filters->startDate->format('Ymd'),
            $filters->endDate->format('Ymd'),
            $filters->staffId ?? 'all',
            $filters->roleId ?? 'all',
            $filters->status ?? 'all',
        ];
        
        return 'work_hour_report:' . md5(implode(':', $keyParts));
    }

    /**
     * Get cache duration in seconds
     *
     * @return int
     */
    private function getCacheDuration(): int
    {
        // Cache for 5 minutes for real-time data
        // For larger date ranges (> 30 days), cache longer
        return 300; // 5 minutes
    }

    /**
     * Clear cache for specific filters
     *
     * @param FilterCriteria $filters
     * @return void
     */
    public function clearCache(FilterCriteria $filters): void
    {
        $keys = [
            $this->generateCacheKey('staff_summary', $filters),
            $this->generateCacheKey('date_range_stats', $filters),
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear all report cache
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        Cache::tags(['work_hour_reports'])->flush();
    }
}