// Chart colors shared by the analytics dashboards. The categorical order was
// checked for colour-vision-deficiency separation on the white card surface;
// keep it, and fold extra series into "Other" rather than adding hues.
export const SERIES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4'];
export const OTHER = '#b4b2a9';
export const PREVIOUS = '#cbd5e1';
export const SURFACE = '#ffffff';
export const GRID = '#e2e8f0';
export const INK = '#334155';
export const MUTED = '#64748b';

// One-hue sequential ramp for magnitude (the time-of-week grid), light to dark.
export const RAMP = ['#cde2fb', '#9ec5f4', '#6da7ec', '#3987e5', '#256abf', '#184f95'];
export const EMPTY_CELL = '#f1f5f9';

export const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? false : { duration: 250 },
    plugins: {
        legend: {
            position: 'bottom',
            labels: { color: INK, usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, boxHeight: 10, padding: 16 },
        },
        tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 6,
            boxPadding: 4,
            usePointStyle: true,
        },
    },
};

export const valueAxis = {
    beginAtZero: true,
    grid: { color: GRID },
    border: { display: false },
    ticks: { color: MUTED, precision: 0 },
};

export const categoryAxis = {
    grid: { display: false },
    border: { color: GRID },
    ticks: { color: MUTED, autoSkip: true, maxRotation: 0 },
};

export const percent = (part, whole) => (whole > 0 ? Math.round((part / whole) * 100) : 0);

export const plural = (count, singular, pluralForm = `${singular}s`) => `${count.toLocaleString()} ${count === 1 ? singular : pluralForm}`;
