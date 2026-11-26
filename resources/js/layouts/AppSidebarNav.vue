<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SidebarLink } from '@/components/ui';
import { isCurrentUrl } from '@/lib/url';

/**
 * The app's main navigation. Add links here:
 *   <SidebarLink title="Projects" href="/projects" icon="folder-open" />
 *   <SidebarDropdown title="Settings" icon="gear"> <SidebarDropdownLink title="Profile" href="/settings/profile" /> </SidebarDropdown>
 *
 * Icons are Phosphor names (https://phosphoricons.com), or Heroicons with a `hi:` prefix.
 */
const page = usePage();

/** History covers /generations and each generation's page, but not the generate form under it. */
const onHistory = computed(() => isCurrentUrl('/generations', page.url, true) && !isCurrentUrl('/generations/create', page.url));
</script>

<template>
    <SidebarLink v-if="page.props.auth.can.viewDashboard" title="Dashboard" href="/dashboard" icon="house" />
    <SidebarLink v-if="page.props.auth.can.generateImages" title="Generate" href="/generations/create" icon="magic-wand" />
    <SidebarLink v-if="page.props.auth.can.viewHistory" title="History" href="/generations" icon="images" :active="onHistory" />
    <SidebarLink v-if="page.props.auth.can.manageCredits" title="Credits" href="/credits" icon="coins" />
    <SidebarLink v-if="page.props.auth.can.viewReports" title="Reports" href="/reports" icon="receipt" />
    <SidebarLink v-if="page.props.auth.can.viewAdminReport" title="Admin report" href="/admin/report" icon="scales" />
    <SidebarLink v-if="page.props.auth.can.manageUsers" title="Users" href="/admin/users" icon="users" />
    <SidebarLink v-if="page.props.auth.can.manageRoles" title="Roles" href="/admin/roles" icon="shield-check" /></template>
