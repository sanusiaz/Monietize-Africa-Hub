<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ services: Object, categories: Array, providers: Array, filters: Object });
const { money, number, label } = useFormat();

const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');
const go = () => router.get('/admin/services', { search: search.value || undefined, category: category.value || undefined }, { preserveState: true, replace: true });
let timer;
watch(search, () => { clearTimeout(timer); timer = setTimeout(go, 300); });
watch(category, go);

const blank = () => ({ category_id: props.categories[0]?.id ?? '', provider_id: '', provider_service_id: '', name: '', description: '', rate: '', min: 50, max: 10000, drip_feed: false, refill: false, is_active: true });
const show = ref(false);
const editing = ref(null);
const form = useForm(blank());

const openNew = () => { editing.value = null; form.defaults(blank()).reset(); show.value = true; };
const openEdit = (s) => {
    editing.value = s;
    form.defaults({ category_id: s.category_id, provider_id: s.provider_id ?? '', provider_service_id: s.provider_service_id ?? '', name: s.name, description: s.description ?? '', rate: s.rate, min: s.min, max: s.max, drip_feed: s.drip_feed, refill: s.refill, is_active: s.is_active }).reset();
    show.value = true;
};
const save = () => {
    form.transform((d) => ({ ...d, provider_id: d.provider_id || null }));
    const opts = { preserveScroll: true, onSuccess: () => (show.value = false) };
    editing.value ? form.put(`/admin/services/${editing.value.id}`, opts) : form.post('/admin/services', opts);
};
const remove = (s) => confirm(`Delete "${s.name}"?`) && router.delete(`/admin/services/${s.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="Services" />
    <PageHeader eyebrow="Catalogue" title="Services" subtitle="Rates are priced per 1,000 units. Link a supplier for automatic fulfilment.">
        <select v-model="category" class="input !w-52"><option value="">All categories</option><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select>
        <div class="relative w-56"><Search class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-mist" /><input v-model="search" class="input !pl-10" placeholder="Search services" /></div>
        <button class="btn-gold" @click="openNew"><Plus class="size-4" /> New service</button>
    </PageHeader>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-fg/[0.06]"><tr><th class="th">Service</th><th class="th">Category</th><th class="th">Rate / 1k</th><th class="th">Min – Max</th><th class="th">Supplier</th><th class="th">Status</th><th class="th" /></tr></thead>
                <tbody class="divide-y divide-fg/[0.05]">
                    <tr v-for="s in services.data" :key="s.id" class="hover:bg-fg/[0.02]">
                        <td class="td"><p class="font-medium">{{ s.name }}</p><p class="text-xs text-mist"><span v-if="s.refill">Refill · </span><span v-if="s.drip_feed">Drip-feed</span></p></td>
                        <td class="td text-mist">{{ s.category?.name }}</td>
                        <td class="td font-medium text-gold-300">{{ money(s.rate) }}</td>
                        <td class="td text-mist">{{ number(s.min) }} – {{ number(s.max) }}</td>
                        <td class="td text-mist">{{ s.provider?.name ?? 'Manual' }}</td>
                        <td class="td"><StatusBadge :status="s.is_active ? 'active' : 'suspended'" /></td>
                        <td class="td"><div class="flex justify-end gap-2"><button class="btn-ghost !p-2" @click="openEdit(s)"><Pencil class="size-4" /></button><button class="btn-danger !p-2" @click="remove(s)"><Trash2 class="size-4" /></button></div></td>
                    </tr>
                    <tr v-if="!services.data.length"><td colspan="7" class="td py-12 text-center text-mist">No services yet.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :meta="services" />
    </div>

    <Modal :show="show" wide :title="editing ? 'Edit service' : 'New service'" @close="show = false">
        <form class="space-y-4" @submit.prevent="save">
            <div><label class="label">Name</label><input v-model="form.name" class="input" required /><p v-if="form.errors.name" class="mt-1 text-xs text-rose-300">{{ form.errors.name }}</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Category</label><select v-model="form.category_id" class="input" required><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                <div><label class="label">Rate per 1,000</label><input v-model="form.rate" type="number" step="0.01" min="0" class="input" required /></div>
                <div><label class="label">Minimum</label><input v-model="form.min" type="number" min="1" class="input" required /></div>
                <div><label class="label">Maximum</label><input v-model="form.max" type="number" min="1" class="input" required /><p v-if="form.errors.max" class="mt-1 text-xs text-rose-300">{{ form.errors.max }}</p></div>
                <div><label class="label">Supplier</label><select v-model="form.provider_id" class="input"><option value="">Manual fulfilment</option><option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
                <div><label class="label">Supplier service ID</label><input v-model="form.provider_service_id" class="input" /></div>
            </div>
            <div><label class="label">Description</label><textarea v-model="form.description" rows="2" class="input" /></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="label">Drip-feed</label><select v-model="form.drip_feed" class="input"><option :value="false">Off</option><option :value="true">On</option></select></div>
                <div><label class="label">Auto-refill</label><select v-model="form.refill" class="input"><option :value="false">Off</option><option :value="true">On</option></select></div>
                <div><label class="label">Status</label><select v-model="form.is_active" class="input"><option :value="true">Active</option><option :value="false">Hidden</option></select></div>
            </div>
            <div class="flex justify-end gap-3"><button type="button" class="btn-ghost" @click="show = false">Cancel</button><button class="btn-gold" :disabled="form.processing">Save service</button></div>
        </form>
    </Modal>
</template>
