<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '../icon/Icon.vue';

const props = withDefaults(
    defineProps<{
        title?: string;
        /** Messages to list. Defaults to every validation error on the page. */
        messages?: string[] | Record<string, string | string[]> | null;
    }>(),
    { title: 'Please fix the following fields', messages: null },
);

const page = usePage();

const summaryMessages = computed(() => {
    const source = props.messages ?? (page.props.errors as Record<string, string | string[]> | undefined) ?? {};
    const values = Array.isArray(source) ? source : Object.values(source);

    return [...new Set(values.flat().filter((message) => message))];
});
</script>

<template>
    <div
        v-if="summaryMessages.length"
        class="rounded-box border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
        data-slot="validation-summary"
        role="alert"
        aria-live="polite"
    >
        <div class="flex items-start gap-2">
            <Icon name="hi:exclamation-circle" class="mt-0.5 size-4 shrink-0 !text-red-600 dark:!text-red-300" />
            <div class="min-w-0 flex-1">
                <p class="font-medium">{{ title }}</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    <li v-for="message in summaryMessages" :key="message">{{ message }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>
