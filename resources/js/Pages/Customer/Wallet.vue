<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useFormat } from '../../composables/format';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';

defineOptions({ layout: CustomerLayout });
const props = defineProps({ balance: [String, Number], minTopup: Number, transactions: Array });
const { money, datetime } = useFormat();

const form = useForm({ amount: props.minTopup });
const topup = () => form.post('/wallet/topup');
</script>

<template>
    <Head title="Wallet" />
    <PageHeader eyebrow="Funds" title="My wallet" subtitle="Top up securely with Paystack. Your balance is available instantly." />

    <div class="grid gap-5 lg:grid-cols-5">
        <div class="card relative overflow-hidden p-8 lg:col-span-2">
            <div class="pointer-events-none absolute -right-10 -top-10 size-44 rounded-full bg-gold-500/15 blur-3xl" />
            <p class="eyebrow">Available balance</p>
            <p class="display mt-3 text-6xl text-gold-200">{{ money(balance) }}</p>
            <form class="mt-8 space-y-3" @submit.prevent="topup">
                <label class="label">Amount to add</label>
                <input v-model="form.amount" type="number" :min="minTopup" class="input" required />
                <p v-if="form.errors.amount" class="text-xs text-rose-300">{{ form.errors.amount }}</p>
                <button class="btn-gold w-full" :disabled="form.processing">Fund with Paystack</button>
                <p class="text-center text-xs text-mist">Minimum {{ money(minTopup) }}</p>
            </form>
        </div>

        <div class="card overflow-hidden lg:col-span-3">
            <div class="px-6 pb-3 pt-6"><p class="eyebrow">Ledger</p><h3 class="display text-2xl">Transactions</h3></div>
            <ul class="divide-y divide-fg/[0.05]">
                <li v-for="t in transactions" :key="t.id" class="flex items-center justify-between gap-3 px-6 py-3.5">
                    <div class="min-w-0"><p class="truncate text-sm">{{ t.description }}</p><p class="text-xs text-mist">{{ datetime(t.created_at) }}</p></div>
                    <div class="flex shrink-0 items-center gap-3"><StatusBadge :status="t.type" /><span class="w-24 text-right text-sm font-medium">{{ money(t.amount) }}</span></div>
                </li>
                <li v-if="!transactions.length" class="px-6 py-12 text-center text-sm text-mist">No transactions yet.</li>
            </ul>
        </div>
    </div>
</template>
