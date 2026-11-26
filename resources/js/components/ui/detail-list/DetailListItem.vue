<script setup lang="ts">
defineProps<{ label?: string | null; value?: string | number | null }>();
</script>

<template>
    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:gap-4">
        <dt class="text-xs font-medium tracking-wide text-neutral-500 uppercase sm:pt-0.5 dark:text-neutral-400">
            <slot name="label">{{ label }}</slot>
        </dt>
        <dd class="text-sm text-neutral-900 sm:col-span-2 dark:text-neutral-100">
            <slot>{{ value }}</slot>
        </dd>
    </div>
</template>
