<script setup lang="ts">
withDefaults(defineProps<{ uppercase?: boolean; scope?: 'col' | 'row' | 'colgroup' | 'rowgroup'; align?: 'start' | 'center' | 'end' }>(), {
    uppercase: true,
    scope: 'col',
    align: 'start',
});

// Set through a prop because a text-end class passed in from outside loses to the default text-start.
const alignments = { start: 'text-start', center: 'text-center', end: 'text-end' } as const;
</script>

<template>
    <th :scope="scope" :class="['px-6 py-3 text-xs font-semibold whitespace-nowrap text-gray-500 dark:text-neutral-400', alignments[align], uppercase ? 'uppercase' : '']">
        <slot />
    </th>
</template>
