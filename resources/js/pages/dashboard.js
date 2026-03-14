/**
 * RNS Salon Management System
 * Dashboard Charts
 */
import { CustomApexChart, ins } from '../app.js';

const data = window.__dashboardData || {};

// Monthly Revenue Area Chart
if (document.querySelector('#revenue-chart')) {
    new CustomApexChart({
        selector: '#revenue-chart',
        series: [{ name: 'Revenue', data: data.revenueData || [] }],
        options: () => ({
            chart: {
                type: 'area',
                height: 280,
                toolbar: { show: false },
                zoom: { enabled: false },
            },
            colors: [ins('primary')],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 90, 100],
                },
            },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: {
                categories: data.revenueMonths || [],
                labels: {
                    style: { colors: ins('secondary-color'), fontSize: '12px' },
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    formatter: (v) => '$' + Number(v).toLocaleString(),
                    style: { colors: ins('secondary-color') },
                },
            },
            tooltip: {
                y: { formatter: (v) => '$' + Number(v).toFixed(2) },
            },
            grid: {
                borderColor: ins('border-color'),
                strokeDashArray: 4,
            },
            dataLabels: { enabled: false },
            markers: { size: 0 },
        }),
    });
}

// Today's Appointment Status Donut Chart
if (document.querySelector('#appointment-status-chart') && data.hasAppts) {
    new CustomApexChart({
        selector: '#appointment-status-chart',
        series: data.apptStatusCounts || [0, 0, 0, 0],
        options: () => ({
            chart: {
                type: 'donut',
                height: 280,
            },
            labels: ['Scheduled', 'In Progress', 'Completed', 'Cancelled'],
            colors: [ins('info'), ins('warning'), ins('success'), ins('danger')],
            legend: {
                position: 'bottom',
                labels: { colors: ins('secondary-color') },
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '12px' },
            },
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
                                formatter: (w) =>
                                    w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                            },
                        },
                    },
                },
            },
            tooltip: {
                y: { formatter: (v) => v + ' appointments' },
            },
        }),
    });
}
