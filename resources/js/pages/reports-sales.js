import { CustomApexChart, ins } from '../app.js';

document.addEventListener('DOMContentLoaded', () => {
    const data = window.__reportData || {};
    const currency = window.currencySymbol || '$';
    const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    // ── Revenue Trend — Area Chart ──
    new CustomApexChart({
        selector: '#sales-revenue-trend-chart',
        options: () => ({
            theme: { mode: theme },
            chart: {
                type: 'area',
                height: 280,
                toolbar: { show: false },
                zoom: { enabled: false },
            },
            colors: [ins('success')],
            series: [{ name: 'Revenue', data: (data.trendTotals || []).map(Number) }],
            xaxis: {
                categories: data.trendDates || [],
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
                    formatter: v => currency + v.toLocaleString(),
                    style: { colors: ins('secondary-color') },
                },
            },
            stroke: { width: 2, curve: 'smooth' },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
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

    // ── Payment Methods — Donut Chart ──
    new CustomApexChart({
        selector: '#sales-payment-method-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'donut', height: 280 },
            series: (data.paymentValues || []).map(Number),
            labels: (data.paymentLabels || []).map(
                l => l.charAt(0).toUpperCase() + l.slice(1).replace('_', ' ')
            ),
            colors: [
                ins('primary'),
                ins('success'),
                ins('info'),
                ins('warning'),
                ins('danger'),
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

    // ── Sales by Type — Bar Chart ──
    new CustomApexChart({
        selector: '#sales-by-type-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'bar', height: 260, toolbar: { show: false } },
            colors: [ins('info')],
            series: [
                { name: 'Revenue', data: (data.typeTotals || []).map(Number) },
            ],
            xaxis: {
                categories: data.typeLabels || [],
                labels: { style: { colors: ins('secondary-color') } },
            },
            yaxis: {
                labels: {
                    formatter: v => currency + v.toLocaleString(),
                    style: { colors: ins('secondary-color') },
                },
            },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
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

    // ── Top Services — Horizontal Bar Chart ──
    new CustomApexChart({
        selector: '#sales-top-services-chart',
        options: () => ({
            theme: { mode: theme },
            chart: { type: 'bar', height: 260, toolbar: { show: false } },
            colors: [ins('primary')],
            series: [
                {
                    name: 'Revenue',
                    data: (data.serviceRevenues || [])
                        .slice()
                        .reverse()
                        .map(Number),
                },
            ],
            xaxis: {
                categories: (data.serviceNames || []).slice().reverse(),
                labels: { style: { colors: ins('secondary-color') } },
            },
            yaxis: {
                labels: { style: { colors: ins('secondary-color') } },
            },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
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
});
