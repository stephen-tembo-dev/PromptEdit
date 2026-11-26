<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { ToastMessage } from '@/composables/useToast';

const props = defineProps<{
    toast: ToastMessage;
    paused: boolean;
    progressBarVariant: 'full' | 'thin';
    progressBarAlignment: 'bottom' | 'top';
}>();

const emit = defineEmits<{ dismiss: [] }>();

const TYPES = {
    info: {
        text: 'text-gray-600 dark:text-gray-400',
        // color-mix keeps the tinted backgrounds solid rather than see-through.
        background: 'bg-[color-mix(in_oklab,_var(--color-gray-500)_20%,_var(--color-white)_80%)] dark:bg-[color-mix(in_oklab,_var(--color-gray-600)_10%,_var(--color-neutral-900)_90%)]',
        border: 'border-gray-500/55',
        label: 'Information',
        icon: 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
    },
    success: {
        text: 'text-green-600 dark:text-green-400',
        background: 'bg-[color-mix(in_oklab,_var(--color-green-500)_20%,_var(--color-white)_80%)] dark:bg-[color-mix(in_oklab,_var(--color-green-600)_10%,_var(--color-neutral-900)_90%)]',
        border: 'border-green-500/55',
        label: 'Success',
        icon: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
    error: {
        text: 'text-red-600 dark:text-red-400',
        background: 'bg-[color-mix(in_oklab,_var(--color-red-500)_25%,_var(--color-white)_75%)] dark:bg-[color-mix(in_oklab,_var(--color-red-600)_10%,_var(--color-neutral-900)_90%)]',
        border: 'border-red-500/55',
        label: 'Error',
        icon: 'm9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
    warning: {
        text: 'text-yellow-600 dark:text-yellow-400',
        background: 'bg-[color-mix(in_oklab,_var(--color-yellow-500)_25%,_var(--color-white)_75%)] dark:bg-[color-mix(in_oklab,_var(--color-yellow-600)_10%,_var(--color-neutral-900)_90%)]',
        border: 'border-yellow-500/55',
        label: 'Warning',
        icon: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
    },
} as const;

const config = computed(() => TYPES[props.toast.type] ?? TYPES.info);

const progress = ref(100);
let frame = 0;
let startTime = 0;
let pausedTotal = 0;
let pauseStart: number | null = null;

// Counts down on animation frames, leaving out time spent paused under the pointer.
function tick(now: number): void {
    if (props.paused && pauseStart === null) pauseStart = now;
    else if (!props.paused && pauseStart !== null) {
        pausedTotal += now - pauseStart;
        pauseStart = null;
    }

    if (!props.paused) {
        const elapsed = now - startTime - pausedTotal;
        progress.value = Math.max(0, 100 - (elapsed / props.toast.duration) * 100);

        if (elapsed >= props.toast.duration) {
            emit('dismiss');
            return;
        }
    }

    frame = requestAnimationFrame(tick);
}

onMounted(() => {
    if (props.toast.duration > 0) {
        startTime = performance.now();
        frame = requestAnimationFrame(tick);
    }
});

onBeforeUnmount(() => cancelAnimationFrame(frame));
</script>

<template>
    <div :class="['pointer-events-auto relative w-full max-w-sm overflow-hidden rounded-xl border border-gray-200 py-2 pr-2 pl-4 shadow-lg dark:border-white/15', config.background, config.border]">
        <div class="flex items-center">
            <div :class="['shrink-0', config.text]">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="config.icon" />
                </svg>
                <span class="sr-only">{{ config.label }}</span>
            </div>

            <div class="ml-3 w-0 flex-1">
                <p class="text-sm leading-5 font-medium break-words text-gray-900 dark:text-white">{{ toast.content }}</p>
            </div>

            <div class="ml-4 flex">
                <button
                    type="button"
                    class="z-10 cursor-pointer rounded-full p-2 text-gray-400 transition-colors hover:bg-white/5 focus:ring-0 focus:outline-none"
                    :aria-label="`Close ${config.label.toLowerCase()} notification`"
                    @click="emit('dismiss')"
                >
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <div v-if="toast.showProgress && toast.duration > 0" :class="['absolute right-0 left-0 h-full overflow-hidden', progressBarAlignment === 'top' ? 'top-0' : 'bottom-0']">
            <div
                :class="[progressBarVariant === 'full' ? 'h-full bg-current/10' : 'h-1 bg-current', 'transition-all duration-100 ease-linear', config.text, paused ? 'opacity-30' : 'opacity-50']"
                :style="{ width: `${progress}%` }"
            />
        </div>
    </div>
</template>
