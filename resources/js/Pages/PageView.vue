<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import PublicLayout from '../Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });
defineProps({ page: Object });
</script>

<template>
    <article class="mx-auto max-w-3xl px-5 py-14 sm:py-20">
        <Link href="/" class="mb-8 inline-flex items-center gap-2 text-sm text-mist hover:text-gold-400"><ArrowLeft class="size-4" /> Back to home</Link>
        <h1 class="display text-5xl sm:text-6xl">{{ page.title }}</h1>
        <div class="card mt-10 p-7 sm:p-10">
            <!-- html is rendered server-side from admin markdown with raw HTML stripped -->
            <div class="prose-doc" v-html="page.html" />
        </div>
    </article>
</template>
