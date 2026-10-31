<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useFormat } from '../../composables/format';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';

defineOptions({ layout: CustomerLayout });
const props = defineProps({ orders: Object, statuses: Array, filters: Object });
const { money, number, datetime, label } = useFormat();

const status = ref(props.filters.status ?? '');
watch(status, (v) => router.get('/my-orders', { status: v || undefined }, { preserveState: true, replace: true }));
</script>

<template>
    <Head title="My orders" />
    <PageHeader eyebrow="History" title="My orders" subtitle="Track every order from placement to delivery.">
        <select v-model="status" class="input !w-44"><option value="">All statuses</option><option v-for="s in statuses" :key="s" :value="s">{{ label(s) }}</option></select>
    </PageHeader>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-fg/[0.06]"><tr><th class="th">Order</th><th class="th">Link</th><th class="th">Qty</th><th class="th">Charge</th><th class="th">Remains</th><th class="th">Status</th></tr></thead>
                <tbody class="divide-y divide-fg/[0.05]">
                    <tr v-for="o in orders.data" :key="o.id" class="hover:bg-fg/[0.02]">
                        <td class="td"><p class="font-medium">{{ o.service?.name }}</p><p class="text-xs text-mist">#{{ o.id }} · {{ datetime(o.created_at) }}</p></td>
                        <td class="td max-w-[220px] truncate text-mist">{{ o.link }}</td>
                        <td class="td">{{ number(o.quantity) }}</td>
                        <td class="td font-medium text-gold-300">{{ money(o.charge) }}</td>
                        <td class="td text-mist">{{ o.remains ?? '—' }}</td>
                        <td class="td"><StatusBadge :status="o.status" /></td>
                    </tr>
                    <tr v-if="!orders.data.length"><td colspan="6" class="td py-12 text-center text-mist">No orders yet.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :meta="orders" />
    </div>
</template>
