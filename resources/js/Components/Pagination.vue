<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ meta: Object });

// Laravel sends labels such as "&laquo; Previous"; show them as plain text arrows instead of injecting HTML
const text = (l) => l.label.replace('&laquo; Previous', '‹ Prev').replace('Next &raquo;', 'Next ›');
</script>

<template>
    <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between border-t border-fg/[0.06] px-5 py-4">
        <p class="text-xs text-mist">Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}</p>
        <div class="flex gap-1">
            <template v-for="(l, i) in meta.links" :key="i">
                <Link
                    v-if="l.url"
                    :href="l.url"
                    preserve-scroll
                    class="min-w-8 rounded-lg px-2.5 py-1.5 text-center text-xs transition"
                    :class="l.active ? 'bg-gold-500 font-semibold text-on-gold' : 'text-mist hover:bg-fg/5 hover:text-ivory'"
                >{{ text(l) }}</Link>
                <span v-else class="min-w-8 px-2.5 py-1.5 text-center text-xs text-mist/40">{{ text(l) }}</span>
            </template>
        </div>
    </div>
</template>
