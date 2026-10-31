<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    CreditCard,
    FileText,
    Inbox,
    Layers,
    LayoutDashboard,
    LogOut,
    Menu,
    Network,
    Settings,
    ShoppingBag,
    Search,
    Tags,
    Users,
    Wallet,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useFormat } from '../composables/format';
import Brand from '../Components/Brand.vue';
import SeoHead from '../Components/SeoHead.vue';
import ThemeToggle from '../Components/ThemeToggle.vue';

const page = usePage();
const { money } = useFormat();
const open = ref(false);

const nav = [
    { label: 'Overview', href: '/admin', icon: LayoutDashboard, exact: true },
    { label: 'Orders', href: '/admin/orders', icon: ShoppingBag },
    { label: 'Customers', href: '/admin/users', icon: Users },
    { label: 'Wallet & Payments', href: '/admin/payments', icon: Wallet },
    { label: 'Services', href: '/admin/services', icon: Layers },
    { label: 'Categories', href: '/admin/categories', icon: Tags },
    { label: 'Suppliers', href: '/admin/providers', icon: Network },
    { label: 'Messages', href: '/admin/messages', icon: Inbox, badge: true },
    { label: 'Pages', href: '/admin/pages', icon: FileText },
    { label: 'SEO', href: '/admin/seo', icon: Search },
    { label: 'Settings', href: '/admin/settings', icon: Settings },
];

const isActive = (item) => (item.exact ? page.url.split('?')[0] === item.href : page.url.startsWith(item.href));

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
const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen lg:pl-72">
        <SeoHead no-title />
        <!-- sidebar -->
        <div v-if="open" class="fixed inset-0 z-30 bg-black/70 lg:hidden" @click="open = false" />
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-fg/[0.06] bg-ink-900/95 backdrop-blur transition-transform lg:translate-x-0"
            :class="open ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center justify-between px-7 pb-6 pt-8">
                <div>
                    <Brand />
                    <Link href="/admin" class="eyebrow mt-2 block !text-[9px] hover:text-gold-400">Admin panel</Link>
                </div>
                <button class="text-mist lg:hidden" @click="open = false"><X class="size-5" /></button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition"
                    :class="isActive(item) ? 'bg-gold-500/10 text-gold-200' : 'text-mist hover:bg-fg/[0.04] hover:text-ivory'"
                >
                    <span v-if="isActive(item)" class="absolute -left-4 h-5 w-1 rounded-r-full bg-gold-500" />
                    <component :is="item.icon" class="size-[18px]" :class="isActive(item) ? 'text-gold-400' : ''" />
                    {{ item.label }}
                    <span v-if="item.badge && page.props.unreadMessages" class="ml-auto rounded-full bg-gold-500 px-2 py-0.5 text-[10px] font-semibold text-on-gold">{{ page.props.unreadMessages }}</span>
                </Link>
                <Link href="/" class="mt-3 flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs text-mist transition hover:text-gold-400">View public website ↗</Link>
            </nav>

            <div class="m-4 rounded-2xl border border-fg/[0.06] bg-fg/[0.02] p-4">
                <div class="flex items-center gap-3">
                    <span class="grid size-9 place-items-center rounded-full bg-gold-500/15 text-sm font-semibold text-gold-300">
                        {{ page.props.auth.user.name.charAt(0) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ page.props.auth.user.name }}</p>
                        <p class="truncate text-xs text-mist">{{ page.props.auth.user.email }}</p>
                    </div>
                    <ThemeToggle />
                    <button class="rounded-lg p-2 text-mist hover:bg-fg/5 hover:text-rose-300" title="Sign out" @click="logout">
                        <LogOut class="size-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- top bar (mobile) -->
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-fg/[0.06] bg-ink-950/80 px-5 py-4 backdrop-blur lg:hidden">
            <button class="text-ivory" @click="open = true"><Menu class="size-6" /></button>
            <Brand size="sm" />
            <ThemeToggle />
        </header>

        <main :key="page.url" class="page-in mx-auto max-w-[1400px] px-5 py-8 sm:px-8 lg:px-10 lg:py-12">
            <slot />
        </main>

        <Transition
            enter-active-class="transition duration-300"
            enter-from-class="translate-y-3 opacity-0"
            leave-active-class="transition duration-200"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toast"
                class="fixed bottom-6 right-6 z-[60] flex max-w-sm items-center gap-3 rounded-xl border px-4 py-3 text-sm shadow-2xl backdrop-blur"
                :class="toast.type === 'success' ? 'border-gold-500/40 bg-ink-800/95 text-gold-200' : 'border-rose-500/40 bg-ink-800/95 text-rose-200'"
            >
                <CreditCard class="size-4 shrink-0" />
                {{ toast.text }}
            </div>
        </Transition>
    </div>
</template>
