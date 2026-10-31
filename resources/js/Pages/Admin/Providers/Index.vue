<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Download, Network, Pencil, Plus, Trash2, Wallet } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ providers: Array, categories: Array });
const { number } = useFormat();

const show = ref(false);
const editing = ref(null);
const form = useForm({ name: '', api_url: '', api_key: '', is_active: true });

const openNew = () => { editing.value = null; form.defaults({ name: '', api_url: '', api_key: '', is_active: true }).reset(); show.value = true; };
const openEdit = (p) => { editing.value = p; form.defaults({ name: p.name, api_url: p.api_url, api_key: '', is_active: p.is_active }).reset(); show.value = true; };
const save = () => {
    const opts = { preserveScroll: true, onSuccess: () => (show.value = false) };
    editing.value ? form.put(`/admin/providers/${editing.value.id}`, opts) : form.post('/admin/providers', opts);
};
const remove = (p) => confirm(`Remove "${p.name}"?`) && router.delete(`/admin/providers/${p.id}`, { preserveScroll: true });

// supplier balance
const balances = reactive({});
const getJson = async (url) => {
    const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    const body = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(body.error ?? 'Request failed.');
    return body;
};
const checkBalance = async (p) => {
    balances[p.id] = { loading: true };
    try {
        const b = await getJson(`/admin/providers/${p.id}/balance`);
        balances[p.id] = { text: `${number(b.balance)} ${b.currency ?? ''}`.trim() };
    } catch (e) {
        balances[p.id] = { error: e.message };
    }
};

// import
const importing = ref(null);
const catalogue = ref([]);
const loading = ref(false);
const loadError = ref('');
const picked = ref(new Set());
const filter = ref('');
const importForm = useForm({ category_id: '', markup: 30, services: [] });

const openImport = async (p) => {
    importing.value = p;
    catalogue.value = [];
    picked.value = new Set();
    filter.value = '';
    loadError.value = '';
    importForm.category_id = props.categories[0]?.id ?? '';
    loading.value = true;
    try { catalogue.value = await getJson(`/admin/providers/${p.id}/catalogue`); } catch (e) { loadError.value = e.message; }
    loading.value = false;
};
const visible = computed(() => catalogue.value.filter((s) => `${s.id} ${s.name} ${s.category}`.toLowerCase().includes(filter.value.toLowerCase())).slice(0, 200));
const toggle = (id) => { const s = new Set(picked.value); s.has(id) ? s.delete(id) : s.add(id); picked.value = s; };
const toggleAll = () => { const s = new Set(picked.value); const all = visible.value.every((v) => s.has(v.id)); visible.value.forEach((v) => (all ? s.delete(v.id) : s.add(v.id))); picked.value = s; };
const runImport = () => {
    importForm.services = [...picked.value];
    importForm.post(`/admin/providers/${importing.value.id}/import`, { preserveScroll: true, onSuccess: () => (importing.value = null) });
};
</script>

<template>
    <Head title="Suppliers" />
    <PageHeader eyebrow="Fulfilment network" title="Suppliers" subtitle="Connect any standard SMM panel API. Paid orders are sent automatically and their status is kept in sync.">
        <button class="btn-gold" @click="openNew"><Plus class="size-4" /> Add supplier</button>
    </PageHeader>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div v-for="p in providers" :key="p.id" class="card p-5">
            <div class="flex items-start justify-between">
                <span class="grid size-10 place-items-center rounded-xl border border-gold-500/20 bg-gold-500/10 text-gold-400"><Network class="size-5" /></span>
                <StatusBadge :status="p.is_active ? 'active' : 'suspended'" />
            </div>
            <h3 class="display mt-4 text-2xl">{{ p.name }}</h3>
            <p class="mt-1 truncate text-xs text-mist">{{ p.api_url }}</p>
            <p class="mt-3 text-sm text-mist">{{ p.services_count }} linked services</p>

            <button class="mt-3 flex w-full items-center justify-between rounded-xl border border-fg/[0.07] bg-ink-900/60 px-3.5 py-2.5 text-left text-sm transition hover:border-gold-500/30" @click="checkBalance(p)">
                <span class="flex items-center gap-2 text-mist"><Wallet class="size-4" /> Supplier balance</span>
                <span v-if="balances[p.id]?.loading" class="text-mist">Checking…</span>
                <span v-else-if="balances[p.id]?.error" class="max-w-[55%] truncate text-xs text-rose-300" :title="balances[p.id].error">{{ balances[p.id].error }}</span>
                <span v-else-if="balances[p.id]?.text" class="font-medium text-gold-300">{{ balances[p.id].text }}</span>
                <span v-else class="text-xs text-gold-400">Check</span>
            </button>

            <div class="mt-4 flex flex-wrap gap-2">
                <button class="btn-gold !px-3 !py-2" :disabled="!p.is_active" @click="openImport(p)"><Download class="size-4" /> Import services</button>
                <button class="btn-ghost !px-3 !py-2" @click="openEdit(p)"><Pencil class="size-4" /></button>
                <button class="btn-danger !px-3 !py-2" @click="remove(p)"><Trash2 class="size-4" /></button>
            </div>
        </div>
        <div v-if="!providers.length" class="card col-span-full p-12 text-center text-mist">No suppliers yet. Services without a supplier are fulfilled manually from the Orders page.</div>
    </div>

    <Modal :show="show" :title="editing ? 'Edit supplier' : 'Add supplier'" @close="show = false">
        <form class="space-y-4" @submit.prevent="save">
            <div><label class="label">Name</label><input v-model="form.name" class="input" required /></div>
            <div><label class="label">API URL</label><input v-model="form.api_url" type="url" class="input" placeholder="https://supplier.com/api/v2" required /><p v-if="form.errors.api_url" class="mt-1 text-xs text-rose-300">{{ form.errors.api_url }}</p></div>
            <div><label class="label">API key {{ editing ? '(leave blank to keep current)' : '' }}</label><input v-model="form.api_key" type="password" class="input" :required="!editing" autocomplete="off" /></div>
            <div><label class="label">Status</label><select v-model="form.is_active" class="input"><option :value="true">Active</option><option :value="false">Disabled</option></select></div>
            <div class="flex justify-end gap-3"><button type="button" class="btn-ghost" @click="show = false">Cancel</button><button class="btn-gold" :disabled="form.processing">Save</button></div>
        </form>
    </Modal>

    <Modal :show="!!importing" wide :title="`Import from ${importing?.name}`" @close="importing = null">
        <p v-if="loading" class="py-10 text-center text-sm text-mist">Loading supplier catalogue…</p>
        <p v-else-if="loadError" class="rounded-lg border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-200">{{ loadError }}</p>
        <form v-else class="space-y-4" @submit.prevent="runImport">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Add to category</label><select v-model="importForm.category_id" class="input" required><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                    <p v-if="!categories.length" class="mt-1 text-xs text-gold-300">Create a category first.</p></div>
                <div><label class="label">Markup on supplier rate (%)</label><input v-model="importForm.markup" type="number" min="0" max="1000" step="0.1" class="input" required /></div>
            </div>
            <div class="flex items-center gap-3">
                <input v-model="filter" class="input" placeholder="Filter by name, category or ID" />
                <button type="button" class="btn-ghost shrink-0" @click="toggleAll">Toggle shown</button>
            </div>
            <ul class="max-h-72 space-y-1.5 overflow-y-auto pr-1">
                <li v-for="s in visible" :key="s.id">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-fg/[0.06] px-3.5 py-2.5 text-sm transition hover:border-gold-500/30" :class="picked.has(s.id) ? 'bg-gold-500/10' : ''">
                        <input type="checkbox" :checked="picked.has(s.id)" class="size-4 accent-[#c9a45c]" @change="toggle(s.id)" />
                        <span class="min-w-0 flex-1"><span class="block truncate">{{ s.name }}</span><span class="text-xs text-mist">#{{ s.id }} · {{ s.category }} · {{ number(s.min) }}–{{ number(s.max) }}</span></span>
                        <span v-if="s.imported" class="text-[10px] uppercase tracking-wider text-mist">Imported</span>
                        <span class="text-gold-300">{{ s.rate }}</span>
                    </label>
                </li>
                <li v-if="!visible.length" class="py-8 text-center text-sm text-mist">No services match.</li>
            </ul>
            <p v-if="importForm.errors.services || importForm.errors.markup" class="text-xs text-rose-300">{{ importForm.errors.services ?? importForm.errors.markup }}</p>
            <div class="flex items-center justify-between">
                <span class="text-xs text-mist">{{ picked.size }} selected · re-importing updates price and limits</span>
                <div class="flex gap-3"><button type="button" class="btn-ghost" @click="importing = null">Cancel</button><button class="btn-gold" :disabled="importForm.processing || !picked.size">Import</button></div>
            </div>
        </form>
    </Modal>
</template>
