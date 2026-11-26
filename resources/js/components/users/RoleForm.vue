<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Button, Card, Checkbox, CheckboxGroup, Description, Field, FieldError, Heading, Input, Label, Text } from '@/components/ui';
import type { PermissionOption } from '@/types/users';

/**
 * The name and permissions of a role, shared by the New role and Edit role pages.
 */
defineProps<{
    form: InertiaForm<{ name: string; permissions: string[] }>;
    permissions: PermissionOption[];
    submitLabel: string;
}>();

defineEmits<{ submit: [] }>();
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="$emit('submit')">
        <Card size="full" class="space-y-6">
            <Field required>
                <Label for="role-name">Name</Label>
                <Description>What people will see on the users page, such as "Support" or "Finance".</Description>
                <Input id="role-name" v-model="form.name" name="name" maxlength="50" autocomplete="off" required autofocus />
                <FieldError name="name" />
            </Field>

            <section class="space-y-3" aria-labelledby="role-permissions">
                <div class="space-y-1">
                    <Heading id="role-permissions" level="h3" size="xs">Permissions</Heading>
                    <Text class="text-sm text-neutral-500! dark:text-neutral-400!">Everyone with this role gets these straight away.</Text>
                </div>

                <CheckboxGroup v-model="form.permissions" variant="cards">
                    <Checkbox
                        v-for="permission in permissions"
                        :key="permission.value"
                        name="permissions"
                        :value="permission.value"
                        :label="permission.label"
                        :description="permission.description"
                    />
                </CheckboxGroup>
                <FieldError name="permissions" />
            </section>
        </Card>

        <div class="flex justify-end gap-2">
            <Button href="/admin/roles" variant="outline" color="neutral">Cancel</Button>
            <Button type="submit" :loading="form.processing">{{ submitLabel }}</Button>
        </div>
    </form>
</template>
