<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, useAttrs, useSlots } from 'vue';
import { buttonColors, type UiColor } from '../colors';
import Icon from '../icon/Icon.vue';
import IconLoading from '../icon/IconLoading.vue';

defineOptions({ inheritAttrs: false });

export type ButtonVariant = 'primary' | 'solid' | 'soft' | 'outline' | 'ghost' | 'danger' | 'none';
export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset';
        size?: ButtonSize;
        color?: UiColor | 'primary' | null;
        variant?: ButtonVariant;
        disabled?: boolean;
        loading?: boolean;
        icon?: string | null;
        iconAfter?: string | null;
        iconVariant?: string | null;
        iconClasses?: string | null;
        /** Renders an Inertia <Link>. Add `target` or `download` (or `external`) for a plain <a>. */
        href?: string | null;
        /** HTTP method for an Inertia link, e.g. `post` for a logout button. */
        method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
        external?: boolean;
        as?: 'button' | 'a' | 'div';
    }>(),
    {
        type: 'button',
        size: 'sm',
        color: null,
        variant: 'primary',
        disabled: false,
        loading: false,
        icon: null,
        iconAfter: null,
        iconVariant: null,
        iconClasses: null,
        href: null,
        method: 'get',
        external: false,
        as: 'button',
    },
);

const slots = useSlots();
const attrs = useAttrs();

// No content means an icon-only, square button.
const squared = computed(() => !slots.default);

const sizeClasses = computed(() => {
    const { size, icon, iconAfter } = props;
    const sq = squared.value;

    switch (size) {
        case 'lg':
            return ['[:where(&)]:h-12 text-md', sq ? 'w-12' : `${icon ? 'ps-4' : 'ps-5'} ${iconAfter ? 'pe-4' : 'pe-5'}`];
        case 'md':
            return ['[:where(&)]:h-10 text-base', sq ? 'w-10' : `${icon ? 'ps-3' : 'ps-4'} ${iconAfter ? 'pe-3' : 'pe-4'}`];
        case 'xs':
            return ['[:where(&)]:h-6 text-xs', sq ? 'w-6' : `${icon ? 'ps-1' : 'ps-2'} ${iconAfter ? 'pe-1' : 'pe-2'}`];
        case 'sm':
        default:
            return ['[:where(&)]:h-9 text-sm', sq ? 'w-9' : `${icon ? 'ps-2' : 'ps-3'} ${iconAfter ? 'pe-2' : 'pe-3'}`];
    }
});

const resolvedIconVariant = computed(() => {
    if (props.iconVariant) return props.iconVariant;

    switch (props.size) {
        case 'xs':
            return 'micro';
        case 'sm':
            return 'mini';
        default:
            return squared.value ? 'mini' : 'micro';
    }
});

const iconClassList = computed(() => [
    props.iconClasses,
    props.size !== 'xs' ? 'size-5' : 'size-4',
    props.variant === 'primary' ? '!text-[var(--color-primary-fg)]' : '',
    props.variant === 'outline' ? '!text-[var(--color-primary-content)]' : '',
]);

const colorClasses = computed(() => (props.color ? buttonColors[props.color] : ''));

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'primary':
            return [
                'bg-[var(--color-primary)] hover:bg-[var(--color-primary)]/90',
                'text-[var(--color-primary-fg)]',
                'border border-black/10 dark:border-0',
                colorClasses.value,
            ];
        case 'solid':
            return ['bg-neutral-800/5 hover:bg-neutral-800/10 dark:bg-white/10 dark:hover:bg-white/20', 'text-neutral-800 dark:text-white'];
        case 'soft':
            return ['text-neutral-500 hover:text-neutral-800 dark:text-neutral-400 dark:hover:text-white', 'bg-transparent'];
        case 'outline':
            return [
                'border border-[--alpha(var(--color-primary)/20%)] hover:border-[--alpha(var(--color-primary)/25%)]',
                'bg-[--alpha(var(--color-primary)/5%)] hover:bg-[--alpha(var(--color-primary)/7%)]',
                // The content shade is dark enough to read on the tint.
                'text-[var(--color-primary-content)]',
                colorClasses.value,
            ];
        case 'ghost':
            return [
                'bg-transparent hover:bg-[--alpha(var(--color-neutral-900)/5%)] dark:hover:bg-[--alpha(var(--color-white)/5%)]',
                'text-neutral-800 dark:text-white',
            ];
        case 'danger':
            return ['dark:shadow-none', 'bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500', 'text-white'];
        default:
            return [];
    }
});

const classes = computed(() => [
    'relative [:where(&)]:inline-flex items-center font-medium justify-center gap-x-2 whitespace-nowrap transition-colors duration-200',
    'disabled:opacity-55 dark:disabled:opacity-55 disabled:cursor-default disabled:pointer-events-none cursor-pointer',
    'aria-disabled:opacity-55 aria-disabled:pointer-events-none',
    props.variant !== 'none' ? '[&_a]:no-underline [&_a]:decoration-none [&_a:hover]:no-underline [:where(&)]:rounded-field' : '',
    // The loading spinner shows over the content, which goes transparent but keeps the button's width.
    '[&>[data-loading]:first-child]:flex',
    '[&>[data-loading]:first-child~*]:opacity-0',
    ...sizeClasses.value,
    ...variantClasses.value,
]);

const ariaLabel = computed(() => {
    if (attrs['aria-label'] || !squared.value) return attrs['aria-label'] as string | undefined;

    const name = (props.icon ?? props.iconAfter ?? 'Button').replace(/^hi:/, '');

    return name.replace(/[-_]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
});

const kind = computed<'link' | 'a' | 'div' | 'button'>(() => {
    if (props.href) {
        return props.external || 'target' in attrs || 'download' in attrs ? 'a' : 'link';
    }

    if (props.as === 'a') return 'a';
    if (props.as === 'div') return 'div';

    return 'button';
});

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <Link
        v-if="kind === 'link'"
        :href="href!"
        :method="method"
        v-bind="attrs"
        :class="classes"
        :aria-label="ariaLabel"
        :aria-busy="loading ? 'true' : 'false'"
        :aria-disabled="disabled ? 'true' : undefined"
        data-slot="button"
    >
        <div v-if="loading" data-loading="true" class="absolute inset-0 hidden items-center justify-center">
            <IconLoading :variant="resolvedIconVariant" :class="iconClassList" data-slot="loading-indicator" />
        </div>
        <Icon v-if="icon" :name="icon" :variant="resolvedIconVariant" :class="iconClassList" />
        <span v-if="$slots.default" data-text><slot /></span>
        <Icon v-if="iconAfter" :name="iconAfter" :variant="resolvedIconVariant" :class="iconClassList" />
    </Link>

    <component
        :is="kind"
        v-else
        v-bind="attrs"
        :href="kind === 'a' ? (href ?? undefined) : undefined"
        :type="kind === 'button' ? type : undefined"
        :disabled="kind === 'button' ? isDisabled : undefined"
        :role="kind === 'a' && !href ? 'button' : undefined"
        :class="classes"
        :aria-label="ariaLabel"
        :aria-busy="loading ? 'true' : 'false'"
        :aria-disabled="disabled ? 'true' : 'false'"
        data-slot="button"
    >
        <div v-if="loading" data-loading="true" class="absolute inset-0 hidden items-center justify-center">
            <IconLoading :variant="resolvedIconVariant" :class="iconClassList" data-slot="loading-indicator" />
        </div>
        <Icon v-if="icon" :name="icon" :variant="resolvedIconVariant" :class="iconClassList" />
        <span v-if="$slots.default" data-text><slot /></span>
        <Icon v-if="iconAfter" :name="iconAfter" :variant="resolvedIconVariant" :class="iconClassList" />
    </component>
</template>
