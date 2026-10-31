import '@fontsource-variable/inter';
import '@fontsource/cormorant-garamond/500.css';
import '@fontsource/cormorant-garamond/600.css';
import '@fontsource/cormorant-garamond/700.css';
import '../css/app.css';
import './composables/theme';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

let siteName = 'Monetize Hub';

// fade-and-rise when an element scrolls into view; skipped for visitors who prefer reduced motion
const reveal = {
    mounted(el, binding) {
        if (typeof IntersectionObserver === 'undefined' || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        el.classList.add('js-reveal');
        if (binding.value) el.style.setProperty('--reveal-delay', `${binding.value}ms`);
        const io = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                el.classList.add('is-visible');
                io.disconnect();
            }
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        io.observe(el);
    },
};

createInertiaApp({
    title: (title) => (!title ? siteName : title.includes(siteName) ? title : `${title} · ${siteName}`),
    // pages load on demand, so a visitor on the home page never downloads the admin charts
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        return pages[`./Pages/${name}.vue`]();
    },
    setup({ el, App, props, plugin }) {
        siteName = props.initialPage.props.site?.name ?? siteName;
        createApp({ render: () => h(App, props) }).use(plugin).directive('reveal', reveal).mount(el);
    },
    progress: { color: '#c9a45c' },
});
