<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { isCurrentUrl } from '@/lib/url';
import Icon from '../icon/Icon.vue';

const props = withDefaults(
    defineProps<{
        href: string;
        label?: string;
        icon?: string | null;
        /** Highlighted state. Defaults to whether the current URL is this link. */
        active?: boolean | null;
    }>(),
    { label: 'Home', icon: null, active: null },
);

const page = usePage();

const isActive = computed(() => props.active ?? isCurrentUrl(props.href, page.url));
</script>

<template>
    <Link
        :href="href"
        :aria-current="isActive ? 'page' : undefined"
        :class="[
            'flex inline-flex snap-start items-center justify-center gap-2 gap-x-2 self-start rounded-lg border px-3 py-2 text-sm whitespace-nowrap focus:text-gray-500 focus:outline-hidden disabled:pointer-events-none disabled:opacity-50 dark:text-neutral-200 dark:hover:text-neutral-400 dark:focus:text-neutral-400',
            isActive
                ? 'border-gray-200! bg-white text-gray-800 shadow-2xs focus:text-gray-800 dark:border-neutral-700! dark:bg-neutral-800 dark:focus:text-neutral-200'
                : 'border-transparent text-gray-800 hover:text-gray-500',
        ]"
    >
        <Icon v-if="icon" :name="icon" class="size-4" />
        <slot>{{ label }}</slot>
    </Link>
</template>
