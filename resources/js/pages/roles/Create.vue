<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import RoleForm from '@/components/users/RoleForm.vue';
import { PageHeader } from '@/components/ui';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PermissionOption } from '@/types/users';

defineProps<{
    permissions: PermissionOption[];
}>();

const form = useForm({
    name: '',
    permissions: [] as string[],
});

const submit = () => form.post('/admin/roles');
</script>

<template>
    <AppLayout title="New role">
        <PageHeader title="New role" description="Name the role and choose its permissions. You can give it to users afterwards." back="/admin/roles" />

        <RoleForm :form="form" :permissions="permissions" submit-label="Create role" @submit="submit" />
    </AppLayout>
</template>
