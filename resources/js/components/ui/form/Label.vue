<script setup lang="ts">
import { computed } from 'vue';
import { useField } from './context';

const props = withDefaults(
    defineProps<{
        text?: string | null;
        /** The control this label names. */
        for?: string;
        /** Shows the required asterisk. Inherited from a surrounding <Field required>. */
        required?: boolean | null;
    }>(),
    { text: null, for: undefined, required: null },
);

const field = useField();

const isRequired = computed(() => props.required ?? field?.required.value ?? false);
</script>

<template>
    <label
        :for="props.for"
        class="block text-sm font-medium select-none [:where(&)]:text-start [:where(&)]:text-neutral-800 [:where(&)]:dark:text-white"
        data-slot="label"
    >
        <slot>{{ text }}</slot>
        <span v-if="isRequired" class="px-1 py-1 text-xs text-red-500" aria-hidden="true">*</span>
    </label>
</template>
