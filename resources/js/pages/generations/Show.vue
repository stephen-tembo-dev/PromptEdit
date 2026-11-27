<script setup lang="ts">
import { computed } from 'vue';
import GenerationPreview from '@/components/generations/GenerationPreview.vue';
import GenerationStatusBadge from '@/components/generations/GenerationStatusBadge.vue';
import { Alert, Button, Card, DetailList, DetailListItem, Heading, PageHeader, Text, TextLink } from '@/components/ui';
import { useLiveGenerations } from '@/composables/generations/useLiveGenerations';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Generation } from '@/types/generations';

const props = defineProps<{ generation: Generation }>();

useLiveGenerations(() => !props.generation.is_finished, ['generation']);

const formatDate = (iso: string | null) => (iso ? new Date(iso).toLocaleString() : '—');

const tokens = computed(() => {
    const usage = props.generation.usage;

    return usage
        ? [
              { label: 'Text input', value: usage.input_text_tokens },
              { label: 'Reference images', value: usage.input_image_tokens },
              { label: 'Output', value: usage.output_tokens },
              { label: 'Reasoning', value: usage.reasoning_tokens },
          ]
        : [];
});
</script>

<template>
    <AppLayout title="Generation">
        <PageHeader title="Generation" back="/generations">
            <template #badge>
                <GenerationStatusBadge :generation="generation" />
            </template>
            <template v-if="generation.image_url" #actions>
                <!-- external: a normal link, since the response is a file, not a page. -->
                <Button :href="`/generations/${generation.id}/download`" external icon="download-simple">Download</Button>
            </template>
        </PageHeader>

        <div class="grid items-start gap-6 lg:grid-cols-5">
            <div class="space-y-4 lg:col-span-3">
                <GenerationPreview :generation="generation" />

                <Alert v-if="generation.status === 'failed'" variant="error">
                    {{ generation.error_message ?? 'This generation failed.' }} You were not charged.
                </Alert>

                <Text v-if="generation.image_expired" class="text-sm text-neutral-500! dark:text-neutral-400!">
                    Pixelforge keeps image links for 24 hours after a generation finishes, so this one can no longer be shown.
                </Text>
            </div>

            <div class="space-y-4 lg:col-span-2">
                <Card size="full" class="space-y-3">
                    <Heading size="sm">Prompt</Heading>
                    <Text class="whitespace-pre-line">{{ generation.prompt }}</Text>
                </Card>

                <Card size="full">
                    <DetailList heading="Cost">
                        <DetailListItem label="Estimated" :value="generation.estimated_charge" />
                        <DetailListItem label="Held while running" :value="generation.held_charge ?? '—'" />
                        <DetailListItem label="Actual" :value="generation.actual_charge ?? 'Settled when it finishes'" />
                    </DetailList>
                </Card>

                <Card size="full">
                    <DetailList heading="Details">
                        <DetailListItem label="Size" :value="generation.size" />
                        <DetailListItem label="Quality" :value="generation.quality" />
                        <DetailListItem label="Requested" :value="formatDate(generation.created_at)" />
                        <DetailListItem label="Finished" :value="formatDate(generation.completed_at)" />
                        <DetailListItem v-for="token in tokens" :key="token.label" :label="`${token.label} tokens`" :value="token.value.toLocaleString()" />
                    </DetailList>
                </Card>

                <Card v-if="generation.reference_images?.length" size="full" class="space-y-3">
                    <Heading size="sm">Reference images</Heading>
                    <ul class="space-y-1">
                        <li v-for="url in generation.reference_images" :key="url" class="truncate text-sm">
                            <TextLink :href="url" external open-in-new-tab>{{ url }}</TextLink>
                        </li>
                    </ul>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
