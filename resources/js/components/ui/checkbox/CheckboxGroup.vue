<script setup lang="ts">
import { computed, provide } from 'vue';
import { checkboxGroupKey, type CheckboxValue, type CheckboxVariant } from './context';

const props = withDefaults(defineProps<{ variant?: CheckboxVariant }>(), { variant: 'default' });

/** The values of the checked boxes. */
const model = defineModel<CheckboxValue[]>({ default: () => [] });

provide(checkboxGroupKey, { model, variant: computed(() => props.variant) });

const classes = computed(() => {
    switch (props.variant) {
        case 'pills':
            return 'flex flex-wrap gap-2';
        case 'cards':
            return 'flex flex-col gap-2';
        default:
            return [
                '[&>[data-slot=checkbox-wrapper]:not(:first-child)]:mt-3',
                // More room after a checkbox that has a description.
                '[&>[data-slot=checkbox-wrapper]:has([data-slot=checkbox-description])+[data-slot=checkbox-wrapper]]:mt-4',
            ];
    }
});
</script>

<template>
    <div :class="classes" role="group" data-slot="checkbox-group">
        <slot />
    </div>
</template>
