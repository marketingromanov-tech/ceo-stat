import './bootstrap';
import Chart from 'chart.js/auto';

const dashboardCharts = new Map();

const money = new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const tooltipLabel = (context) => {
    const value = typeof context.parsed === 'number'
        ? context.parsed
        : context.chart.options.indexAxis === 'y'
            ? context.parsed.x
            : context.parsed.y;

    return `${context.dataset.label}: ${money.format(value ?? 0)}`;
};

function chartOptions(indexAxis = 'x') {
    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: tooltipLabel } },
        },
        scales: indexAxis === 'y'
            ? {
                x: { grid: { color: '#e7e5e4' }, ticks: { color: '#78716c', callback: (value) => money.format(value) } },
                y: { grid: { display: false }, ticks: { color: '#78716c' } },
            }
            : {
                x: { grid: { display: false }, ticks: { color: '#78716c', maxRotation: 0, autoSkip: true } },
                y: { grid: { color: '#e7e5e4' }, ticks: { color: '#78716c', callback: (value) => money.format(value) } },
            },
    };
}

function createChart(id, configuration) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    dashboardCharts.get(id)?.destroy();
    dashboardCharts.set(id, new Chart(canvas, configuration));
}

function renderDashboardCharts() {
    const source = document.getElementById('dashboard-chart-data');
    if (!source) return;
    const data = JSON.parse(source.textContent);
    const positiveNegative = (values) => values.map((value) => value < 0 ? '#ff8f6b' : '#5b93ff');

    createChart('daily-results-chart', {
        type: 'bar',
        data: { labels: data.daily.labels, datasets: [{ label: 'Результат', data: data.daily.values, backgroundColor: positiveNegative(data.daily.values), borderRadius: 6 }] },
        options: chartOptions(),
    });
    createChart('cumulative-results-chart', {
        type: 'line',
        data: { labels: data.cumulative.labels, datasets: [{ label: 'Накоплено', data: data.cumulative.values, borderColor: '#ffc327', backgroundColor: 'rgba(255,195,39,.12)', fill: true, tension: .35, pointRadius: 2, pointHoverRadius: 5 }] },
        options: chartOptions(),
    });
    createChart('robot-results-chart', {
        type: 'bar',
        data: { labels: data.robots.labels, datasets: [{ label: 'Результат', data: data.robots.values, backgroundColor: positiveNegative(data.robots.values), borderRadius: 7 }] },
        options: chartOptions('y'),
    });
    createChart('monthly-results-chart', {
        type: 'bar',
        data: { labels: data.monthly.labels, datasets: [{ label: 'Результат', data: data.monthly.values, backgroundColor: positiveNegative(data.monthly.values), borderRadius: 7 }] },
        options: chartOptions(),
    });
}

document.addEventListener('DOMContentLoaded', renderDashboardCharts);
document.addEventListener('livewire:navigated', renderDashboardCharts);
