<script setup lang="ts">
import { computed, provide } from 'vue';
import { tabsKey, type TabKey, type TabsVariant } from './context';

const props = withDefaults(defineProps<{ variant?: TabsVariant }>(), { variant: 'underline' });

/** The active tab: its `name`, or its position (0, 1, …) when tabs have no names. Defaults to the first tab. */
const active = defineModel<TabKey | null>({ default: null });

let tabCount = 0;
let panelCount = 0;

provide(tabsKey, {
    active,
    variant: computed(() => props.variant),
    registerTab: () => tabCount++,
    registerPanel: () => panelCount++,
    select: (key) => (active.value = key),
    isActive: (name, index) => {
        if (active.value === null) return index === 0;

        return name != null && name !== '' ? name === active.value : Number(index) === Number(active.value);
    },
});

const classes = computed(() => {
    switch (props.variant) {
        case 'outlined':
            return [
                'rounded-3xl text-neutral-800 dark:text-neutral-200',
                // Square off the panel corner under the first or last tab when that tab is active.
                '[&:has(:first-child[data-slot=tabs-group].justify-start_>_:first-child[data-active=true])_[data-slot=tabs-panel]]:rounded-tl-none',
                '[&:has(:first-child[data-slot=tabs-group].justify-start_>_:first-child:is(:focus,:hover))_[data-slot=tabs-panel]]:rounded-tl-none',
                '[&:has(:first-child[data-slot=tabs-group].justify-end_>_:last-child[data-active=true])_[data-slot=tabs-panel]]:rounded-tr-none',
                '[&:has(:first-child[data-slot=tabs-group].justify-end_>_:last-child:is(:focus,:hover))_[data-slot=tabs-panel]]:rounded-tr-none',
            ];
        case 'underline':
            return ['text-neutral-800 dark:text-neutral-200'];
        default:
            return [];
    }
});
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
