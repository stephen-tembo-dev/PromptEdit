<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { errorIdFor, usePageErrors } from '../form/context';

const props = withDefaults(
    defineProps<{
        name?: string | null;
        rows?: number;
        resize?: 'none' | 'both' | 'horizontal' | 'vertical';
        disabled?: boolean;
        invalid?: boolean | null;
    }>(),
    { name: null, rows: 3, resize: 'vertical', disabled: false, invalid: null },
);

const model = defineModel<string | null>({ default: '' });

const pageErrors = usePageErrors(() => props.name);
const isInvalid = computed(() => props.invalid ?? pageErrors.value.length > 0);
const errorId = computed(() => (isInvalid.value ? errorIdFor(props.name) : undefined));

// Starts at `rows` lines and grows with its content.
const initialHeight = computed(() => `${props.rows * 1.5 + 0.75}rem`);

const textarea = ref<HTMLTextAreaElement | null>(null);
let observer: ResizeObserver | null = null;

function resize(): void {
    const el = textarea.value;
    if (!el) return;

    el.style.height = 'auto';
    const minimum = parseFloat(getComputedStyle(document.documentElement).fontSize) * (props.rows * 1.5 + 0.75);

    el.style.height = el.scrollHeight < minimum ? initialHeight.value : `${el.scrollHeight}px`;
}

onMounted(() => {
    if (!textarea.value) return;

    textarea.value.style.height = initialHeight.value;
    resize();

    observer = new ResizeObserver(() => resize());
    observer.observe(textarea.value);
    window.addEventListener('resize', resize);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    window.removeEventListener('resize', resize);
});

watch(model, () => nextTick(resize));

const resizeClass = computed(() => ({ none: 'resize-none', both: 'resize', horizontal: 'resize-x', vertical: 'resize-y' })[props.resize]);
</script>

<template>
    <textarea
        ref="textarea"
        v-model="model"
        :name="name ?? undefined"
        :rows="rows"
        :disabled="disabled"
        :aria-invalid="isInvalid ? 'true' : undefined"
        :aria-describedby="errorId"
        :class="[
            'inline-block w-full p-2 text-neutral-800 placeholder-neutral-400 disabled:text-neutral-500 disabled:placeholder-neutral-400/70 sm:text-sm dark:text-neutral-300 dark:placeholder-neutral-400 dark:disabled:text-neutral-400 dark:disabled:placeholder-neutral-500',
            'bg-white dark:bg-neutral-900 dark:disabled:bg-neutral-800',
            'transition-colors duration-200 disabled:cursor-not-allowed',
            'rounded-box border',
            'focus:ring-2 focus:ring-offset-0 focus:outline-none',
            isInvalid
                ? 'border-red-500 focus:border-red-500 focus:ring-red-500/25 dark:border-red-400 dark:focus:border-red-400 dark:focus:ring-red-400/25'
                : 'border-black/10 focus:border-black/15 focus:ring-neutral-900/15 dark:border-white/10 dark:focus:border-white/20 dark:focus:ring-neutral-100/15',
            resizeClass,
        ]"
        data-slot="textarea"
        @input="resize"
    />
</template>
