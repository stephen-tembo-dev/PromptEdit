<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { isCurrentUrl } from '@/lib/url';

const props = withDefaults(defineProps<{ title: string; href: string; active?: boolean | null; visible?: boolean }>(), { active: null, visible: true });

const page = usePage();

const isActive = computed(() => props.active ?? isCurrentUrl(props.href, page.url, true));
</script>

<template>
    <li v-if="visible">
        <Link
            :href="href"
            :aria-current="isActive ? 'page' : undefined"
            :class="[
                'flex gap-x-4 rounded-lg px-3 py-2 text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-1 dark:focus-visible:ring-primary-400 dark:focus-visible:ring-offset-neutral-900',
                isActive
                    ? 'bg-primary-50 font-medium text-primary-800 dark:bg-primary-700/25 dark:text-white'
                    : 'text-gray-700 hover:bg-neutral-100 hover:text-gray-900 focus:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-primary-700/25 dark:hover:text-white dark:focus:bg-primary-700',
            ]"
        >
            {{ title }}
        </Link>
    </li>
</template>
