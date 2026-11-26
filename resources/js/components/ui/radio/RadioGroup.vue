<script setup lang="ts">
import { computed, provide, useId } from 'vue';
import { usePageErrors } from '../form/context';
import { radioGroupKey, type RadioValue, type RadioVariant } from './context';

const props = withDefaults(
    defineProps<{
        name?: string | null;
        label?: string | null;
        /** An error to show under the group. Defaults to Laravel's validation error for `name`. */
        error?: string | null;
        direction?: 'vertical' | 'horizontal';
        disabled?: boolean;
        variant?: RadioVariant;
        labelClass?: string;
        indicator?: boolean;
    }>(),
    { name: null, label: null, error: null, direction: 'vertical', disabled: false, variant: 'default', labelClass: '', indicator: true },
);

const model = defineModel<RadioValue>({ default: null });

const id = useId();
const groupName = computed(() => props.name ?? `radio-${id}`);

provide(radioGroupKey, {
    model,
    name: groupName,
    variant: computed(() => props.variant),
    disabled: computed(() => props.disabled),
    indicator: computed(() => props.indicator),
});

const pageErrors = usePageErrors(() => props.name);
const errorMessage = computed(() => props.error ?? pageErrors.value[0] ?? null);
</script>

<template>
    <div class="w-full text-start">
        <label v-if="label" :id="`${id}-label`" :class="['mb-4 inline-block font-semibold text-gray-800 dark:text-gray-300', labelClass]">
            {{ label }}
        </label>

        <div
            role="radiogroup"
            :aria-labelledby="label ? `${id}-label` : undefined"
            :class="[
                direction === 'vertical' ? 'space-y-2' : 'flex items-stretch gap-1',
                variant === 'segmented' ? 'w-fit rounded-box bg-neutral-200 p-1 dark:bg-neutral-800' : '',
                variant === 'cards' ? 'p-1' : '',
            ]"
        >
            <slot />
        </div>

        <p v-if="errorMessage" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">{{ errorMessage }}</p>
    </div>
</template>
