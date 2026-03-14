<?php

namespace App\Http\Controllers;

use App\Services\WorkHourReportService;
use App\DTOs\FilterCriteria;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkHourReportController extends Controller
{
    protected WorkHourReportService $reportService;
    protected ExportService $exportService;

    public function __construct(WorkHourReportService $reportService, ExportService $exportService)
    {
        $this->middleware('auth');
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    /**
     * Display the main work hour reports interface
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $this->authorize('view work hour reports');

        // Get filter criteria from request
        $filters = FilterCriteria::fromRequest($request->all());
        
        // Get filter options for the UI
        $filterOptions = $this->reportService->getFilterOptions();
        
        // Get summary statistics
        $summary = $this->reportService->getSummaryStatistics($filters);
        
        // Get staff summary data
        $staffSummary = $this->reportService->getStaffSummary($filters);
        
        // Get detailed report with pagination
        $detailedReport = $this->reportService->getDetailedReport($filters, 20);

        return view('reports.work-hours.index', [
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'summary' => $summary,
            'staffSummary' => $staffSummary,
            'detailedReport' => $detailedReport,
            'filterDescription' => $filters->getFilterDescription(),
        ]);
    }

    /**
     * Get staff work hour summary (JSON API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('view work hour reports');

        $filters = FilterCriteria::fromRequest($request->all());
        
        // Validate filters
        $validationErrors = $filters->validate();
        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'errors' => $validationErrors,
            ], 422);
        }

        $summary = $this->reportService->getStaffSummary($filters);
        $statistics = $this->reportService->getSummaryStatistics($filters);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'statistics' => $statistics,
            'filters' => $filters->getDateRangeArray(),
        ]);
    }

    /**
     * Get detailed attendance report (JSON API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detail(Request $request): JsonResponse
    {
        $this->authorize('view work hour reports');

        $filters = FilterCriteria::fromRequest($request->all());
        
        // Validate filters
        $validationErrors = $filters->validate();
        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'errors' => $validationErrors,
            ], 422);
        }

        $perPage = $request->input('per_page', 20);
        $detailedReport = $this->reportService->getDetailedReport($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $detailedReport->items(),
            'pagination' => [
                'current_page' => $detailedReport->currentPage(),
                'last_page' => $detailedReport->lastPage(),
                'per_page' => $detailedReport->perPage(),
                'total' => $detailedReport->total(),
            ],
            'filters' => $filters->getDateRangeArray(),
        ]);
    }

    /**
     * Export work hour report to CSV or PDF
     *
     * @param Request $request
     * @return StreamedResponse|JsonResponse
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $this->authorize('export work hour reports');

        $filters = FilterCriteria::fromRequest($request->all());
        
        // Validate filters
        $validationErrors = $filters->validate();
        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'errors' => $validationErrors,
            ], 422);
        }

        $format = $filters->exportFormat ?? 'csv';
        $user = $request->user();

        try {
            if ($format === 'csv') {
                return $this->exportService->exportToCsv($filters, $user);
            } elseif ($format === 'pdf') {
                return $this->exportService->exportToPdf($filters, $user);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported export format',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get filter options (JSON API)
     *
     * @return JsonResponse
     */
    public function filterOptions(): JsonResponse
    {
        $this->authorize('view work hour reports');

        $options = $this->reportService->getFilterOptions();

        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }

    /**
     * Get date range statistics (JSON API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dateRangeStats(Request $request): JsonResponse
    {
        $this->authorize('view work hour reports');

        $filters = FilterCriteria::fromRequest($request->all());
        
        // Validate filters
        $validationErrors = $filters->validate();
        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'errors' => $validationErrors,
            ], 422);
        }

        $dateStats = $this->reportService->getDateRangeStatistics($filters);

        return response()->json([
            'success' => true,
            'data' => $dateStats,
            'filters' => $filters->getDateRangeArray(),
        ]);
    }

    /**
     * Quick report for dashboard widget
     *
     * @return JsonResponse
     */
    public function dashboardWidget(): JsonResponse
    {
        $this->authorize('view work hour reports');

        // Default to last 30 days for dashboard
        $filters = FilterCriteria::fromRequest([
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $summary = $this->reportService->getSummaryStatistics($filters);
        $recentStats = $this->reportService->getDateRangeStatistics($filters);

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'recent_stats' => $recentStats->take(7), // Last 7 days
            'period' => $filters->getDateRangeArray(),
        ]);
    }

    /**
     * Validate report parameters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateParameters(Request $request): JsonResponse
    {
        $filters = FilterCriteria::fromRequest($request->all());
        $validationErrors = $filters->validate();

        if (empty($validationErrors)) {
            return response()->json([
                'success' => true,
                'message' => 'Parameters are valid',
                'filters' => $filters->getDateRangeArray(),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'errors' => $validationErrors,
            ], 422);
        }
    }
}