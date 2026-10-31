<script setup>
import { computed } from 'vue';
import { Chart as ChartJS, registerables } from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { theme } from '../composables/theme';

ChartJS.register(...registerables);
ChartJS.defaults.font.family = "'Inter Variable', sans-serif";
ChartJS.defaults.font.size = 11;

const props = defineProps({
    type: { type: String, default: 'line' },
    data: { type: Object, required: true },
    height: { type: Number, default: 260 },
    legend: { type: Boolean, default: false },
    options: { type: Object, default: () => ({}) },
});

const component = computed(() => ({ line: Line, bar: Bar, doughnut: Doughnut })[props.type]);

// chart.js draws on a canvas, so it cannot read CSS variables: pick the palette from the active theme
const palettes = {
    dark: { text: '#9a9aab', grid: 'rgba(255,255,255,0.05)', tipBg: '#0b0b10', tipBorder: 'rgba(201,164,92,0.4)', tipTitle: '#f1e2bd', tipBody: '#f5efe3' },
    light: { text: '#66657a', grid: 'rgba(28,27,36,0.07)', tipBg: '#ffffff', tipBorder: 'rgba(154,118,48,0.5)', tipTitle: '#6b4f12', tipBody: '#1c1b24' },
};

const merged = computed(() => {
    const p = palettes[theme.value];
    const round = props.type === 'doughnut';
    const base = {
        responsive: true,
        maintainAspectRatio: false,
        color: p.text,
        borderColor: p.grid,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                display: props.legend,
                position: round ? 'bottom' : 'top',
                align: round ? 'center' : 'end',
                labels: { usePointStyle: true, boxWidth: 6, boxHeight: 6, padding: 16, color: p.text },
            },
            tooltip: {
                backgroundColor: p.tipBg,
                borderColor: p.tipBorder,
                borderWidth: 1,
                titleColor: p.tipTitle,
                bodyColor: p.tipBody,
                padding: 12,
                cornerRadius: 10,
                boxPadding: 4,
            },
        },
        scales: round
            ? {}
            : {
                  x: { grid: { display: false, color: p.grid }, ticks: { maxTicksLimit: 8, color: p.text }, border: { display: false } },
                  y: { beginAtZero: true, grid: { color: p.grid }, border: { display: false }, ticks: { maxTicksLimit: 5, color: p.text } },
              },
        ...(round ? { cutout: '72%' } : {}),
    };
    const scales = { ...base.scales };
    for (const [axis, extra] of Object.entries(props.options.scales ?? {})) {
        scales[axis] = { ...(scales[axis] ?? {}), ...extra, ticks: { ...(scales[axis]?.ticks ?? {}), ...(extra.ticks ?? {}) }, grid: { ...(scales[axis]?.grid ?? {}), ...(extra.grid ?? {}) } };
    }
    return { ...base, ...props.options, plugins: { ...base.plugins, ...(props.options.plugins ?? {}) }, scales };
});
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <component :is="component" :key="theme" :data="data" :options="merged" />
    </div>
</template>
