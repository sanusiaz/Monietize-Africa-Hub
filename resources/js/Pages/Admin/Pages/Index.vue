<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ pages: Array, protected: Array, reserved: Array });
const { date } = useFormat();

const blank = () => ({ title: '', slug: '', content: '', meta_description: '', is_published: true, show_in_footer: true, sort_order: 0 });
const show = ref(false);
const editing = ref(null);
const tab = ref('write');
const preview = ref('');
const previewing = ref(false);
const form = useForm(blank());

const openNew = () => { editing.value = null; tab.value = 'write'; form.defaults(blank()).reset(); form.clearErrors(); show.value = true; };
const openEdit = (p) => {
    editing.value = p; tab.value = 'write';
    form.defaults({ title: p.title, slug: p.slug, content: p.content, meta_description: p.meta_description ?? '', is_published: p.is_published, show_in_footer: p.show_in_footer, sort_order: p.sort_order }).reset();
    form.clearErrors(); show.value = true;
};

const xsrf = () => decodeURIComponent((document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN=')) ?? '').split('=')[1] ?? '');
watch(tab, async (t) => {
    if (t !== 'preview') return;
    previewing.value = true;
    try {
        const res = await fetch('/admin/pages/preview', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
            body: JSON.stringify({ content: form.content }),
        });
        preview.value = (await res.json()).html ?? '';
    } catch { preview.value = '<p>Preview unavailable.</p>'; }
    previewing.value = false;
});

const save = () => {
    const opts = { preserveScroll: true, onSuccess: () => (show.value = false) };
    editing.value ? form.put(`/admin/pages/${editing.value.id}`, opts) : form.post('/admin/pages', opts);
};
const remove = (p) => confirm(`Delete "${p.title}"? This cannot be undone.`) && router.delete(`/admin/pages/${p.id}`, { preserveScroll: true });
const isProtected = (p) => props.protected.includes(p.slug);
</script>

<template>
    <Head title="Pages" />
    <PageHeader eyebrow="Content" title="Pages" subtitle="Privacy Policy, Terms and any other page you want in the footer. Written in Markdown and published instantly.">
        <button class="btn-gold" @click="openNew"><Plus class="size-4" /> New page</button>
    </PageHeader>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-fg/[0.06]"><tr><th class="th">Page</th><th class="th">Address</th><th class="th">Footer</th><th class="th">Updated</th><th class="th">Status</th><th class="th" /></tr></thead>
                <tbody class="divide-y divide-fg/[0.05]">
                    <tr v-for="p in pages" :key="p.id" class="hover:bg-fg/[0.02]">
                        <td class="td font-medium">{{ p.title }}</td>
                        <td class="td text-mist">/{{ p.slug }}</td>
                        <td class="td text-mist">{{ p.show_in_footer ? 'Shown' : 'Hidden' }}</td>
                        <td class="td text-mist">{{ date(p.updated_at) }}</td>
                        <td class="td"><StatusBadge :status="p.is_published ? 'active' : 'suspended'" /></td>
                        <td class="td"><div class="flex justify-end gap-2">
                            <a v-if="p.is_published" :href="`/${p.slug}`" target="_blank" class="btn-ghost !p-2" title="View page"><ExternalLink class="size-4" /></a>
                            <button class="btn-ghost !p-2" title="Edit" @click="openEdit(p)"><Pencil class="size-4" /></button>
                            <button v-if="!isProtected(p)" class="btn-danger !p-2" title="Delete" @click="remove(p)"><Trash2 class="size-4" /></button>
                        </div></td>
                    </tr>
                    <tr v-if="!pages.length"><td colspan="6" class="td py-12 text-center text-mist">No pages yet.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <Modal :show="show" wide :title="editing ? `Edit: ${editing.title}` : 'New page'" @close="show = false">
        <form class="space-y-4" @submit.prevent="save">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Title</label><input v-model="form.title" class="input" required /><p v-if="form.errors.title" class="mt-1 text-xs text-rose-300">{{ form.errors.title }}</p></div>
                <div><label class="label">Address (URL)</label>
                    <div class="flex items-center gap-2"><span class="text-sm text-mist">/</span><input v-model="form.slug" class="input" placeholder="auto from title" :disabled="editing && isProtected(editing)" /></div>
                    <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-300">{{ form.errors.slug }}</p></div>
            </div>

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label class="label !mb-0">Content (Markdown)</label>
                    <div class="grid grid-cols-2 gap-1 rounded-lg bg-ink-900 p-0.5 text-xs">
                        <button type="button" class="rounded-md px-3 py-1" :class="tab === 'write' ? 'bg-gold-500 font-semibold text-on-gold' : 'text-mist'" @click="tab = 'write'">Write</button>
                        <button type="button" class="rounded-md px-3 py-1" :class="tab === 'preview' ? 'bg-gold-500 font-semibold text-on-gold' : 'text-mist'" @click="tab = 'preview'">Preview</button>
                    </div>
                </div>
                <textarea v-if="tab === 'write'" v-model="form.content" rows="16" class="input resize-y font-mono text-xs leading-relaxed" spellcheck="true" />
                <div v-else class="input max-h-[26rem] min-h-[16rem] overflow-y-auto"><p v-if="previewing" class="text-mist">Rendering…</p><div v-else class="prose-doc" v-html="preview" /></div>
                <p v-if="form.errors.content" class="mt-1 text-xs text-rose-300">{{ form.errors.content }}</p>
                <p class="mt-1.5 text-xs text-mist">Use <code># Heading</code>, <code>## Subheading</code>, <code>**bold**</code>, <code>- list</code> and <code>[text](/link)</code>. Placeholders filled from Settings: <code v-pre>{{site_name}}</code>, <code v-pre>{{company_name}}</code>, <code v-pre>{{support_email}}</code>, <code v-pre>{{country}}</code>, <code v-pre>{{updated_at}}</code>. Add a fallback with <code v-pre>{{support_email|our contact page}}</code>.</p>
            </div>

            <div><label class="label">Search description (optional)</label><input v-model="form.meta_description" class="input" maxlength="300" /></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="label">Status</label><select v-model="form.is_published" class="input"><option :value="true">Published</option><option :value="false">Draft (hidden)</option></select></div>
                <div><label class="label">In footer</label><select v-model="form.show_in_footer" class="input"><option :value="true">Show</option><option :value="false">Hide</option></select></div>
                <div><label class="label">Footer order</label><input v-model="form.sort_order" type="number" min="0" class="input" /></div>
            </div>
            <div class="flex justify-end gap-3"><button type="button" class="btn-ghost" @click="show = false">Cancel</button><button class="btn-gold" :disabled="form.processing">Save page</button></div>
        </form>
    </Modal>
</template>
