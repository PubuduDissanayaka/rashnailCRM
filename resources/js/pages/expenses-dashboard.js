import { CustomApexChart, ins } from '../app'

const sym = () => window.currencySymbol || '$'

// Monthly trend from real DB data
const trend       = window.expenseMonthlyTrend || []
const trendMonths = trend.map(r => r.month)
const trendData   = trend.map(r => parseFloat(r.paid) || 0)

new CustomApexChart({
    selector: '#monthly-trend-chart',
    options: () => ({
        chart: { height: 350, type: 'area', toolbar: { show: false }, zoom: { enabled: false } },
        dataLabels: { enabled: false },
        stroke: { width: 3, curve: 'smooth' },
        colors: [ins('primary')],
        series: [{ name: 'Total Expenses', data: trendData }],
        xaxis: {
            categories: trendMonths,
            axisBorder: { show: false }, axisTicks: { show: false },
            labels: { style: { fontSize: '12px', colors: ins('secondary-color') } }
        },
        yaxis: {
            labels: { formatter: val => sym() + val.toLocaleString(), style: { colors: ins('secondary-color') } },
            title: { text: 'Amount (' + sym() + ')', style: { color: ins('secondary-color') } }
        },
        tooltip: { y: { formatter: val => sym() + val.toLocaleString() } },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.2, stops: [0, 90, 100] } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4, padding: { top: 0, right: 0, bottom: 0, left: 0 } },
        legend: { position: 'top', horizontalAlign: 'left', labels: { colors: ins('secondary-color') } }
    })
})

// Category breakdown from real DB data
const catData   = window.expenseCategoryBreakdown || []
const catLabels = catData.length ? catData.map(c => c.name) : ['No Data']
const catSeries = catData.length ? catData.map(c => parseFloat(c.expenses_sum_total_amount) || 0) : [1]

new CustomApexChart({
    selector: '#category-breakdown-chart',
    options: () => ({
        chart: { height: 350, type: 'donut', toolbar: { show: false } },
        dataLabels: {
            enabled: true,
            formatter: (val, opts) => opts.w.globals.series[opts.seriesIndex].toLocaleString() + ' (' + val.toFixed(1) + '%)'
        },
        colors: [ins('primary'), ins('secondary'), ins('success'), ins('warning'), ins('danger'), ins('info'), ins('dark'), ins('gray')],
        series: catSeries,
        labels: catLabels,
        legend: { position: 'bottom', horizontalAlign: 'center', labels: { colors: ins('secondary-color') }, itemMargin: { horizontal: 10, vertical: 5 } },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name:  { show: true, fontSize: '16px', fontWeight: 600, color: ins('secondary-color') },
                        value: { show: true, fontSize: '24px', fontWeight: 700, color: ins('primary'), formatter: val => sym() + parseInt(val).toLocaleString() },
                        total: { show: true, label: 'Total', color: ins('secondary-color'), formatter: w => sym() + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString() }
                    }
                }
            }
        },
        tooltip: { y: { formatter: val => sym() + val.toLocaleString() } },
        responsive: [{ breakpoint: 480, options: { chart: { height: 300 }, legend: { position: 'bottom' } } }]
    })
})

// Budget utilization from real DB data
const budgetData   = window.expenseBudgetUtilization || []
const budgetLabels = budgetData.map(b => b.category)
const budgetSeries = budgetData.map(b => Math.round(parseFloat(b.utilization) || 0))

if (budgetData.length > 0) {
    new CustomApexChart({
        selector: '#budget-utilization-chart',
        options: () => ({
            chart: { height: 350, type: 'bar', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, barHeight: '70%', borderRadius: 8, dataLabels: { position: 'top' } } },
            colors: [ins('primary')],
            dataLabels: { enabled: true, formatter: val => val + '%', offsetX: 10, style: { fontSize: '12px', colors: ['#fff'] } },
            series: [{ name: 'Utilization', data: budgetSeries }],
            xaxis: {
                categories: budgetLabels,
                labels: { formatter: val => val + '%', style: { colors: ins('secondary-color') } },
                axisBorder: { show: false }, axisTicks: { show: false }, max: 100
            },
            yaxis: { labels: { style: { colors: ins('secondary-color') } } },
            grid: { borderColor: ins('border-color'), strokeDashArray: 4, padding: { top: 0, right: 20, bottom: 0, left: 0 } },
            tooltip: { y: { formatter: val => val + '% utilized' } },
            fill: { opacity: 0.8 },
            annotations: { xaxis: [{ x: 100, borderColor: ins('danger'), label: { borderColor: ins('danger'), style: { color: '#fff', background: ins('danger') }, text: 'Budget Limit' } }] }
        })
    })
} else {
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.querySelector('#budget-utilization-chart')
        if (el) el.innerHTML = '<div class="text-center text-muted py-5"><i class="ti ti-chart-bar fs-24 d-block mb-2"></i>No budget data. Set budgets in Expense Categories.</div>'
    })
}
