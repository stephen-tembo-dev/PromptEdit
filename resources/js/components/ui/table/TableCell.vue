<script setup lang="ts">
withDefaults(defineProps<{ align?: 'start' | 'center' | 'end' }>(), { align: 'start' });

const alignments = { start: 'text-start', center: 'text-center', end: 'text-end' } as const;
</script>

<template>
    <td :class="['max-w-72 px-6 py-4 text-sm text-gray-800 dark:text-neutral-200', alignments[align]]">
        <slot />
    </td>
</template>
