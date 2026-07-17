<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Attendance;
use App\Models\Supply;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $businessName = Setting::get('business.name', config('app.name'));

        // ── KPI Cards ─────────────────────────────────
        $todayAppointmentsCount = Appointment::whereDate('appointment_date', today())->count();
        $todayRevenue = Sale::whereDate('sale_date', today())
            ->where('status', 'completed')
            ->sum('total_amount');
        $monthlyRevenue = Sale::whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->where('status', 'completed')
            ->sum('total_amount');
        $activeCustomersCount = Customer::where('status', 'active')->count();
        $pendingExpensesCount = Expense::where('status', 'pending')->count();
        $totalStaff = User::where('status', 'active')->count();
        $staffCheckedIn = Attendance::whereDate('date', today())
            ->whereNotNull('clock_in')->count();

        // ── Appointment Status Breakdown ──────────────
        $todayStatuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        $apptStatusCounts = [];
        foreach ($todayStatuses as $s) {
            $apptStatusCounts[] = Appointment::whereDate('appointment_date', today())
                ->where('status', $s)->count();
        }

        // ── Monthly Revenue (last 6 months) ───────────
        $revenueMonths = [];
        $revenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $revenueMonths[] = $m->format('M Y');
            $revenueData[] = round(
                Sale::whereMonth('sale_date', $m->month)
                    ->whereYear('sale_date', $m->year)
                    ->where('status', 'completed')
                    ->sum('total_amount'),
                2
            );
        }

        // ── Today's Appointments List ─────────────────
        $todayAppointments = Appointment::with(['customer', 'service', 'user'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_date')
            ->limit(10)
            ->get();

        // ── Recent Sales ──────────────────────────────
        $recentSales = Sale::with(['customer', 'user'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        // ── Low Stock ─────────────────────────────────
        $lowStockItems = Supply::where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->with('category')
            ->limit(6)
            ->get();

        // ── Weekly Revenue (sparkline data) ───────────
        $weeklyRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $weeklyRevenue[] = round(
                Sale::whereDate('sale_date', today()->subDays($i))
                    ->where('status', 'completed')
                    ->sum('total_amount'),
                2
            );
        }

        // ── Payment method breakdown ──────────────────
        $paymentMethods = Sale::whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->where('status', 'completed')
            ->with('payments')
            ->get()
            ->flatMap(fn($s) => $s->payments)
            ->groupBy('method')
            ->map(fn($p) => round($p->sum('amount'), 2))
            ->toArray();

        return view('dashboard.index', compact(
            'currencySymbol', 'businessName',
            'todayAppointmentsCount', 'todayRevenue', 'monthlyRevenue',
            'activeCustomersCount', 'pendingExpensesCount',
            'totalStaff', 'staffCheckedIn',
            'apptStatusCounts', 'revenueMonths', 'revenueData',
            'todayAppointments', 'recentSales', 'lowStockItems',
            'weeklyRevenue', 'paymentMethods'
        ));
    }
}
