<script setup lang="ts">
import { computed, inject } from 'vue';
import Button from '../button/Button.vue';
import { tabsKey } from './context';

const props = withDefaults(
    defineProps<{
        name?: string | null;
        label?: string | null;
        icon?: string | null;
        iconAfter?: string | null;
        iconVariant?: string | null;
    }>(),
    { name: null, label: null, icon: null, iconAfter: null, iconVariant: null },
);

const tabs = inject(tabsKey);

if (!tabs) {
    throw new Error('<Tab> must be used inside <Tabs>.');
}

const index = tabs.registerTab();

const active = computed(() => tabs.isActive(props.name, index));

const classes = computed(() => {
    switch (tabs.variant.value) {
        case 'outlined':
            return [
                'justify-center rounded-box rounded-b-none border-neutral-950/10 p-3 hover:bg-white/5 focus:bg-white/5 focus:outline-none dark:border-neutral-300/20',
                'data-[active=true]:bg-white dark:data-[active=true]:bg-neutral-800',
                'data-[active=true]:hover:bg-white dark:data-[active=true]:hover:bg-neutral-800',
                'data-[active=true]:focus:bg-white dark:data-[active=true]:focus:bg-neutral-800',
                'data-[active=true]:border data-[active=true]:border-b-0',
            ];
        case 'non-contained':
            return [
                'text-neutral-900 data-[active=true]:bg-neutral-900/10 dark:text-neutral-50 dark:data-[active=true]:bg-white/10',
                'hover:bg-white/10 focus:bg-white/10',
                'rounded-[calc(var(--noncontained-variant-radius)-var(--noncontained-variant-padding))]',
            ];
        case 'pills':
            return ['h-8 rounded-full text-sm font-medium whitespace-nowrap', 'data-[active=true]:bg-(--color-primary) data-[active=true]:text-(--color-primary-fg)'];
        default:
            return [
                'pb-3 text-sm font-medium whitespace-nowrap focus:outline-none',
                'text-neutral-500 dark:text-neutral-400',
                'hover:text-neutral-900 dark:hover:text-neutral-100',
                'data-[active=true]:text-primary data-[active=true]:hover:text-primary',
                'data-[active=true]:border-b-2 data-[active=true]:border-primary',
            ];
    }
});

function select(): void {
    tabs!.select(props.name ?? index);
}
</script>

<template>
    <Button
        variant="none"
        :icon="icon"
        :icon-after="iconAfter"
        :icon-variant="iconVariant"
        :class="[...classes, 'ps-1! pe-1!']"
        role="tab"
        :aria-selected="active ? 'true' : 'false'"
        :tabindex="active ? 0 : -1"
        :data-active="active ? 'true' : 'false'"
        :data-name="name ?? undefined"
        data-ui-tab
        @click="select"
        @focus="select"
    >
        <span class="flex-1"><slot>{{ label }}</slot></span>
    </Button>
</template>
