<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{ tone?: 'neutral' | 'success' | 'warning' | 'danger' }>(), { tone: 'neutral' });

const toneClasses = computed(
    () =>
        ({
            success: ['border-green-200 bg-green-50 dark:border-green-500/20 dark:bg-green-500/10', 'text-green-800 dark:text-green-400'],
            danger: ['border-red-200 bg-red-50 dark:border-red-500/20 dark:bg-red-500/10', 'text-red-800 dark:text-red-400'],
            warning: ['border-amber-200 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10', 'text-amber-800 dark:text-amber-400'],
            neutral: ['border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5', 'text-gray-500 dark:text-neutral-400'],
        })[props.tone],
);
</script>

<template>
    <div :class="['rounded-b-xl border border-t-0 px-6 py-3', toneClasses[0]]">
        <p :class="['text-sm font-medium', toneClasses[1]]"><slot /></p>
    </div>
</template>
