<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, useSlots } from 'vue';
import { avatarBadgeColors, avatarColors } from '../colors';
import Icon from '../icon/Icon.vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        src?: string | null;
        /** Full name: gives the initials, the alt text and (with color="auto") a stable colour. */
        name?: string | null;
        initials?: string | null;
        /** Use only the first initial. */
        singleInitial?: boolean;
        icon?: string | null;
        iconVariant?: string;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
        circle?: boolean;
        /** A palette colour, or `auto` to pick one from the name. */
        color?: string | null;
        colorSeed?: string | null;
        alt?: string | null;
        href?: string | null;
        /** A small status dot. `true` for an empty dot, or text such as a count. */
        badge?: boolean | string | number | null;
        badgeColor?: string | null;
        badgeCircle?: boolean;
        badgePosition?: 'top left' | 'top right' | 'bottom left' | 'bottom right';
        badgeVariant?: 'solid' | 'outline';
        badgeClass?: string;
    }>(),
    {
        src: null,
        name: null,
        initials: null,
        singleInitial: false,
        icon: null,
        iconVariant: 'regular',
        size: 'md',
        circle: false,
        color: null,
        colorSeed: null,
        alt: null,
        href: null,
        badge: null,
        badgeColor: null,
        badgeCircle: false,
        badgePosition: 'bottom right',
        badgeVariant: 'solid',
        badgeClass: '',
    },
);

const slots = useSlots();

const PALETTE = ['red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];

const resolvedInitials = computed(() => {
    if (props.initials) return props.initials;
    if (!props.name) return null;

    const parts = props.name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return null;
    if (props.singleInitial) return parts[0][0].toUpperCase();
    if (parts.length > 1) return (parts[0][0] + parts[1][0]).toUpperCase();

    return parts[0][0].toUpperCase() + (parts[0][1] ?? '').toLowerCase();
});

const hasText = computed(() => !!props.icon || !!resolvedInitials.value || !!slots.default);
const resolvedIcon = computed(() => props.icon ?? (hasText.value ? null : 'user'));

// CRC32, matching the Blade version so a name keeps the same colour.
function crc32(text: string): number {
    let crc = 0 ^ -1;

    for (let i = 0; i < text.length; i++) {
        crc ^= text.charCodeAt(i);
        for (let k = 0; k < 8; k++) crc = (crc >>> 1) ^ (0xedb88320 & -(crc & 1));
    }

    return (crc ^ -1) >>> 0;
}

const resolvedColor = computed(() => {
    if (props.color !== 'auto') return props.color;

    const seed = props.colorSeed ?? props.name ?? props.icon ?? resolvedInitials.value ?? '';

    return PALETTE[crc32(seed) % PALETTE.length];
});

const radius = computed(() => {
    if (props.circle) return '[--avatar-radius:calc(infinity*1px)]';

    return { xl: '[--avatar-radius:var(--radius-xl)]', lg: '[--avatar-radius:var(--radius-lg)]', md: '[--avatar-radius:var(--radius-lg)]', sm: '[--avatar-radius:var(--radius-md)]', xs: '[--avatar-radius:var(--radius-sm)]' }[props.size];
});

const sizeClass = computed(
    () =>
        ({
            xl: '[:where(&)]:size-16 [:where(&)]:text-base',
            lg: '[:where(&)]:size-12 [:where(&)]:text-base',
            md: '[:where(&)]:size-10 [:where(&)]:text-sm',
            sm: '[:where(&)]:size-8 [:where(&)]:text-sm',
            xs: '[:where(&)]:size-6 [:where(&)]:text-xs',
        })[props.size],
);

const colorClass = computed(() => (resolvedColor.value && avatarColors[resolvedColor.value]) || 'bg-neutral-200 dark:bg-neutral-500 [&>[data-slot=icon]]:text-white!');

const iconSizeClass = computed(() => ({ lg: 'size-8', xl: 'size-6', md: 'size-6', sm: 'size-5', xs: 'size-4' })[props.size]);

const badgeClasses = computed(() => [
    'absolute z-10 ring-[2px] ring-white dark:ring-neutral-900',
    'flex items-center justify-center overflow-hidden tabular-nums',
    'text-[.625rem] font-medium text-black dark:text-white',
    props.badgeVariant === 'outline' ? 'after:absolute after:inset-[3px] after:bg-white dark:after:bg-neutral-900' : '',
    props.badgeCircle ? 'rounded-full after:rounded-full' : 'rounded-[3px] after:rounded-[1px]',
    props.size === 'sm' || props.size === 'xs' ? 'h-2 min-w-2' : 'h-3 min-w-3',
    { 'top left': 'top-0 left-0', 'top right': 'top-0 right-0', 'bottom left': 'bottom-0 left-0', 'bottom right': 'bottom-0 right-0' }[props.badgePosition],
    (props.badgeColor && avatarBadgeColors[props.badgeColor]) || 'bg-white dark:bg-neutral-900',
    props.badgeClass,
]);

const classes = computed(() => ['flex items-center justify-center overflow-hidden rounded-(--avatar-radius)', radius.value, sizeClass.value, colorClass.value]);
</script>

<template>
    <div class="relative w-fit" data-slot="avatar" :data-size="size">
        <component :is="href ? Link : 'div'" :href="href ?? undefined" v-bind="$attrs" :class="classes">
            <img v-if="src" :src="src" :alt="alt ?? name ?? ''" class="size-full object-cover" />
            <Icon v-else-if="resolvedIcon" :name="resolvedIcon" :variant="iconVariant" :class="['text-black!', iconSizeClass]" />
            <span v-else class="select-none"><slot>{{ resolvedInitials }}</slot></span>

            <div v-if="badge !== null && badge !== false" :class="badgeClasses" aria-hidden="true">
                {{ typeof badge === 'string' || typeof badge === 'number' ? badge : '' }}
            </div>
        </component>
    </div>
</template>
