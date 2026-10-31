<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import PageHeader from '../../Components/PageHeader.vue';
import { useFormat } from '../../composables/format';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';

defineOptions({ layout: CustomerLayout });
const props = defineProps({ categories: Array });
const { money, number, label } = useFormat();
const page = usePage();

const platforms = computed(() => [...new Set(props.categories.map((c) => c.platform))]);
const platform = ref(platforms.value[0] ?? '');
const categoryId = ref(null);
const search = ref('');

const cats = computed(() => props.categories.filter((c) => c.platform === platform.value));
watch([platform, cats], () => { if (!cats.value.find((c) => c.id === categoryId.value)) categoryId.value = cats.value[0]?.id ?? null; }, { immediate: true });

const services = computed(() => {
    const list = props.categories.find((c) => c.id === categoryId.value)?.services ?? [];
    return list.filter((s) => s.name.toLowerCase().includes(search.value.toLowerCase()));
});

const form = useForm({ service_id: null, link: '', quantity: '' });
const service = computed(() => props.categories.flatMap((c) => c.services).find((s) => s.id === form.service_id));
watch(categoryId, () => (form.service_id = null));

const orderForm = ref(null);
const linkInput = ref(null);
const choose = async (s) => {
    form.service_id = s.id;
    if (!form.quantity || form.quantity < s.min) form.quantity = s.min;
    await nextTick();
    // on phones the form sits below the list, so bring it into view
    if (window.matchMedia('(max-width: 1023px)').matches) orderForm.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    linkInput.value?.focus({ preventScroll: true });
};

// quick quantities that fit the chosen service's limits
const presets = computed(() => (service.value ? [100, 500, 1000, 5000, 10000].filter((q) => q >= service.value.min && q <= service.value.max) : []));

const charge = computed(() => (service.value && form.quantity ? Math.round(service.value.rate * form.quantity) / 1000 : 0));
const balance = computed(() => Number(page.props.auth.user.balance));
const short = computed(() => charge.value > balance.value);
const shortBy = computed(() => Math.max(0, charge.value - balance.value));
const placeholder = computed(() => (platform.value ? `https://${platform.value === 'twitter' ? 'x' : platform.value}.com/your-link` : 'https://'));
const submit = () => form.post('/new-order', { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <Head title="New order" />
    <PageHeader title="Place an order" subtitle="Choose a service, paste your link and pick a quantity. The price is shown before you pay." />

    <div class="grid gap-5 lg:grid-cols-5">
        <div class="card p-6 lg:col-span-3">
            <div class="-mx-1 mb-5 flex gap-2 overflow-x-auto px-1 pb-1">
                <button v-for="p in platforms" :key="p" class="shrink-0 rounded-full border px-4 py-1.5 text-sm transition"
                    :class="platform === p ? 'border-gold-500 bg-gold-500 font-semibold text-on-gold' : 'border-fg/10 text-mist hover:border-gold-500/40'" @click="platform = p">{{ label(p) }}</button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <select v-model="categoryId" class="input" aria-label="Category"><option v-for="c in cats" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                <input v-model="search" class="input" placeholder="Search services" aria-label="Search services" />
            </div>

            <ul class="mt-4 max-h-[28rem] space-y-2 overflow-y-auto pr-1">
                <li v-for="s in services" :key="s.id">
                    <button type="button" class="flex w-full items-center justify-between gap-4 rounded-xl border px-4 py-3.5 text-left transition"
                        :class="form.service_id === s.id ? 'border-gold-500/70 bg-gold-500/10 shadow-[0_0_0_3px_rgb(201_164_92/0.12)]' : 'border-fg/[0.07] bg-fg/[0.02] hover:border-gold-500/40 hover:bg-fg/[0.04]'" @click="choose(s)">
                        <span class="min-w-0"><span class="block font-medium leading-snug">{{ s.name }}</span>
                            <span class="mt-0.5 block text-xs text-mist">{{ number(s.min) }} to {{ number(s.max) }}<span v-if="s.refill"> · Refill guarantee</span><span v-if="s.drip_feed"> · Drip-feed</span></span></span>
                        <span class="shrink-0 text-right"><span class="price block text-3xl leading-none">{{ money(s.rate) }}</span><span class="mt-1 block text-[11px] text-mist">per 1,000</span></span>
                    </button>
                </li>
                <li v-if="!services.length" class="py-10 text-center text-sm text-mist">No services match your search.</li>
            </ul>
        </div>

        <form ref="orderForm" class="card h-fit scroll-mt-24 space-y-4 p-6 lg:sticky lg:top-24 lg:col-span-2" @submit.prevent="submit">
            <div>
                <h3 class="display text-2xl">{{ service?.name ?? 'Choose a service first' }}</h3>
                <p v-if="service" class="mt-1 text-xs text-mist">{{ money(service.rate) }} per 1,000<span v-if="service.description"> · {{ service.description }}</span></p>
                <p v-else class="mt-1 text-xs text-mist">Pick one from the list to see your total.</p>
            </div>

            <div><label class="label" for="o-link">Link to your profile or post</label>
                <input id="o-link" ref="linkInput" v-model="form.link" class="input" :placeholder="placeholder" required inputmode="url" autocomplete="off" />
                <p v-if="form.errors.link" class="mt-1 text-xs text-rose-300">{{ form.errors.link }}</p></div>

            <div><label class="label" for="o-qty">Quantity <span v-if="service" class="text-mist/70">(min {{ number(service.min) }}, max {{ number(service.max) }})</span></label>
                <input id="o-qty" v-model.number="form.quantity" type="number" :min="service?.min" :max="service?.max" class="input" required inputmode="numeric" />
                <div v-if="presets.length" class="mt-2 flex flex-wrap gap-1.5">
                    <button v-for="q in presets" :key="q" type="button" class="rounded-lg border border-fg/10 px-2.5 py-1 text-xs text-mist transition hover:border-gold-500/50 hover:text-ivory" :class="form.quantity === q ? '!border-gold-500 !text-gold-300' : ''" @click="form.quantity = q">{{ number(q) }}</button>
                </div>
                <p v-if="form.errors.quantity" class="mt-1 text-xs text-rose-300">{{ form.errors.quantity }}</p></div>

            <div class="rounded-xl border border-gold-500/25 bg-gold-500/10 p-4">
                <div class="flex items-end justify-between gap-3"><span class="text-sm text-mist">You pay</span><span class="price text-5xl leading-none">{{ money(charge) }}</span></div>
                <div class="mt-2 flex justify-between text-xs text-mist"><span>Wallet balance</span><span>{{ money(balance) }}</span></div>
            </div>
            <p v-if="form.errors.service_id" class="text-xs text-rose-300">{{ form.errors.service_id }}</p>
            <p v-if="short && service" class="rounded-lg border border-gold-500/30 bg-gold-500/10 p-3 text-xs text-gold-200">You need {{ money(shortBy) }} more in your wallet. <Link href="/wallet" class="font-semibold underline">Add money</Link></p>
            <button class="btn-gold w-full !py-3" :disabled="form.processing || !service || short">{{ form.processing ? 'Placing order…' : 'Place order' }}</button>
        </form>
    </div>
</template>
