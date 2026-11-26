<script setup lang="ts">
defineProps<{ heading?: string | null }>();
</script>

<template>
    <div class="overflow-hidden rounded-box border border-black/10 dark:border-white/10">
        <div v-if="heading" class="border-b border-black/10 bg-neutral-50 px-4 py-3 dark:border-white/10 dark:bg-white/5">
            <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ heading }}</h4>
        </div>

        <dl class="divide-y divide-black/10 dark:divide-white/10">
            <slot />
        </dl>
    </div>
</template>
