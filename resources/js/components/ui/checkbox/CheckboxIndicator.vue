<script setup lang="ts">
import { computed } from 'vue';
import Icon from '../icon/Icon.vue';

const props = withDefaults(
    defineProps<{
        checked?: boolean;
        indeterminate?: boolean;
        disabled?: boolean;
        invalid?: boolean;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
        /** False when a parent element is the interactive control (the card variant). */
        interactive?: boolean;
    }>(),
    { checked: false, indeterminate: false, disabled: false, invalid: false, size: 'md', interactive: true },
);

const emit = defineEmits<{ toggle: [] }>();

const sizeClass = computed(() => ({ xs: 'size-4', sm: 'size-5', md: 'size-6', lg: 'size-7', xl: 'size-8' })[props.size]);
const iconSizeClass = computed(() => ({ xs: 'size-3', sm: 'size-4', md: 'size-4', lg: 'size-5', xl: 'size-5' })[props.size]);

const toggle = () => {
    if (!props.disabled) emit('toggle');
};
</script>

<template>
    <div
        :role="interactive ? 'checkbox' : undefined"
        :tabindex="interactive ? (disabled ? -1 : 0) : undefined"
        :aria-checked="interactive ? (indeterminate ? 'mixed' : checked ? 'true' : 'false') : undefined"
        :aria-invalid="invalid ? 'true' : undefined"
        :aria-disabled="disabled ? 'true' : undefined"
        :data-checked="checked && !indeterminate ? '' : undefined"
        :data-indeterminate="indeterminate ? '' : undefined"
        :class="[
            'flex appearance-none items-center justify-center overflow-hidden border',
            'bg-white dark:bg-neutral-800',
            'transition-all duration-200',
            'shadow-none dark:shadow-sm',
            'focus:ring-2 focus:ring-offset-0 focus:outline-none',
            'rounded-field',
            sizeClass,
            disabled ? 'cursor-not-allowed opacity-50 shadow-none' : 'hover:border-neutral-400 dark:hover:border-neutral-500',
            invalid
                ? 'border-2 border-red-600/30 focus:border-red-600/30 focus:ring-red-600/20 dark:border-red-400/30 dark:focus:border-red-400/30 dark:focus:ring-red-400/20'
                : 'border-black/10 focus:border-black/15 focus:ring-neutral-900/15 dark:border-white/15 dark:focus:border-white/20 dark:focus:ring-neutral-100/15',
            'data-[checked]:border-[var(--color-primary)] data-[checked]:bg-[var(--color-primary)]',
            'data-[indeterminate]:border-[var(--color-primary)] data-[indeterminate]:bg-[var(--color-primary)]',
        ]"
        data-slot="checkbox-indicator"
        @click.stop="interactive && toggle()"
        @keydown.space.prevent="interactive && toggle()"
        @keydown.enter.prevent="interactive && toggle()"
    >
        <Icon v-if="checked && !indeterminate" name="check" variant="bold" :class="[iconSizeClass, '!text-[var(--color-primary-fg)] transition-all duration-200']" />
        <Icon v-if="indeterminate" name="minus" variant="bold" :class="[iconSizeClass, '!text-[var(--color-primary-fg)] transition-all duration-200']" />
    </div>
</template>
