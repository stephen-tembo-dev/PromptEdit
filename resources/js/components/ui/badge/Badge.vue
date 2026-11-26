<script setup lang="ts">
import { computed } from 'vue';
import { badgeColors, type UiColor } from '../colors';
import Icon from '../icon/Icon.vue';

const props = withDefaults(
    defineProps<{
        variant?: 'soft' | 'solid' | 'outline' | 'muted';
        color?: UiColor | null;
        size?: 'xs' | 'sm' | 'md' | 'lg';
        /** Icon name. Pass `false` to suppress the check an emerald badge gets by default. */
        icon?: string | false | null;
        iconAfter?: string | null;
        iconVariant?: string | null;
        pill?: boolean;
    }>(),
    { variant: 'soft', color: null, size: 'md', icon: null, iconAfter: null, iconVariant: null, pill: false },
);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'text-[10px] leading-4 px-1.5 py-0 gap-1 [&>[data-slot=icon]]:size-3';
        case 'sm':
            return 'text-xs leading-4 px-2 py-0.5 gap-1 [&>[data-slot=icon]]:size-3.5';
        case 'lg':
            return 'text-sm leading-5 px-3 py-1 gap-1.5 [&>[data-slot=icon]]:size-4.5';
        default:
            return 'text-xs leading-5 px-2.5 py-0.5 gap-1.5 [&>[data-slot=icon]]:size-4';
    }
});

const variantClasses = computed(() => {
    switch (props.variant) {
        // Filled: the colour carries the badge.
        case 'solid':
            return ['bg-(--color-primary) text-(--color-primary-fg)', 'border border-black/5 dark:border-0'];
        // Outlined: colour on the border and text only.
        case 'outline':
            return ['border border-(--color-primary)/35 text-[var(--badge-ink,var(--color-primary))] bg-transparent'];
        // Neutral: no colour at all, for counts that should not compete with the content.
        case 'muted':
            return ['bg-neutral-800/5 dark:bg-white/10', 'text-neutral-700 dark:text-neutral-200'];
        // Soft (default): tinted background, readable colour text.
        default:
            return ['bg-(--color-primary)/10 dark:bg-(--color-primary)/20', 'text-[var(--badge-ink,var(--color-primary))]'];
    }
});

// Emerald means done everywhere, and the brand is emerald too, so a done badge also carries a check.
const leadingIcon = computed(() => {
    if (props.icon === false) return null;
    if (props.icon) return props.icon;
    if (props.color === 'emerald' && props.variant !== 'solid') return 'check';

    return null;
});

const leadingIconVariant = computed(() => props.iconVariant ?? (props.color === 'emerald' && !props.icon ? 'bold' : null));

const classes = computed(() => [
    'inline-flex items-center font-medium whitespace-nowrap align-middle',
    props.pill ? 'rounded-full' : 'rounded-md',
    sizeClasses.value,
    ...variantClasses.value,
    props.color && props.variant !== 'muted' ? badgeColors[props.color] : '',
]);
</script>

<template>
    <span :class="classes" data-slot="badge">
        <Icon v-if="leadingIcon" :name="leadingIcon" :variant="leadingIconVariant" class="shrink-0" />
        <slot />
        <Icon v-if="iconAfter" :name="iconAfter" :variant="iconVariant" class="shrink-0" />
    </span>
</template>
