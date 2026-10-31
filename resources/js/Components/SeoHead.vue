<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';

// `noTitle`: private pages (account, admin, sign-in) set their own <Head title>, we only add robots
const props = defineProps({ noTitle: Boolean });

const page = usePage();
const seo = computed(() => page.props.seo);

// structured data lives in a plain <script> so it survives Inertia's head management; refresh it on navigation
const syncJsonLd = () => {
    let el = document.getElementById('seo-jsonld');
    const data = seo.value?.jsonld;
    if (!data) { el?.remove(); return; }
    if (!el) {
        el = document.createElement('script');
        el.type = 'application/ld+json';
        el.id = 'seo-jsonld';
        document.head.appendChild(el);
    }
    el.textContent = JSON.stringify(data);
};
onMounted(syncJsonLd);
watch(() => seo.value?.jsonld, syncJsonLd);
</script>

<template>
    <Head>
        <title v-if="!props.noTitle">{{ seo.title }}</title>
        <meta head-key="description" name="description" :content="seo.description" />
        <meta head-key="robots" name="robots" :content="seo.robots" />
        <link v-if="seo.canonical" head-key="canonical" rel="canonical" :href="seo.canonical" />
        <meta head-key="og:type" property="og:type" :content="seo.type" />
        <meta head-key="og:site_name" property="og:site_name" :content="seo.site_name" />
        <meta head-key="og:title" property="og:title" :content="seo.title" />
        <meta head-key="og:description" property="og:description" :content="seo.description" />
        <meta head-key="og:image" property="og:image" :content="seo.image" />
        <meta head-key="og:url" property="og:url" :content="seo.canonical ?? ''" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="seo.title" />
        <meta head-key="twitter:description" name="twitter:description" :content="seo.description" />
        <meta head-key="twitter:image" name="twitter:image" :content="seo.image" />
    </Head>
</template>
