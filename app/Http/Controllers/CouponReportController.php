<?php

namespace App\Http\Controllers;

use App\Services\CouponReportService;
use App\DTOs\FilterCriteria;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CouponReportController extends Controller
{
    protected CouponReportService $reportService;
    protected ExportService $exportService;

    public function __construct(CouponReportService $reportService, ExportService $exportService)
    {
        $this->middleware('auth');
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    /**
     * Display the coupon reports dashboard
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $this->authorize('view reports');

        // Get filter criteria from request
        $filters = FilterCriteria::fromRequest($request->all());
        
        // Get summary statistics
        $summary = $this->reportService->getSummaryStats($filters);
        
        // Get redemption analytics (last 30 days)
        $redemptionAnalytics = $this->reportService->getRedemptionAnalytics($filters);
        
        // Get performance by type
        $performanceByType = $this->reportService->getPerformanceByType($filters);
        
        // Get top performing coupons
        $topCoupons = $this->reportService->getTopPerformingCoupons($filters, 10);
        
        // Get redemption by location
        $redemptionByLocation = $this->reportService->getRedemptionByLocation($filters);
        
        // Get redemption by customer group
        $redemptionByCustomerGroup = $this->reportService->getRedemptionByCustomerGroup($filters);

        return view('reports.coupons.index', [
            'filters' => $filters,
            'summary' => $summary,
            'redemptionAnalytics' => $redemptionAnalytics,
            'performanceByType' => $performanceByType,
            'topCoupons' => $topCoupons,
            'redemptionByLocation' => $redemptionByLocation,
            'redemptionByCustomerGroup' => $redemptionByCustomerGroup,
        ]);
    }

    /**
     * Get redemption analytics data (JSON API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function redemptionAnalytics(Request $request): JsonResponse
    {
        $this->authorize('view reports');

        $filters = FilterCriteria::fromRequest($request->all());
        $data = $this->reportService->getRedemptionAnalytics($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get performance by type data (JSON API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function performanceByType(Request $request): JsonResponse
    {
        $this->authorize('view reports');

        $filters = FilterCriteria::fromRequest($request->all());
        $data = $this->reportService->getPerformanceByType($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get usage by period data (JSON API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function usageByPeriod(Request $request): JsonResponse
    {
        $this->authorize('view reports');

        $filters = FilterCriteria::fromRequest($request->all());
        $period = $request->input('period', 'day');
        $data = $this->reportService->getUsageByPeriod($filters, $period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get top performing coupons data (JSON API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function topCoupons(Request $request): JsonResponse
    {
        $this->authorize('view reports');

        $filters = FilterCriteria::fromRequest($request->all());
        $limit = $request->input('limit', 10);
        $data = $this->reportService->getTopPerformingCoupons($filters, $limit);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Export coupon report data
     *
     * @param Request $request
     * @param string $type
     * @return StreamedResponse
     */
    public function export(Request $request, string $type): StreamedResponse
    {
        $this->authorize('export reports');

        $filters = FilterCriteria::fromRequest($request->all());
        
        $data = match($type) {
            'redemptions' => $this->reportService->getRedemptionAnalytics($filters),
            'performance' => $this->reportService->getPerformanceByType($filters),
            'usage' => $this->reportService->getUsageByPeriod($filters, $request->input('period', 'day')),
            'top-coupons' => $this->reportService->getTopPerformingCoupons($filters, 50),
            'locations' => $this->reportService->getRedemptionByLocation($filters),
            'customer-groups' => $this->reportService->getRedemptionByCustomerGroup($filters),
            default => [],
        };

        $filename = 'coupon_report_' . $type . '_' . $filters->startDate->format('Y-m-d') . '_' . $filters->endDate->format('Y-m-d');
        
        return $this->exportService->exportToCsv($data, $filename . '.csv');
    }
}