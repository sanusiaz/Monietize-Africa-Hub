<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ orders: Object, statuses: Array, filters: Object });
const { money, number, datetime, label } = useFormat();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const go = () => router.get('/admin/orders', { search: search.value || undefined, status: status.value || undefined }, { preserveState: true, replace: true });
let timer;
watch(search, () => { clearTimeout(timer); timer = setTimeout(go, 300); });
watch(status, go);

const editing = ref(null);
const form = useForm({ status: '', start_count: null, remains: null, notes: '' });
const edit = (o) => {
    editing.value = o;
    form.defaults({ status: o.status, start_count: o.start_count, remains: o.remains, notes: o.notes ?? '' }).reset();
};
const canSend = computed(() => editing.value && !editing.value.provider_order_id && editing.value.status === 'pending' && editing.value.service?.provider_id && editing.value.service?.provider_service_id);
const sendToSupplier = () => router.post(`/admin/orders/${editing.value.id}/submit`, {}, { preserveScroll: true, onSuccess: () => (editing.value = null) });
const save = () => form.put(`/admin/orders/${editing.value.id}`, { preserveScroll: true, onSuccess: () => (editing.value = null) });
</script>

<template>
    <Head title="Orders" />
    <PageHeader eyebrow="Fulfilment" title="Orders" subtitle="Update progress; cancelling or refunding returns funds to the customer wallet automatically.">
        <select v-model="status" class="input !w-44"><option value="">All statuses</option><option v-for="s in statuses" :key="s" :value="s">{{ label(s) }}</option></select>
        <div class="relative w-60"><Search class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-mist" /><input v-model="search" class="input !pl-10" placeholder="Order # or link" /></div>
    </PageHeader>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-fg/[0.06]"><tr><th class="th">Order</th><th class="th">Customer</th><th class="th">Link</th><th class="th">Qty</th><th class="th">Charge</th><th class="th">Status</th><th class="th" /></tr></thead>
                <tbody class="divide-y divide-fg/[0.05]">
                    <tr v-for="o in orders.data" :key="o.id" class="hover:bg-fg/[0.02]">
                        <td class="td"><p class="font-medium">{{ o.service?.name }}</p><p class="text-xs text-mist">#{{ o.id }} · {{ datetime(o.created_at) }}</p></td>
                        <td class="td text-mist">{{ o.user?.name }}</td>
                        <td class="td max-w-[200px] truncate text-mist">{{ o.link }}</td>
                        <td class="td">{{ number(o.quantity) }}</td>
                        <td class="td font-medium text-gold-300">{{ money(o.charge) }}</td>
                        <td class="td"><StatusBadge :status="o.status" /></td>
                        <td class="td text-right"><button class="btn-ghost !p-2" @click="edit(o)"><Pencil class="size-4" /></button></td>
                    </tr>
                    <tr v-if="!orders.data.length"><td colspan="7" class="td py-12 text-center text-mist">No orders match.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :meta="orders" />
    </div>

    <Modal :show="!!editing" :title="`Order #${editing?.id}`" @close="editing = null">
        <form class="space-y-4" @submit.prevent="save">
            <div><label class="label">Status</label><select v-model="form.status" class="input"><option v-for="s in statuses" :key="s" :value="s">{{ label(s) }}</option></select></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="label">Start count</label><input v-model="form.start_count" type="number" min="0" class="input" /></div>
                <div><label class="label">Remains</label><input v-model="form.remains" type="number" min="0" class="input" /></div>
            </div>
            <div v-if="editing?.provider_order_id" class="rounded-lg border border-fg/[0.07] bg-ink-900/60 p-3 text-xs text-mist">Supplier order ID: <span class="font-mono text-ivory">{{ editing.provider_order_id }}</span>. Status syncs automatically every 5 minutes.</div>
            <div v-else-if="canSend" class="flex items-center justify-between gap-3 rounded-lg border border-gold-500/30 bg-gold-500/10 p-3 text-xs text-gold-200">
                <span>This order has not reached its supplier yet.</span>
                <button type="button" class="btn-gold !px-3 !py-1.5 !text-xs" @click="sendToSupplier">Send now</button>
            </div>
            <div><label class="label">Internal notes</label><textarea v-model="form.notes" rows="3" class="input" /></div>
            <p v-if="['canceled', 'refunded'].includes(form.status) && !['canceled', 'refunded'].includes(editing?.status)" class="rounded-lg border border-gold-500/30 bg-gold-500/10 p-3 text-xs text-gold-200">
                Saving will refund {{ money(editing?.charge) }} to the customer's wallet.
            </p>
            <div class="flex justify-end gap-3"><button type="button" class="btn-ghost" @click="editing = null">Cancel</button><button class="btn-gold" :disabled="form.processing">Save order</button></div>
        </form>
    </Modal>
</template>
