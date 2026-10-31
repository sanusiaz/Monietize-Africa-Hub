<script setup>
import { Head } from '@inertiajs/vue3';
import { CircleDollarSign, Clock3, ShoppingBag, UserPlus, Users, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import Chart from '../../Components/Chart.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useFormat } from '../../composables/format';
import AdminLayout from '../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ kpis: Object, charts: Object, recentOrders: Array, recentPayments: Array });
const { money, number, label, datetime } = useFormat();

const GOLD = '#c9a45c';
const palette = ['#c9a45c', '#7c8cf8', '#34d399', '#f472b6', '#38bdf8', '#a78bfa', '#fb923c'];
const statusColors = {
    completed: '#34d399', in_progress: '#38bdf8', processing: '#7c8cf8', pending: '#fbbf24',
    partial: '#a78bfa', canceled: '#fb7185', refunded: '#f43f5e',
};

const gradient = (color) => (ctx) => {
    const { chart } = ctx;
    if (!chart.chartArea) return color + '22';
    const g = chart.ctx.createLinearGradient(0, chart.chartArea.top, 0, chart.chartArea.bottom);
    g.addColorStop(0, color + '55');
    g.addColorStop(1, color + '00');
    return g;
};

const area = (labelText, data, color) => ({
    label: labelText, data, borderColor: color, backgroundColor: gradient(color), fill: true,
    tension: 0.38, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: color,
});

const revenue = computed(() => ({ labels: props.charts.labels, datasets: [area('Revenue', props.charts.revenue, GOLD)] }));
const orders = computed(() => ({
    labels: props.charts.labels,
    datasets: [{ label: 'Orders', data: props.charts.orders, backgroundColor: '#7c8cf8', borderRadius: 6, borderSkipped: false, maxBarThickness: 14 }],
}));
const walletFlow = computed(() => ({
    labels: props.charts.labels,
    datasets: [area('Funded', props.charts.wallet_in, '#34d399'), area('Spent', props.charts.wallet_out, '#f472b6')],
}));
const signups = computed(() => ({ labels: props.charts.labels, datasets: [area('New customers', props.charts.signups, '#38bdf8')] }));

const statusKeys = computed(() => Object.keys(props.charts.by_status));
const byStatus = computed(() => ({
    labels: statusKeys.value.map(label),
    datasets: [{ data: statusKeys.value.map((k) => props.charts.by_status[k]), backgroundColor: statusKeys.value.map((k) => statusColors[k] ?? '#999'), borderWidth: 0, spacing: 3, borderRadius: 4 }],
}));
const byPlatform = computed(() => ({
    labels: props.charts.by_platform.map((p) => label(p.platform)),
    datasets: [{ data: props.charts.by_platform.map((p) => Number(p.revenue)), backgroundColor: palette, borderWidth: 0, spacing: 3, borderRadius: 4 }],
}));
const channels = computed(() => ({
    labels: props.charts.channels.map((c) => label(c.channel)),
    datasets: [{ data: props.charts.channels.map((c) => c.total), backgroundColor: palette, borderWidth: 0, spacing: 3, borderRadius: 4 }],
}));
const topServices = computed(() => ({
    labels: props.charts.top_services.map((s) => s.name),
    datasets: [{ label: 'Orders', data: props.charts.top_services.map((s) => s.total), backgroundColor: GOLD, borderRadius: 8, borderSkipped: false, barThickness: 16 }],
}));

const moneyTicks = { y: { ticks: { callback: (v) => money(v, true), maxTicksLimit: 5 } } };
const moneyTip = { callbacks: { label: (c) => ` ${c.dataset.label}: ${money(c.parsed.y)}` } };
const donutMoney = { callbacks: { label: (c) => ` ${money(c.parsed)}` } };
const hbar = { indexAxis: 'y', scales: { x: { grid: { display: false } }, y: { grid: { display: false } } } };
</script>

<template>
    <Head title="Overview" />
    <PageHeader eyebrow="Overview" title="Dashboard" subtitle="Revenue, orders and wallet activity at a glance." />

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <StatCard label="Total revenue" :value="money(kpis.revenue, true)" :change="kpis.revenue_change" hint="vs last month" :icon="CircleDollarSign" />
        <StatCard label="This month" :value="money(kpis.month_revenue, true)" hint="wallet funding received" :icon="Wallet" />
        <StatCard label="Customers" :value="number(kpis.users)" :hint="`+${kpis.new_users} in 30 days`" :icon="Users" />
        <StatCard label="Orders" :value="number(kpis.orders)" :hint="`${money(kpis.order_value, true)} order value`" :icon="ShoppingBag" />
        <StatCard label="Awaiting fulfilment" :value="number(kpis.pending_orders)" hint="pending & in progress" :icon="Clock3" />
        <StatCard label="Wallet liability" :value="money(kpis.wallet_liability, true)" hint="held in customer wallets" :icon="UserPlus" />
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <div class="card p-6 xl:col-span-2">
            <div class="mb-5"><p class="eyebrow">Last 30 days</p><h3 class="display text-2xl">Revenue</h3></div>
            <Chart type="line" :data="revenue" :height="300" :options="{ scales: moneyTicks, plugins: { tooltip: moneyTip } }" />
        </div>
        <div class="card p-6">
            <div class="mb-5"><p class="eyebrow">Fulfilment</p><h3 class="display text-2xl">Order status</h3></div>
            <Chart type="doughnut" :data="byStatus" :height="300" legend />
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
        <div class="card p-6">
            <div class="mb-5"><p class="eyebrow">Daily volume</p><h3 class="display text-2xl">Orders placed</h3></div>
            <Chart type="bar" :data="orders" />
        </div>
        <div class="card p-6">
            <div class="mb-5"><p class="eyebrow">Money in / out</p><h3 class="display text-2xl">Wallet flow</h3></div>
            <Chart type="line" :data="walletFlow" legend :options="{ scales: moneyTicks, plugins: { tooltip: moneyTip } }" />
        </div>
        <div class="card p-6 lg:col-span-2 xl:col-span-1">
            <div class="mb-5"><p class="eyebrow">Acquisition</p><h3 class="display text-2xl">New customers</h3></div>
            <Chart type="line" :data="signups" :options="{ scales: { y: { ticks: { precision: 0 } } } }" />
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div class="card p-6">
            <div class="mb-5"><p class="eyebrow">Order value</p><h3 class="display text-2xl">Revenue by platform</h3></div>
            <Chart type="doughnut" :data="byPlatform" legend :options="{ plugins: { tooltip: donutMoney } }" />
        </div>
        <div class="card p-6">
            <div class="mb-5"><p class="eyebrow">Best sellers</p><h3 class="display text-2xl">Top services</h3></div>
            <Chart type="bar" :data="topServices" :options="hbar" />
        </div>
        <div class="card p-6">
            <div class="mb-5"><p class="eyebrow">Paystack</p><h3 class="display text-2xl">Payment channels</h3></div>
            <Chart type="doughnut" :data="channels" legend />
        </div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="px-6 pb-3 pt-6"><p class="eyebrow">Latest</p><h3 class="display text-2xl">Recent orders</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-fg/[0.05]">
                        <tr v-for="o in recentOrders" :key="o.id" class="hover:bg-fg/[0.02]">
                            <td class="td"><p class="font-medium">{{ o.service?.name }}</p><p class="text-xs text-mist">#{{ o.id }} · {{ o.user?.name }}</p></td>
                            <td class="td text-right text-gold-300">{{ money(o.charge) }}</td>
                            <td class="td text-right"><StatusBadge :status="o.status" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card overflow-hidden">
            <div class="px-6 pb-3 pt-6"><p class="eyebrow">Latest</p><h3 class="display text-2xl">Recent wallet funding</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-fg/[0.05]">
                        <tr v-for="p in recentPayments" :key="p.id" class="hover:bg-fg/[0.02]">
                            <td class="td"><p class="font-medium">{{ p.user?.name }}</p><p class="text-xs text-mist">{{ datetime(p.created_at) }}</p></td>
                            <td class="td text-right text-gold-300">{{ money(p.amount) }}</td>
                            <td class="td text-right"><StatusBadge :status="p.status" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
