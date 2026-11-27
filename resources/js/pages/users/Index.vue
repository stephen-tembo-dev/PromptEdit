<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import {
    Avatar,
    Badge,
    Button,
    Card,
    Empty,
    EmptyContents,
    EmptyMedia,
    Heading,
    Icon,
    Input,
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
import { DEFAULT_PER_PAGE, useServerPagination } from '@/composables/usePagination';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Paginated } from '@/types/generations';
import type { ManagedUser } from '@/types/users';

const props = defineProps<{
    users: Paginated<ManagedUser>;
    search: string;
}>();

const page = usePage();

const goTo = useServerPagination(['users']);

/* Searching reloads the list as you type, debounced so each keystroke isn't a request. */
const query = ref(props.search);
let debounce: ReturnType<typeof setTimeout> | undefined;

watch(query, (value) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        // Back to page 1, but keep the page size.
        const perPage = props.users.meta.per_page === DEFAULT_PER_PAGE ? undefined : props.users.meta.per_page;

        router.get('/admin/users', { search: value.trim() || undefined, per_page: perPage }, { preserveState: true, preserveScroll: true, replace: true, only: ['users', 'search'] });
    }, 300);
});

onBeforeUnmount(() => clearTimeout(debounce));

const formatDate = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
</script>

<template>
    <AppLayout title="Users">
        <PageHeader title="Users" description="Everyone with an account. Open a user to change their roles and permissions.">
            <template #actions>
                <Button v-if="page.props.auth.can.manageRoles" href="/admin/roles" variant="outline" color="neutral" icon="shield-check">Roles</Button>
                <Button href="/admin/users/create" icon="user-plus">New user</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <Card size="full" class="space-y-4">
                <div class="max-w-sm">
                    <Input v-model="query" type="search" left-icon="magnifying-glass" placeholder="Search by name or email" aria-label="Search users" clearable />
                </div>

                <Empty v-if="users.data.length === 0">
                    <EmptyMedia>
                        <span class="grid size-12 place-items-center rounded-full bg-neutral-500/10">
                            <Icon name="user-circle-dashed" class="size-6 text-neutral-500! dark:text-neutral-400!" />
                        </span>
                    </EmptyMedia>
                    <EmptyContents>
                        <Heading size="md">No users match “{{ search }}”</Heading>
                        <Text class="text-neutral-500! dark:text-neutral-400!">Try part of their name or email address.</Text>
                    </EmptyContents>
                </Empty>

                <Table v-else>
                    <TableHead>
                        <TableRow>
                            <TableHeader>User</TableHeader>
                            <TableHeader>Roles</TableHeader>
                            <TableHeader>Joined</TableHeader>
                            <TableHeader align="end"><span class="sr-only">Actions</span></TableHeader>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <Avatar :name="user.name" size="sm" color="auto" circle />
                                    <div class="min-w-0">
                                        <span class="flex items-center gap-2 font-medium">
                                            <span class="truncate">{{ user.name }}</span>
                                            <Badge v-if="user.is_current_user" size="sm" color="neutral">You</Badge>
                                        </span>
                                        <span class="block truncate text-xs text-neutral-500 dark:text-neutral-400">{{ user.email }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div v-if="user.roles?.length" class="flex flex-wrap gap-1.5">
                                    <Badge v-for="role in user.roles" :key="role.id" size="sm" color="neutral">{{ role.name }}</Badge>
                                </div>
                                <span v-else class="text-neutral-500 dark:text-neutral-400">No role</span>
                            </TableCell>
                            <TableCell class="whitespace-nowrap tabular-nums">{{ formatDate(user.created_at) }}</TableCell>
                            <TableCell align="end">
                                <Button :href="`/admin/users/${user.id}/edit`" variant="ghost" color="neutral" size="xs" icon-after="caret-right">
                                    {{ user.can_update_access ? 'Manage' : 'View' }}
                                    <span class="sr-only">{{ user.name }}</span>
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <Pagination
                    :page="users.meta.current_page"
                    :per-page="users.meta.per_page"
                    :total="users.meta.total"
                    @update:page="goTo({ page: $event })"
                    @update:per-page="goTo({ per_page: $event })"
                />
            </Card>
        </div>
    </AppLayout>
</template>
