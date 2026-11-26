<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Alert, Button, Heading, IconLoading, Text } from '@/components/ui';
import type { Quote } from '@/types/generations';

/**
 * "Estimated $X, at most $Y". On submit the maximum plus a small safety margin is
 * held from the balance; the actual charge is settled once Pixelforge reports the
 * real usage.
 */
defineProps<{
    quote: Quote | null;
    loading: boolean;
    unavailable: boolean;
}>();

const page = usePage();
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-2">
            <Heading size="sm">Cost</Heading>
            <IconLoading v-if="loading" class="size-4" aria-label="Updating the estimate" />
        </div>

        <Alert v-if="unavailable" variant="warning">Prices could not be loaded. Try again in a moment.</Alert>

        <template v-else-if="quote">
            <dl class="space-y-3">
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-sm text-neutral-600 dark:text-neutral-400">Estimated</dt>
                    <dd class="text-2xl font-semibold tracking-tight text-neutral-950 tabular-nums dark:text-neutral-50">{{ quote.estimated_charge }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-sm text-neutral-600 dark:text-neutral-400">At most</dt>
                    <dd class="text-sm font-medium text-neutral-800 tabular-nums dark:text-neutral-200">{{ quote.max_charge }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4 border-t border-black/10 pt-3 dark:border-white/10">
                    <dt class="text-sm text-neutral-600 dark:text-neutral-400">Your balance</dt>
                    <dd class="text-sm text-neutral-800 tabular-nums dark:text-neutral-200">{{ quote.balance }}</dd>
                </div>
            </dl>

            <Text class="text-xs text-neutral-500! dark:text-neutral-400!">
                {{ quote.held_charge }} (the maximum plus a small safety margin) is held from your balance while the image is made. You pay only what it actually costs, and
                nothing if it fails.
            </Text>

            <Alert v-if="!quote.can_afford" variant="warning">
                Your balance does not cover the {{ quote.held_charge }} held while the image is made.
                <template v-if="page.props.auth.can.manageCredits" #controls>
                    <Button href="/credits" size="xs" variant="outline" color="amber">Buy credits</Button>
                </template>
            </Alert>
        </template>

        <Text v-else class="text-sm text-neutral-500! dark:text-neutral-400!">Write a prompt to see what it will cost.</Text>
    </div>
</template>
