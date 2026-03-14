/**
 * Template Name: UBold - Admin & Dashboard Template
 * By (Author): Coderthemes
 * Module/App (File Name): Expenses Dashboard Charts
 */

import { CustomApexChart, ins } from '../app'

//
// Monthly Trend Chart - Area chart with gradient fill
//
new CustomApexChart({
    selector: '#monthly-trend-chart',
    options: () => ({
        chart: {
            height: 350,
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: 3,
            curve: 'smooth'
        },
        colors: [ins('primary')],
        series: [{
            name: 'Total Expenses',
            data: [12000, 15000, 18000, 22000, 19000, 25000, 28000, 30000, 27000, 32000, 35000, 40000]
        }],
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    fontSize: '12px',
                    colors: ins('secondary-color')
                }
            }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return (window.currencySymbol || '$') + val.toLocaleString()
                },
                style: {
                    colors: ins('secondary-color')
                }
            },
            title: {
                text: 'Amount (' + (window.currencySymbol || '$') + ')',
                style: {
                    color: ins('secondary-color')
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return (window.currencySymbol || '$') + val.toLocaleString()
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.2,
                stops: [0, 90, 100]
            }
        },
        grid: {
            borderColor: ins('border-color'),
            strokeDashArray: 4,
            padding: {
                top: 0,
                right: 0,
                bottom: 0,
                left: 0
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            labels: {
                colors: ins('secondary-color')
            }
        }
    })
})

//
// Category Breakdown Chart - Donut chart with category colors
//
new CustomApexChart({
    selector: '#category-breakdown-chart',
    options: () => ({
        chart: {
            height: 350,
            type: 'donut',
            toolbar: { show: false }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                return opts.w.globals.series[opts.seriesIndex].toLocaleString() + ' (' + val.toFixed(1) + '%)'
            },
            style: {
                fontSize: '12px',
                fontWeight: 'normal'
            }
        },
        colors: [
            ins('primary'),
            ins('secondary'),
            ins('success'),
            ins('warning'),
            ins('danger'),
            ins('info'),
            ins('dark'),
            ins('gray')
        ],
        series: [18000, 12000, 9000, 7000, 5000, 4000, 3000, 2000],
        labels: ['Utilities', 'Rent', 'Office Supplies', 'Travel', 'Equipment', 'Salaries', 'Marketing', 'Other'],
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            labels: {
                colors: ins('secondary-color')
            },
            itemMargin: {
                horizontal: 10,
                vertical: 5
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '16px',
                            fontWeight: 600,
                            color: ins('secondary-color')
                        },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontWeight: 700,
                            color: ins('primary'),
                            formatter: function (val) {
                                return (window.currencySymbol || '$') + parseInt(val).toLocaleString()
                            }
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            color: ins('secondary-color'),
                            formatter: function (w) {
                                return (window.currencySymbol || '$') + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString()
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return (window.currencySymbol || '$') + val.toLocaleString()
                }
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    })
})

//
// Budget Utilization Chart - Horizontal bar chart showing percentage
//
new CustomApexChart({
    selector: '#budget-utilization-chart',
    options: () => ({
        chart: {
            height: 350,
            type: 'bar',
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '70%',
                borderRadius: 8,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        colors: [ins('primary')],
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val + '%'
            },
            offsetX: 10,
            style: {
                fontSize: '12px',
                colors: ['#fff']
            }
        },
        series: [{
            name: 'Utilization',
            data: [85, 72, 90, 65, 78, 92, 60, 45]
        }],
        xaxis: {
            categories: ['Utilities', 'Rent', 'Office Supplies', 'Travel', 'Equipment', 'Salaries', 'Marketing', 'Other'],
            labels: {
                formatter: function (val) {
                    return val + '%'
                },
                style: {
                    colors: ins('secondary-color')
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            max: 100
        },
        yaxis: {
            labels: {
                style: {
                    colors: ins('secondary-color')
                }
            }
        },
        grid: {
            borderColor: ins('border-color'),
            strokeDashArray: 4,
            padding: {
                top: 0,
                right: 20,
                bottom: 0,
                left: 0
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + '% utilized'
                }
            }
        },
        fill: {
            opacity: 0.8,
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'horizontal',
                shadeIntensity: 0.25,
                gradientToColors: [ins('primary-rgb', 0.6)],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 0.8,
                stops: [0, 50, 100]
            }
        },
        annotations: {
            xaxis: [{
                x: 100,
                borderColor: ins('danger'),
                label: {
                    borderColor: ins('danger'),
                    style: {
                        color: '#fff',
                        background: ins('danger')
                    },
                    text: 'Budget Limit'
                }
            }]
        }
    })
})

// Auto-rerender on theme change is already handled by CustomApexChart class
// No additional event listeners needed