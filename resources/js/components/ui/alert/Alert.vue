<script setup lang="ts">
import { computed } from 'vue';
import { alertColors, type UiColor } from '../colors';
import Icon from '../icon/Icon.vue';

const props = withDefaults(
    defineProps<{
        variant?: 'info' | 'success' | 'warning' | 'error';
        /** Every alert carries an icon, so its meaning never rests on colour alone. Pass `false` to leave it out. */
        icon?: string | false | null;
        color?: UiColor | 'gray' | null;
    }>(),
    { variant: 'info', icon: null, color: null },
);

const resolvedIcon = computed(() => {
    if (props.icon === false) return null;
    if (props.icon) return props.icon;

    return { success: 'check-circle', warning: 'warning', error: 'warning-circle', info: 'info' }[props.variant];
});

// Errors and warnings interrupt a screen reader; information and success wait their turn.
const role = computed(() => (props.variant === 'error' || props.variant === 'warning' ? 'alert' : 'status'));

const resolvedColor = computed(() => props.color ?? ({ info: 'blue', success: 'green', warning: 'yellow', error: 'red' } as const)[props.variant]);
</script>

<template>
    <div
        :class="[
            'flex items-center gap-3 rounded-box border px-4 py-3.5 text-sm',
            // With a heading and a description, the icon aligns to the first line.
            '[&:has([data-slot=alert-heading]):has([data-slot=alert-description])]:[&_[data-slot=alert-icon]]:self-start',
            'border-[var(--border-color)] bg-[var(--bg-color)] text-[var(--text-color)] dark:border-[var(--border-color)]/20 dark:bg-[var(--bg-color)]/10',
            '[&_[data-slot=icon]]:text-(--icon-color)',
            alertColors[resolvedColor],
        ]"
        data-slot="alert"
        :role="role"
    >
        <div v-if="resolvedIcon" class="flex shrink-0 items-center justify-center" data-slot="alert-icon">
            <Icon :name="resolvedIcon" class="size-5 text-[var(--icon-color)]" />
        </div>

        <div class="space-y-1">
            <slot />
        </div>

        <div v-if="$slots.controls" class="ml-auto h-full" data-slot="alert-controls">
            <slot name="controls" />
        </div>
    </div>
</template>
