<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Badge, Button, Card, PageHeader, Pagination, Table, TableBody, TableCell, TableHead, TableHeader, TableRow, Text } from '@/components/ui';
import { useClientPagination } from '@/composables/usePagination';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PermissionOption, Role } from '@/types/users';

const props = defineProps<{
    roles: Role[];
    permissions: PermissionOption[];
}>();

const page = usePage();

const { page: rolePage, perPage: rolesPerPage, total: roleCount, rows: visibleRoles } = useClientPagination(() => props.roles);

const labelFor = (value: string) => props.permissions.find((permission) => permission.value === value)?.label ?? value;
</script>

<template>
    <AppLayout title="Roles">
        <PageHeader title="Roles" description="A role bundles permissions, so you can give several to a user at once.">
            <template #actions>
                <Button v-if="page.props.auth.can.manageUsers" href="/admin/users" variant="outline" color="neutral" icon="users">Users</Button>
                <Button href="/admin/roles/create" icon="plus">New role</Button>
            </template>
        </PageHeader>

        <Card size="full" class="space-y-4">
            <Table>
                <TableHead>
                    <TableRow>
                        <TableHeader>Role</TableHeader>
                        <TableHeader>Permissions</TableHeader>
                        <TableHeader align="end">Users</TableHeader>
                        <TableHeader align="end"><span class="sr-only">Actions</span></TableHeader>
                    </TableRow>
                </TableHead>
                <TableBody>
                    <TableRow v-for="role in visibleRoles" :key="role.id">
                        <TableCell>
                            <span class="flex flex-wrap items-center gap-2 font-medium">
                                {{ role.name }}
                                <Badge v-if="role.is_built_in" size="sm" color="neutral" icon="lock-simple">Built-in</Badge>
                            </span>
                        </TableCell>
                        <TableCell class="max-w-none!">
                            <div v-if="role.permissions?.length" class="flex flex-wrap gap-1.5">
                                <Badge v-for="permission in role.permissions" :key="permission" size="sm" variant="outline" color="neutral">{{ labelFor(permission) }}</Badge>
                            </div>
                            <span v-else class="text-neutral-500 dark:text-neutral-400">None</span>
                        </TableCell>
                        <TableCell align="end" class="tabular-nums">{{ role.users_count ?? 0 }}</TableCell>
                        <TableCell align="end">
                            <Button v-if="role.can_update" :href="`/admin/roles/${role.id}/edit`" variant="ghost" color="neutral" size="xs" icon-after="caret-right">
                                Edit
                                <span class="sr-only">{{ role.name }}</span>
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <Pagination v-model:page="rolePage" v-model:per-page="rolesPerPage" :total="roleCount" />

            <Text v-if="roles.some((role) => role.is_built_in)" class="text-xs text-neutral-500! dark:text-neutral-400!">
                Built-in roles are defined in the code and reset on every deploy, so they can be given to users but not edited here.
            </Text>
        </Card>
    </AppLayout>
</template>
