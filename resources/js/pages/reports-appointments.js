import { CustomApexChart, ins } from '../app'

const data = window.__reportData || {}

// Daily Trend — line chart
new CustomApexChart({
    selector: '#appt-daily-trend-chart',
    options: () => ({
        chart: { type: 'line', height: 280, toolbar: { show: false }, zoom: { enabled: false } },
        colors: [ins('info')],
        series: [{ name: 'Appointments', data: data.dailyCounts || [] }],
        xaxis: {
            categories: data.dailyDates || [],
            labels: { style: { colors: ins('secondary-color') }, rotate: -30, hideOverlappingLabels: true },
            axisBorder: { show: false }, axisTicks: { show: false },
        },
        yaxis: { labels: { style: { colors: ins('secondary-color') }, formatter: v => Math.round(v) } },
        stroke: { width: 2, curve: 'smooth' },
        markers: { size: 3, colors: [ins('info')], strokeColors: '#fff', strokeWidth: 2 },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' appointments' } },
    }),
})

// Status Distribution — donut
new CustomApexChart({
    selector: '#appt-status-dist-chart',
    options: () => {
        const colorMap = { scheduled: ins('info'), in_progress: ins('warning'), completed: ins('success'), cancelled: ins('danger') }
        const labels  = (data.statusLabels || []).map(l => l.charAt(0).toUpperCase() + l.slice(1).replace('_', ' '))
        const colors  = (data.statusLabels || []).map(k => colorMap[k] || ins('primary'))
        return {
            chart: { type: 'donut', height: 280 },
            series: (data.statusCounts || []).map(Number),
            labels,
            colors,
            legend: { position: 'bottom', labels: { colors: ins('secondary-color') } },
            dataLabels: { enabled: true, style: { fontSize: '11px' } },
            plotOptions: { pie: { donut: { size: '65%' } } },
        }
    },
})

// By Day of Week — column chart
new CustomApexChart({
    selector: '#appt-dow-chart',
    options: () => ({
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        colors: [ins('primary')],
        series: [{ name: 'Appointments', data: data.dowCounts || [] }],
        xaxis: {
            categories: data.dowLabels || [],
            labels: { style: { colors: ins('secondary-color') } },
        },
        yaxis: { labels: { style: { colors: ins('secondary-color') }, formatter: v => Math.round(v) } },
        plotOptions: { bar: { borderRadius: 5, columnWidth: '55%' } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' bookings' } },
    }),
})

// Top Services — horizontal bar
new CustomApexChart({
    selector: '#appt-top-services-chart',
    options: () => ({
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        colors: [ins('success')],
        series: [{ name: 'Bookings', data: (data.serviceCounts || []).slice().reverse().map(Number) }],
        xaxis: {
            categories: (data.serviceNames || []).slice().reverse(),
            labels: { style: { colors: ins('secondary-color') } },
        },
        yaxis: { labels: { style: { colors: ins('secondary-color') } } },
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' bookings' } },
    }),
})
