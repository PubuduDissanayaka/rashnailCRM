import { CustomApexChart, ins } from '../app'

const data     = window.__reportData || {}
const currency = window.currencySymbol || '$'

// Stock by Category — bar
new CustomApexChart({
    selector: '#inv-stock-category-chart',
    options: () => ({
        chart: { type: 'bar', height: 280, toolbar: { show: false } },
        colors: [ins('primary')],
        series: [{ name: 'Stock Units', data: (data.catStock || []).map(Number) }],
        xaxis: {
            categories: data.catNames || [],
            labels: { style: { colors: ins('secondary-color') } },
            axisBorder: { show: false }, axisTicks: { show: false },
        },
        yaxis: { labels: { style: { colors: ins('secondary-color') }, formatter: v => Math.round(v) } },
        plotOptions: { bar: { borderRadius: 5, columnWidth: '55%' } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' units' } },
    }),
})

// Stock Value Distribution — donut
new CustomApexChart({
    selector: '#inv-value-donut-chart',
    options: () => ({
        chart: { type: 'donut', height: 280 },
        series: (data.valCatValues || []).map(Number),
        labels: data.valCatNames || [],
        colors: [ins('success'), ins('primary'), ins('info'), ins('warning'), ins('danger'), ins('secondary')],
        legend: { position: 'bottom', labels: { colors: ins('secondary-color') } },
        dataLabels: { enabled: true, style: { fontSize: '10px' } },
        plotOptions: { pie: { donut: { size: '60%' } } },
        tooltip: { y: { formatter: v => currency + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
    }),
})

// Top Used Supplies — horizontal bar
new CustomApexChart({
    selector: '#inv-top-used-chart',
    options: () => ({
        chart: { type: 'bar', height: 220, toolbar: { show: false } },
        colors: [ins('warning')],
        series: [{ name: 'Qty Used', data: (data.usedQty || []).slice().reverse().map(Number) }],
        xaxis: {
            categories: (data.usedNames || []).slice().reverse(),
            labels: { style: { colors: ins('secondary-color') } },
        },
        yaxis: { labels: { style: { colors: ins('secondary-color') } } },
        plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        grid: { borderColor: ins('border-color'), strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' units used' } },
    }),
})
