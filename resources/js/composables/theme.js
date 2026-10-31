import { ref } from 'vue';

const KEY = 'theme';
const media = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: light)') : null;

const read = () => {
    try { return localStorage.getItem(KEY); } catch { return null; }
};

const resolve = () => read() ?? (media?.matches ? 'light' : 'dark');

// 'light' | 'dark' — shared by every toggle and by the charts
export const theme = ref(typeof document !== 'undefined' ? (document.documentElement.classList.contains('light') ? 'light' : 'dark') : 'dark');

const apply = (value) => {
    theme.value = value;
    document.documentElement.classList.toggle('light', value === 'light');
};

export function toggleTheme() {
    const next = theme.value === 'light' ? 'dark' : 'light';
    try { localStorage.setItem(KEY, next); } catch { /* storage unavailable: still switch for this visit */ }
    apply(next);
}

// follow the device while the visitor has not chosen explicitly
media?.addEventListener('change', () => {
    if (!read()) apply(resolve());
});

if (typeof document !== 'undefined') apply(resolve());
