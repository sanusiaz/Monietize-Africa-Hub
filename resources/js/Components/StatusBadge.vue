<script setup>
import { computed } from 'vue';
import { useFormat } from '../composables/format';

const props = defineProps({ status: String });
const { label } = useFormat();

const tone = computed(
    () =>
        ({
            completed: 'emerald',
            success: 'emerald',
            active: 'emerald',
            credit: 'emerald',
            pending: 'amber',
            processing: 'sky',
            in_progress: 'sky',
            partial: 'violet',
            canceled: 'rose',
            refunded: 'rose',
            failed: 'rose',
            abandoned: 'rose',
            debit: 'rose',
            suspended: 'rose',
            admin: 'gold',
            customer: 'slate',
        })[props.status] ?? 'slate',
);

const styles = {
    emerald: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/25',
    amber: 'bg-amber-500/10 text-amber-300 ring-amber-500/25',
    sky: 'bg-sky-500/10 text-sky-300 ring-sky-500/25',
    violet: 'bg-violet-500/10 text-violet-300 ring-violet-500/25',
    rose: 'bg-rose-500/10 text-rose-300 ring-rose-500/25',
    gold: 'bg-gold-500/10 text-gold-300 ring-gold-500/30',
    slate: 'bg-fg/5 text-mist ring-fg/10',
};
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset" :class="styles[tone]">
        <span class="size-1.5 rounded-full bg-current opacity-80" />
        {{ label(status) }}
    </span>
</template>
