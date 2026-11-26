<script setup lang="ts">
import { maxWidths } from '../colors';

withDefaults(defineProps<{ size?: keyof typeof maxWidths | string }>(), { size: 'md' });
</script>

<template>
    <div :class="['border border-black/10 bg-white dark:border-white/10 dark:bg-neutral-800', '[:where(&)]:rounded-[15px] [:where(&)]:p-4', maxWidths[size] ?? '']">
        <slot />
    </div>
</template>
