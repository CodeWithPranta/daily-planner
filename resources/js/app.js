import Chart from 'chart.js/auto';

// Make Chart available globally for inline scripts
window.Chart = Chart;

// Analytics chart initialization
window.__chartInstances = {};

window.initAnalyticsCharts = function() {
    // Destroy existing chart instances to prevent "canvas already in use" errors
    Object.values(window.__chartInstances).forEach(function(c) { if (c) c.destroy(); });
    window.__chartInstances = {};

    var dataEl = document.getElementById('analytics-data');
    if (!dataEl) return;

    var raw = dataEl.textContent;
    var data;
    try {
        data = JSON.parse(raw);
    } catch (e) {
        console.error('Analytics JSON parse error:', e.message);
        console.error('Raw content (first 200 chars):', raw.substring(0, 200));
        return;
    }

    if (typeof Chart === 'undefined') return;

    var isDark = document.documentElement.classList.contains('dark');

    // Score trend line chart
    var trendEl = document.getElementById('scoreTrendChart');
    if (trendEl && data.scoreTrend.length > 0) {
        window.__chartInstances.scoreTrend = new Chart(trendEl, {
            type: 'line',
            data: {
                labels: data.scoreTrend.map(function(d) { return d.label; }),
                datasets: [{
                    label: 'Score',
                    data: data.scoreTrend.map(function(d) { return d.score; }),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(128,128,128,0.1)' } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // Category distribution doughnut
    var catEl = document.getElementById('categoryChart');
    if (catEl && data.categoryDistribution.length > 0) {
        window.__chartInstances.doughnut = new Chart(catEl, {
            type: 'doughnut',
            data: {
                labels: data.categoryDistribution.map(function(c) { return c.label; }),
                datasets: [{
                    data: data.categoryDistribution.map(function(c) { return c.value; }),
                    backgroundColor: data.categoryDistribution.map(function(c) { return c.color || '#71717a'; }),
                    borderWidth: 2,
                    borderColor: isDark ? '#18181b' : '#fafafa',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 10, font: { size: 11 } }
                    }
                }
            }
        });
    }

    // Time by category stacked bar
    var weeklyEl = document.getElementById('weeklyCategoryChart');
    if (weeklyEl && data.weeklyTimeByCategory.datasets && data.weeklyTimeByCategory.datasets.length > 0) {
        window.__chartInstances.stackedBar = new Chart(weeklyEl, {
            type: 'bar',
            data: {
                labels: data.weeklyTimeByCategory.labels,
                datasets: data.weeklyTimeByCategory.datasets.map(function(ds) {
                    return {
                        label: ds.label,
                        data: ds.data,
                        backgroundColor: ds.color || '#71717a',
                        borderRadius: 4,
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(128,128,128,0.1)' } }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 10, font: { size: 11 } }
                    }
                }
            }
        });
    }
};

function initWhenReady() {
    if (typeof Chart !== 'undefined') {
        window.initAnalyticsCharts();
    } else {
        var checkInterval = setInterval(function() {
            if (typeof Chart !== 'undefined') {
                clearInterval(checkInterval);
                window.initAnalyticsCharts();
            }
        }, 100);
        setTimeout(function() { clearInterval(checkInterval); }, 5000);
    }
}

// Re-init charts after SPA navigation (wire:navigate)
document.addEventListener('livewire:navigated', function() {
    if (document.getElementById('analytics-data')) {
        setTimeout(initWhenReady, 50);
    }
});

// Re-init charts after Livewire component re-render (range change)
document.addEventListener('analytics-range-changed', function() {
    setTimeout(initWhenReady, 50);
});

// Initial load
if (document.getElementById('analytics-data')) {
    initWhenReady();
}

// Register service worker for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}