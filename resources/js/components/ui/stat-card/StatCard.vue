<script setup lang="ts">
import { computed, inject } from 'vue';
import { statColors } from '../colors';
import Icon from '../icon/Icon.vue';
import Text from '../typography/Text.vue';
import { statAlignKey } from './context';

const props = withDefaults(
    defineProps<{
        icon?: string | null;
        value?: string | number | null;
        label?: string;
        title?: string | null;
        color?: string;
        variant?: 'default' | 'compact';
        /** A change such as "+12%" or "-3": green when it starts with +, red with -. */
        change?: string | null;
        description?: string | null;
    }>(),
    { icon: null, value: null, label: '', title: null, color: 'primary', variant: 'default', change: null, description: null },
);

const align = inject(
    statAlignKey,
    computed(() => 'start' as const),
);

const palette = computed(() => statColors[props.color] ?? statColors.primary);

// A zero recedes so the figures that matter stand out.
const isZero = computed(() => ['0', '0%', '0.0'].includes(String(props.value ?? '').trim()));

const changeClass = computed(() => {
    if (props.change?.startsWith('+')) return 'text-green-600 dark:text-green-500';
    if (props.change?.startsWith('-')) return 'text-red-600 dark:text-red-500';

    return 'text-gray-500 dark:text-neutral-400';
});
</script>

<template>
    <div v-if="variant === 'compact'" class="flex flex-col space-y-2 rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-50/5">
        <div class="flex items-center gap-x-2">
            <span v-if="icon" :class="['flex size-8 items-center justify-center rounded-full', palette.chip]">
                <Icon :name="icon" :class="palette.icon" />
            </span>
            <Text v-if="title" class="text-gray-500! dark:text-neutral-400!">{{ title }}</Text>
        </div>

        <div :class="['flex items-baseline gap-x-1.5', { start: 'justify-start', center: 'justify-center', end: 'justify-end' }[align]]">
            <p
                v-if="value !== null"
                :class="['text-2xl font-semibold tracking-tight tabular-nums sm:text-3xl', isZero ? 'text-neutral-400 dark:text-neutral-500' : 'text-neutral-900 dark:text-white']"
            >
                {{ value }}
            </p>
            <span v-if="change" :class="['inline-flex items-center gap-x-1 rounded-full text-sm', changeClass]">{{ change }}</span>
        </div>

        <Text v-if="description" class="text-xs text-gray-500! dark:text-neutral-400!">{{ description }}</Text>
    </div>

    <div
        v-else
        class="block rounded-xl border border-black/10 bg-white p-2 text-start text-xs whitespace-nowrap text-gray-800 shadow-xs sm:p-2.5 sm:text-sm 2xl:w-41.5 dark:border-white/10 dark:bg-neutral-800 dark:text-neutral-200"
    >
        <span class="flex h-full flex-col gap-y-2 sm:gap-y-3">
            <span class="flex justify-between gap-x-3">
                <span v-if="icon" :class="['flex size-7 shrink-0 items-center justify-center rounded-full sm:size-8', palette.chip]">
                    <Icon :name="icon" class="text-current!" />
                </span>
                <span v-if="value !== null" class="text-sm font-medium">{{ value }}</span>
            </span>
            <span class="block ps-1"><slot>{{ label }}</slot></span>
        </span>
    </div>
</template>
