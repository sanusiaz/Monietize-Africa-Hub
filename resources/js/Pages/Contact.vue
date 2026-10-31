<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Clock, Mail, MapPin, Phone, Send } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PublicLayout from '../Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const page = usePage();
const site = computed(() => page.props.site);
const user = page.props.auth.user;

const form = useForm({ name: user?.name ?? '', email: user?.email ?? '', subject: '', message: '', website: '' });
const sent = ref(false);
const submit = () => form.post('/contact', {
    preserveScroll: true,
    onSuccess: () => { sent.value = true; form.reset('subject', 'message'); },
});

const details = computed(() => [
    { icon: Mail, label: 'Email', value: site.value.support_email, href: site.value.support_email && `mailto:${site.value.support_email}` },
    { icon: Phone, label: 'Phone', value: site.value.phone, href: site.value.phone && `tel:${site.value.phone}` },
    { icon: Clock, label: 'Support hours', value: site.value.hours },
    { icon: MapPin, label: 'Address', value: [site.value.address, site.value.country].filter(Boolean).join(', ') },
].filter((d) => d.value));
</script>

<template>
    <div class="mx-auto max-w-6xl px-5 py-14 sm:py-20">
        <div class="mx-auto mb-14 max-w-2xl text-center">
            <p class="eyebrow mb-3">Contact</p>
            <h1 class="display text-5xl sm:text-6xl">Contact us</h1>
            <p class="mt-5 text-mist">Questions about an order, your wallet or our services? Send us a message and we will reply by email.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <aside class="space-y-4 lg:col-span-2">
                <div v-for="d in details" :key="d.label" class="card flex items-start gap-4 p-5">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-gold-500/25 bg-gold-500/10 text-gold-400"><component :is="d.icon" class="size-[18px]" /></span>
                    <div class="min-w-0"><p class="eyebrow !text-[10px]">{{ d.label }}</p>
                        <a v-if="d.href" :href="d.href" class="mt-1 block break-words text-sm hover:text-gold-400">{{ d.value }}</a>
                        <p v-else class="mt-1 text-sm">{{ d.value }}</p></div>
                </div>
                <div class="card p-5 text-sm text-mist">
                    <p class="font-medium text-ivory">Before you write</p>
                    <p class="mt-1.5 leading-relaxed">For an order question, include your order number and the email on your account so we can help faster. We will never ask for your password.</p>
                </div>
            </aside>

            <div class="card p-6 sm:p-8 lg:col-span-3">
                <div v-if="sent" class="py-10 text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-emerald-500/10 text-emerald-400"><CheckCircle2 class="size-7" /></span>
                    <h2 class="display mt-5 text-3xl">Message sent</h2>
                    <p class="mx-auto mt-3 max-w-sm text-sm text-mist">Thank you for reaching out. We've received your message and will reply to <span class="text-ivory">{{ form.email }}</span>.</p>
                    <button class="btn-ghost mt-7" @click="sent = false">Send another message</button>
                </div>

                <form v-else class="space-y-5" novalidate @submit.prevent="submit">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="label" for="c-name">Your name</label><input id="c-name" v-model="form.name" class="input" autocomplete="name" required /><p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-300">{{ form.errors.name }}</p></div>
                        <div><label class="label" for="c-email">Email</label><input id="c-email" v-model="form.email" type="email" class="input" autocomplete="email" required /><p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-300">{{ form.errors.email }}</p></div>
                    </div>
                    <div><label class="label" for="c-subject">Subject</label><input id="c-subject" v-model="form.subject" class="input" required /><p v-if="form.errors.subject" class="mt-1.5 text-xs text-rose-300">{{ form.errors.subject }}</p></div>
                    <div><label class="label" for="c-message">Message</label><textarea id="c-message" v-model="form.message" rows="6" class="input resize-y" required /><p v-if="form.errors.message" class="mt-1.5 text-xs text-rose-300">{{ form.errors.message }}</p></div>
                    <!-- honeypot: hidden from people, tempting to bots -->
                    <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true"><label>Website<input v-model="form.website" tabindex="-1" autocomplete="off" /></label></div>
                    <button class="btn-gold w-full sm:w-auto" :disabled="form.processing"><Send class="size-4" /> {{ form.processing ? 'Sending…' : 'Send message' }}</button>
                </form>
            </div>
        </div>
    </div>
</template>
