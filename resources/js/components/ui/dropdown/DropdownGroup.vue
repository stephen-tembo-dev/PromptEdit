<script setup lang="ts">
defineProps<{ label?: string | null }>();
</script>

<template>
    <div class="contents space-y-(--dropdown-padding) pt-1" role="group">
        <div v-if="label" class="col-span-full px-[calc(--spacing(1.5)_+_var(--dropdown-padding))] text-start text-xs font-medium tracking-wide text-neutral-500 dark:text-neutral-400">
            {{ label }}
        </div>
        <slot />
    </div>
</template>
