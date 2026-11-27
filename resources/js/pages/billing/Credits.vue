<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BalanceCard from '@/components/billing/BalanceCard.vue';
import {
    Alert,
    Button,
    Card,
    Empty,
    EmptyContents,
    Heading,
    PageHeader,
    Pagination,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    Text,
} from '@/components/ui';
import { useServerPagination } from '@/composables/usePagination';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CreditPreset, CreditPurchase } from '@/types/billing';
import type { Paginated } from '@/types/generations';

defineProps<{
    presets: CreditPreset[];
    purchases: Paginated<CreditPurchase>;
}>();

const goTo = useServerPagination(['purchases']);

/*
 * Each purchase carries a fresh idempotency key, so a double-click or a resent form
 * adds the credits once. A new key is minted only after a purchase goes through.
 */
const form = useForm({
    amount_dollars: 0,
    idempotency_key: crypto.randomUUID(),
});

const buy = (dollars: number) => {
    form.amount_dollars = dollars;
    form.post('/credits', {
        preserveScroll: true,
        onSuccess: () => {
            form.idempotency_key = crypto.randomUUID();
        },
    });
};

const formatDate = (iso: string) => new Date(iso).toLocaleString();
</script>

<template>
    <AppLayout title="Credits">
        <PageHeader title="Credits" description="Credits are US dollars. Generations are paid from this balance." />

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <div class="space-y-4">
                <BalanceCard :show-buy="false" />

                <Card size="full" class="space-y-4">
                    <div class="space-y-1">
                        <Heading size="sm">Buy credits</Heading>
                        <Text class="text-sm text-neutral-500! dark:text-neutral-400!">This is a simulated purchase: no payment is taken.</Text>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <Button
                            v-for="preset in presets"
                            :key="preset.value"
                            variant="outline"
                            :loading="form.processing && form.amount_dollars === preset.value"
                            :disabled="form.processing"
                            class="tabular-nums"
                            @click="buy(preset.value)"
                        >
                            {{ preset.label }}
                        </Button>
                    </div>

                    <Alert v-if="form.hasErrors" variant="error">The purchase could not be completed. Please try again.</Alert>
                </Card>
            </div>

            <Card size="full" class="space-y-4 lg:col-span-2">
                <Heading size="sm">Purchase history</Heading>

                <Empty v-if="purchases.data.length === 0">
                    <EmptyContents>
                        <Text class="text-neutral-500! dark:text-neutral-400!">No purchases yet.</Text>
                    </EmptyContents>
                </Empty>

                <Table v-else>
                    <TableHead>
                        <TableRow>
                            <TableHeader>Date</TableHeader>
                            <TableHeader align="end">Amount</TableHeader>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="purchase in purchases.data" :key="purchase.id">
                            <TableCell>{{ formatDate(purchase.created_at) }}</TableCell>
                            <TableCell align="end" class="tabular-nums">{{ purchase.amount }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <Pagination
                    :page="purchases.meta.current_page"
                    :per-page="purchases.meta.per_page"
                    :total="purchases.meta.total"
                    @update:page="goTo({ page: $event })"
                    @update:per-page="goTo({ per_page: $event })"
                />
            </Card>
        </div>
    </AppLayout>
</template>
