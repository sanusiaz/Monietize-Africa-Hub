<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, LogOut, Menu, PlusCircle, ShoppingBag, Wallet, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Brand from '../Components/Brand.vue';
import LiveChat from '../Components/LiveChat.vue';
import SeoHead from '../Components/SeoHead.vue';
import ThemeToggle from '../Components/ThemeToggle.vue';
import { useFormat } from '../composables/format';

const page = usePage();
const { money } = useFormat();
const open = ref(false);

const nav = [
    { label: 'Dashboard', href: '/account', icon: LayoutDashboard, exact: true },
    { label: 'New order', href: '/new-order', icon: PlusCircle },
    { label: 'My orders', href: '/my-orders', icon: ShoppingBag },
    { label: 'Wallet', href: '/wallet', icon: Wallet },
];
const isActive = (i) => (i.exact ? page.url.split('?')[0] === i.href : page.url.startsWith(i.href));

const flash = computed(() => page.props.flash);
const toast = ref(null);
watch(
    () => [flash.value.success, flash.value.error],
    ([success, error]) => {
        if (!success && !error) return;
        toast.value = { type: success ? 'success' : 'error', text: success || error };
        setTimeout(() => (toast.value = null), 4500);
    },
    { immediate: true },
);
watch(() => page.url, () => (open.value = false));
</script>

<template>
    <div class="min-h-screen">
        <SeoHead no-title />
        <header class="sticky top-0 z-30 border-b border-fg/[0.06] bg-ink-950/85 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3">
                <Brand />

                <nav class="hidden items-center gap-1 md:flex">
                    <Link v-for="i in nav" :key="i.href" :href="i.href" class="flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm transition"
                        :class="isActive(i) ? 'bg-gold-500/10 text-gold-200' : 'text-mist hover:bg-fg/[0.04] hover:text-ivory'">
                        <component :is="i.icon" class="size-4" /> {{ i.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <Link href="/wallet" class="rounded-xl border border-gold-500/25 bg-gold-500/10 px-3.5 py-2 text-sm font-semibold text-gold-200 transition hover:bg-gold-500/20" title="Wallet balance">{{ money(page.props.auth.user.balance) }}</Link>
                    <ThemeToggle />
                    <button class="hidden rounded-lg p-2 text-mist hover:bg-fg/5 hover:text-rose-300 md:block" title="Sign out" @click="router.post('/logout')"><LogOut class="size-4" /></button>
                    <button class="p-2 md:hidden" aria-label="Menu" @click="open = !open"><component :is="open ? X : Menu" class="size-6" /></button>
                </div>
            </div>
            <nav v-if="open" class="menu-in space-y-1 border-t border-fg/[0.06] px-5 py-3 md:hidden">
                <Link v-for="i in nav" :key="i.href" :href="i.href" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm"
                    :class="isActive(i) ? 'bg-gold-500/10 text-gold-200' : 'text-mist'"><component :is="i.icon" class="size-4" /> {{ i.label }}</Link>
                <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-rose-300" @click="router.post('/logout')"><LogOut class="size-4" /> Sign out</button>
            </nav>
        </header>

        <main :key="page.url" class="page-in mx-auto max-w-6xl px-5 py-10"><slot /></main>

        <footer class="border-t border-fg/[0.06]">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-6 text-xs text-mist">
                <span>© {{ new Date().getFullYear() }} {{ page.props.site.company_name }}</span>
                <span class="flex flex-wrap gap-x-5 gap-y-1">
                    <Link href="/contact" class="hover:text-gold-400">Contact</Link>
                    <Link v-for="p in page.props.footerPages" :key="p.slug" :href="`/${p.slug}`" class="hover:text-gold-400">{{ p.title }}</Link>
                </span>
            </div>
        </footer>

        <LiveChat />

        <Transition enter-active-class="transition duration-300" enter-from-class="translate-y-3 opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
            <div v-if="toast" class="fixed bottom-6 left-5 z-[60] max-w-sm rounded-xl border px-4 py-3 text-sm shadow-2xl backdrop-blur"
                :class="toast.type === 'success' ? 'border-gold-500/40 bg-ink-800/95 text-gold-200' : 'border-rose-500/40 bg-ink-800/95 text-rose-200'">{{ toast.text }}</div>
        </Transition>
    </div>
</template>
