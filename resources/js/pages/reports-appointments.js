import { CustomApexChart, ins } from '../app.js';

document.addEventListener('DOMContentLoaded', () => {
    const data = window.__reportData || {};
    const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    // ── Daily Trend — Line Chart ──
    new CustomApexChart({
        selector: '#appt-daily-trend-chart',
        options: () => ({
            theme: { mode: theme },
            chart: {
                type: 'line',
                height: 280,
                toolbar: { show: false },
                zoom: { enabled: false },
            },
            colors: [ins('info')],
            series: [
                { name: 'Appointments', data: (data.dailyCounts || []).map(Number) },
            ],
            xaxis: {
                categories: data.dailyDates || [],
                labels: {
                    style: { colors: ins('secondary-color') },
                    rotate: -30,
                    hideOverlappingLabels: true,
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    style: { colors: ins('secondary-color') },
                    formatter: v => Math.round(v),
                },
            },
            stroke: { width: 2, curve: 'smooth' },
            markers: {
                size: 3,
                colors: [ins('info')],
                strokeColors: '#fff',
                strokeWidth: 2,
            },
            grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: v => v + ' appointments' } },
        }),
    });

    // ── Status Distribution — Donut Chart ──
    new CustomApexChart({
        selector: '#appt-status-dist-chart',
        options: () => {
            const colorMap = {
                scheduled: ins('info'),
                in_progress: ins('warning'),
                completed: ins('success'),
                cancelled: ins('danger'),
            };
            const labels = (data.statusLabels || []).map(
                l => l.charAt(0).toUpperCase() + l.slice(1).replace('_', ' ')
            );
            const colors = (data.statusLabels || []).map(
                k => colorMap[k] || ins('primary')
            );
            return {
                theme: { mode: theme },
                chart: { type: 'donut', height: 280 },
                series: (data.statusValues || []).map(Number),
                labels,
                colors,
                legend: {
                    position: 'bottom',
                    labels: { colors: ins('secondary-color') },
                },
                dataLabels: { enabled: true, style: { fontSize: '11px' } },
                plotOptions: { pie: { donut: { size: '65%' } } },
            };
        },
    });

    // ── Day of Week — Bar Chart ──
    new CustomApexChart({
        selector: '#appt-dow-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'bar', height: 260, toolbar: { show: false } },
            colors: [ins('primary')],
            series: [
                { name: 'Appointments', data: (data.dowCounts || []).map(Number) },
            ],
            xaxis: {
                categories: data.dowLabels || [],
                labels: { style: { colors: ins('secondary-color') } },
            },
            yaxis: {
                labels: {
                    style: { colors: ins('secondary-color') },
                    formatter: v => Math.round(v),
                },
            },
            plotOptions: { bar: { borderRadius: 5, columnWidth: '55%' } },
            grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: v => v + ' bookings' } },
        }),
    });

    // ── Top Services — Horizontal Bar Chart ──
    new CustomApexChart({
        selector: '#appt-top-services-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'bar', height: 260, toolbar: { show: false } },
            colors: [ins('success')],
            series: [
                {
                    name: 'Bookings',
                    data: (data.topServiceValues || [])
                        .slice()
                        .reverse()
                        .map(Number),
                },
            ],
            xaxis: {
                categories: (data.topServiceLabels || []).slice().reverse(),
                labels: { style: { colors: ins('secondary-color') } },
            },
            yaxis: {
                labels: { style: { colors: ins('secondary-color') } },
            },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
            grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: v => v + ' bookings' } },
        }),
    });
});
