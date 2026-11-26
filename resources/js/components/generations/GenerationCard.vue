<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import GenerationPreview from '@/components/generations/GenerationPreview.vue';
import GenerationStatusBadge from '@/components/generations/GenerationStatusBadge.vue';
import { Text } from '@/components/ui';
import type { Generation } from '@/types/generations';

/**
 * One generation in the history grid: the image, its live status, and what it was
 * estimated to cost against what it actually cost. The prompt is only shown on
 * the detail page.
 */
defineProps<{ generation: Generation }>();
</script>

<template>
    <Link
        :href="`/generations/${generation.id}`"
        class="group flex flex-col gap-3 rounded-[15px] border border-black/10 bg-white p-3 transition-colors hover:border-black/20 focus-visible:ring-2 focus-visible:ring-neutral-900/15 focus-visible:outline-none dark:border-white/10 dark:bg-neutral-800 dark:hover:border-white/20 dark:focus-visible:ring-white/20"
    >
        <GenerationPreview :generation="generation" frame="square" />

        <div class="flex items-center justify-between gap-2">
            <GenerationStatusBadge :generation="generation" />
            <Text class="text-xs text-neutral-500! tabular-nums dark:text-neutral-400!">{{ generation.size }} · {{ generation.quality }}</Text>
        </div>

        <dl class="mt-auto flex items-start justify-between gap-2 border-t border-black/10 pt-3 text-sm dark:border-white/10">
            <div>
                <dt class="text-xs text-neutral-500 dark:text-neutral-400">Estimated</dt>
                <dd class="text-neutral-800 tabular-nums dark:text-neutral-200">{{ generation.estimated_charge }}</dd>
            </div>
            <div class="text-end">
                <dt class="text-xs text-neutral-500 dark:text-neutral-400">Actual</dt>
                <dd class="font-medium text-neutral-950 tabular-nums dark:text-neutral-50">
                    <template v-if="generation.actual_charge">{{ generation.actual_charge }}</template>
                    <span v-else class="font-normal text-neutral-500 dark:text-neutral-400">Pending</span>
                </dd>
            </div>
        </dl>
    </Link>
</template>
