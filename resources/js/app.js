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

function sparklineOptions(centerValues = false) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: tooltipLabel } } },
        scales: { x: { display: false, offset: centerValues }, y: { display: false } },
        layout: { padding: { top: 8 } },
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
        data: { labels: data.daily.labels, datasets: [{ label: 'Результат', data: data.daily.values, borderColor: '#5b93ff', backgroundColor: 'rgba(91,147,255,.12)', fill: true, tension: .42 }] },
        options: sparklineOptions(),
    });
    createChart('cumulative-results-chart', {
        type: 'line',
        data: { labels: data.cumulative.labels, datasets: [{ label: 'Накоплено', data: data.cumulative.values, borderColor: '#ffc327', backgroundColor: 'rgba(255,195,39,.12)', fill: true, tension: .42 }] },
        options: sparklineOptions(),
    });
    createChart('robot-results-chart', {
        type: 'line',
        data: { labels: data.robots.labels, datasets: [{ label: 'Результат', data: data.robots.values, borderColor: '#605bff', backgroundColor: 'rgba(96,91,255,.12)', fill: true, tension: .42, pointRadius: data.robots.values.length === 1 ? 4 : 0 }] },
        options: sparklineOptions(true),
    });
    createChart('monthly-results-chart', {
        type: 'line',
        data: { labels: data.monthly.labels, datasets: [{ label: 'Результат', data: data.monthly.values, borderColor: '#ff8f6b', backgroundColor: 'rgba(255,143,107,.12)', fill: true, tension: .42, pointRadius: data.monthly.values.length === 1 ? 4 : 0 }] },
        options: sparklineOptions(),
    });
}

function updateDashboardStat(name, value, digits, suffix = '') {
    const formatted = new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(value);

    document.querySelectorAll(`[data-dashboard-stat="${name}"]`).forEach((element) => {
        element.textContent = `${formatted}${suffix}`;
        if (name.includes('percent')) {
            element.classList.toggle('text-red-700', value < 0);
            element.classList.toggle('text-red-600', value < 0);
            element.classList.toggle('text-[#605bff]', value >= 0);
        }
    });
}

let dashboardRequestId = 0;

window.addEventListener('calendar-period-changed', async (event) => {
    const month = event.detail?.month;
    if (!month || !document.getElementById('dashboard-chart-data')) return;
    const requestId = ++dashboardRequestId;

    const response = await fetch(`/dashboard-data?month=${encodeURIComponent(month)}`, {
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) return;

    const data = await response.json();
    if (requestId !== dashboardRequestId) return;
    updateDashboardStat('month-profit', data.monthProfit, 2);
    updateDashboardStat('month-percent', data.monthPercent, 3, '%');
    updateDashboardStat('month-percent-signed', data.monthPercent, 3, '% за месяц');
    document.querySelectorAll('[data-dashboard-stat="month-percent-signed"]').forEach((element) => {
        if (data.monthPercent >= 0) element.textContent = `+${element.textContent}`;
    });
    updateDashboardStat('day-percent', data.dayPercent, 3, '%');
    updateDashboardStat('daily-average', data.dailyAverage, 2);
    document.getElementById('dashboard-chart-data').textContent = JSON.stringify(data.chartData);
    renderDashboardCharts();
});

document.addEventListener('DOMContentLoaded', renderDashboardCharts);
document.addEventListener('livewire:navigated', renderDashboardCharts);
