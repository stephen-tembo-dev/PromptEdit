<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Button, Card, Text } from '@/components/ui';

/**
 * The user's spendable credit (holds for running generations already deducted),
 * from the shared `balance` prop, with a way to top up.
 */
withDefaults(defineProps<{ showBuy?: boolean }>(), { showBuy: true });

const page = usePage();
</script>

<template>
    <Card size="full" class="flex items-center justify-between gap-4">
        <div>
            <Text class="text-sm text-neutral-600! dark:text-neutral-400!">Balance</Text>
            <p class="text-2xl font-semibold tracking-tight text-neutral-950 tabular-nums dark:text-neutral-50">{{ page.props.balance }}</p>
        </div>
        <Button v-if="showBuy" href="/credits" variant="outline" icon="coins">Buy credits</Button>
    </Card>
</template>
