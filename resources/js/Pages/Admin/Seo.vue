<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, RotateCcw, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';
import PageHeader from '../../Components/PageHeader.vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ values: Object, defaults: Object, siteName: String, links: Object, appUrl: String, localhost: Boolean });

const form = useForm({ ...props.values, seo_noindex: props.values.seo_noindex === '1' });
const save = () => form.put('/admin/seo', { preserveScroll: true });
const reset = () => confirm('Replace every SEO setting with the recommended defaults?') && router.post('/admin/seo/reset', {}, { preserveScroll: true, onSuccess: () => location.reload() });

const fill = (text) => String(text ?? '').replaceAll('{site_name}', props.siteName);
const homeTitle = computed(() => fill(form.seo_home_title));
const homeDesc = computed(() => fill(form.seo_home_description));
const shown = (s, max) => (s.length > max ? s.slice(0, max - 1).trimEnd() + '…' : s);
const host = computed(() => props.appUrl.replace(/^https?:\/\//, '').replace(/\/$/, ''));

// Google shows roughly 60 characters of title and 160 of description
const meter = (len, ideal) => (len === 0 ? 'text-rose-300' : len > ideal ? 'text-amber-300' : 'text-emerald-300');
</script>

<template>
    <Head title="SEO" />
    <PageHeader eyebrow="Search & sharing" title="SEO" subtitle="Control how your site appears in Google and when links are shared. The values below are the recommended defaults, change anything you like.">
        <button class="btn-ghost" type="button" @click="reset"><RotateCcw class="size-4" /> Restore defaults</button>
    </PageHeader>

    <div v-if="localhost" class="mb-5 flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-300">
        <TriangleAlert class="mt-0.5 size-4 shrink-0" />
        <p>Your site address is currently <strong>{{ appUrl }}</strong>. Canonical links, the sitemap and social images use <code>APP_URL</code> from your <code>.env</code> file, so set it to your real domain (for example <code>https://yourdomain.com</code>) before you go live.</p>
    </div>

    <form class="grid gap-5 xl:grid-cols-2" @submit.prevent="save">
        <!-- home -->
        <div class="card space-y-4 p-6">
            <div><p class="eyebrow">Home page</p><h3 class="display text-2xl">Google result</h3></div>

            <div class="rounded-xl border border-fg/10 bg-white p-4 text-left shadow-sm">
                <p class="truncate text-xs text-[#202124]">{{ host }}</p>
                <p class="mt-0.5 truncate text-[19px] leading-snug text-[#1a0dab]">{{ shown(homeTitle, 60) }}</p>
                <p class="mt-1 text-[13px] leading-snug text-[#4d5156]">{{ shown(homeDesc, 160) }}</p>
            </div>

            <div>
                <div class="flex justify-between"><label class="label">Page title</label><span class="text-xs" :class="meter(homeTitle.length, 60)">{{ homeTitle.length }} / 60</span></div>
                <input v-model="form.seo_home_title" class="input" required />
                <p v-if="form.errors.seo_home_title" class="mt-1 text-xs text-rose-300">{{ form.errors.seo_home_title }}</p>
            </div>
            <div>
                <div class="flex justify-between"><label class="label">Description</label><span class="text-xs" :class="meter(homeDesc.length, 160)">{{ homeDesc.length }} / 160</span></div>
                <textarea v-model="form.seo_home_description" rows="3" class="input resize-y" required />
                <p v-if="form.errors.seo_home_description" class="mt-1 text-xs text-rose-300">{{ form.errors.seo_home_description }}</p>
            </div>
            <p class="text-xs text-mist">Write <code v-pre>{site_name}</code> where your site name should appear. Keep titles near 60 characters and descriptions near 160 so Google does not cut them off.</p>
        </div>

        <!-- other pages -->
        <div class="card space-y-4 p-6">
            <div><p class="eyebrow">Other pages</p><h3 class="display text-2xl">Titles and descriptions</h3></div>
            <div>
                <label class="label">Title format for all other pages</label>
                <input v-model="form.seo_title_template" class="input" required />
                <p class="mt-1 text-xs text-mist">Must contain <code v-pre>{title}</code>. Example: <code v-pre>{title} | {site_name}</code> gives “Contact Us | {{ siteName }}”.</p>
                <p v-if="form.errors.seo_title_template" class="mt-1 text-xs text-rose-300">{{ form.errors.seo_title_template }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Contact page title</label><input v-model="form.seo_contact_title" class="input" required /></div>
                <div><label class="label">Fallback description</label><input v-model="form.seo_default_description" class="input" required /></div>
            </div>
            <div><label class="label">Contact page description</label><textarea v-model="form.seo_contact_description" rows="2" class="input resize-y" required /></div>
            <p class="text-xs text-mist">Pages you create under Admin → Pages use their own title and “search description”.</p>
        </div>

        <!-- sharing -->
        <div class="card space-y-4 p-6">
            <div><p class="eyebrow">Social sharing</p><h3 class="display text-2xl">Link previews</h3></div>
            <div>
                <label class="label">Share image URL (optional)</label>
                <input v-model="form.seo_og_image" type="url" class="input" placeholder="Leave blank to use the default logo image" />
                <p class="mt-1 text-xs text-mist">Used when a link is shared on WhatsApp, Facebook, X and similar. Best size is 1200 × 630.</p>
                <p v-if="form.errors.seo_og_image" class="mt-1 text-xs text-rose-300">{{ form.errors.seo_og_image }}</p>
            </div>
            <img :src="form.seo_og_image || '/images/og-image.png'" alt="Share image preview" class="aspect-[1200/630] w-full rounded-xl border border-fg/10 object-cover" />
            <div><label class="label">X (Twitter) username</label><input v-model="form.seo_twitter_handle" class="input" placeholder="@yourbrand" /><p v-if="form.errors.seo_twitter_handle" class="mt-1 text-xs text-rose-300">{{ form.errors.seo_twitter_handle }}</p></div>
        </div>

        <!-- crawling -->
        <div class="card space-y-4 p-6">
            <div><p class="eyebrow">Search engines</p><h3 class="display text-2xl">Crawling and sitemap</h3></div>

            <div class="grid gap-3 sm:grid-cols-2">
                <a :href="links.sitemap" target="_blank" class="btn-ghost justify-between"><span>sitemap.xml</span><ExternalLink class="size-4" /></a>
                <a :href="links.robots" target="_blank" class="btn-ghost justify-between"><span>robots.txt</span><ExternalLink class="size-4" /></a>
            </div>
            <p class="text-xs text-mist">Both files are created automatically and stay up to date as you add pages and services. Submit <code>{{ links.sitemap }}</code> in Google Search Console after launch.</p>

            <div>
                <label class="label">Allow search engines to list this site</label>
                <select v-model="form.seo_noindex" class="input"><option :value="false">Yes, let Google index the site</option><option :value="true">No, ask search engines to stay away</option></select>
                <p v-if="form.seo_noindex" class="mt-2 rounded-lg border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">The whole site is hidden from Google while this is on. Use it only for a test or staging site.</p>
            </div>
            <div><label class="label">Extra robots.txt rules (optional)</label><textarea v-model="form.seo_robots_extra" rows="3" class="input resize-y font-mono text-xs" placeholder="User-agent: BadBot&#10;Disallow: /" /></div>
        </div>

        <!-- verification -->
        <div class="card space-y-4 p-6 xl:col-span-2">
            <div><p class="eyebrow">Ownership</p><h3 class="display text-2xl">Search console verification</h3>
                <p class="mt-1 text-xs text-mist">Paste only the code from the verification tag, for example the part inside <code>content="…"</code>.</p></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="label">Google Search Console</label><input v-model="form.seo_google_verification" class="input font-mono text-xs" autocomplete="off" /><p v-if="form.errors.seo_google_verification" class="mt-1 text-xs text-rose-300">{{ form.errors.seo_google_verification }}</p></div>
                <div><label class="label">Bing Webmaster Tools</label><input v-model="form.seo_bing_verification" class="input font-mono text-xs" autocomplete="off" /><p v-if="form.errors.seo_bing_verification" class="mt-1 text-xs text-rose-300">{{ form.errors.seo_bing_verification }}</p></div>
            </div>
        </div>

        <div class="xl:col-span-2"><button class="btn-gold" :disabled="form.processing">Save SEO settings</button></div>
    </form>
</template>
