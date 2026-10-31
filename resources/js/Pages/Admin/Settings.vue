<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Copy, Eye, Send, ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';
import PageHeader from '../../Components/PageHeader.vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ settings: Object, webhookUrl: String, emailTypes: Object, mailConfigured: Boolean, chatProviders: Object, chatLive: Boolean });
const s = props.settings;

const form = useForm({
    site_name: s.site_name, currency: s.currency, min_topup: s.min_topup, tagline: s.tagline ?? '',
    company_name: s.company_name ?? '', business_address: s.business_address ?? '', business_country: s.business_country ?? '',
    support_email: s.support_email ?? '', support_phone: s.support_phone ?? '', support_hours: s.support_hours ?? '',
    social_instagram: s.social_instagram ?? '', social_facebook: s.social_facebook ?? '', social_x: s.social_x ?? '',
    social_tiktok: s.social_tiktok ?? '', social_telegram: s.social_telegram ?? '', social_whatsapp: s.social_whatsapp ?? '',
    admin_email: s.admin_email ?? '', mail_host: s.mail_host ?? '', mail_port: s.mail_port, mail_encryption: s.mail_encryption,
    mail_username: s.mail_username ?? '', mail_password: '', mail_from_address: s.mail_from_address ?? '', mail_from_name: s.mail_from_name ?? '',
    paystack_public_key: s.paystack_public_key ?? '', paystack_secret_key: '',
    chat_enabled: !!s.chat_enabled, chat_provider: s.chat_provider, chat_tawk_property: s.chat_tawk_property ?? '', chat_tawk_widget: s.chat_tawk_widget ?? 'default',
    chat_tidio_key: s.chat_tidio_key ?? '', chat_crisp_id: s.chat_crisp_id ?? '', chat_custom_code: s.chat_custom_code ?? '',
});

const save = () => form.put('/admin/settings', { preserveScroll: true, onSuccess: () => form.reset('mail_password', 'paystack_secret_key') });
const sending = ref(false);
const sendTest = () => router.post('/admin/settings/test-email', {}, { preserveScroll: true, onStart: () => (sending.value = true), onFinish: () => (sending.value = false) });

const copied = ref(false);
const copy = async () => {
    try { await navigator.clipboard.writeText(props.webhookUrl); copied.value = true; setTimeout(() => (copied.value = false), 1800); } catch { /* clipboard unavailable */ }
};

const social = [['social_instagram', 'Instagram'], ['social_facebook', 'Facebook'], ['social_x', 'X (Twitter)'], ['social_tiktok', 'TikTok'], ['social_telegram', 'Telegram'], ['social_whatsapp', 'WhatsApp']];
</script>

<template>
    <Head title="Settings" />
    <PageHeader eyebrow="Configuration" title="Settings" subtitle="Everything customers see, and every way the site contacts you, is managed here." />

    <form class="grid gap-5 xl:grid-cols-2" @submit.prevent="save">
        <!-- general -->
        <div class="card space-y-4 p-6">
            <div><p class="eyebrow">Storefront</p><h3 class="display text-2xl">General</h3></div>
            <div><label class="label">Site name</label><input v-model="form.site_name" class="input" required /></div>
            <div><label class="label">Tagline (shown in the footer)</label><input v-model="form.tagline" class="input" placeholder="Premium social growth, delivered through a wallet you control." /></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="label">Currency</label><select v-model="form.currency" class="input"><option v-for="c in ['NGN', 'GHS', 'ZAR', 'KES', 'USD']" :key="c">{{ c }}</option></select></div>
                <div><label class="label">Minimum top-up</label><input v-model="form.min_topup" type="number" min="100" class="input" required /><p v-if="form.errors.min_topup" class="mt-1 text-xs text-rose-300">{{ form.errors.min_topup }}</p></div>
            </div>
        </div>

        <!-- business -->
        <div class="card space-y-4 p-6">
            <div><p class="eyebrow">Public details</p><h3 class="display text-2xl">Business & contact</h3>
                <p class="mt-1 text-xs text-mist">Shown in the footer and on the Contact page, and filled into your Privacy Policy and Terms.</p></div>
            <div><label class="label">Company / legal name</label><input v-model="form.company_name" class="input" placeholder="Defaults to the site name" /></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Support email</label><input v-model="form.support_email" type="email" class="input" /><p v-if="form.errors.support_email" class="mt-1 text-xs text-rose-300">{{ form.errors.support_email }}</p></div>
                <div><label class="label">Phone</label><input v-model="form.support_phone" class="input" /></div>
            </div>
            <div><label class="label">Support hours</label><input v-model="form.support_hours" class="input" placeholder="Mon–Fri, 9am–6pm" /></div>
            <div><label class="label">Business address</label><input v-model="form.business_address" class="input" /></div>
            <div><label class="label">Country (used for governing law in your Terms)</label><input v-model="form.business_country" class="input" /></div>
        </div>

        <!-- social -->
        <div class="card space-y-4 p-6">
            <div><p class="eyebrow">Footer</p><h3 class="display text-2xl">Social links</h3><p class="mt-1 text-xs text-mist">Leave blank to hide a link.</p></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div v-for="[key, name] in social" :key="key"><label class="label">{{ name }} URL</label><input v-model="form[key]" type="url" class="input" placeholder="https://" /><p v-if="form.errors[key]" class="mt-1 text-xs text-rose-300">{{ form.errors[key] }}</p></div>
            </div>
        </div>

        <!-- paystack -->
        <div class="card space-y-4 p-6">
            <div class="flex items-start justify-between">
                <div><p class="eyebrow">Payment gateway</p><h3 class="display text-2xl">Paystack</h3></div>
                <span v-if="settings.has_secret" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] text-emerald-300 ring-1 ring-inset ring-emerald-500/25"><ShieldCheck class="size-3.5" /> Secret key set</span>
            </div>
            <div><label class="label">Public key</label><input v-model="form.paystack_public_key" class="input font-mono text-xs" placeholder="pk_live_..." autocomplete="off" /></div>
            <div><label class="label">Secret key {{ settings.has_secret ? '(leave blank to keep current)' : '' }}</label><input v-model="form.paystack_secret_key" type="password" class="input font-mono text-xs" placeholder="sk_live_..." autocomplete="new-password" /></div>
            <div>
                <label class="label">Webhook URL (add this in your Paystack dashboard)</label>
                <div class="flex gap-2"><input :value="webhookUrl" readonly class="input font-mono text-xs" /><button type="button" class="btn-ghost shrink-0" @click="copy"><Copy class="size-4" />{{ copied ? 'Copied' : 'Copy' }}</button></div>
            </div>
        </div>

        <!-- live chat -->
        <div class="card space-y-4 p-6 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="eyebrow">Customer support</p><h3 class="display text-2xl">Live chat</h3>
                    <p class="mt-1 max-w-xl text-xs text-mist">Shows a chat bubble to visitors and customers (never in the admin panel). Replies happen in your chat provider's own app or dashboard. Turn it off at any time and the bubble disappears right away.</p></div>
                <span class="rounded-full px-2.5 py-1 text-[11px] ring-1 ring-inset" :class="chatLive ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/25' : 'bg-fg/5 text-mist ring-fg/10'">{{ chatLive ? 'Live on the site' : 'Not showing' }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div><label class="label">Live chat</label><select v-model="form.chat_enabled" class="input"><option :value="true">On</option><option :value="false">Off</option></select></div>
                <div class="md:col-span-2"><label class="label">Provider</label><select v-model="form.chat_provider" class="input"><option v-for="(name, key) in chatProviders" :key="key" :value="key">{{ name }}</option></select></div>
            </div>

            <div v-if="form.chat_provider === 'tawk'" class="grid gap-4 md:grid-cols-2">
                <div><label class="label">Tawk.to property ID</label><input v-model="form.chat_tawk_property" class="input font-mono text-xs" placeholder="e.g. 5f3a1b2c4d5e6f7a8b9c0d1e" autocomplete="off" /><p v-if="form.errors.chat_tawk_property" class="mt-1 text-xs text-rose-300">{{ form.errors.chat_tawk_property }}</p></div>
                <div><label class="label">Widget ID</label><input v-model="form.chat_tawk_widget" class="input font-mono text-xs" placeholder="default" autocomplete="off" /><p v-if="form.errors.chat_tawk_widget" class="mt-1 text-xs text-rose-300">{{ form.errors.chat_tawk_widget }}</p></div>
                <p class="text-xs text-mist md:col-span-2">In Tawk.to open Administration, then Channels, then Chat Widget. The embed link looks like <code>embed.tawk.to/PROPERTY_ID/WIDGET_ID</code>.</p>
            </div>
            <div v-else-if="form.chat_provider === 'tidio'">
                <label class="label">Tidio public key</label><input v-model="form.chat_tidio_key" class="input font-mono text-xs" autocomplete="off" /><p v-if="form.errors.chat_tidio_key" class="mt-1 text-xs text-rose-300">{{ form.errors.chat_tidio_key }}</p>
                <p class="mt-1 text-xs text-mist">The key is the part before <code>.js</code> in <code>code.tidio.co/KEY.js</code>.</p>
            </div>
            <div v-else-if="form.chat_provider === 'crisp'">
                <label class="label">Crisp website ID</label><input v-model="form.chat_crisp_id" class="input font-mono text-xs" autocomplete="off" /><p v-if="form.errors.chat_crisp_id" class="mt-1 text-xs text-rose-300">{{ form.errors.chat_crisp_id }}</p>
            </div>
            <div v-else>
                <label class="label">Embed code from your chat provider</label><textarea v-model="form.chat_custom_code" rows="6" class="input resize-y font-mono text-xs" placeholder="&lt;script&gt; ... &lt;/script&gt;" />
                <p class="mt-1 text-xs text-mist">Paste the full snippet exactly as the provider gives it. Only paste code from a provider you trust, because it runs on every public page.</p>
            </div>
            <p v-if="form.chat_enabled && !chatLive" class="rounded-lg border border-gold-500/30 bg-gold-500/10 p-3 text-xs text-gold-200">Chat is switched on, but nothing will show until you save the provider's ID or code above.</p>
        </div>

        <!-- email -->
        <div class="card space-y-5 p-6 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="eyebrow">Notifications</p><h3 class="display text-2xl">Email</h3>
                    <p class="mt-1 max-w-xl text-xs text-mist">Order alerts, receipts and customer emails are sent through your own SMTP account. Until a host is saved, emails are only written to the application log and nobody receives them.</p></div>
                <span class="rounded-full px-2.5 py-1 text-[11px] ring-1 ring-inset" :class="mailConfigured ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/25' : 'bg-amber-500/10 text-amber-300 ring-amber-500/25'">{{ mailConfigured ? 'SMTP configured' : 'SMTP not configured' }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="label">Admin notification email</label><input v-model="form.admin_email" type="email" class="input" placeholder="Where new-order and contact alerts go" /><p class="mt-1 text-xs text-mist">If blank, alerts go to every active admin account.</p><p v-if="form.errors.admin_email" class="mt-1 text-xs text-rose-300">{{ form.errors.admin_email }}</p></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="label">From name</label><input v-model="form.mail_from_name" class="input" placeholder="Defaults to site name" /></div>
                    <div><label class="label">From address</label><input v-model="form.mail_from_address" type="email" class="input" placeholder="no-reply@yourdomain.com" /><p v-if="form.errors.mail_from_address" class="mt-1 text-xs text-rose-300">{{ form.errors.mail_from_address }}</p></div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="md:col-span-2"><label class="label">SMTP host</label><input v-model="form.mail_host" class="input" placeholder="smtp.yourprovider.com" autocomplete="off" /></div>
                <div><label class="label">Port</label><input v-model="form.mail_port" type="number" class="input" /></div>
                <div><label class="label">Encryption</label><select v-model="form.mail_encryption" class="input"><option value="tls">TLS / STARTTLS (587)</option><option value="ssl">SSL (465)</option><option value="none">None</option></select></div>
                <div class="md:col-span-2"><label class="label">SMTP username</label><input v-model="form.mail_username" class="input" autocomplete="off" /></div>
                <div class="md:col-span-2"><label class="label">SMTP password {{ settings.has_mail_password ? '(leave blank to keep current)' : '' }}</label><input v-model="form.mail_password" type="password" class="input" autocomplete="new-password" /></div>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-fg/[0.07] pt-5">
                <button type="button" class="btn-ghost" :disabled="sending || form.isDirty" :title="form.isDirty ? 'Save your changes first' : ''" @click="sendTest"><Send class="size-4" /> {{ sending ? 'Sending…' : 'Send test email to me' }}</button>
                <span class="text-xs text-mist">{{ form.isDirty ? 'Save first, then send a test.' : 'Sends through the saved settings.' }}</span>
            </div>

            <div>
                <p class="label">Preview every email (sample data)</p>
                <div class="flex flex-wrap gap-2">
                    <a v-for="(name, type) in emailTypes" :key="type" :href="`/admin/emails/${type}`" target="_blank" class="btn-ghost !px-3 !py-2 !text-xs"><Eye class="size-3.5" /> {{ name }}</a>
                </div>
            </div>
        </div>

        <div class="xl:col-span-2"><button class="btn-gold" :disabled="form.processing">Save settings</button></div>
    </form>
</template>
