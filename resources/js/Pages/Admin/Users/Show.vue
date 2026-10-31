<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ customer: Object, transactions: Array, orders: Array });
const { money, datetime } = useFormat();

const profile = useForm({
    name: props.customer.name,
    email: props.customer.email,
    role: props.customer.role,
    is_active: props.customer.is_active,
});
const wallet = useForm({ type: 'credit', amount: '', note: '' });

const saveProfile = () => profile.put(`/admin/users/${props.customer.id}`, { preserveScroll: true });
const adjust = () => wallet.post(`/admin/users/${props.customer.id}/wallet`, { preserveScroll: true, onSuccess: () => wallet.reset('amount', 'note') });
</script>

<template>
    <Head :title="customer.name" />
    <Link href="/admin/users" class="mb-4 inline-flex items-center gap-2 text-sm text-mist hover:text-gold-300"><ArrowLeft class="size-4" /> All customers</Link>
    <PageHeader eyebrow="Customer" :title="customer.name" :subtitle="customer.email" />

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card relative overflow-hidden p-6">
            <div class="pointer-events-none absolute -right-10 -top-10 size-40 rounded-full bg-gold-500/15 blur-3xl" />
            <p class="eyebrow">Wallet balance</p>
            <p class="display mt-3 text-5xl text-gold-200">{{ money(customer.balance) }}</p>

            <form class="mt-6 space-y-3" @submit.prevent="adjust">
                <div class="grid grid-cols-2 gap-1 rounded-xl bg-ink-900 p-1">
                    <button v-for="t in ['credit', 'debit']" :key="t" type="button" class="rounded-lg py-2 text-sm font-medium capitalize transition"
                        :class="wallet.type === t ? 'bg-gold-500 text-on-gold' : 'text-mist'" @click="wallet.type = t">{{ t }}</button>
                </div>
                <input v-model="wallet.amount" type="number" min="1" step="0.01" class="input" placeholder="Amount" required />
                <input v-model="wallet.note" class="input" placeholder="Note (optional)" />
                <p v-if="wallet.errors.amount" class="text-xs text-rose-300">{{ wallet.errors.amount }}</p>
                <button class="btn-gold w-full" :disabled="wallet.processing">Adjust wallet</button>
            </form>
        </div>

        <form class="card space-y-4 p-6 lg:col-span-2" @submit.prevent="saveProfile">
            <div><p class="eyebrow">Account</p><h3 class="display text-2xl">Profile & access</h3></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Name</label><input v-model="profile.name" class="input" required /></div>
                <div><label class="label">Email</label><input v-model="profile.email" type="email" class="input" required /><p v-if="profile.errors.email" class="mt-1 text-xs text-rose-300">{{ profile.errors.email }}</p></div>
                <div>
                    <label class="label">Role</label>
                    <select v-model="profile.role" class="input"><option value="customer">Customer</option><option value="admin">Admin</option></select>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select v-model="profile.is_active" class="input"><option :value="true">Active</option><option :value="false">Suspended</option></select>
                </div>
            </div>
            <button class="btn-gold" :disabled="profile.processing">Save changes</button>
        </form>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="px-6 pb-3 pt-6"><p class="eyebrow">Ledger</p><h3 class="display text-2xl">Wallet transactions</h3></div>
            <table class="w-full"><tbody class="divide-y divide-fg/[0.05]">
                <tr v-for="t in transactions" :key="t.id">
                    <td class="td"><p>{{ t.description }}</p><p class="text-xs text-mist">{{ datetime(t.created_at) }}</p></td>
                    <td class="td text-right font-medium" :class="t.type === 'credit' ? 'text-emerald-300' : 'text-rose-300'">{{ t.type === 'credit' ? '+' : '−' }}{{ money(t.amount) }}</td>
                    <td class="td text-right text-xs text-mist">Bal {{ money(t.balance_after) }}</td>
                </tr>
                <tr v-if="!transactions.length"><td class="td py-10 text-center text-mist">No transactions yet.</td></tr>
            </tbody></table>
        </div>
        <div class="card overflow-hidden">
            <div class="px-6 pb-3 pt-6"><p class="eyebrow">Activity</p><h3 class="display text-2xl">Recent orders</h3></div>
            <table class="w-full"><tbody class="divide-y divide-fg/[0.05]">
                <tr v-for="o in orders" :key="o.id">
                    <td class="td"><p>{{ o.service?.name }}</p><p class="max-w-[220px] truncate text-xs text-mist">#{{ o.id }} · {{ o.link }}</p></td>
                    <td class="td text-right text-gold-300">{{ money(o.charge) }}</td>
                    <td class="td text-right"><StatusBadge :status="o.status" /></td>
                </tr>
                <tr v-if="!orders.length"><td class="td py-10 text-center text-mist">No orders yet.</td></tr>
            </tbody></table>
        </div>
    </div>
</template>
