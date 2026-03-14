import { CustomApexChart, ins } from '../app'

const data     = window.__reportData || {}
const currency = window.currencySymbol || '$'

// Monthly Trend — bar
new CustomApexChart({
    selector: '#exp-monthly-trend-chart',
    options: () => ({
        chart: { type: 'bar', height: 280, toolbar: { show: false } },
        colors: [ins('danger')],
        series: [{ name: 'Expenses', data: (data.trendAmounts || []).map(Number) }],
        xaxis: {
            categories: data.trendMonths || [],
            labels: { style: { colors: ins('secondary-color') } },
            axisBorder: { show: false }, axisTicks: { show: false },
        },
        yaxis: { labels: { formatter: v => currency + v.toLocaleString(), style: { colors: ins('secondary-color') } } },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => currency + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
    }),
})

// By Category — donut
new CustomApexChart({
    selector: '#exp-category-chart',
    options: () => ({
        chart: { type: 'donut', height: 280 },
        series: (data.catTotals || []).map(Number),
        labels: data.catLabels || [],
        colors: [ins('primary'), ins('success'), ins('info'), ins('warning'), ins('danger'), ins('secondary')],
        legend: { position: 'bottom', labels: { colors: ins('secondary-color') } },
        dataLabels: { enabled: true, style: { fontSize: '10px' } },
        plotOptions: { pie: { donut: { size: '60%' } } },
        tooltip: { y: { formatter: v => currency + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
    }),
})

// Status Breakdown — bar
new CustomApexChart({
    selector: '#exp-status-chart',
    options: () => ({
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        colors: [ins('info')],
        series: [{ name: 'Count', data: (data.statusCounts || []).map(Number) }],
        xaxis: {
            categories: data.statusLabels || [],
            labels: { style: { colors: ins('secondary-color') } },
        },
        yaxis: { labels: { style: { colors: ins('secondary-color') }, formatter: v => Math.round(v) } },
        plotOptions: { bar: { borderRadius: 5, columnWidth: '50%' } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' expenses' } },
    }),
})

// Payment Method — donut
new CustomApexChart({
    selector: '#exp-payment-chart',
    options: () => ({
        chart: { type: 'donut', height: 260 },
        series: (data.payTotals || []).map(Number),
        labels: (data.payLabels || []).map(l => l ? l.charAt(0).toUpperCase() + l.slice(1).replace(/_/g, ' ') : 'Other'),
        colors: [ins('success'), ins('primary'), ins('warning'), ins('info'), ins('secondary')],
        legend: { position: 'bottom', labels: { colors: ins('secondary-color') } },
        dataLabels: { enabled: true },
        plotOptions: { pie: { donut: { size: '65%' } } },
        tooltip: { y: { formatter: v => currency + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
    }),
})
