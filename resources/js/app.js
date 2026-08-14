import Chart from 'chart.js/auto';

// Make Chart available globally for inline scripts
window.Chart = Chart;

// Register service worker for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}