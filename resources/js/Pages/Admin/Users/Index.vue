<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useFormat } from '../../../composables/format';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ users: Object, filters: Object });
const { money, date } = useFormat();

const search = ref(props.filters.search ?? '');
let timer;
watch(search, (v) => {
    clearTimeout(timer);
    timer = setTimeout(() => router.get('/admin/users', { search: v || undefined }, { preserveState: true, replace: true }), 300);
});
</script>

<template>
    <Head title="Customers" />
    <PageHeader eyebrow="People" title="Customers" subtitle="Every account, wallet balance and order count in one view.">
        <div class="relative w-64">
            <Search class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-mist" />
            <input v-model="search" class="input !pl-10" placeholder="Search name or email" />
        </div>
    </PageHeader>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-fg/[0.06]">
                    <tr><th class="th">Customer</th><th class="th">Role</th><th class="th">Wallet</th><th class="th">Orders</th><th class="th">Joined</th><th class="th">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-fg/[0.05]">
                    <tr v-for="u in users.data" :key="u.id" class="transition hover:bg-fg/[0.02]">
                        <td class="td">
                            <Link :href="`/admin/users/${u.id}`" class="flex items-center gap-3">
                                <span class="grid size-9 place-items-center rounded-full bg-gold-500/15 text-sm font-semibold text-gold-300">{{ u.name.charAt(0) }}</span>
                                <span><span class="block font-medium hover:text-gold-300">{{ u.name }}</span><span class="text-xs text-mist">{{ u.email }}</span></span>
                            </Link>
                        </td>
                        <td class="td"><StatusBadge :status="u.role" /></td>
                        <td class="td font-medium text-gold-300">{{ money(u.balance) }}</td>
                        <td class="td">{{ u.orders_count }}</td>
                        <td class="td text-mist">{{ date(u.created_at) }}</td>
                        <td class="td"><StatusBadge :status="u.is_active ? 'active' : 'suspended'" /></td>
                    </tr>
                    <tr v-if="!users.data.length"><td colspan="6" class="td py-12 text-center text-mist">No customers found.</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :meta="users" />
    </div>
</template>
