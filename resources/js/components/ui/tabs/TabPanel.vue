<script setup lang="ts">
import { computed, inject } from 'vue';
import { tabsKey } from './context';

const props = withDefaults(defineProps<{ name?: string | null }>(), { name: null });

const tabs = inject(tabsKey);

if (!tabs) {
    throw new Error('<TabPanel> must be used inside <Tabs>.');
}

const index = tabs.registerPanel();

const active = computed(() => tabs.isActive(props.name, index));

const classes = computed(() => {
    switch (tabs.variant.value) {
        case 'outlined':
            return 'rounded-box border border-black/10 bg-white p-3 dark:border-white/10 dark:bg-neutral-800';
        case 'non-contained':
            return 'mt-2';
        case 'underline':
            return 'pt-4';
        default:
            return '';
    }
});
</script>

<template>
    <div v-show="active" role="tabpanel" :class="['text-start text-neutral-900 dark:text-neutral-50', classes]" :data-name="name ?? undefined" data-slot="tabs-panel">
        <slot />
    </div>
</template>
