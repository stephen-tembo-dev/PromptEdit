<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { Avatar, Button, Dropdown, DropdownItem, DropdownSeparator, Toast, Tooltip, useTheme } from '@/components/ui';
import AppSidebarNav from './AppSidebarNav.vue';

/**
 * The signed-in shell, ported from stephenson-research's layouts/app.blade.php:
 * a fixed header, a sidebar that slides in on small screens, and the page in the middle.
 */
defineProps<{ title?: string }>();

const page = usePage();
const theme = useTheme();

const sidebarOpen = ref(false);

// Close the mobile sidebar after following a link.
const stopListening = router.on('navigate', () => (sidebarOpen.value = false));
onBeforeUnmount(stopListening);

watch(sidebarOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});
</script>

<template>
    <Head v-if="title" :title="title" />

    <!-- Header -->
    <header class="fixed inset-x-0 top-0 z-35 flex flex-wrap border-b border-gray-200 bg-white md:flex-nowrap md:justify-start lg:ms-65 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex w-full basis-full items-center justify-between px-2 py-2.5 sm:px-5">
            <div class="flex items-center md:gap-x-3">
                <button
                    type="button"
                    class="me-4 inline-flex size-8 items-center justify-center gap-x-2 rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-800 shadow-2xs hover:bg-gray-50 focus:bg-gray-100 focus:outline-hidden lg:hidden dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700"
                    aria-haspopup="dialog"
                    :aria-expanded="sidebarOpen ? 'true' : 'false'"
                    aria-controls="app-sidebar"
                    aria-label="Toggle navigation"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <span class="sr-only">Menu</span>
                    <svg class="size-5" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true">
                        <path d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z" />
                    </svg>
                </button>
            </div>

            <div class="flex items-center justify-end gap-x-2">
                <Tooltip :content="theme.isResolvedToDark.value ? 'Light theme' : 'Dark theme'" placement="bottom">
                    <Button
                        variant="ghost"
                        class="size-9.5 rounded-full text-gray-500 dark:text-neutral-400"
                        :icon="theme.isResolvedToDark.value ? 'moon' : 'sun'"
                        icon-variant="regular"
                        :aria-label="theme.isResolvedToDark.value ? 'Switch to light theme' : 'Switch to dark theme'"
                        @click="theme.toggle()"
                    />
                </Tooltip>

                <div v-if="page.props.auth.user" class="ml-3 flex h-9.5 items-center">
                    <Dropdown position="bottom-end" menu-class="w-60">
                        <template #button>
                            <button type="button" class="inline-flex shrink-0 items-center rounded-full focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-600" aria-label="Account menu">
                                <Avatar :name="page.props.auth.user.name" color="auto" size="md" circle />
                            </button>
                        </template>

                        <template #menu>
                            <div class="col-span-full flex items-center gap-x-3 px-2 py-2">
                                <Avatar :name="page.props.auth.user.name" color="auto" size="sm" circle />
                                <div class="min-w-0 grow">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-neutral-300">{{ page.props.auth.user.name }}</p>
                                    <p class="text-xs break-all whitespace-normal text-gray-500 dark:text-neutral-500">{{ page.props.auth.user.email }}</p>
                                </div>
                            </div>
                            <template v-if="page.props.auth.can.viewDashboard">
                                <DropdownSeparator />
                                <DropdownItem icon="house" href="/dashboard">Dashboard</DropdownItem>
                            </template>
                            <DropdownSeparator />
                            <DropdownItem icon="sign-out" href="/logout" method="post" as="button">Log out</DropdownItem>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </div>
    </header>

    <!-- Backdrop behind the mobile sidebar -->
    <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-300" leave-to-class="opacity-0">
        <div v-if="sidebarOpen" class="fixed inset-0 z-34 bg-neutral-900/50 lg:hidden" aria-hidden="true" @click="sidebarOpen = false" />
    </Transition>

    <!-- Sidebar -->
    <aside
        id="app-sidebar"
        :class="[
            'fixed inset-y-0 start-0 z-35 h-full w-65 transform border-e border-gray-200 bg-white transition-transform duration-300',
            'lg:end-auto lg:bottom-0 lg:block lg:translate-x-0 dark:border-neutral-700 dark:bg-neutral-800',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        ]"
        aria-label="Sidebar"
        @keydown.escape="sidebarOpen = false"
    >
        <div class="relative flex h-full max-h-full flex-col pt-3">
            <header class="mb-4 flex h-11.5 items-center gap-x-1 ps-5 pe-2 lg:ps-8">
                <Link href="/" class="flex flex-none items-center gap-2 rounded-md font-semibold focus:opacity-80 focus:outline-hidden" :aria-label="page.props.name">
                    <AppLogo />
                    <span class="text-md text-gray-800 dark:text-neutral-300">{{ page.props.name }}</span>
                </Link>

                <div class="ms-auto lg:hidden">
                    <Button variant="ghost" size="sm" icon="x" aria-label="Close navigation" @click="sidebarOpen = false" />
                </div>
            </header>

            <div
                class="mt-1.5 h-full overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-300 [&::-webkit-scrollbar-track]:bg-gray-100 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500 dark:[&::-webkit-scrollbar-track]:bg-neutral-700"
            >
                <nav class="flex w-full flex-col flex-wrap pb-3">
                    <ul class="flex flex-col gap-y-1">
                        <AppSidebarNav />
                    </ul>
                </nav>
            </div>
        </div>
    </aside>

    <!-- Page -->
    <main class="min-h-full w-full px-4 pt-14 sm:px-6 md:px-8 lg:ps-72">
        <div class="relative m-auto max-w-7xl pt-8 pb-4 sm:pb-6 md:pb-8">
            <slot />
        </div>
    </main>

    <Toast position="top-right" :max-toasts="5" />
</template>
