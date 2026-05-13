import { CustomApexChart, ins } from '../app.js';

document.addEventListener('DOMContentLoaded', () => {
    const data = window.__reportData || {};
    const currency = window.currencySymbol || '$';
    const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    // ── Monthly Trend — Bar Chart ──
    new CustomApexChart({
        selector: '#exp-monthly-trend-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            colors: [ins('danger')],
            series: [
                { name: 'Expenses', data: (data.trendAmounts || []).map(Number) },
            ],
            xaxis: {
                categories: data.trendMonths || [],
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
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: v =>
                        currency + Number(v).toFixed(2),
                },
            },
        }),
    });

    // ── By Category — Donut Chart ──
    new CustomApexChart({
        selector: '#exp-category-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'donut', height: 280 },
            series: (data.categoryValues || []).map(Number),
            labels: data.categoryLabels || [],
            colors: [
                ins('primary'),
                ins('success'),
                ins('info'),
                ins('warning'),
                ins('danger'),
                ins('secondary'),
            ],
            legend: {
                position: 'bottom',
                labels: { colors: ins('secondary-color') },
            },
            dataLabels: { enabled: true, style: { fontSize: '10px' } },
            plotOptions: { pie: { donut: { size: '60%' } } },
            tooltip: {
                y: {
                    formatter: v =>
                        currency + Number(v).toFixed(2),
                },
            },
        }),
    });

    // ── Status Breakdown — Bar Chart ──
    new CustomApexChart({
        selector: '#exp-status-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'bar', height: 260, toolbar: { show: false } },
            colors: [ins('info')],
            series: [
                { name: 'Count', data: (data.statusValues || []).map(Number) },
            ],
            xaxis: {
                categories: data.statusLabels || [],
                labels: { style: { colors: ins('secondary-color') } },
            },
            yaxis: {
                labels: {
                    style: { colors: ins('secondary-color') },
                    formatter: v => Math.round(v),
                },
            },
            plotOptions: { bar: { borderRadius: 5, columnWidth: '50%' } },
            grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: v => v + ' expenses' } },
        }),
    });

    // ── Payment Method — Donut Chart ──
    new CustomApexChart({
        selector: '#exp-payment-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'donut', height: 260 },
            series: (data.pmValues || []).map(Number),
            labels: (data.pmLabels || []).map(
                l =>
                    l
                        ? l.charAt(0).toUpperCase() + l.slice(1).replace(/_/g, ' ')
                        : 'Other'
            ),
            colors: [
                ins('success'),
                ins('primary'),
                ins('warning'),
                ins('info'),
                ins('secondary'),
            ],
            legend: {
                position: 'bottom',
                labels: { colors: ins('secondary-color') },
            },
            dataLabels: { enabled: true },
            plotOptions: { pie: { donut: { size: '65%' } } },
            tooltip: {
                y: {
                    formatter: v =>
                        currency + Number(v).toFixed(2),
                },
            },
        }),
    });
});
