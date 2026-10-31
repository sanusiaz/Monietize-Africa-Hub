<script setup>
import { TrendingDown, TrendingUp } from 'lucide-vue-next';

defineProps({ label: String, value: String, hint: String, change: Number, icon: Object });
</script>

<template>
    <div class="card group relative overflow-hidden p-5">
        <div class="pointer-events-none absolute -right-8 -top-8 size-28 rounded-full bg-gold-500/10 blur-2xl transition group-hover:bg-gold-500/20" />
        <div class="flex items-start justify-between">
            <p class="eyebrow">{{ label }}</p>
            <span class="grid size-9 place-items-center rounded-xl border border-gold-500/20 bg-gold-500/10 text-gold-400">
                <component :is="icon" class="size-4" />
            </span>
        </div>
        <p class="display mt-4 text-4xl">{{ value }}</p>
        <div class="mt-2 flex items-center gap-2 text-xs text-mist">
            <span
                v-if="change !== null && change !== undefined"
                class="inline-flex items-center gap-1 font-medium"
                :class="change >= 0 ? 'text-emerald-400' : 'text-rose-400'"
            >
                <component :is="change >= 0 ? TrendingUp : TrendingDown" class="size-3.5" />
                {{ Math.abs(change) }}%
            </span>
            <span>{{ hint }}</span>
        </div>
    </div>
</template>
