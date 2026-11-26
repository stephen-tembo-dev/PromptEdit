<script setup lang="ts">
withDefaults(defineProps<{ label?: string | null; labelHidden?: boolean; verticalSpace?: string }>(), {
    label: null,
    labelHidden: false,
    verticalSpace: 'space-y-4',
});
</script>

<template>
    <fieldset
        :class="[
            'rounded-box border border-black/10 p-5 text-start dark:border-white/10',
            // A fieldset that wraps fields spaces them itself.
            '[&>[data-slot=field]]:my-0 [&>[data-slot=field]:not(:first-of-type)]:my-2',
            verticalSpace,
        ]"
    >
        <legend v-if="label" :class="['-ms-2 mbe-0! px-2 text-sm leading-6 font-medium text-neutral-950 dark:text-white', labelHidden ? 'sr-only' : '']">
            {{ label }}
        </legend>
        <slot />
    </fieldset>
</template>
