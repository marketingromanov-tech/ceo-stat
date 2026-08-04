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

function sparklineOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: tooltipLabel } } },
        scales: { x: { display: false }, y: { display: false } },
        elements: { point: { radius: 0, hoverRadius: 4 }, line: { borderWidth: 2.5 } },
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
    createChart('daily-results-chart', {
        type: 'line',
        data: { labels: data.daily.labels, datasets: [{ label: 'Результат', data: data.daily.values, borderColor: '#5b93ff', backgroundColor: 'rgba(91,147,255,.10)', fill: true, tension: .38 }] },
        options: sparklineOptions(),
    });
    createChart('cumulative-results-chart', {
        type: 'line',
        data: { labels: data.cumulative.labels, datasets: [{ label: 'Накоплено', data: data.cumulative.values, borderColor: '#605bff', backgroundColor: 'rgba(96, 91, 255, .10)', fill: true, tension: .28, pointRadius: 2, pointHoverRadius: 5 }] },
        options: sparklineOptions(),
    });
    createChart('trading-dynamics-chart', {
        type: 'line',
        data: { labels: data.daily.labels, datasets: [{ label: 'Результат', data: data.daily.values, borderColor: '#605bff', backgroundColor: 'rgba(96,91,255,.08)', fill: true, tension: .35, pointRadius: 2, pointHoverRadius: 5 }] },
        options: chartOptions(),
    });
    createChart('robot-results-chart', {
        type: 'doughnut',
        data: { labels: data.robots.labels, datasets: [{ label: 'Результат', data: data.robots.values.map((value) => Math.abs(value)), backgroundColor: ['#5b93ff', '#ffd66b', '#ff8f6b', '#605bff', '#26c0e2'], borderWidth: 0, spacing: 2 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', color: '#6b6a7d', padding: 16, boxWidth: 8 } }, tooltip: { callbacks: { label: tooltipLabel } } } },
    });
    createChart('monthly-results-chart', {
        type: 'bar',
        data: { labels: data.monthly.labels, datasets: [{ label: 'Результат', data: data.monthly.values, backgroundColor: data.monthly.values.map((_, index) => index % 2 ? '#5b93ff' : '#ff8f6b'), borderRadius: 7, barThickness: 10 }] },
        options: chartOptions('y'),
    });
}

document.addEventListener('DOMContentLoaded', renderDashboardCharts);
document.addEventListener('livewire:navigated', renderDashboardCharts);
