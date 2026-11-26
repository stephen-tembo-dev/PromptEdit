<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AccessFields from '@/components/users/AccessFields.vue';
import AccessSummary from '@/components/users/AccessSummary.vue';
import { Button, Card, Description, Field, FieldError, Heading, Input, Label, PageHeader } from '@/components/ui';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PermissionOption, Role } from '@/types/users';

const props = defineProps<{
    roles: Role[];
    permissions: PermissionOption[];
    /** Customer, so a new account can use the app the same as one that signed up. */
    defaultRoleIds: number[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [...props.defaultRoleIds],
    permissions: [] as string[],
});

const submit = () => form.post('/admin/users', { onError: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <AppLayout title="New user">
        <PageHeader title="New user" description="Make an account and choose what it can do." back="/admin/users" />

        <form class="grid items-start gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-6 lg:col-span-2">
                <Card size="full" class="space-y-4">
                    <Heading level="h2" size="sm">Account</Heading>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <Field required>
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" name="name" autocomplete="off" required autofocus />
                            <FieldError name="name" />
                        </Field>

                        <Field required>
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" name="email" type="email" autocomplete="off" required />
                            <FieldError name="email" />
                        </Field>

                        <Field required>
                            <Label for="password">Password</Label>
                            <Input id="password" v-model="form.password" name="password" type="password" autocomplete="new-password" revealable required />
                            <FieldError name="password" />
                        </Field>

                        <Field required>
                            <Label for="password_confirmation">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                revealable
                                required
                            />
                            <FieldError name="password_confirmation" />
                        </Field>
                    </div>

                    <Description>Share the password with them yourself. They can change it after signing in.</Description>
                </Card>

                <Card size="full">
                    <AccessFields v-model:selected-roles="form.roles" v-model:selected-permissions="form.permissions" :roles="roles" :permissions="permissions" />
                </Card>
            </div>

            <div class="space-y-4 lg:sticky lg:top-36">
                <Card size="full">
                    <AccessSummary :roles="roles" :permissions="permissions" :selected-roles="form.roles" :selected-permissions="form.permissions" />
                </Card>

                <Button type="submit" size="md" class="w-full" icon="user-plus" :loading="form.processing">Create user</Button>
            </div>
        </form>
    </AppLayout>
</template>
