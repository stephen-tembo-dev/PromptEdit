<script setup lang="ts">
import { computed } from 'vue';
import { usePageErrors } from '../form/context';

defineOptions({ inheritAttrs: false });

/** A plain browser checkbox styled to match (the Blade kit's "stash" checkbox). */
const props = withDefaults(defineProps<{ name?: string | null; label?: string | null; description?: string | null }>(), {
    name: null,
    label: null,
    description: null,
});

const model = defineModel<boolean>({ default: false });

const pageErrors = usePageErrors(() => props.name);
const invalid = computed(() => pageErrors.value.length > 0);
</script>

<template>
    <label class="inline-flex items-start gap-3 text-start">
        <input
            v-model="model"
            type="checkbox"
            :name="name ?? undefined"
            v-bind="$attrs"
            :class="[
                'mt-0.5 h-4 w-4 rounded border border-black/20 text-primary-700 shadow-sm transition focus:ring-2 focus:ring-primary-200 focus:ring-offset-0',
                invalid ? 'border-red-500 focus:ring-red-200' : '',
            ]"
            data-slot="control"
        />

        <span v-if="label || description || $slots.default" class="space-y-1">
            <span v-if="label || $slots.default" class="block text-sm font-medium text-neutral-800 dark:text-white">
                <slot>{{ label }}</slot>
            </span>
            <span v-if="description" class="block text-sm text-neutral-500 dark:text-neutral-400">{{ description }}</span>
        </span>
    </label>
</template>
