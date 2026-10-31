<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, BarChart3, Calculator, ChevronDown, CreditCard, Droplets, ListChecks, RefreshCcw, ShieldCheck, Timer, Wallet, Zap } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useFormat } from '../composables/format';
import PublicLayout from '../Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });
const props = defineProps({ platforms: Array, serviceCount: Number, minRate: [String, Number], catalogue: Object, faqs: Array });

const page = usePage();
const { money, number, label } = useFormat();
const user = computed(() => page.props.auth.user);
const home = computed(() => (user.value?.role === 'admin' ? '/admin' : '/account'));
const name = computed(() => page.props.site.name);

// ---- price list ----
const platformKeys = computed(() => Object.keys(props.catalogue ?? {}));
const platform = ref(platformKeys.value[0] ?? '');
const showAll = ref(false);
const LIMIT = 9;
watch(platform, () => (showAll.value = false));
const rows = computed(() => props.catalogue?.[platform.value] ?? []);
const visible = computed(() => (showAll.value ? rows.value : rows.value.slice(0, LIMIT)));

// ---- calculator ----
const allServices = computed(() => Object.values(props.catalogue ?? {}).flat());
const calcId = ref(allServices.value[0]?.id ?? null);
const calcQty = ref(1000);
const calcService = computed(() => allServices.value.find((s) => s.id === calcId.value));
const total = computed(() => (calcService.value ? Math.round(calcService.value.rate * (calcQty.value || 0)) / 1000 : 0));
const qtyHint = computed(() => {
    const s = calcService.value;
    if (!s) return '';
    if (calcQty.value < s.min) return `Minimum order is ${number(s.min)}.`;
    if (calcQty.value > s.max) return `Maximum order is ${number(s.max)}.`;
    return '';
});

const steps = [
    { icon: CreditCard, title: 'Add money to your wallet', text: 'Pay with Paystack by card or bank transfer. Your balance is ready as soon as the payment clears.' },
    { icon: ListChecks, title: 'Choose a service', text: 'Pick a platform and service, paste the link to your profile or post, and enter a quantity. You see the total first.' },
    { icon: Timer, title: 'Follow your order', text: 'Watch it move from pending to completed in your dashboard. We also email you when it is done.' },
];

const features = [
    { icon: Wallet, title: 'One wallet for everything', text: 'Top up once and pay for each order from your balance. Every credit and debit is listed.' },
    { icon: ShieldCheck, title: 'Safe payments', text: 'Paystack handles your payment. We never see or store your card details.' },
    { icon: Zap, title: 'Orders start on their own', text: 'Paid orders go straight to our suppliers, so nobody has to approve them by hand.' },
    { icon: RefreshCcw, title: 'Automatic refunds', text: 'If an order is cancelled or only partly delivered, the rest goes back to your wallet.' },
    { icon: Droplets, title: 'Drip-feed and refill', text: 'Spread delivery over time, or pick services that come with a refill guarantee.' },
    { icon: BarChart3, title: 'Live order tracking', text: 'See the status, start count and what is left to deliver for every order.' },
];
</script>

<template>
    <div id="top">
        <!-- hero -->
        <section class="relative overflow-hidden">
            <div class="orb pointer-events-none absolute -top-24 left-1/2 size-[520px] -translate-x-[80%] rounded-full bg-gold-500/15 blur-3xl" />
            <div class="orb pointer-events-none absolute top-24 left-1/2 size-[380px] translate-x-[10%] rounded-full bg-gold-500/10 blur-3xl [animation-delay:-6s]" />
            <div class="relative mx-auto max-w-4xl px-5 pb-20 pt-16 text-center sm:pt-24">
                <h1 class="reveal-in display text-5xl leading-[1.05] sm:text-7xl">Followers, likes and views for your social accounts</h1>
                <p class="reveal-in mx-auto mt-6 max-w-xl text-lg text-mist [animation-delay:90ms]">Every price is listed per 1,000. Pay from your wallet, then follow each order until it is delivered.</p>

                <div v-if="minRate" class="reveal-in mx-auto mt-9 inline-flex items-baseline gap-3 rounded-2xl border border-gold-500/30 bg-gold-500/10 px-6 py-4 [animation-delay:160ms]">
                    <span class="text-sm text-mist">Prices start from</span>
                    <span class="price text-5xl leading-none">{{ money(minRate) }}</span>
                    <span class="text-sm text-mist">per 1,000</span>
                </div>

                <div class="reveal-in mt-9 flex flex-wrap justify-center gap-3 [animation-delay:230ms]">
                    <a href="#prices" class="btn-gold !px-7 !py-3.5 text-base">See all prices <ArrowRight class="size-4" /></a>
                    <Link :href="user ? '/new-order' : '/register'" class="btn-ghost !px-7 !py-3.5 text-base">{{ user ? 'Place an order' : 'Create a free account' }}</Link>
                </div>

                <div v-if="platforms.length" class="reveal-in mt-12 flex flex-wrap items-center justify-center gap-2.5 [animation-delay:300ms]">
                    <span v-for="p in platforms" :key="p" class="rounded-full border border-fg/10 bg-fg/[0.03] px-4 py-1.5 text-sm text-mist">{{ label(p) }}</span>
                </div>
            </div>
        </section>

        <!-- prices -->
        <section v-if="platformKeys.length" id="prices" class="mx-auto max-w-6xl scroll-mt-20 px-5 py-16">
            <div v-reveal class="mb-10 text-center">
                <h2 class="display text-4xl sm:text-5xl">Price list</h2>
                <p class="mx-auto mt-3 max-w-xl text-mist">All prices are per 1,000. {{ serviceCount }} services in total, cheapest first.</p>
            </div>

            <div v-reveal class="-mx-1 mb-6 flex gap-2 overflow-x-auto px-1 pb-1 sm:justify-center">
                <button v-for="p in platformKeys" :key="p" class="shrink-0 rounded-full border px-5 py-2 text-sm font-medium transition"
                    :class="platform === p ? 'border-gold-500 bg-gold-500 text-on-gold shadow-lg shadow-gold-500/20' : 'border-fg/10 text-mist hover:border-gold-500/40 hover:text-ivory'" @click="platform = p">
                    {{ label(p) }} <span class="ml-1 opacity-70">{{ catalogue[p].length }}</span>
                </button>
            </div>

            <div :key="platform" class="page-in grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="s in visible" :key="s.id" class="card lift flex flex-col p-5 hover:border-gold-500/40">
                    <p class="text-xs text-mist">{{ s.category }}</p>
                    <h3 class="mt-1 min-h-[2.75rem] text-[15px] font-semibold leading-snug">{{ s.name }}</h3>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="price text-[2.6rem] leading-none">{{ money(s.rate) }}</span>
                        <span class="text-xs text-mist">per 1,000</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5 text-[11px]">
                        <span class="rounded-md bg-fg/[0.06] px-2 py-1 text-mist">{{ number(s.min) }} to {{ number(s.max) }}</span>
                        <span v-if="s.refill" class="rounded-md bg-emerald-500/10 px-2 py-1 text-emerald-300">Refill guarantee</span>
                        <span v-if="s.drip_feed" class="rounded-md bg-sky-500/10 px-2 py-1 text-sky-300">Drip-feed</span>
                    </div>
                    <Link :href="user ? '/new-order' : '/register'" class="btn-ghost mt-5 w-full !py-2 text-[13px]">{{ user ? 'Order this' : 'Sign up to order' }}</Link>
                </div>
            </div>

            <div v-if="rows.length > LIMIT" class="mt-8 text-center">
                <button class="btn-ghost" @click="showAll = !showAll">{{ showAll ? 'Show fewer' : `Show all ${rows.length} ${label(platform)} services` }} <ChevronDown class="size-4 transition" :class="showAll ? 'rotate-180' : ''" /></button>
            </div>
        </section>

        <!-- calculator -->
        <section v-if="calcService" class="mx-auto max-w-4xl px-5 py-10">
            <div v-reveal class="card overflow-hidden p-6 sm:p-9">
                <div class="mb-6 flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-gold-500/15 text-gold-400"><Calculator class="size-5" /></span>
                    <div><h2 class="display text-3xl">Work out your price</h2><p class="text-sm text-mist">Pick a service and a quantity to see the exact total.</p></div></div>
                <div class="grid items-end gap-5 md:grid-cols-5">
                    <div class="md:col-span-3"><label class="label" for="calc-service">Service</label>
                        <select id="calc-service" v-model="calcId" class="input">
                            <optgroup v-for="p in platformKeys" :key="p" :label="label(p)"><option v-for="s in catalogue[p]" :key="s.id" :value="s.id">{{ s.name }}</option></optgroup>
                        </select></div>
                    <div class="md:col-span-2"><label class="label" for="calc-qty">Quantity</label><input id="calc-qty" v-model.number="calcQty" type="number" :min="calcService.min" :max="calcService.max" step="50" class="input" /></div>
                </div>
                <div class="mt-6 flex flex-wrap items-end justify-between gap-4 rounded-2xl border border-gold-500/25 bg-gold-500/10 px-6 py-5">
                    <div><p class="text-sm text-mist">Total for {{ number(calcQty || 0) }}</p><p class="price text-6xl leading-none">{{ money(total) }}</p>
                        <p v-if="qtyHint" class="mt-2 text-xs text-rose-300">{{ qtyHint }}</p></div>
                    <Link :href="user ? '/new-order' : '/register'" class="btn-gold !px-6 !py-3">{{ user ? 'Place this order' : 'Create account to order' }} <ArrowRight class="size-4" /></Link>
                </div>
            </div>
        </section>

        <!-- how -->
        <section id="how" class="mx-auto max-w-6xl scroll-mt-20 px-5 py-16">
            <div v-reveal class="mb-10 text-center"><h2 class="display text-4xl sm:text-5xl">How it works</h2></div>
            <div class="grid gap-5 md:grid-cols-3">
                <div v-for="(s, i) in steps" :key="s.title" v-reveal="i * 90" class="card lift p-7">
                    <div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-xl border border-gold-500/25 bg-gold-500/10 text-gold-400"><component :is="s.icon" class="size-5" /></span><span class="display text-3xl text-fg/20">{{ i + 1 }}</span></div>
                    <h3 class="display mt-5 text-2xl">{{ s.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist">{{ s.text }}</p>
                </div>
            </div>
        </section>

        <!-- features -->
        <section id="features" class="mx-auto max-w-6xl scroll-mt-20 px-5 py-16">
            <div v-reveal class="mb-10 text-center"><h2 class="display text-4xl sm:text-5xl">Why people use {{ name }}</h2></div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="(f, i) in features" :key="f.title" v-reveal="(i % 3) * 80" class="card lift p-6">
                    <component :is="f.icon" class="size-6 text-gold-400" />
                    <h3 class="display mt-4 text-2xl">{{ f.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist">{{ f.text }}</p>
                </div>
            </div>
        </section>

        <!-- faq -->
        <section id="faq" class="mx-auto max-w-3xl scroll-mt-20 px-5 py-16">
            <div v-reveal class="mb-8 text-center"><h2 class="display text-4xl sm:text-5xl">Common questions</h2></div>
            <div class="space-y-3">
                <details v-for="f in faqs" :key="f.q" v-reveal class="card group px-6 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium">
                        {{ f.q }}<ChevronDown class="size-4 shrink-0 text-gold-400 transition group-open:rotate-180" />
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-mist">{{ f.a }}</p>
                </details>
            </div>
            <p class="mt-8 text-center text-sm text-mist">Something else? <Link href="/contact" class="text-gold-400 hover:text-gold-300">Send us a message</Link>.</p>
        </section>

        <!-- cta -->
        <section class="mx-auto max-w-6xl px-5 pb-24 pt-6">
            <div v-reveal class="card relative overflow-hidden px-6 py-14 text-center">
                <div class="orb pointer-events-none absolute -right-20 -top-20 size-72 rounded-full bg-gold-500/15 blur-3xl" />
                <h2 class="display relative text-4xl sm:text-5xl">Ready to place your first order?</h2>
                <p class="relative mx-auto mt-3 max-w-md text-mist">Create an account, add money to your wallet and order in a few minutes.</p>
                <Link :href="user ? home : '/register'" class="btn-gold relative mt-7 !px-8 !py-3.5 text-base">{{ user ? 'Open dashboard' : 'Create a free account' }} <ArrowRight class="size-4" /></Link>
            </div>
        </section>
    </div>
</template>
