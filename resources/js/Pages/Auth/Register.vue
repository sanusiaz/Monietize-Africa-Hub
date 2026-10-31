<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Create account" />
    <p class="eyebrow mb-3">Join us</p>
    <h1 class="display text-5xl">Create account</h1>

    <form class="mt-10 space-y-5" @submit.prevent="submit">
        <div v-for="f in [
            { k: 'name', l: 'Full name', t: 'text' },
            { k: 'email', l: 'Email', t: 'email' },
            { k: 'password', l: 'Password', t: 'password' },
            { k: 'password_confirmation', l: 'Confirm password', t: 'password' },
        ]" :key="f.k">
            <label class="label">{{ f.l }}</label>
            <input v-model="form[f.k]" :type="f.t" class="input" required />
            <p v-if="form.errors[f.k]" class="mt-1.5 text-xs text-rose-300">{{ form.errors[f.k] }}</p>
        </div>
        <p class="text-xs leading-relaxed text-mist">
            By creating an account you agree to our
            <Link href="/terms-and-conditions" class="text-gold-400 hover:text-gold-300">Terms &amp; Conditions</Link> and
            <Link href="/privacy-policy" class="text-gold-400 hover:text-gold-300">Privacy Policy</Link>.
        </p>
        <button class="btn-gold w-full" :disabled="form.processing">Create account</button>
    </form>

    <p class="mt-8 text-center text-sm text-mist">
        Already registered? <Link href="/login" class="text-gold-400 hover:text-gold-300">Sign in</Link>
    </p>
</template>
