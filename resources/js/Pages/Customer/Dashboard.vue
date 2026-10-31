<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Clock3, Receipt, Wallet } from 'lucide-vue-next';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useFormat } from '../../composables/format';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';

defineOptions({ layout: CustomerLayout });
defineProps({ stats: Object, recent: Array });
const { money, datetime } = useFormat();
const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Dashboard" />
    <PageHeader eyebrow="Your account" :title="`Welcome, ${user.name.split(' ')[0]}`" subtitle="Add money to your wallet and place an order in a few clicks.">
        <Link href="/new-order" class="btn-gold">Place new order</Link>
    </PageHeader>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Wallet" :value="money(stats.balance, true)" hint="available balance" :icon="Wallet" />
        <StatCard label="Total orders" :value="String(stats.orders)" :hint="`${money(stats.spent, true)} spent`" :icon="Receipt" />
        <StatCard label="In progress" :value="String(stats.active)" hint="being processed" :icon="Clock3" />
        <StatCard label="Completed" :value="String(stats.completed)" hint="delivered orders" :icon="CheckCircle2" />
    </div>

    <div class="card mt-5 overflow-hidden">
        <div class="flex items-center justify-between px-6 pb-3 pt-6">
            <div><p class="eyebrow">Latest</p><h3 class="display text-2xl">Recent orders</h3></div>
            <Link href="/my-orders" class="text-sm text-gold-400 hover:text-gold-300">View all</Link>
        </div>
        <ul class="divide-y divide-fg/[0.05]">
            <li v-for="o in recent" :key="o.id" class="flex flex-wrap items-center justify-between gap-3 px-6 py-3.5">
                <div class="min-w-0"><p class="font-medium">{{ o.service?.name }}</p><p class="max-w-xs truncate text-xs text-mist">#{{ o.id }} · {{ datetime(o.created_at) }}</p></div>
                <div class="flex items-center gap-4"><span class="text-gold-300">{{ money(o.charge) }}</span><StatusBadge :status="o.status" /></div>
            </li>
            <li v-if="!recent.length" class="px-6 py-12 text-center text-sm text-mist">No orders yet. <Link href="/new-order" class="text-gold-400">place your first one</Link>.</li>
        </ul>
    </div>
</template>
