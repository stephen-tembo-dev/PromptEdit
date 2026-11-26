<script setup lang="ts">
import Button from '../button/Button.vue';
import Heading from '../typography/Heading.vue';
import Text from '../typography/Text.vue';

/**
 * The title bar at the top of a page. It stays pinned under the app header while the page scrolls,
 * so the page's actions are always in reach.
 *
 *   <PageHeader title="Projects" description="Everything you're working on" back="/dashboard">
 *       <template #badge><Badge color="emerald">Active</Badge></template>
 *       <template #actions>
 *           <Button variant="outline" color="neutral" icon="file-doc">Export</Button>
 *           <Button icon="plus">New project</Button>
 *       </template>
 *   </PageHeader>
 *
 * Actions read left to right from least to most important, so the primary action sits at the far right.
 */
withDefaults(defineProps<{ title?: string; description?: string | null; back?: string | null }>(), {
    title: 'Page Title',
    description: null,
    back: null,
});
</script>

<template>
    <header class="sticky top-14.5 z-30 -mx-1 -mt-3 mb-4 bg-gray-50/90 px-1 pt-3 pb-3 backdrop-blur-md lg:mb-6 dark:bg-neutral-900/90" data-slot="page-header">
        <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
            <!-- The actions wrap onto their own row once the title would have less than 18rem. -->
            <div class="flex min-w-[min(100%,18rem)] flex-1 items-start gap-2">
                <Button v-if="back" :href="back" class="mt-0.5 shrink-0 rounded-full ps-1.75! pe-1.75!" variant="ghost" size="sm" icon="caret-left" aria-label="Back" />

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <Heading level="h1" size="xl" class="tracking-tight text-balance">{{ title }}</Heading>
                        <slot name="badge" />
                    </div>
                    <Text v-if="description" class="mt-0.5 text-sm text-pretty opacity-70">{{ description }}</Text>
                </div>
            </div>

            <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2" data-slot="page-actions">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
