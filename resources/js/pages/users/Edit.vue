<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AccessFields from '@/components/users/AccessFields.vue';
import AccessSummary from '@/components/users/AccessSummary.vue';
import { Alert, Badge, Button, Card, DetailList, DetailListItem, PageHeader } from '@/components/ui';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ManagedUser, PermissionOption, Role } from '@/types/users';

const props = defineProps<{
    user: ManagedUser;
    roles: Role[];
    permissions: PermissionOption[];
}>();

const form = useForm({
    roles: (props.user.roles ?? []).map((role) => role.id),
    permissions: [...(props.user.permissions ?? [])],
});

// After a save the new values become the baseline, so the button settles back to "no changes".
const submit = () => form.put(`/admin/users/${props.user.id}`, { preserveScroll: true, onSuccess: () => form.defaults() });

const joined = props.user.created_at ? new Date(props.user.created_at).toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' }) : '—';
</script>

<template>
    <AppLayout :title="user.name">
        <PageHeader :title="user.name" :description="user.email" back="/admin/users">
            <template v-if="user.is_current_user" #badge>
                <Badge color="neutral">You</Badge>
            </template>
        </PageHeader>

        <form class="grid items-start gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-6 lg:col-span-2">
                <Alert v-if="!user.can_update_access" variant="info">
                    You can't change your own roles or permissions, so you can't lock yourself out. Ask another user manager to do it.
                </Alert>

                <Card size="full">
                    <AccessFields
                        v-model:selected-roles="form.roles"
                        v-model:selected-permissions="form.permissions"
                        :roles="roles"
                        :permissions="permissions"
                        :disabled="!user.can_update_access"
                    />
                </Card>

                <DetailList heading="Account">
                    <DetailListItem label="Email" :value="user.email" />
                    <DetailListItem label="Joined" :value="joined" />
                </DetailList>
            </div>

            <div class="space-y-4 lg:sticky lg:top-36">
                <Card size="full">
                    <AccessSummary :roles="roles" :permissions="permissions" :selected-roles="form.roles" :selected-permissions="form.permissions" />
                </Card>

                <Button v-if="user.can_update_access" type="submit" size="md" class="w-full" :loading="form.processing" :disabled="!form.isDirty">
                    {{ form.isDirty ? 'Save access' : 'No changes' }}
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
