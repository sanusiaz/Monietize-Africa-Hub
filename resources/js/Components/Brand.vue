<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ size: { type: String, default: 'md' }, noText: Boolean });
const page = usePage();

// "Monetize Hub": everything but the last word in the text colour, the last word in gold, as in the logo
const parts = computed(() => {
    const words = String(page.props.site.name).trim().split(/\s+/);
    return words.length > 1 ? { head: words.slice(0, -1).join(' '), tail: words.at(-1) } : { head: words[0], tail: '' };
});
const dims = { sm: 'h-8 text-lg', md: 'h-9 text-xl', lg: 'h-11 text-2xl' };
</script>

<template>
    <Link href="/" class="group inline-flex items-center gap-2.5" :aria-label="`${page.props.site.name} home`">
        <!-- dark tile version of the mark: the silver ring stays visible on light and dark pages alike -->
        <img src="/images/icon-192.png" alt="" width="40" height="40" class="aspect-square w-auto rounded-[22%] shadow-sm transition duration-300 group-hover:scale-110 group-hover:-rotate-3" :class="dims[props.size].split(' ')[0]" />
        <span v-if="!noText" class="font-sans font-bold leading-none tracking-tight text-ivory" :class="dims[props.size].split(' ')[1]">
            {{ parts.head }}<span v-if="parts.tail" class="text-gold-500"> {{ parts.tail }}</span>
        </span>
    </Link>
</template>
