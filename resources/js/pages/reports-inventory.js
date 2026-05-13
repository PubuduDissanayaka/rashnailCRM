import { CustomApexChart, ins } from '../app.js';

document.addEventListener('DOMContentLoaded', () => {
    const data = window.__reportData || {};
    const currency = window.currencySymbol || '$';
    const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    // ── Stock by Category — Bar Chart ──
    new CustomApexChart({
        selector: '#inv-stock-category-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            colors: [ins('primary')],
            series: [
                { name: 'Stock Units', data: (data.stockCatValues || []).map(Number) },
            ],
            xaxis: {
                categories: data.stockCatLabels || [],
                labels: { style: { colors: ins('secondary-color') } },
                axisBorder: { show: false },
                axisTicks: { show: false },
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
            tooltip: { y: { formatter: v => v + ' units' } },
        }),
    });

    // ── Stock Value Distribution — Donut Chart ──
    new CustomApexChart({
        selector: '#inv-value-donut-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'donut', height: 280 },
            series: (data.valueCatValues || []).map(Number),
            labels: data.valueCatLabels || [],
            colors: [
                ins('success'),
                ins('primary'),
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

    // ── Top Used Supplies — Horizontal Bar Chart ──
    new CustomApexChart({
        selector: '#inv-top-used-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'bar', height: 220, toolbar: { show: false } },
            colors: [ins('warning')],
            series: [
                {
                    name: 'Qty Used',
                    data: (data.topUsedValues || [])
                        .slice()
                        .reverse()
                        .map(Number),
                },
            ],
            xaxis: {
                categories: (data.topUsedLabels || []).slice().reverse(),
                labels: { style: { colors: ins('secondary-color') } },
            },
            yaxis: {
                labels: { style: { colors: ins('secondary-color') } },
            },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
            grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: v => v + ' units used' } },
        }),
    });
});
