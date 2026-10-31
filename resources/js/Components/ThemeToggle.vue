<script setup>
import { Moon, Sun } from 'lucide-vue-next';
import { theme, toggleTheme } from '../composables/theme';
</script>

<template>
    <button
        type="button"
        class="grid size-9 place-items-center rounded-xl border border-fg/10 bg-fg/[0.03] text-mist transition hover:border-gold-500/40 hover:text-gold-400"
        :title="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
        :aria-label="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
        @click="toggleTheme"
    >
        <Moon v-if="theme === 'light'" class="size-[18px]" />
        <Sun v-else class="size-[18px]" />
    </button>
</template>
