<script setup lang="ts">
import { computed } from 'vue';
import Icon from '../icon/Icon.vue';
import { errorIdFor, usePageErrors } from './context';

const props = withDefaults(
    defineProps<{
        /** Field name: shows Laravel's validation messages for it from the page's `errors`. */
        name?: string | null;
        /** Extra or manual messages, e.g. `form.errors.email` from useForm. */
        messages?: string | string[] | null;
    }>(),
    { name: null, messages: null },
);

const pageErrors = usePageErrors(() => props.name);

const errorMessages = computed(() => {
    const manual = props.messages == null ? [] : Array.isArray(props.messages) ? props.messages : [props.messages];

    return [...new Set([...pageErrors.value, ...manual].filter((message) => message))];
});

const errorId = computed(() => errorIdFor(props.name));
</script>

<template>
    <div
        v-if="errorMessages.length"
        :id="errorId"
        aria-live="polite"
        role="alert"
        class="mt-2 flex items-start gap-2 text-sm text-red-600 dark:text-red-400 [&>[data-slot=icon]]:!text-red-600 [&>[data-slot=icon]]:dark:!text-red-400"
        data-slot="error"
    >
        <Icon name="hi:exclamation-circle" class="mt-0.5 h-4 w-4 flex-shrink-0" />
        <div class="flex-1">
            <span v-if="errorMessages.length === 1">{{ errorMessages[0] }}</span>
            <ul v-else class="list-inside list-disc space-y-1">
                <li v-for="message in errorMessages" :key="message">{{ message }}</li>
            </ul>
        </div>
    </div>
</template>
