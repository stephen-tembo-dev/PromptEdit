<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Badge,
    Card,
    Empty,
    EmptyContents,
    PageHeader,
    Pagination,
    Segmented,
    StatCard,
    StatCardGroup,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    Text,
    TextLink,
    type UiColor,
} from '@/components/ui';
import { DEFAULT_PER_PAGE, useServerPagination } from '@/composables/usePagination';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Transaction, TransactionKind } from '@/types/billing';
import type { Paginated } from '@/types/generations';

const props = defineProps<{
    totals: { bought: string; charged: string; held: string; balance: string };
    transactions: Paginated<Transaction>;
    filter: string;
}>();

const page = usePage();

const filters = [
    { value: 'all', label: 'All' },
    { value: 'purchases', label: 'Credits bought' },
    { value: 'charges', label: 'Charges' },
    { value: 'holds', label: 'Holds' },
];

const selected = ref(props.filter);

const goTo = useServerPagination(['transactions']);

// Back to page 1, but keep the page size.
watch(selected, (value) =>
    router.get(
        '/reports',
        {
            type: value === 'all' ? undefined : value,
            per_page: props.transactions.meta.per_page === DEFAULT_PER_PAGE ? undefined : props.transactions.meta.per_page,
        },
        { preserveState: true, preserveScroll: true, replace: true, only: ['transactions', 'filter'] },
    ),
);

/** Each kind keeps the badge meaning used elsewhere: amber is pending, sky is information. */
const badges: Record<TransactionKind, { color: UiColor; icon: string }> = {
    purchase: { color: 'emerald', icon: 'coins' },
    hold: { color: 'amber', icon: 'lock-simple' },
    release: { color: 'sky', icon: 'arrow-u-up-left' },
    charge: { color: 'neutral', icon: 'receipt' },
};

const formatDate = (iso: string) => new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <AppLayout title="Reports">
        <PageHeader title="Reports" description="Every credit you've bought and every hold, charge and release for your images, newest first." />

        <div class="space-y-6">
            <StatCardGroup>
                <StatCard variant="compact" icon="coins" title="Credits bought" :value="totals.bought" />
                <StatCard variant="compact" icon="receipt" title="Charged" :value="totals.charged" description="For finished images" />
                <StatCard variant="compact" icon="lock-simple" title="Held now" :value="totals.held" description="For images still being made" />
                <StatCard variant="compact" icon="wallet" title="Balance" :value="totals.balance" description="Bought − charged − held" />
            </StatCardGroup>

            <Card size="full" class="space-y-4">
                <Segmented v-model="selected" :options="filters" label="Show" />

                <Empty v-if="transactions.data.length === 0">
                    <EmptyContents>
                        <Text class="text-neutral-500! dark:text-neutral-400!">{{ filter === 'all' ? 'No transactions yet.' : 'Nothing of this kind yet.' }}</Text>
                    </EmptyContents>
                </Empty>

                <Table v-else>
                    <TableHead>
                        <TableRow>
                            <TableHeader>Date</TableHeader>
                            <TableHeader>Transaction</TableHeader>
                            <TableHeader align="end">Amount</TableHeader>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="transaction in transactions.data" :key="transaction.id">
                            <TableCell class="whitespace-nowrap tabular-nums">{{ formatDate(transaction.created_at) }}</TableCell>
                            <TableCell class="max-w-md!">
                                <div class="space-y-1">
                                    <Badge size="sm" :color="badges[transaction.kind].color" :icon="badges[transaction.kind].icon">{{ transaction.label }}</Badge>
                                    <span v-if="transaction.kind === 'purchase'" class="block text-xs text-neutral-500 dark:text-neutral-400">Added to your balance</span>
                                    <template v-else-if="transaction.prompt">
                                        <TextLink
                                            v-if="page.props.auth.can.viewHistory && transaction.generation_id"
                                            :href="`/generations/${transaction.generation_id}`"
                                            :primary="false"
                                            class="block truncate text-xs"
                                        >
                                            {{ transaction.prompt }}
                                        </TextLink>
                                        <span v-else class="block truncate text-xs text-neutral-500 dark:text-neutral-400">{{ transaction.prompt }}</span>
                                    </template>
                                </div>
                            </TableCell>
                            <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ transaction.amount }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <Pagination
                    :page="transactions.meta.current_page"
                    :per-page="transactions.meta.per_page"
                    :total="transactions.meta.total"
                    @update:page="goTo({ page: $event })"
                    @update:per-page="goTo({ per_page: $event })"
                />

                <Text class="text-xs text-neutral-500! dark:text-neutral-400!">
                    When you generate an image, the most it can cost is held. Once it finishes, the hold is returned and the real price is charged, or nothing if it
                    failed.
                </Text>
            </Card>
        </div>
    </AppLayout>
</template>
