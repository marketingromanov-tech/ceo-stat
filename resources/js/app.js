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
    if (!canvas) {
        dashboardCharts.get(id)?.destroy();
        dashboardCharts.delete(id);
        return;
    }
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

function renderRobotStatisticsCharts() {
    const source = document.getElementById('robot-statistics-chart-data');
    if (!source) return;
    const data = JSON.parse(source.textContent);
    const options = sparklineOptions(true);
    createChart('robot-growth-chart', { type: 'line', data: { labels: data.growth.labels, datasets: [{ label: 'Баланс', data: data.growth.values, borderColor: '#605bff', backgroundColor: 'rgba(96,91,255,.10)', fill: true, tension: .3 }] }, options });
    createChart('robot-daily-chart', { type: 'bar', data: { labels: data.daily.labels, datasets: [{ label: 'Результат', data: data.daily.values, backgroundColor: data.daily.values.map(value => value < 0 ? '#b91c1c' : '#605bff'), borderRadius: 4 }] }, options });
    createChart('robot-monthly-chart', { type: 'bar', data: { labels: data.monthly.labels, datasets: [{ label: 'Прибыль', data: data.monthly.values, backgroundColor: data.monthly.values.map(value => value < 0 ? '#b91c1c' : '#605bff'), borderRadius: 5 }] }, options });
}

function renderPortfolioStatisticsCharts() {
    const source = document.getElementById('portfolio-statistics-chart-data');
    if (!source) {
        ['portfolio-growth-chart', 'portfolio-daily-chart', 'portfolio-monthly-chart', 'portfolio-robots-chart']
            .forEach(id => createChart(id, {}));
        return;
    }
    const data = JSON.parse(source.textContent);
    const palette = ['#605bff', '#5b93ff', '#ff8f6b', '#ffc327', '#22a06b', '#a855f7', '#0ea5e9', '#f43f5e'];

    createChart('portfolio-growth-chart', {
        type: 'line',
        data: { labels: data.growth.labels, datasets: [{ label: 'Equity', data: data.growth.values, borderColor: '#605bff', backgroundColor: 'rgba(96,91,255,.10)', fill: true, tension: .3 }] },
        options: sparklineOptions(true),
    });
    createChart('portfolio-daily-chart', {
        type: 'bar',
        data: { labels: data.daily.labels, datasets: [{ label: 'Результат', data: data.daily.values, backgroundColor: data.daily.values.map(value => value < 0 ? '#b91c1c' : '#605bff'), borderRadius: 4 }] },
        options: sparklineOptions(true),
    });
    createChart('portfolio-monthly-chart', {
        type: 'bar',
        data: { labels: data.monthly.labels, datasets: [{ label: 'Прибыль', data: data.monthly.values, backgroundColor: data.monthly.values.map(value => value < 0 ? '#b91c1c' : '#605bff'), borderRadius: 5 }] },
        options: sparklineOptions(true),
    });

    const robotOptions = sparklineOptions(true);
    robotOptions.indexAxis = 'y';
    robotOptions.plugins.tooltip.callbacks.label = context => `${context.dataset.label}: ${money.format(context.parsed.x ?? 0)}%`;
    createChart('portfolio-robots-chart', {
        type: 'bar',
        data: { labels: data.robots.labels, datasets: [{ label: 'Доля', data: data.robots.values, backgroundColor: data.robots.values.map((value, index) => value < 0 ? '#b91c1c' : palette[index % palette.length]), borderRadius: 5 }] },
        options: robotOptions,
    });
}

function openMobileSummaries() {
    document.querySelectorAll('.mobile-summary-list > details').forEach((details) => {
        details.open = true;
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

async function updateDashboardPeriod(month) {
    if (!month || !document.getElementById('dashboard-chart-data')) return;
    const requestId = ++dashboardRequestId;

    const response = await fetch(`/dashboard-data?month=${encodeURIComponent(month)}`, {
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) return;

    const data = await response.json();
    if (requestId !== dashboardRequestId) return;
    const monthLabel = new Intl.DateTimeFormat('ru-RU', { month: 'long', year: 'numeric' })
        .format(new Date(`${month}-01T12:00:00`));
    document.getElementById('dashboard-chart-data').dataset.month = month;
    document.querySelectorAll('[data-dashboard-period-name]').forEach((element) => {
        element.textContent = monthLabel;
    });
    document.querySelectorAll('[data-dashboard-month-label]').forEach((element) => {
        element.textContent = `Итого за ${monthLabel}`;
    });
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
}

window.addEventListener('calendar-period-changed', (event) => {
    updateDashboardPeriod(event.detail?.month);
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-dashboard-month-change]');
    const source = document.getElementById('dashboard-chart-data');
    if (!button || !source) return;

    const [year, month] = source.dataset.month.split('-').map(Number);
    const date = new Date(year, month - 1 + Number(button.dataset.dashboardMonthChange), 1, 12);
    const selectedMonth = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    window.Livewire?.dispatch('dashboard-period-selected', { month: selectedMonth });
    updateDashboardPeriod(selectedMonth);
});

document.addEventListener('DOMContentLoaded', () => {
    renderDashboardCharts();
    renderRobotStatisticsCharts();
    renderPortfolioStatisticsCharts();
    openMobileSummaries();
});
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => requestAnimationFrame(() => {
        renderRobotStatisticsCharts();
        renderPortfolioStatisticsCharts();
    }));
});
document.addEventListener('livewire:navigated', () => {
    renderDashboardCharts();
    renderRobotStatisticsCharts();
    renderPortfolioStatisticsCharts();
    openMobileSummaries();
});
