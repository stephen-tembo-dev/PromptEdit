<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import GenerationCard from '@/components/generations/GenerationCard.vue';
import { Button, Card, Empty, EmptyContents, EmptyMedia, Heading, Icon, PageHeader, StatCard, StatCardGroup, Text } from '@/components/ui';
import { useLiveGenerations } from '@/composables/generations/useLiveGenerations';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Generation } from '@/types/generations';

const props = defineProps<{
    stats: {
        images: number;
        images_this_month: number;
        spent: string;
        spent_this_month: string;
        /** Null until the first image is finished. */
        average_cost: string | null;
        unfinished: number;
    };
    recent: Generation[];
    hasUnfinished: boolean;
}>();

const page = usePage();

useLiveGenerations(() => props.hasUnfinished, ['stats', 'recent', 'hasUnfinished']);

const spentDescription = computed(() =>
    props.stats.average_cost ? `${props.stats.spent_this_month} this month · about ${props.stats.average_cost} each` : `${props.stats.spent_this_month} this month`,
);
</script>

<template>
    <AppLayout title="Dashboard">
        <PageHeader title="Dashboard" :description="`Welcome back, ${page.props.auth.user?.name}.`">
            <template v-if="page.props.auth.can.generateImages" #actions>
                <Button href="/generations/create" icon="magic-wand">Generate</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <StatCardGroup>
                <StatCard variant="compact" icon="coins" title="Balance" :value="page.props.balance ?? '—'" description="Ready to spend" />
                <StatCard variant="compact" icon="images" title="Images made" :value="stats.images" :description="`${stats.images_this_month} this month`" />
                <StatCard variant="compact" icon="receipt" title="Spent" :value="stats.spent" :description="spentDescription" />
                <StatCard
                    variant="compact"
                    icon="hourglass-medium"
                    :color="stats.unfinished > 0 ? 'sky' : 'primary'"
                    title="In progress"
                    :value="stats.unfinished"
                    :description="stats.unfinished > 0 ? 'Updates live' : 'Nothing running'"
                />
            </StatCardGroup>

            <section v-if="page.props.auth.can.viewHistory" class="space-y-4" aria-labelledby="recent-heading">
                <div class="flex items-center justify-between gap-4">
                    <Heading id="recent-heading" size="sm">Latest images</Heading>
                    <Button v-if="recent.length" href="/generations" variant="ghost" color="neutral" size="xs" icon-after="caret-right">View all</Button>
                </div>

                <div v-if="recent.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <GenerationCard v-for="generation in recent" :key="generation.id" :generation="generation" />
                </div>

                <Card v-else size="full">
                    <Empty>
                        <EmptyMedia>
                            <span class="grid size-12 place-items-center rounded-full bg-primary-500/10">
                                <Icon name="images" class="size-6 text-primary-700! dark:text-primary-400!" />
                            </span>
                        </EmptyMedia>
                        <EmptyContents>
                            <Heading size="md">No images yet</Heading>
                            <Text class="max-w-sm text-neutral-500! dark:text-neutral-400!">Describe one and you'll see what it costs before you send it.</Text>
                            <Button v-if="page.props.auth.can.generateImages" href="/generations/create" class="mt-2" icon="magic-wand">Generate your first image</Button>
                        </EmptyContents>
                    </Empty>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
