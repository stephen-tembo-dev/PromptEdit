<script setup lang="ts">
import { computed } from 'vue';
import { Badge, Checkbox, CheckboxGroup, FieldError, Heading, Text } from '@/components/ui';
import type { PermissionOption, Role } from '@/types/users';

/**
 * The roles and extra permissions checkboxes on the New user and user pages.
 * Each role card lists what it grants, so nobody has to open the roles page to
 * find out what "Support" means.
 */
const props = withDefaults(
    defineProps<{
        roles: Role[];
        permissions: PermissionOption[];
        disabled?: boolean;
    }>(),
    { disabled: false },
);

const selectedRoles = defineModel<number[]>('selectedRoles', { required: true });
const selectedPermissions = defineModel<string[]>('selectedPermissions', { required: true });

const labelFor = (value: string) => props.permissions.find((permission) => permission.value === value)?.label ?? value;

const roleSummary = (role: Role) => (role.permissions?.length ? role.permissions.map(labelFor).join(', ') : 'No permissions yet');

/** The ticked roles that already grant each permission, so its checkbox can say so. */
const grantedBy = computed(() => {
    const sources: Record<string, string[]> = {};

    for (const role of props.roles) {
        if (!selectedRoles.value.includes(role.id)) continue;

        for (const permission of role.permissions ?? []) {
            (sources[permission] ??= []).push(role.name);
        }
    }

    return sources;
});

const permissionDescription = (permission: PermissionOption) => {
    const roles = grantedBy.value[permission.value];

    return roles ? `${permission.description} Already included in ${roles.join(' and ')}.` : permission.description;
};
</script>

<template>
    <div class="space-y-8">
        <section class="space-y-3" aria-labelledby="access-roles">
            <div class="space-y-1">
                <Heading id="access-roles" level="h3" size="xs">Roles</Heading>
                <Text class="text-sm text-neutral-500! dark:text-neutral-400!">
                    A role bundles permissions. Customer opens the normal pages; without any role, a user can only sign in.
                </Text>
            </div>

            <Text v-if="roles.length === 0" class="text-sm text-neutral-500! dark:text-neutral-400!">No roles exist yet.</Text>

            <CheckboxGroup v-else v-model="selectedRoles" variant="cards" class="grid! gap-2 sm:grid-cols-2">
                <Checkbox v-for="role in roles" :key="role.id" name="roles" :value="role.id" :description="roleSummary(role)" :disabled="disabled">
                    <span class="flex flex-wrap items-center gap-2">
                        {{ role.name }}
                        <Badge v-if="role.is_built_in" size="sm" color="neutral" icon="lock-simple">Built-in</Badge>
                    </span>
                </Checkbox>
            </CheckboxGroup>
            <FieldError name="roles" />
        </section>

        <section class="space-y-3" aria-labelledby="access-permissions">
            <div class="space-y-1">
                <Heading id="access-permissions" level="h3" size="xs">Extra permissions</Heading>
                <Text class="text-sm text-neutral-500! dark:text-neutral-400!">Given to this user on top of their roles.</Text>
            </div>

            <CheckboxGroup v-model="selectedPermissions">
                <Checkbox
                    v-for="permission in permissions"
                    :key="permission.value"
                    name="permissions"
                    :value="permission.value"
                    :label="permission.label"
                    :description="permissionDescription(permission)"
                    :disabled="disabled"
                />
            </CheckboxGroup>
            <FieldError name="permissions" />
        </section>
    </div>
</template>
