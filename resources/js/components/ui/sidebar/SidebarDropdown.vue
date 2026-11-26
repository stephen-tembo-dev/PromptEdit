<script setup lang="ts">
import { ref, useId, watch } from 'vue';
import Icon from '../icon/Icon.vue';

/**
 * A collapsible section of sidebar links.
 *
 *   <SidebarDropdown title="Administration" icon="gear" :active="$page.url.startsWith('/admin')">
 *       <SidebarDropdownLink title="Users" href="/admin/users" />
 *   </SidebarDropdown>
 */
const props = withDefaults(defineProps<{ title: string; icon?: string | null; active?: boolean }>(), { icon: 'house', active: false });

const open = ref(props.active);
const id = useId();

watch(
    () => props.active,
    (value) => value && (open.value = true),
);
</script>

<template>
    <li class="w-full px-2 lg:px-5">
        <button
            type="button"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-controls="id"
            class="flex w-full items-center gap-x-4 rounded-lg px-3 py-2 text-start text-sm text-gray-800 hover:bg-neutral-100 focus:bg-neutral-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-1 disabled:pointer-events-none disabled:opacity-50 dark:text-neutral-300 dark:hover:bg-primary-700/25 dark:focus:bg-primary-700/25 dark:focus-visible:ring-primary-400 dark:focus-visible:ring-offset-neutral-900"
            @click="open = !open"
        >
            <Icon v-if="icon" :name="icon" class="size-5" />
            {{ title }}
            <Icon name="caret-down" variant="bold" :class="['ms-auto size-3 transition-transform', open ? '-rotate-180' : '']" />
        </button>

        <div v-show="open" :id="id" class="w-full" role="region">
            <ul class="relative mt-1 flex flex-col gap-y-1 ps-7 before:absolute before:start-4.5 before:top-0 before:h-full before:w-0.5 before:bg-gray-100 dark:before:bg-neutral-700">
                <slot />
            </ul>
        </div>
    </li>
</template>
