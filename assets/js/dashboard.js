/* global Chart */
document.addEventListener('DOMContentLoaded', function () {
    const trendCanvas = document.getElementById('cashflowChart');
    if (trendCanvas && window.dashboardTrends) {
        const {labels, income, expense} = window.dashboardTrends;
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: income,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.08)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Pengeluaran',
                        data: expense,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.08)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: 'IDR',
                                    maximumFractionDigits: 0
                                }).format(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {display: true}
                }
            }
        });
    }

    const reportTrendCanvas = document.getElementById('reportTrendChart');
    if (reportTrendCanvas && window.reportChartData) {
        const {labels, income, expense} = window.reportChartData;
        new Chart(reportTrendCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: income,
                        backgroundColor: '#22c55e'
                    },
                    {
                        label: 'Pengeluaran',
                        data: expense,
                        backgroundColor: '#f97316'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: 'IDR',
                                    maximumFractionDigits: 0
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });
    }

    const expensePieCanvas = document.getElementById('expensePieChart');
    if (expensePieCanvas && window.reportChartData) {
        const breakdown = window.reportChartData.expenseBreakdown || [];
        new Chart(expensePieCanvas, {
            type: 'doughnut',
            data: {
                labels: breakdown.map(item => item.label),
                datasets: [{
                    data: breakdown.map(item => item.value),
                    backgroundColor: ['#6366f1', '#f43f5e', '#f59e0b', '#22c55e', '#0ea5e9', '#a855f7', '#ec4899']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    const typeSelect = document.getElementById('type');
    const categorySelect = document.getElementById('category_id');
    if (typeSelect && categorySelect) {
        const applyCategoryFilter = () => {
            const currentType = typeSelect.value;
            Array.from(categorySelect.options).forEach(option => {
                if (!option.dataset.type) {
                    option.hidden = false;
                    return;
                }
                option.hidden = option.dataset.type !== currentType;
            });

            const selectedOption = categorySelect.selectedOptions[0];
            if (selectedOption && selectedOption.hidden) {
                categorySelect.value = '';
            }
        };

        typeSelect.addEventListener('change', applyCategoryFilter);
        applyCategoryFilter();
    }
});
