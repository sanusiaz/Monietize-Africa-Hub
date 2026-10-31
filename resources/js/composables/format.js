import { usePage } from '@inertiajs/vue3';

const symbols = { NGN: '₦', USD: '$', GHS: 'GH₵', ZAR: 'R', KES: 'KSh ' };

export function useFormat() {
    const page = usePage();

    const money = (value, compact = false) => {
        const symbol = symbols[page.props.site?.currency] ?? '';
        const n = Number(value ?? 0);
        const body = compact
            ? new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(n)
            : new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
        return `${symbol}${body}`;
    };

    const number = (value) => new Intl.NumberFormat('en').format(Number(value ?? 0));

    const date = (value) =>
        value
            ? new Date(value).toLocaleDateString('en', { day: 'numeric', month: 'short', year: 'numeric' })
            : '—';

    const datetime = (value) =>
        value
            ? new Date(value).toLocaleString('en', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
            : '—';

    const brands = { tiktok: 'TikTok', youtube: 'YouTube', twitter: 'X (Twitter)', whatsapp: 'WhatsApp', linkedin: 'LinkedIn' };
    const label = (value) => brands[String(value ?? '').toLowerCase()] ?? String(value ?? '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

    return { money, number, date, datetime, label };
}
