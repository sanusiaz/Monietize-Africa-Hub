<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Sign in" />
    <p class="eyebrow mb-3">Welcome back</p>
    <h1 class="display text-5xl">Sign in</h1>
    <p class="mt-3 text-sm text-mist">Enter your credentials to access your dashboard.</p>

    <form class="mt-10 space-y-5" @submit.prevent="submit">
        <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" autocomplete="email" autofocus required />
            <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-300">{{ form.errors.email }}</p>
        </div>
        <div>
            <label class="label">Password</label>
            <input v-model="form.password" type="password" class="input" autocomplete="current-password" required />
        </div>
        <label class="flex items-center gap-2 text-sm text-mist">
            <input v-model="form.remember" type="checkbox" class="size-4 rounded border-fg/20 bg-ink-900 accent-[#c9a45c]" />
            Keep me signed in
        </label>
        <button class="btn-gold w-full" :disabled="form.processing">Sign in</button>
    </form>

    <p class="mt-8 text-center text-sm text-mist">
        New here? <Link href="/register" class="text-gold-400 hover:text-gold-300">Create an account</Link>
    </p>
</template>
