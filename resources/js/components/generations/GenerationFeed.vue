<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import GenerationCard from '@/components/generations/GenerationCard.vue';
import type { Generation } from '@/types/generations';

/**
 * Card grid for history and the dashboard. `data` is the Inertia::scroll() prop.
 * The URL isn't updated, so a refresh starts from the newest images.
 */
defineProps<{
    data: string;
    generations: Generation[];
}>();
</script>

<template>
    <InfiniteScroll :data="data" :buffer="400" preserve-url only-next class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <GenerationCard v-for="generation in generations" :key="generation.id" :generation="generation" />

        <template #loading>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" role="status" aria-label="Loading more images">
                <div
                    v-for="index in 3"
                    :key="index"
                    class="flex animate-pulse flex-col gap-3 rounded-[15px] border border-black/10 bg-white p-3 motion-reduce:animate-none dark:border-white/10 dark:bg-neutral-800"
                    aria-hidden="true"
                >
                    <div class="aspect-square w-full rounded-box bg-neutral-100 dark:bg-neutral-900" />
                    <div class="flex items-center justify-between">
                        <div class="h-5 w-24 rounded-md bg-neutral-100 dark:bg-neutral-900" />
                        <div class="h-4 w-20 rounded-md bg-neutral-100 dark:bg-neutral-900" />
                    </div>
                    <div class="flex justify-between border-t border-black/10 pt-3 dark:border-white/10">
                        <div class="h-8 w-16 rounded-md bg-neutral-100 dark:bg-neutral-900" />
                        <div class="h-8 w-16 rounded-md bg-neutral-100 dark:bg-neutral-900" />
                    </div>
                </div>
            </div>
        </template>
    </InfiniteScroll>
</template>
