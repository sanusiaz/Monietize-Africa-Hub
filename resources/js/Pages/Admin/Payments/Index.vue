<script setup>
import { Head, router } from '@inertiajs/vue3';
import { RefreshCw } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatCard from '../../../Components/StatCard.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import { CircleCheck, Clock3, TriangleAlert } from 'lucide-vue-next';

defineOptions({ layout: AdminLayout });
const props = defineProps({ payments: Object, transactions: Array, totals: Object, filters: Object });
const { money, datetime, label } = useFormat();

const status = ref(props.filters.status ?? '');
watch(status, (v) => router.get('/admin/payments', { status: v || undefined }, { preserveState: true, replace: true }));
const verify = (p) => router.post(`/admin/payments/${p.id}/verify`, {}, { preserveScroll: true });
</script>

<template>
    <Head title="Wallet & Payments" />
    <PageHeader eyebrow="Finance" title="Wallet & Payments" subtitle="Paystack top-ups and the wallet ledger. Successful payments credit wallets automatically.">
        <select v-model="status" class="input !w-44"><option value="">All payments</option><option v-for="s in ['success', 'pending', 'failed', 'abandoned']" :key="s" :value="s">{{ label(s) }}</option></select>
    </PageHeader>

    <div class="mb-5 grid gap-5 sm:grid-cols-3">
        <StatCard label="Collected" :value="money(totals.success, true)" hint="successful payments" :icon="CircleCheck" />
        <StatCard label="Pending" :value="money(totals.pending, true)" hint="awaiting confirmation" :icon="Clock3" />
        <StatCard label="Failed / abandoned" :value="String(totals.failed)" hint="payments" :icon="TriangleAlert" />
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-fg/[0.06]"><tr><th class="th">Reference</th><th class="th">Customer</th><th class="th">Amount</th><th class="th">Channel</th><th class="th">Status</th><th class="th" /></tr></thead>
                    <tbody class="divide-y divide-fg/[0.05]">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-fg/[0.02]">
                            <td class="td"><p class="font-mono text-xs">{{ p.reference }}</p><p class="text-xs text-mist">{{ datetime(p.created_at) }}</p></td>
                            <td class="td text-mist">{{ p.user?.name }}</td>
                            <td class="td font-medium text-gold-300">{{ money(p.amount) }}</td>
                            <td class="td text-mist">{{ p.channel ? label(p.channel) : '—' }}</td>
                            <td class="td"><StatusBadge :status="p.status" /></td>
                            <td class="td text-right"><button v-if="p.status !== 'success'" class="btn-ghost !p-2" title="Re-verify with Paystack" @click="verify(p)"><RefreshCw class="size-4" /></button></td>
                        </tr>
                        <tr v-if="!payments.data.length"><td colspan="6" class="td py-12 text-center text-mist">No payments found.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :meta="payments" />
        </div>

        <div class="card overflow-hidden">
            <div class="px-6 pb-3 pt-6"><p class="eyebrow">Ledger</p><h3 class="display text-2xl">Latest wallet activity</h3></div>
            <ul class="divide-y divide-fg/[0.05]">
                <li v-for="t in transactions" :key="t.id" class="flex items-center justify-between px-6 py-3.5">
                    <div><p class="text-sm">{{ t.user?.name }}</p><p class="text-xs text-mist">{{ label(t.source) }} · {{ datetime(t.created_at) }}</p></div>
                    <p class="text-sm font-medium" :class="t.type === 'credit' ? 'text-emerald-300' : 'text-rose-300'">{{ t.type === 'credit' ? '+' : '−' }}{{ money(t.amount) }}</p>
                </li>
            </ul>
        </div>
    </div>
</template>
