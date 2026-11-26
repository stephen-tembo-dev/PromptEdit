<script setup lang="ts">
import { computed, provide } from 'vue';
import { statAlignKey } from './context';

const props = withDefaults(
    defineProps<{
        /** Columns per breakpoint, 1 to 6. */
        cols?: { default?: number; sm?: number; md?: number; lg?: number; xl?: number };
        flex?: boolean;
        align?: 'start' | 'center' | 'end';
    }>(),
    { cols: () => ({ default: 1, sm: 2, lg: 4 }), flex: false, align: 'start' },
);

provide(
    statAlignKey,
    computed(() => props.align),
);

// Spelled out so Tailwind sees every class.
const COLS: Record<string, Record<number, string>> = {
    default: { 1: 'grid-cols-1', 2: 'grid-cols-2', 3: 'grid-cols-3', 4: 'grid-cols-4', 5: 'grid-cols-5', 6: 'grid-cols-6' },
    sm: { 1: 'sm:grid-cols-1', 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-3', 4: 'sm:grid-cols-4', 5: 'sm:grid-cols-5', 6: 'sm:grid-cols-6' },
    md: { 1: 'md:grid-cols-1', 2: 'md:grid-cols-2', 3: 'md:grid-cols-3', 4: 'md:grid-cols-4', 5: 'md:grid-cols-5', 6: 'md:grid-cols-6' },
    lg: { 1: 'lg:grid-cols-1', 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4', 5: 'lg:grid-cols-5', 6: 'lg:grid-cols-6' },
    xl: { 1: 'xl:grid-cols-1', 2: 'xl:grid-cols-2', 3: 'xl:grid-cols-3', 4: 'xl:grid-cols-4', 5: 'xl:grid-cols-5', 6: 'xl:grid-cols-6' },
};

const colClasses = computed(() =>
    Object.entries(props.cols)
        .map(([breakpoint, count]) => COLS[breakpoint]?.[count as number] ?? '')
        .join(' '),
);
</script>

<template>
    <div :class="['grid w-full gap-2 md:gap-4', colClasses, flex ? '2xl:flex 2xl:flex-wrap' : '']">
        <slot />
    </div>
</template>
