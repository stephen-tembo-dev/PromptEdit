<script setup lang="ts">
import { computed, provide, ref } from 'vue';
import { accordionKey } from './context';

const props = withDefaults(
    defineProps<{
        /** Let several items stay open at once. */
        multiple?: boolean;
        /** Put the chevron before the title. */
        reverse?: boolean;
    }>(),
    { multiple: false, reverse: false },
);

const open = ref<string[]>([]);

provide(accordionKey, {
    multiple: computed(() => props.multiple),
    reverse: computed(() => props.reverse),
    isOpen: (id) => open.value.includes(id),
    setOpen: (id, value) => {
        if (!props.multiple) {
            open.value = value ? [id] : open.value.filter((item) => item !== id);
            return;
        }

        open.value = value ? [...new Set([...open.value, id])] : open.value.filter((item) => item !== id);
    },
});
</script>

<template>
    <div class="flex w-full flex-col">
        <slot />
    </div>
</template>
