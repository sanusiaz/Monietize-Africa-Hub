<script setup>
import { Head, router } from '@inertiajs/vue3';
import { CheckCheck, Mail, MailOpen, Reply, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ messages: Object, filters: Object, unread: Number });
const { datetime } = useFormat();

const filter = ref(props.filters.filter ?? '');
const search = ref(props.filters.search ?? '');
const go = () => router.get('/admin/messages', { filter: filter.value || undefined, search: search.value || undefined }, { preserveState: true, replace: true });
let timer;
watch(search, () => { clearTimeout(timer); timer = setTimeout(go, 300); });
watch(filter, go);

const open = ref(null);
const view = (m) => {
    open.value = m;
    if (!m.read_at) router.put(`/admin/messages/${m.id}/read`, {}, { preserveScroll: true, preserveState: true, only: ['messages', 'unreadMessages', 'unread'] });
};
const toggle = (m) => router.put(`/admin/messages/${m.id}/read`, {}, { preserveScroll: true });
const remove = (m) => confirm('Delete this message?') && router.delete(`/admin/messages/${m.id}`, { preserveScroll: true, onSuccess: () => (open.value = null) });
const readAll = () => router.post('/admin/messages/read-all', {}, { preserveScroll: true });
</script>

<template>
    <Head title="Messages" />
    <PageHeader eyebrow="Inbox" title="Messages" :subtitle="`Contact-form messages from your website. ${unread} unread.`">
        <select v-model="filter" class="input !w-36"><option value="">All</option><option value="unread">Unread</option></select>
        <div class="relative w-56"><Search class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-mist" /><input v-model="search" class="input !pl-10" placeholder="Search messages" /></div>
        <button class="btn-ghost" :disabled="!unread" @click="readAll"><CheckCheck class="size-4" /> Mark all read</button>
    </PageHeader>

    <div class="card overflow-hidden">
        <ul class="divide-y divide-fg/[0.05]">
            <li v-for="m in messages.data" :key="m.id">
                <button class="flex w-full items-start gap-4 px-6 py-4 text-left transition hover:bg-fg/[0.03]" @click="view(m)">
                    <span class="mt-1.5 size-2.5 shrink-0 rounded-full" :class="m.read_at ? 'bg-transparent ring-1 ring-fg/20' : 'bg-gold-500'" />
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-3"><span class="truncate" :class="m.read_at ? 'text-mist' : 'font-semibold'">{{ m.name }} <span class="font-normal text-mist">· {{ m.email }}</span></span><span class="shrink-0 text-xs text-mist">{{ datetime(m.created_at) }}</span></span>
                        <span class="mt-0.5 block truncate text-sm" :class="m.read_at ? 'text-mist' : ''">{{ m.subject }}</span>
                        <span class="mt-0.5 block truncate text-xs text-mist">{{ m.message }}</span>
                    </span>
                </button>
            </li>
            <li v-if="!messages.data.length" class="px-6 py-16 text-center text-sm text-mist">No messages yet.</li>
        </ul>
        <Pagination :meta="messages" />
    </div>

    <Modal :show="!!open" wide :title="open?.subject" @close="open = null">
        <div v-if="open" class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                <p><span class="font-medium">{{ open.name }}</span> · <a :href="`mailto:${open.email}`" class="text-gold-400 hover:text-gold-300">{{ open.email }}</a></p>
                <p class="text-xs text-mist">{{ datetime(open.created_at) }}<span v-if="open.ip_address"> · IP {{ open.ip_address }}</span></p>
            </div>
            <div class="whitespace-pre-wrap rounded-xl border border-fg/[0.07] bg-ink-900/60 p-4 text-sm leading-relaxed">{{ open.message }}</div>
            <div class="flex flex-wrap justify-between gap-3">
                <button class="btn-danger" @click="remove(open)"><Trash2 class="size-4" /> Delete</button>
                <div class="flex gap-3">
                    <button class="btn-ghost" @click="toggle(open); open = null"><component :is="open.read_at ? Mail : MailOpen" class="size-4" /> {{ open.read_at ? 'Mark unread' : 'Mark read' }}</button>
                    <a :href="`mailto:${open.email}?subject=${encodeURIComponent('Re: ' + open.subject)}`" class="btn-gold"><Reply class="size-4" /> Reply by email</a>
                </div>
            </div>
        </div>
    </Modal>
</template>
