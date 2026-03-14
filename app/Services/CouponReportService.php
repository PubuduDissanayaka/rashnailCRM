<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Sale;
use App\Models\Location;
use App\Models\CustomerGroup;
use App\DTOs\FilterCriteria;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class CouponReportService
{
    /**
     * Get coupon summary statistics for dashboard
     *
     * @param FilterCriteria $filters
     * @return array
     */
    public function getSummaryStats(FilterCriteria $filters): array
    {
        $cacheKey = $this->generateCacheKey('summary_stats', $filters);
        
        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($filters) {
            $totalCoupons = Coupon::whereBetween('created_at', [$filters->startDate, $filters->endDate])->count();
            $activeCoupons = Coupon::whereBetween('created_at', [$filters->startDate, $filters->endDate])
                ->where('active', true)
                ->count();
            $redeemedCoupons = CouponRedemption::whereBetween('created_at', [$filters->startDate, $filters->endDate])
                ->count();
            $totalDiscount = CouponRedemption::whereBetween('created_at', [$filters->startDate, $filters->endDate])
                ->sum('discount_amount');
            $totalSalesWithCoupons = Sale::whereBetween('created_at', [$filters->startDate, $filters->endDate])
                ->where('coupon_discount_amount', '>', 0)
                ->count();
            $totalRevenueImpact = Sale::whereBetween('created_at', [$filters->startDate, $filters->endDate])
                ->sum('coupon_discount_amount');
            
            return [
                'total_coupons' => $totalCoupons,
                'active_coupons' => $activeCoupons,
                'redeemed_coupons' => $redeemedCoupons,
                'total_discount' => (float) $totalDiscount,
                'total_sales_with_coupons' => $totalSalesWithCoupons,
                'total_revenue_impact' => (float) $totalRevenueImpact,
                'redemption_rate' => $totalCoupons > 0 ? ($redeemedCoupons / $totalCoupons) * 100 : 0,
                'avg_discount_per_redemption' => $redeemedCoupons > 0 ? (float) $totalDiscount / $redeemedCoupons : 0,
            ];
        });
    }

    /**
     * Get redemption analytics by date
     *
     * @param FilterCriteria $filters
     * @return Collection
     */
    public function getRedemptionAnalytics(FilterCriteria $filters): Collection
    {
        $cacheKey = $this->generateCacheKey('redemption_analytics', $filters);
        
        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($filters) {
            return CouponRedemption::select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as redemptions'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('COUNT(DISTINCT coupon_id) as unique_coupons'),
                DB::raw('COUNT(DISTINCT customer_id) as unique_customers'),
            ])
            ->whereBetween('created_at', [$filters->startDate, $filters->endDate])
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
        });
    }

    /**
     * Get performance metrics by coupon type
     *
     * @param FilterCriteria $filters
     * @return Collection
     */
    public function getPerformanceByType(FilterCriteria $filters): Collection
    {
        $cacheKey = $this->generateCacheKey('performance_by_type', $filters);
        
        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($filters) {
            return Coupon::select([
                'type',
                DB::raw('COUNT(*) as total_coupons'),
                DB::raw('SUM(CASE WHEN coupons.active = 1 THEN 1 ELSE 0 END) as active_coupons'),
                DB::raw('COUNT(CASE WHEN coupons.active = 0 OR coupons.end_date < NOW() THEN 1 END) as expired_coupons'),
                DB::raw('COUNT(DISTINCT CASE WHEN coupon_redemptions.id IS NOT NULL THEN coupons.id END) as redeemed_coupons'),
                DB::raw('COALESCE(SUM(coupon_redemptions.discount_amount), 0) as total_discount'),
                DB::raw('COALESCE(COUNT(coupon_redemptions.id), 0) as total_redemptions'),
            ])
            ->leftJoin('coupon_redemptions', 'coupons.id', '=', 'coupon_redemptions.coupon_id')
            ->whereBetween('coupons.created_at', [$filters->startDate, $filters->endDate])
            ->groupBy('coupons.type')
            ->orderBy('total_redemptions', 'desc')
            ->get();
        });
    }

    /**
     * Get top performing coupons (by redemptions or discount amount)
     *
     * @param FilterCriteria $filters
     * @param int $limit
     * @return Collection
     */
    public function getTopPerformingCoupons(FilterCriteria $filters, int $limit = 10): Collection
    {
        $cacheKey = $this->generateCacheKey('top_coupons', $filters) . '_' . $limit;
        
        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($filters, $limit) {
            return Coupon::select([
                'coupons.id',
                'coupons.code',
                'coupons.name',
                'coupons.type',
                'coupons.discount_value',
                'coupons.max_discount_amount',
                DB::raw('CASE WHEN coupons.active = 0 OR coupons.end_date < NOW() THEN "expired" WHEN coupon_redemptions.id IS NOT NULL THEN "redeemed" ELSE "active" END as status'),
                DB::raw('COUNT(coupon_redemptions.id) as redemption_count'),
                DB::raw('COALESCE(SUM(coupon_redemptions.discount_amount), 0) as total_discount_given'),
                DB::raw('COALESCE(AVG(coupon_redemptions.discount_amount), 0) as avg_discount_per_redemption'),
            ])
            ->leftJoin('coupon_redemptions', 'coupons.id', '=', 'coupon_redemptions.coupon_id')
            ->whereBetween('coupon_redemptions.created_at', [$filters->startDate, $filters->endDate])
            ->groupBy('coupons.id', 'coupons.code', 'coupons.name', 'coupons.type', 'coupons.discount_value', 'coupons.max_discount_amount', 'status')
            ->orderBy('redemption_count', 'desc')
            ->limit($limit)
            ->get();
        });
    }

    /**
     * Get usage reports by time period (daily, weekly, monthly)
     *
     * @param FilterCriteria $filters
     * @param string $period (day, week, month)
     * @return Collection
     */
    public function getUsageByPeriod(FilterCriteria $filters, string $period = 'day'): Collection
    {
        $cacheKey = $this->generateCacheKey('usage_by_period_' . $period, $filters);
        
        return Cache::remember($cacheKey, $this->getCacheDuration(), function () use ($filters, $period) {
            $format = match($period) {
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                default => '%Y-%m-%d',
            };
            
            return CouponRedemption::select([
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('COUNT(*) as redemptions'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('COUNT(DISTINCT coupon_id) as unique_coupons'),
                DB::raw('COUNT(DISTINCT customer_id) as unique_customers'),
            ])
            ->whereBetween('created_at', [$filters->startDate, $filters->endDate])
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->get();
        });
    }

    /**
     * Get redemption breakdown by location
     *
     * @param FilterCriteria $filters
     * @return Collection
     */
    public function getRedemptionByLocation(FilterCriteria $filters): Collection
    {
        return collect([]);
    }

    /**
     * Get redemption breakdown by customer group
     *
     * @param FilterCriteria $filters
     * @return Collection
     */
    public function getRedemptionByCustomerGroup(FilterCriteria $filters): Collection
    {
        return collect([]);
    }

    /**
     * Generate cache key for report data
     *
     * @param string $reportType
     * @param FilterCriteria $filters
     * @return string
     */
    private function generateCacheKey(string $reportType, FilterCriteria $filters): string
    {
        return 'coupon_report_' . $reportType . '_' . $filters->startDate->format('Ymd') . '_' . $filters->endDate->format('Ymd');
    }

    /**
     * Get cache duration in seconds (default: 5 minutes)
     *
     * @return int
     */
    private function getCacheDuration(): int
    {
        return config('cache.report_cache_duration', 300);
    }
}