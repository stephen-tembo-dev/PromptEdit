<script setup lang="ts">
import { computed } from 'vue';
import { Heading, Icon, Text } from '@/components/ui';
import type { PermissionOption, Role } from '@/types/users';

/**
 * What the ticked roles and permissions add up to, permission by permission,
 * with where each one comes from. It updates as boxes are ticked, so the result
 * is clear before saving.
 */
const props = defineProps<{
    roles: Role[];
    permissions: PermissionOption[];
    selectedRoles: number[];
    selectedPermissions: string[];
}>();

const rows = computed(() =>
    props.permissions.map((permission) => {
        const fromRoles = props.roles
            .filter((role) => props.selectedRoles.includes(role.id) && role.permissions?.includes(permission.value))
            .map((role) => role.name);
        const direct = props.selectedPermissions.includes(permission.value);
        const sources = [...fromRoles.map((name) => `${name} role`), ...(direct ? ['Given directly'] : [])];

        return { ...permission, granted: sources.length > 0, source: sources.join(' · ') };
    }),
);

const grantedCount = computed(() => rows.value.filter((row) => row.granted).length);
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-baseline justify-between gap-3">
            <Heading level="h2" size="sm">What they can do</Heading>
            <Text class="text-xs text-neutral-500! tabular-nums dark:text-neutral-400!">{{ grantedCount }} of {{ rows.length }}</Text>
        </div>

        <ul class="divide-y divide-black/10 dark:divide-white/10">
            <li v-for="row in rows" :key="row.value" class="flex items-start gap-3 py-2.5 first:pt-0 last:pb-0">
                <Icon
                    :name="row.granted ? 'check-circle' : 'minus-circle'"
                    :class="['mt-0.5 size-5 shrink-0', row.granted ? 'text-primary-600! dark:text-primary-400!' : 'text-neutral-400! dark:text-neutral-500!']"
                />
                <div class="min-w-0">
                    <Text :class="['text-sm', row.granted ? 'font-medium' : 'text-neutral-500! dark:text-neutral-400!']">{{ row.label }}</Text>
                    <Text class="text-xs text-neutral-500! dark:text-neutral-400!">{{ row.granted ? row.source : 'Not included' }}</Text>
                </div>
            </li>
        </ul>

        <Text v-if="grantedCount === 0" class="border-t border-black/10 pt-3 text-xs text-neutral-500! dark:border-white/10 dark:text-neutral-400!">
            With nothing ticked, they can sign in but no page will open for them.
        </Text>
    </div>
</template>
