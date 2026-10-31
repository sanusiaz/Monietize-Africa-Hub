<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import Brand from '../Components/Brand.vue';
import SeoHead from '../Components/SeoHead.vue';
import ThemeToggle from '../Components/ThemeToggle.vue';

const page = usePage();
const points = [
    'Every price is shown per 1,000 before you order',
    'Pay from your wallet with Paystack',
    'Follow each order until it is delivered',
];
</script>

<template>
    <div class="grid min-h-screen lg:grid-cols-2">
        <SeoHead no-title />
        <div class="relative hidden overflow-hidden border-r border-fg/[0.06] lg:block">
            <div class="orb absolute -left-24 top-10 size-[420px] rounded-full bg-gold-500/15 blur-3xl" />
            <div class="relative flex h-full flex-col justify-between p-14">
                <Brand size="lg" />
                <div class="reveal-in">
                    <h2 class="display max-w-md text-5xl leading-[1.08]">Your orders, wallet and prices in one place.</h2>
                    <ul class="mt-8 space-y-3.5">
                        <li v-for="p in points" :key="p" class="flex items-start gap-3 text-mist">
                            <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-gold-500/15 text-gold-400"><Check class="size-3" stroke-width="3" /></span>{{ p }}
                        </li>
                    </ul>
                </div>
                <p class="text-xs text-mist/70">© {{ new Date().getFullYear() }} {{ page.props.site.company_name }} · <Link href="/contact" class="hover:text-gold-400">Contact</Link></p>
            </div>
        </div>
        <div class="relative flex flex-col items-center justify-center p-6 sm:p-12">
            <div class="absolute right-5 top-5"><ThemeToggle /></div>
            <div class="absolute left-5 top-5 lg:hidden"><Brand size="sm" /></div>
            <div class="reveal-in w-full max-w-md pt-10 lg:pt-0"><slot /></div>
        </div>
    </div>
</template>
