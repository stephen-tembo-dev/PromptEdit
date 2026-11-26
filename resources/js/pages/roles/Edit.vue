<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import RoleForm from '@/components/users/RoleForm.vue';
import { Button, Confirm, PageHeader } from '@/components/ui';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PermissionOption, Role } from '@/types/users';

const props = defineProps<{
    role: Role;
    permissions: PermissionOption[];
}>();

const form = useForm({
    name: props.role.name,
    permissions: [...(props.role.permissions ?? [])],
});

const submit = () => form.put(`/admin/roles/${props.role.id}`);

const deleteRole = () => new Promise((resolve) => router.delete(`/admin/roles/${props.role.id}`, { onFinish: resolve }));

const usersNote = computed(() => {
    const count = props.role.users_count ?? 0;

    if (count === 0) {
        return 'No users have this role.';
    }

    return `${count} ${count === 1 ? 'user loses' : 'users lose'} its permissions, unless another role or a direct permission still gives them.`;
});
</script>

<template>
    <AppLayout :title="`Edit ${role.name}`">
        <PageHeader :title="role.name" description="Changes apply straight away to everyone with this role." back="/admin/roles">
            <template #actions>
                <Confirm :action="deleteRole" tone="danger" :title="`Delete the ${role.name} role?`" :description="usersNote" confirm-label="Delete role">
                    <Button variant="outline" color="red" icon="trash">Delete role</Button>
                </Confirm>
            </template>
        </PageHeader>

        <RoleForm :form="form" :permissions="permissions" submit-label="Save role" @submit="submit" />
    </AppLayout>
</template>
