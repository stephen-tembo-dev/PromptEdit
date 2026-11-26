<script setup lang="ts">
import { inject, onBeforeUnmount, onMounted } from 'vue';
import { listboxKey } from './context';

/**
 * A "create new" row at the end of a <Select> or <Combobox>. Enter triggers it when no option is active,
 * so typing a new name and pressing Enter creates it.
 *
 *   <SelectCreateOption @create="createTag(query)">Create "{{ query }}"</SelectCreateOption>
 */
const emit = defineEmits<{ create: [] }>();

const listbox = inject(listboxKey);

onMounted(() => listbox?.setCreateHandler(() => emit('create')));
onBeforeUnmount(() => listbox?.setCreateHandler(null));
</script>

<template>
    <li
        role="option"
        aria-selected="false"
        data-slot="create-option"
        class="group col-span-full w-full cursor-pointer items-center gap-x-2 self-center rounded-[calc(var(--popup-round)-var(--popup-padding))] px-3 py-2 text-sm hover:bg-neutral-800/5 dark:hover:bg-white/5"
        @click="emit('create')"
    >
        <slot />
    </li>
</template>
