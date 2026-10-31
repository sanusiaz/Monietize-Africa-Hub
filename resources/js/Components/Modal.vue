<script setup>
import { X } from 'lucide-vue-next';
import { onMounted, onUnmounted } from 'vue';

defineProps({ show: Boolean, title: String, wide: Boolean });
const emit = defineEmits(['close']);

const onKey = (e) => e.key === 'Escape' && emit('close');
onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-10">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="emit('close')" />
                <div class="card relative w-full" :class="wide ? 'max-w-2xl' : 'max-w-lg'">
                    <div class="flex items-center justify-between border-b border-fg/[0.06] px-6 py-4">
                        <h3 class="display text-2xl">{{ title }}</h3>
                        <button class="rounded-lg p-1.5 text-mist hover:bg-fg/5 hover:text-ivory" @click="emit('close')">
                            <X class="size-5" />
                        </button>
                    </div>
                    <div class="p-6"><slot /></div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
