<script setup lang="ts">
import { computed } from 'vue';
import { maxWidths } from '../colors';
import Heading from '../typography/Heading.vue';
import Text from '../typography/Text.vue';
import TableFooter from './TableFooter.vue';

const props = withDefaults(
    defineProps<{
        heading?: string | null;
        description?: string | null;
        rounded?: boolean;
        bordered?: boolean;
        size?: keyof typeof maxWidths | string;
        variant?: 'default' | 'results';
        footer?: string | null;
        footerTone?: 'neutral' | 'success' | 'warning' | 'danger';
    }>(),
    { heading: null, description: null, rounded: true, bordered: true, size: 'full', variant: 'default', footer: null, footerTone: 'neutral' },
);

const hasFooter = computed(() => !!props.footer);

const roundedClass = computed(() => (props.variant === 'results' || hasFooter.value ? 'rounded-t-xl' : props.rounded ? 'rounded-xl' : ''));
</script>

<template>
    <div :class="['space-y-0', maxWidths[size] ?? '']">
        <div v-if="heading || description" class="mb-4 space-y-2">
            <Heading v-if="heading" level="h4" size="">{{ heading }}</Heading>
            <Text v-if="description">{{ description }}</Text>
        </div>

        <div
            :class="[
                'overflow-x-auto dark:bg-gray-900',
                '[&::-webkit-scrollbar]:h-2 [&::-webkit-scrollbar-thumb]:rounded-none [&::-webkit-scrollbar-thumb]:bg-gray-300 [&::-webkit-scrollbar-track]:bg-gray-100 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500 dark:[&::-webkit-scrollbar-track]:bg-neutral-700',
                roundedClass,
                bordered ? 'border border-gray-200 dark:border-white/10' : '',
                hasFooter ? 'border-b-0' : '',
            ]"
        >
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                <slot />
            </table>
        </div>

        <TableFooter v-if="footer" :tone="footerTone">{{ footer }}</TableFooter>
    </div>
</template>
