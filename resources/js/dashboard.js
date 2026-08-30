import {
    Chart,
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale,
    DoughnutController,
    BarController,
    Tooltip,
} from 'chart.js';

Chart.register(ArcElement, BarElement, CategoryScale, LinearScale, DoughnutController, BarController, Tooltip);

function readData(canvas) {
    return {
        labels: JSON.parse(canvas.dataset.labels || '[]'),
        values: JSON.parse(canvas.dataset.values || '[]'),
        colors: JSON.parse(canvas.dataset.colors || '[]'),
    };
}

function renderDoughnut(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const { labels, values, colors } = readData(canvas);
    if (!values.length) return;

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }],
        },
        options: {
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true },
            },
        },
    });
}

function renderBar(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const { labels, values, colors } = readData(canvas);
    if (!values.length) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: colors, borderRadius: 4, maxBarThickness: 22 }],
        },
        options: {
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#748ba4' },
                    grid: { color: 'rgba(255,255,255,.06)' },
                },
                y: {
                    ticks: { color: '#c9d8ee' },
                    grid: { display: false },
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', function () {
    renderDoughnut('statusChart');
    renderBar('ideaStatusChart');
    renderBar('ideaPriorityChart');
});
