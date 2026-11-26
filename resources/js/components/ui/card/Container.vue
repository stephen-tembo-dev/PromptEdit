<script setup lang="ts">
import { maxWidths } from '../colors';

withDefaults(defineProps<{ size?: keyof typeof maxWidths | string }>(), { size: 'md' });
</script>

<template>
    <div :class="['m-auto bg-white dark:bg-neutral-900', maxWidths[size] ?? '']">
        <slot />
    </div>
</template>
