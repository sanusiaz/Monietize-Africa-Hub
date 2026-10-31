<script setup>
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

/**
 * Loads the live-chat widget the admin configured (Admin → Settings → Live chat).
 * Renders nothing itself. The provider's script is added once per visit, and only while chat is enabled.
 */
const page = usePage();
let loaded = null;

const addScript = (attrs, text = '') => {
    const s = document.createElement('script');
    Object.entries(attrs).forEach(([k, v]) => (v === true ? s.setAttribute(k, '') : s.setAttribute(k, v)));
    if (text) s.text = text;
    document.body.appendChild(s);
};

const load = (chat) => {
    if (!chat || loaded || typeof window === 'undefined') return;
    loaded = chat.provider;

    if (chat.provider === 'tawk') {
        window.Tawk_API = window.Tawk_API || {};
        window.Tawk_LoadStart = new Date();
        addScript({ async: true, src: `https://embed.tawk.to/${chat.property}/${chat.widget || 'default'}`, charset: 'UTF-8', crossorigin: '*' });
    } else if (chat.provider === 'tidio') {
        addScript({ async: true, src: `https://code.tidio.co/${chat.key}.js` });
    } else if (chat.provider === 'crisp') {
        window.$crisp = [];
        window.CRISP_WEBSITE_ID = chat.id;
        addScript({ async: true, src: 'https://client.crisp.chat/l.js' });
    } else if (chat.provider === 'custom') {
        // admin-supplied embed code: re-create each <script> so the browser actually runs it
        const doc = new DOMParser().parseFromString(chat.code, 'text/html');
        doc.querySelectorAll('script').forEach((old) => {
            const attrs = {};
            [...old.attributes].forEach((a) => (attrs[a.name] = a.value));
            addScript(attrs, old.textContent ?? '');
        });
    }
};

watch(() => page.props.chat, load, { immediate: true });
</script>

<template><span hidden /></template>
