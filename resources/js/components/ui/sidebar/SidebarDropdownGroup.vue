<script setup lang="ts">
import { ref, useId } from 'vue';
import Icon from '../icon/Icon.vue';

/** A collapsible sub-group nested inside a <SidebarDropdown>. */
const props = withDefaults(defineProps<{ title: string; active?: boolean }>(), { active: false });

const open = ref(props.active);
const id = useId();
</script>

<template>
    <li class="w-full">
        <button
            type="button"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-controls="id"
            class="flex w-full items-center gap-x-4 rounded-lg px-3 py-2 text-start text-sm text-gray-700 hover:bg-primary-100 hover:text-gray-900 focus:bg-primary-100 focus:outline-none dark:text-neutral-300 dark:hover:bg-primary-700/25 dark:hover:text-white dark:focus:bg-primary-700/25"
            @click="open = !open"
        >
            {{ title }}
            <Icon name="caret-down" variant="bold" :class="['ms-auto size-3 transition-transform', open ? '-rotate-180' : '']" />
        </button>

        <div v-show="open" :id="id" class="w-full" role="region">
            <ul class="mt-1 flex flex-col gap-y-1 ps-3">
                <slot />
            </ul>
        </div>
    </li>
</template>
