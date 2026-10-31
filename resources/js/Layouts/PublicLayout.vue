<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Clock, Mail, MapPin, Menu, Phone, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Brand from '../Components/Brand.vue';
import LiveChat from '../Components/LiveChat.vue';
import SeoHead from '../Components/SeoHead.vue';
import ThemeToggle from '../Components/ThemeToggle.vue';

const page = usePage();
const site = computed(() => page.props.site);
const user = computed(() => page.props.auth.user);
const home = computed(() => (user.value?.role === 'admin' ? '/admin' : '/account'));
const menu = ref(false);

const nav = [
    { label: 'Prices', href: '/#prices' },
    { label: 'How it works', href: '/#how' },
    { label: 'Features', href: '/#features' },
    { label: 'FAQ', href: '/#faq' },
    { label: 'Contact', href: '/contact' },
];

const socials = computed(() => Object.entries(site.value.socials ?? {}));
const socialNames = { instagram: 'Instagram', facebook: 'Facebook', x: 'X', tiktok: 'TikTok', telegram: 'Telegram', whatsapp: 'WhatsApp' };

// banner for success / error flashes (contact form etc.)
const flash = computed(() => page.props.flash);
const toast = ref(null);
watch(() => [flash.value.success, flash.value.error], ([s, e]) => {
    if (!s && !e) return;
    toast.value = { type: s ? 'success' : 'error', text: s || e };
    setTimeout(() => (toast.value = null), 6000);
}, { immediate: true });
watch(() => page.url, () => (menu.value = false));
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <SeoHead />
        <header class="sticky top-0 z-40 border-b border-fg/[0.06] bg-ink-950/80 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3.5">
                <Brand />
                <nav class="hidden items-center gap-7 text-sm text-mist lg:flex">
                    <Link v-for="l in nav" :key="l.href" :href="l.href" class="transition hover:text-gold-400" :class="page.url === l.href ? 'text-gold-400' : ''">{{ l.label }}</Link>
                </nav>
                <div class="flex items-center gap-2.5">
                    <ThemeToggle />
                    <div class="hidden items-center gap-2.5 sm:flex">
                        <Link v-if="user" :href="home" class="btn-gold">Dashboard <ArrowRight class="size-4" /></Link>
                        <template v-else><Link href="/login" class="btn-ghost">Sign in</Link><Link href="/register" class="btn-gold">Get started</Link></template>
                    </div>
                    <button class="p-2 lg:hidden" aria-label="Menu" @click="menu = !menu"><component :is="menu ? X : Menu" class="size-6" /></button>
                </div>
            </div>
            <div v-if="menu" class="menu-in space-y-1 border-t border-fg/[0.06] px-5 py-4 text-sm lg:hidden">
                <Link v-for="l in nav" :key="l.href" :href="l.href" class="block rounded-lg px-3 py-2.5 text-mist hover:bg-fg/5">{{ l.label }}</Link>
                <div class="flex gap-3 pt-3">
                    <Link v-if="user" :href="home" class="btn-gold flex-1">Dashboard</Link>
                    <template v-else><Link href="/login" class="btn-ghost flex-1">Sign in</Link><Link href="/register" class="btn-gold flex-1">Get started</Link></template>
                </div>
            </div>
        </header>

        <main :key="page.url.split('#')[0]" class="page-in flex-1"><slot /></main>

        <LiveChat />

        <footer class="border-t border-fg/[0.07] bg-ink-900/40">
            <div class="mx-auto grid max-w-6xl gap-10 px-5 py-14 md:grid-cols-2 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <Brand />
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-mist">{{ site.tagline }}</p>
                    <div v-if="socials.length" class="mt-5 flex flex-wrap gap-2">
                        <a v-for="[key, url] in socials" :key="key" :href="url" target="_blank" rel="noopener noreferrer" class="rounded-full border border-fg/10 px-3 py-1 text-xs text-mist transition hover:border-gold-500/40 hover:text-gold-400">{{ socialNames[key] ?? key }}</a>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <p class="eyebrow mb-4">Explore</p>
                    <ul class="space-y-2.5 text-sm text-mist">
                        <li v-for="l in nav.slice(0, 4)" :key="l.href"><Link :href="l.href" class="transition hover:text-gold-400">{{ l.label }}</Link></li>
                    </ul>
                </div>

                <div class="lg:col-span-2">
                    <p class="eyebrow mb-4">Account</p>
                    <ul class="space-y-2.5 text-sm text-mist">
                        <template v-if="user"><li><Link :href="home" class="transition hover:text-gold-400">Dashboard</Link></li></template>
                        <template v-else><li><Link href="/login" class="transition hover:text-gold-400">Sign in</Link></li><li><Link href="/register" class="transition hover:text-gold-400">Create account</Link></li></template>
                        <li><Link href="/contact" class="transition hover:text-gold-400">Contact us</Link></li>
                    </ul>
                    <p class="eyebrow mb-4 mt-8">Legal</p>
                    <ul class="space-y-2.5 text-sm text-mist">
                        <li v-for="p in page.props.footerPages" :key="p.slug"><Link :href="`/${p.slug}`" class="transition hover:text-gold-400">{{ p.title }}</Link></li>
                    </ul>
                </div>

                <div class="lg:col-span-4">
                    <p class="eyebrow mb-4">Get in touch</p>
                    <ul class="space-y-3 text-sm text-mist">
                        <li v-if="site.support_email" class="flex items-start gap-3"><Mail class="mt-0.5 size-4 shrink-0 text-gold-500" /><a :href="`mailto:${site.support_email}`" class="break-all transition hover:text-gold-400">{{ site.support_email }}</a></li>
                        <li v-if="site.phone" class="flex items-start gap-3"><Phone class="mt-0.5 size-4 shrink-0 text-gold-500" /><a :href="`tel:${site.phone}`" class="transition hover:text-gold-400">{{ site.phone }}</a></li>
                        <li v-if="site.hours" class="flex items-start gap-3"><Clock class="mt-0.5 size-4 shrink-0 text-gold-500" /><span>{{ site.hours }}</span></li>
                        <li v-if="site.address" class="flex items-start gap-3"><MapPin class="mt-0.5 size-4 shrink-0 text-gold-500" /><span>{{ site.address }}<template v-if="site.country">, {{ site.country }}</template></span></li>
                        <li><Link href="/contact" class="inline-flex items-center gap-1.5 text-gold-400 hover:text-gold-300">Send us a message <ArrowRight class="size-3.5" /></Link></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-fg/[0.06]">
                <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-5 text-xs text-mist">
                    <span>© {{ new Date().getFullYear() }} {{ site.company_name }}. All rights reserved.</span>
                    <span class="flex flex-wrap gap-x-5 gap-y-1"><Link v-for="p in page.props.footerPages" :key="p.slug" :href="`/${p.slug}`" class="hover:text-gold-400">{{ p.title }}</Link></span>
                </div>
            </div>
        </footer>

        <Transition enter-active-class="transition duration-300" enter-from-class="translate-y-3 opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
            <div v-if="toast" class="fixed bottom-6 right-5 z-[60] max-w-sm rounded-xl border px-4 py-3 text-sm shadow-2xl backdrop-blur"
                :class="toast.type === 'success' ? 'border-gold-500/40 bg-ink-800/95 text-gold-200' : 'border-rose-500/40 bg-ink-800/95 text-rose-200'">{{ toast.text }}</div>
        </Transition>
    </div>
</template>
