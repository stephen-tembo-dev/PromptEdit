<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import ReconciliationStatusBadge from '@/components/billing/ReconciliationStatusBadge.vue';
import {
    Alert,
    AlertDescription,
    AlertHeading,
    Button,
    Card,
    Empty,
    EmptyContents,
    Field,
    FieldError,
    Heading,
    Input,
    Label,
    PageHeader,
    Pagination,
    StatCard,
    StatCardGroup,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    Text,
} from '@/components/ui';
import { useClientPagination } from '@/composables/usePagination';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ReconciliationReport } from '@/types/billing';

const props = defineProps<{
    report: ReconciliationReport;
    maxDays: number;
}>();

const form = useForm({
    start_date: props.report.start_date,
    end_date: props.report.end_date,
});

const apply = () => form.get('/admin/report', { preserveScroll: true });

const { page: dayPage, perPage: daysPerPage, total: dayCount, rows: visibleDays } = useClientPagination(() => props.report.days);
const { page: userPage, perPage: usersPerPage, total: userCount, rows: visibleUsers } = useClientPagination(() => props.report.users);

const formatDay = (date: string) =>
    new Date(`${date}T00:00:00Z`).toLocaleDateString(undefined, { timeZone: 'UTC', weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <AppLayout title="Admin report">
        <PageHeader title="Admin report" description="Revenue, Pixelforge cost and margin per UTC day, checked against Pixelforge's own usage figures." />

        <div class="space-y-6">
            <Card size="full">
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
                    <Field class="w-44">
                        <Label>From</Label>
                        <Input v-model="form.start_date" name="start_date" type="date" />
                    </Field>
                    <Field class="w-44">
                        <Label>To</Label>
                        <Input v-model="form.end_date" name="end_date" type="date" />
                    </Field>
                    <Button type="submit" variant="outline" color="neutral" :loading="form.processing">Show report</Button>
                    <Text class="basis-full text-xs text-neutral-500! dark:text-neutral-400!">
                        Days are UTC and a generation counts on the day it completed, as Pixelforge bills it. Up to {{ maxDays }} days.
                    </Text>
                    <FieldError name="start_date" class="basis-full" />
                    <FieldError name="end_date" class="basis-full" />
                </form>
            </Card>

            <Alert v-if="!report.pixelforge_available" variant="warning">
                <AlertHeading>Pixelforge's usage could not be loaded</AlertHeading>
                <AlertDescription>The figures below are from our own records only. Try again in a moment to reconcile them.</AlertDescription>
            </Alert>
            <Alert v-else-if="report.totals.mismatched_days > 0" variant="error">
                <AlertHeading>{{ report.totals.mismatched_days }} {{ report.totals.mismatched_days === 1 ? 'day does' : 'days do' }} not match Pixelforge</AlertHeading>
                <AlertDescription>
                    Pixelforge billed a different number of generations, tokens or dollars than we recorded. Usage is per API key, so another app using the same key also shows up here.
                </AlertDescription>
            </Alert>

            <StatCardGroup>
                <StatCard variant="compact" icon="coins" title="Charged to users" :value="report.totals.charged" />
                <StatCard
                    variant="compact"
                    icon="receipt"
                    title="Pixelforge cost"
                    :value="report.totals.pixelforge_cost ?? report.totals.provider_cost"
                    :description="report.totals.pixelforge_cost === null ? 'From our records' : `Our records: ${report.totals.provider_cost}`"
                />
                <StatCard variant="compact" icon="trend-up" title="Margin" :value="report.totals.margin" />
                <StatCard
                    variant="compact"
                    icon="scales"
                    title="Days not matching"
                    :value="report.pixelforge_available ? report.totals.mismatched_days : '—'"
                />
            </StatCardGroup>

            <Card size="full" class="space-y-4">
                <div class="space-y-1">
                    <Heading size="sm">By day</Heading>
                    <Text class="text-sm text-neutral-500! dark:text-neutral-400!">Newest first. Days with no generations on either side are left out.</Text>
                </div>

                <Empty v-if="report.days.length === 0">
                    <EmptyContents>
                        <Text class="text-neutral-500! dark:text-neutral-400!">No generations completed in this range.</Text>
                    </EmptyContents>
                </Empty>

                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeader>Day</TableHeader>
                                <TableHeader>Status</TableHeader>
                                <TableHeader align="end">Generations</TableHeader>
                                <TableHeader align="end">Our cost</TableHeader>
                                <TableHeader align="end">Pixelforge cost</TableHeader>
                                <TableHeader align="end">Difference</TableHeader>
                                <TableHeader align="end">Charged</TableHeader>
                                <TableHeader align="end">Margin</TableHeader>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="day in visibleDays" :key="day.date">
                                <TableCell class="whitespace-nowrap">{{ formatDay(day.date) }}</TableCell>
                                <TableCell><ReconciliationStatusBadge :status="day.status" /></TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">
                                    {{ day.generations }}
                                    <span v-if="day.pixelforge_generations !== null && day.pixelforge_generations !== day.generations" class="text-neutral-500 dark:text-neutral-400">
                                        / {{ day.pixelforge_generations }}
                                    </span>
                                </TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ day.provider_cost }}</TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ day.pixelforge_cost ?? '—' }}</TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">
                                    {{ day.cost_difference ?? '—' }}
                                    <span v-if="day.tokens_match === false" class="block text-xs text-neutral-500 dark:text-neutral-400">tokens differ</span>
                                </TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ day.charged }}</TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ day.margin }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <Pagination v-model:page="dayPage" v-model:per-page="daysPerPage" :total="dayCount" label="Day pages" />

                <Text v-if="report.days.some((day) => day.pixelforge_generations !== null && day.pixelforge_generations !== day.generations)" class="text-xs text-neutral-500! dark:text-neutral-400!">
                    Where the counts differ, generations are shown as ours / Pixelforge's.
                </Text>
            </Card>

            <Card size="full" class="space-y-4">
                <div class="space-y-1">
                    <Heading size="sm">By user</Heading>
                    <Text class="text-sm text-neutral-500! dark:text-neutral-400!">Succeeded generations in this range, biggest spender first. Cost is from our records.</Text>
                </div>

                <Empty v-if="report.users.length === 0">
                    <EmptyContents>
                        <Text class="text-neutral-500! dark:text-neutral-400!">No user spent credits in this range.</Text>
                    </EmptyContents>
                </Empty>

                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeader>User</TableHeader>
                                <TableHeader align="end">Generations</TableHeader>
                                <TableHeader align="end">Charged</TableHeader>
                                <TableHeader align="end">Cost</TableHeader>
                                <TableHeader align="end">Margin</TableHeader>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            <TableRow v-for="user in visibleUsers" :key="user.id">
                                <TableCell>
                                    <span class="block font-medium">{{ user.name }}</span>
                                    <span class="block text-xs text-neutral-500 dark:text-neutral-400">{{ user.email }}</span>
                                </TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ user.generations }}</TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ user.charged }}</TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ user.provider_cost }}</TableCell>
                                <TableCell align="end" class="whitespace-nowrap tabular-nums">{{ user.margin }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <Pagination v-model:page="userPage" v-model:per-page="usersPerPage" :total="userCount" label="User pages" />
            </Card>
        </div>
    </AppLayout>
</template>
