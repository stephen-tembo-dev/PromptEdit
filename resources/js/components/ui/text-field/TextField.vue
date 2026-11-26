<script setup lang="ts">
defineProps<{ label: string; value?: string | number | null }>();
</script>

<template>
    <div class="space-y-1 text-sm">
        <p class="text-neutral-500 dark:text-neutral-400">{{ label }}</p>
        <p class="font-medium text-neutral-900 dark:text-neutral-100">
            <slot>{{ value === null || value === undefined || value === '' ? 'N/A' : value }}</slot>
        </p>
    </div>
</template>
