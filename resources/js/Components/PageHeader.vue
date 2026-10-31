<script setup>
defineProps({ eyebrow: String, title: String, subtitle: String });
</script>

<template>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-2">{{ eyebrow }}</p>
            <h1 class="display text-4xl sm:text-5xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-2 max-w-xl text-sm text-mist">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-3"><slot /></div>
    </div>
</template>
