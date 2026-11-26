<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import GenerationCard from '@/components/generations/GenerationCard.vue';
import { Button, Card, Empty, EmptyContents, EmptyMedia, Heading, Icon, PageHeader, Text } from '@/components/ui';
import { useLiveGenerations } from '@/composables/generations/useLiveGenerations';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Generation, Paginated } from '@/types/generations';

const props = defineProps<{
    generations: Paginated<Generation>;
    hasUnfinished: boolean;
}>();

const page = usePage();

useLiveGenerations(() => props.hasUnfinished, ['generations', 'hasUnfinished']);
</script>

<template>
    <AppLayout title="History">
        <PageHeader title="History" description="Every image you have generated, with its estimated and actual cost.">
            <template v-if="page.props.auth.can.generateImages" #actions>
                <Button href="/generations/create" icon="magic-wand">Generate</Button>
            </template>
        </PageHeader>

        <Card v-if="generations.data.length === 0" size="full">
            <Empty>
                <EmptyMedia>
                    <span class="grid size-12 place-items-center rounded-full bg-primary-500/10">
                        <Icon name="images" class="size-6 text-primary-700! dark:text-primary-400!" />
                    </span>
                </EmptyMedia>
                <EmptyContents>
                    <Heading size="md">No images yet</Heading>
                    <Text class="max-w-sm text-neutral-500! dark:text-neutral-400!">Your generations will appear here, with their status updating live.</Text>
                    <Button v-if="page.props.auth.can.generateImages" href="/generations/create" class="mt-2" icon="magic-wand">Generate your first image</Button>
                </EmptyContents>
            </Empty>
        </Card>

        <div v-else class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <GenerationCard v-for="generation in generations.data" :key="generation.id" :generation="generation" />
            </div>

            <nav v-if="generations.meta.last_page > 1" class="flex items-center justify-between gap-4" aria-label="Pages">
                <Text class="text-sm text-neutral-500! tabular-nums dark:text-neutral-400!">
                    {{ generations.meta.from }}–{{ generations.meta.to }} of {{ generations.meta.total }}
                </Text>
                <div class="flex gap-2">
                    <Button :href="generations.links.prev ?? undefined" :disabled="!generations.links.prev" variant="outline" color="neutral" size="sm" icon="caret-left">Newer</Button>
                    <Button :href="generations.links.next ?? undefined" :disabled="!generations.links.next" variant="outline" color="neutral" size="sm" icon-after="caret-right">Older</Button>
                </div>
            </nav>
        </div>
    </AppLayout>
</template>
