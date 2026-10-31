<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
defineProps({ categories: Array });
const { label } = useFormat();

const platforms = ['instagram', 'facebook', 'tiktok', 'youtube', 'twitter', 'telegram', 'spotify', 'other'];
const show = ref(false);
const editing = ref(null);
const form = useForm({ name: '', platform: 'instagram', sort_order: 0, is_active: true });

const openNew = () => { editing.value = null; form.defaults({ name: '', platform: 'instagram', sort_order: 0, is_active: true }).reset(); show.value = true; };
const openEdit = (c) => { editing.value = c; form.defaults({ name: c.name, platform: c.platform, sort_order: c.sort_order, is_active: c.is_active }).reset(); show.value = true; };
const save = () => {
    const opts = { preserveScroll: true, onSuccess: () => (show.value = false) };
    editing.value ? form.put(`/admin/categories/${editing.value.id}`, opts) : form.post('/admin/categories', opts);
};
const remove = (c) => confirm(`Delete "${c.name}"?`) && router.delete(`/admin/categories/${c.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="Categories" />
    <PageHeader eyebrow="Catalogue" title="Categories" subtitle="Group services by platform and control what customers see.">
        <button class="btn-gold" @click="openNew"><Plus class="size-4" /> New category</button>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="c in categories" :key="c.id" class="card p-5">
            <div class="flex items-start justify-between">
                <div><p class="eyebrow">{{ label(c.platform) }}</p><h3 class="display mt-1 text-2xl">{{ c.name }}</h3></div>
                <StatusBadge :status="c.is_active ? 'active' : 'suspended'" />
            </div>
            <p class="mt-3 text-sm text-mist">{{ c.services_count }} services · order {{ c.sort_order }}</p>
            <div class="mt-4 flex gap-2">
                <button class="btn-ghost !px-3 !py-2" @click="openEdit(c)"><Pencil class="size-4" /> Edit</button>
                <button class="btn-danger !px-3 !py-2" @click="remove(c)"><Trash2 class="size-4" /></button>
            </div>
        </div>
    </div>

    <Modal :show="show" :title="editing ? 'Edit category' : 'New category'" @close="show = false">
        <form class="space-y-4" @submit.prevent="save">
            <div><label class="label">Name</label><input v-model="form.name" class="input" required /><p v-if="form.errors.name" class="mt-1 text-xs text-rose-300">{{ form.errors.name }}</p></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="label">Platform</label><select v-model="form.platform" class="input"><option v-for="p in platforms" :key="p" :value="p">{{ label(p) }}</option></select></div>
                <div><label class="label">Sort order</label><input v-model="form.sort_order" type="number" min="0" class="input" /></div>
            </div>
            <div><label class="label">Visibility</label><select v-model="form.is_active" class="input"><option :value="true">Active</option><option :value="false">Hidden</option></select></div>
            <div class="flex justify-end gap-3"><button type="button" class="btn-ghost" @click="show = false">Cancel</button><button class="btn-gold" :disabled="form.processing">Save</button></div>
        </form>
    </Modal>
</template>
