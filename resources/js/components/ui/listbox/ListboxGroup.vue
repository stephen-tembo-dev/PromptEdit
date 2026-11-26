<script setup lang="ts">
defineProps<{ label?: string | null }>();
</script>

<template>
    <li role="presentation" class="group col-span-full grid grid-cols-subgrid pt-1">
        <div
            v-if="label"
            class="col-span-full mb-0.5 px-[calc(--spacing(1.5)_+_var(--popup-padding))] text-start text-sm font-medium tracking-wide text-neutral-500 dark:text-neutral-400"
            role="presentation"
        >
            {{ label }}
        </div>
        <ul role="group" :aria-label="label ?? undefined" class="col-span-full grid grid-cols-subgrid gap-y-1">
            <slot />
        </ul>
    </li>
</template>
