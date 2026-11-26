<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        level?: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6';
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '';
    }>(),
    { level: 'h2', size: 'sm' },
);

const sizeClass = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'text-sm';
        case 'md':
            return 'text-lg';
        case 'lg':
            return 'text-xl';
        case 'xl':
            return 'text-2xl';
        default:
            return 'text-base';
    }
});
</script>

<template>
    <!-- No alignment of its own: a heading follows its container, so it centres inside empty states. -->
    <component :is="level" :class="['font-semibold text-neutral-800 dark:text-white', sizeClass]" data-slot="heading">
        <slot />
    </component>
</template>
