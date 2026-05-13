import { CustomApexChart, ins } from '../app.js';

document.addEventListener('DOMContentLoaded', () => {
    const data = window.__reportData || {};
    const currency = window.currencySymbol || '$';
    const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    // ── Revenue vs Expenses — Line Chart ──
    new CustomApexChart({
        selector: '#revenue-vs-expenses-chart',
        options: () => ({
            theme: { mode: theme },
            chart: {
                type: 'line',
                height: 310,
                toolbar: { show: false },
                zoom: { enabled: false },
            },
            colors: [ins('success'), ins('danger')],
            series: [
                { name: 'Revenue', data: (data.revenue || []).map(Number) },
                { name: 'Expenses', data: (data.expenses || []).map(Number) },
            ],
            xaxis: {
                categories: data.months || [],
                labels: { style: { colors: ins('secondary-color') } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    formatter: v => currency + v.toLocaleString(),
                    style: { colors: ins('secondary-color') },
                },
            },
            stroke: { width: 2, curve: 'smooth' },
            grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: ins('secondary-color') },
            },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: v =>
                        currency + Number(v).toFixed(2),
                },
            },
        }),
    });

    // ── Appointment Status — Donut Chart ──
    new CustomApexChart({
        selector: '#appt-status-donut-chart',
        options: () => {
            const statusColors = {
                scheduled: ins('info'),
                in_progress: ins('warning'),
                completed: ins('success'),
                cancelled: ins('danger'),
            };
            const raw = data.apptStatus || {};
            const labels = Object.keys(raw).map(
                k => k.charAt(0).toUpperCase() + k.slice(1).replace('_', ' ')
            );
            const series = Object.values(raw).map(Number);
            const colors = Object.keys(raw).map(k => statusColors[k] || ins('primary'));

            return {
                theme: { mode: theme },
                chart: { type: 'donut', height: 310 },
                series,
                labels,
                colors,
                legend: {
                    position: 'bottom',
                    labels: { colors: ins('secondary-color') },
                },
                dataLabels: { enabled: true, style: { fontSize: '12px' } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    color: ins('secondary-color'),
                                    formatter: w =>
                                        w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                                },
                            },
                        },
                    },
                },
            };
        },
    });
});
