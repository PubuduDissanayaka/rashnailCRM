import { CustomApexChart, ins } from '../app'

const data = window.__reportData || {}
const currency = window.currencySymbol || '$'

// Revenue vs Expenses — area chart
new CustomApexChart({
    selector: '#revenue-vs-expenses-chart',
    options: () => ({
        chart: { type: 'area', height: 310, toolbar: { show: false }, zoom: { enabled: false } },
        colors: [ins('success'), ins('danger')],
        series: [
            { name: 'Revenue', data: data.revenue || [] },
            { name: 'Expenses', data: data.expenses || [] },
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
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right', labels: { colors: ins('secondary-color') } },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => currency + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
    }),
})

// Today's Appointment Status — donut
new CustomApexChart({
    selector: '#appt-status-donut-chart',
    options: () => {
        const statusColors = {
            scheduled:   ins('info'),
            in_progress: ins('warning'),
            completed:   ins('success'),
            cancelled:   ins('danger'),
        }
        const labels  = Object.keys(data.apptStatus || {}).map(s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' '))
        const series  = Object.values(data.apptStatus || {}).map(Number)
        const colors  = Object.keys(data.apptStatus || {}).map(k => statusColors[k] || ins('primary'))
        return {
            chart: { type: 'donut', height: 310 },
            series,
            labels,
            colors,
            legend: { position: 'bottom', labels: { colors: ins('secondary-color') } },
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
                                formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                            },
                        },
                    },
                },
            },
        }
    },
})
