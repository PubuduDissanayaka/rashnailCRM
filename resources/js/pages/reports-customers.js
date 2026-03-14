import { CustomApexChart, ins } from '../app'

const data     = window.__reportData || {}
const currency = window.currencySymbol || '$'

// Acquisition Trend — area
new CustomApexChart({
    selector: '#cust-acquisition-chart',
    options: () => ({
        chart: { type: 'area', height: 280, toolbar: { show: false }, zoom: { enabled: false } },
        colors: [ins('primary')],
        series: [{ name: 'New Customers', data: data.acqCounts || [] }],
        xaxis: {
            categories: data.acqMonths || [],
            labels: { style: { colors: ins('secondary-color') }, rotate: -20, hideOverlappingLabels: true },
            axisBorder: { show: false }, axisTicks: { show: false },
        },
        yaxis: { labels: { style: { colors: ins('secondary-color') }, formatter: v => Math.round(v) } },
        stroke: { width: 2, curve: 'smooth' },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' customers' } },
    }),
})

// Gender — pie
new CustomApexChart({
    selector: '#cust-gender-chart',
    options: () => ({
        chart: { type: 'pie', height: 280 },
        series: (data.genderCounts || []).map(Number),
        labels: (data.genderLabels || []).map(l => l ? l.charAt(0).toUpperCase() + l.slice(1).replace(/_/g, ' ') : 'Not set'),
        colors: [ins('info'), ins('pink') || ins('danger'), ins('warning'), ins('secondary')],
        legend: { position: 'bottom', labels: { colors: ins('secondary-color') } },
        dataLabels: { enabled: true, style: { fontSize: '11px' } },
    }),
})

// Status — donut
new CustomApexChart({
    selector: '#cust-status-chart',
    options: () => ({
        chart: { type: 'donut', height: 280 },
        series: (data.statusCounts || []).map(Number),
        labels: (data.statusLabels || []).map(l => l ? l.charAt(0).toUpperCase() + l.slice(1) : 'Unknown'),
        colors: [ins('success'), ins('secondary'), ins('danger')],
        legend: { position: 'bottom', labels: { colors: ins('secondary-color') } },
        dataLabels: { enabled: true },
        plotOptions: { pie: { donut: { size: '65%' } } },
    }),
})

// Top Customers by Spend — horizontal bar
new CustomApexChart({
    selector: '#cust-top-spend-chart',
    options: () => ({
        chart: { type: 'bar', height: 280, toolbar: { show: false } },
        colors: [ins('warning')],
        series: [{ name: 'Total Spent', data: (data.topSpend || []).slice().reverse().map(Number) }],
        xaxis: {
            categories: (data.topNames || []).slice().reverse(),
            labels: { style: { colors: ins('secondary-color') } },
        },
        yaxis: { labels: { style: { colors: ins('secondary-color') } } },
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => currency + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
    }),
})
