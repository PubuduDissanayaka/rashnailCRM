<?php

namespace App\Services;

use App\DTOs\FilterCriteria;
use App\Services\WorkHourReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Response;
use Carbon\Carbon;
use App\Events\ReportGenerated;
use App\Events\ReportGenerationFailed;
use Illuminate\Support\Facades\Auth;

class ExportService
{
    protected WorkHourReportService $reportService;

    public function __construct(WorkHourReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Export work hour report to CSV
     *
     * @param FilterCriteria $filters
     * @param \App\Models\User|null $generatedBy
     * @return StreamedResponse
     */
    public function exportToCsv(FilterCriteria $filters, ?\App\Models\User $generatedBy = null): StreamedResponse
    {
        try {
            // Get detailed data for export
            $detailedData = $this->reportService->getDetailedReport($filters, 0); // 0 means no pagination
            $summaryData = $this->reportService->getStaffSummary($filters);
            $statistics = $this->reportService->getSummaryStatistics($filters);

            $filename = $this->generateFilename($filters, 'csv');

            // Dispatch report generated event
            if ($generatedBy) {
                ReportGenerated::dispatch(
                    'work_hour',
                    $generatedBy,
                    [
                        'record_count' => $detailedData->count(),
                        'staff_count' => $summaryData->count(),
                        'date_range' => $filters->getDateRangeArray(),
                        'statistics' => $statistics,
                    ],
                    null, // download URL (streamed response doesn't have a URL)
                    null, // file path
                    null, // file size
                    'csv'
                );
            }

            return new StreamedResponse(function () use ($detailedData, $summaryData, $statistics, $filters) {
                $handle = fopen('php://output', 'w');
                
                // Add UTF-8 BOM for Excel compatibility
                fwrite($handle, "\xEF\xBB\xBF");
                
                // Write report header
                $this->writeCsvHeader($handle, $filters, $statistics);
                
                // Write summary section
                $this->writeSummarySection($handle, $summaryData, $statistics);
                
                // Write detailed data section
                $this->writeDetailedDataSection($handle, $detailedData);
                
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            // Dispatch report generation failed event
            if ($generatedBy) {
                ReportGenerationFailed::dispatch(
                    'work_hour',
                    $generatedBy,
                    $e->getMessage(),
                    'CSV_EXPORT_ERROR',
                    [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }
            
            throw $e;
        }
    }

    /**
     * Export work hour report to PDF
     *
     * @param FilterCriteria $filters
     * @param \App\Models\User|null $generatedBy
     * @return Response
     */
    public function exportToPdf(FilterCriteria $filters, ?\App\Models\User $generatedBy = null): Response
    {
        // Get data for PDF
        $detailedData = $this->reportService->getDetailedReport($filters, 0);
        $summaryData = $this->reportService->getStaffSummary($filters);
        $statistics = $this->reportService->getSummaryStatistics($filters);
        $filterOptions = $this->reportService->getFilterOptions();

        // Get staff and role names for display
        $staffName = null;
        $roleName = null;
        
        if ($filters->hasStaffFilter()) {
            $staff = \App\Models\User::find($filters->staffId);
            $staffName = $staff ? $staff->name : 'Unknown';
        }
        
        if ($filters->hasRoleFilter()) {
            $role = \Spatie\Permission\Models\Role::find($filters->roleId);
            $roleName = $role ? $role->name : 'Unknown';
        }

        // Generate PDF using DomPDF
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.work-hours.pdf', [
                'filters' => $filters,
                'detailedReport' => $detailedData,
                'staffSummary' => $summaryData,
                'summary' => $statistics,
                'staffName' => $staffName,
                'roleName' => $roleName,
            ]);

            $filename = $this->generateFilename($filters, 'pdf');

            // Dispatch report generated event
            if ($generatedBy) {
                ReportGenerated::dispatch(
                    'work_hour',
                    $generatedBy,
                    [
                        'record_count' => $detailedData->count(),
                        'staff_count' => $summaryData->count(),
                        'date_range' => $filters->getDateRangeArray(),
                        'statistics' => $statistics,
                    ],
                    null, // download URL (streamed response doesn't have a URL)
                    null, // file path
                    null, // file size
                    'pdf'
                );
            }

            return $pdf->download($filename);
        } catch (\Exception $e) {
            // Dispatch report generation failed event
            if ($generatedBy) {
                ReportGenerationFailed::dispatch(
                    'work_hour',
                    $generatedBy,
                    $e->getMessage(),
                    'PDF_EXPORT_ERROR',
                    [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }

            // Fallback to JSON response if PDF generation fails
            \Log::error('PDF export failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'PDF export failed: ' . $e->getMessage(),
                'filename' => $this->generateFilename($filters, 'pdf'),
                'data_available' => [
                    'detailed_records' => $detailedData->count(),
                    'summary_records' => $summaryData->count(),
                    'statistics' => $statistics,
                ],
                'note' => 'Please check server logs for details.',
            ], 500);
        }
    }

    /**
     * Generate filename for export
     *
     * @param FilterCriteria $filters
     * @param string $format
     * @return string
     */
    private function generateFilename(FilterCriteria $filters, string $format): string
    {
        $dateRange = $filters->getDateRangeArray();
        $start = Carbon::parse($dateRange['start'])->format('Ymd');
        $end = Carbon::parse($dateRange['end'])->format('Ymd');
        
        $prefix = 'work_hour_report';
        
        if ($filters->hasStaffFilter()) {
            $prefix .= '_staff_' . $filters->staffId;
        }
        
        if ($filters->hasRoleFilter()) {
            $prefix .= '_role_' . $filters->roleId;
        }
        
        return "{$prefix}_{$start}_to_{$end}.{$format}";
    }

    /**
     * Write CSV header with report metadata
     *
     * @param resource $handle
     * @param FilterCriteria $filters
     * @param array $statistics
     * @return void
     */
    private function writeCsvHeader($handle, FilterCriteria $filters, array $statistics): void
    {
        $dateRange = $filters->getDateRangeArray();
        
        // Report header
        fputcsv($handle, ['Work Hour Report']);
        fputcsv($handle, ['Generated: ' . Carbon::now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, ['Period: ' . $dateRange['start'] . ' to ' . $dateRange['end']]);
        fputcsv($handle, ['Total Staff: ' . $statistics['total_staff']]);
        fputcsv($handle, ['Total Hours: ' . $statistics['total_hours']]);
        fputcsv($handle, ['Attendance Rate: ' . $statistics['overall_attendance_rate'] . '%']);
        fputcsv($handle, []); // Empty row
    }

    /**
     * Write summary section to CSV
     *
     * @param resource $handle
     * @param Collection $summaryData
     * @param array $statistics
     * @return void
     */
    private function writeSummarySection($handle, Collection $summaryData, array $statistics): void
    {
        // Summary section header
        fputcsv($handle, ['STAFF SUMMARY']);
        fputcsv($handle, [
            'Staff Name',
            'Email',
            'Total Days',
            'Total Hours',
            'Expected Hours',
            'Overtime Hours',
            'Break Hours',
            'Net Hours',
            'Present Days',
            'Late Days',
            'Absent Days',
            'Leave Days',
            'Half Days',
            'Attendance Rate %',
            'Compliance Rate %',
        ]);

        // Summary data rows
        foreach ($summaryData as $staff) {
            fputcsv($handle, [
                $staff->staff_name,
                $staff->email,
                $staff->total_days,
                round($staff->total_hours, 2),
                round($staff->total_expected_hours, 2),
                round($staff->total_overtime, 2),
                round($staff->total_break_hours, 2),
                round($staff->net_hours, 2),
                $staff->present_days,
                $staff->late_days,
                $staff->absent_days,
                $staff->leave_days,
                $staff->half_day_days,
                round($staff->attendance_rate, 2),
                round($staff->compliance_rate, 2),
            ]);
        }

        // Summary totals
        fputcsv($handle, []); // Empty row
        fputcsv($handle, ['SUMMARY TOTALS']);
        fputcsv($handle, [
            'Total Staff: ' . $statistics['total_staff'],
            'Total Days: ' . $statistics['total_days'],
            'Total Hours: ' . $statistics['total_hours'],
            'Total Expected Hours: ' . $statistics['total_expected_hours'],
            'Total Overtime: ' . $statistics['total_overtime'],
            'Average Hours per Staff: ' . $statistics['average_hours_per_staff'],
            'Overall Attendance Rate: ' . $statistics['overall_attendance_rate'] . '%',
            'Overall Compliance Rate: ' . $statistics['overall_compliance_rate'] . '%',
        ]);
        
        fputcsv($handle, []); // Empty row
        fputcsv($handle, []); // Empty row
    }

    /**
     * Write detailed data section to CSV
     *
     * @param resource $handle
     * @param Collection $detailedData
     * @return void
     */
    private function writeDetailedDataSection($handle, Collection $detailedData): void
    {
        // Detailed data header
        fputcsv($handle, ['DETAILED ATTENDANCE RECORDS']);
        fputcsv($handle, [
            'Date',
            'Staff Name',
            'Email',
            'Check-in',
            'Check-out',
            'Hours Worked',
            'Expected Hours',
            'Overtime Hours',
            'Break Minutes',
            'Net Hours',
            'Status',
            'Late Minutes',
            'Early Departure Minutes',
            'Attendance Type',
            'Business Hours Type',
        ]);

        // Detailed data rows
        foreach ($detailedData as $record) {
            $checkIn = $record->check_in ? Carbon::parse($record->check_in)->format('H:i') : '';
            $checkOut = $record->check_out ? Carbon::parse($record->check_out)->format('H:i') : '';
            $date = Carbon::parse($record->date)->format('Y-m-d');
            
            // Calculate net hours (hours worked minus breaks)
            $breakHours = $record->total_break_minutes / 60;
            $netHours = max(0, ($record->hours_worked ?? 0) - $breakHours);

            fputcsv($handle, [
                $date,
                $record->staff_name,
                $record->email,
                $checkIn,
                $checkOut,
                round($record->hours_worked, 2),
                round($record->expected_hours, 2),
                round($record->overtime_hours, 2),
                $record->total_break_minutes,
                round($netHours, 2),
                ucfirst($record->status),
                $record->late_arrival_minutes,
                $record->early_departure_minutes,
                $record->attendance_type,
                $record->business_hours_type,
            ]);
        }
    }

    /**
     * Format data for PDF export
     *
     * @param Collection $data
     * @param string $section
     * @return array
     */
    private function formatForPdf(Collection $data, string $section): array
    {
        // This would format data for PDF template
        // For now, return basic array structure
        return [
            'section' => $section,
            'count' => $data->count(),
            'data' => $data->toArray(),
        ];
    }

    /**
     * Check if export can be generated
     *
     * @param FilterCriteria $filters
     * @return array Array of validation errors, empty if valid
     */
    public function validateExport(FilterCriteria $filters): array
    {
        $errors = [];

        // Check date range
        if ($filters->startDate->diffInDays($filters->endDate) > 366) {
            $errors[] = 'Date range too large for export (max 1 year)';
        }

        // Check if there's data to export
        $summary = $this->reportService->getStaffSummary($filters);
        if ($summary->isEmpty()) {
            $errors[] = 'No data found for the selected filters';
        }

        return $errors;
    }
}