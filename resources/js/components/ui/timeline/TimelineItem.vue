<script setup lang="ts">
import { computed } from 'vue';
import Badge from '../badge/Badge.vue';
import type { UiColor } from '../colors';
import Text from '../typography/Text.vue';

/** One event in a history: what happened, when, who did it, and what they said (the slot). */
const props = withDefaults(
    defineProps<{
        label: string;
        color?: UiColor;
        /** When it happened: a Date or an ISO 8601 string. */
        at?: Date | string | null;
        actor?: string | null;
        role?: string | null;
    }>(),
    { color: 'neutral', at: null, actor: null, role: null },
);

const dot = computed(() => {
    switch (props.color) {
        case 'emerald':
        case 'green':
        case 'teal':
            return 'bg-emerald-500';
        case 'amber':
        case 'yellow':
        case 'orange':
            return 'bg-amber-500';
        case 'red':
        case 'rose':
            return 'bg-red-500';
        case 'sky':
        case 'blue':
        case 'cyan':
            return 'bg-sky-500';
        case 'indigo':
        case 'violet':
        case 'purple':
            return 'bg-indigo-500';
        default:
            return 'bg-neutral-400 dark:bg-neutral-500';
    }
});

const date = computed(() => (props.at ? new Date(props.at) : null));

// Same shape as the Blade version: 05 Mar 2026, 14:30
const formatted = computed(() => {
    if (!date.value || Number.isNaN(date.value.getTime())) return null;

    const d = date.value;
    const pad = (n: number) => String(n).padStart(2, '0');
    const month = d.toLocaleString('en-GB', { month: 'short' });

    return `${pad(d.getDate())} ${month} ${d.getFullYear()}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
});
</script>

<template>
    <li class="group/item relative ps-6 pb-6 last:pb-0">
        <!-- The rail stops at the last entry. -->
        <span class="absolute start-[5px] top-4 bottom-0 w-px bg-black/10 group-last/item:hidden dark:bg-white/10" aria-hidden="true" />
        <span :class="['absolute start-0 top-1.5 size-[11px] rounded-full ring-4 ring-white dark:ring-neutral-800', dot]" aria-hidden="true" />

        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <Badge size="sm" :color="color">{{ label }}</Badge>
            <time v-if="formatted" :datetime="date!.toISOString()" :title="formatted" class="text-xs tabular-nums opacity-60">{{ formatted }}</time>
        </div>

        <Text v-if="actor" class="mt-1 text-sm">
            <span class="font-medium">{{ actor }}</span><span v-if="role" class="opacity-60"> · {{ role }}</span>
        </Text>

        <Text v-if="$slots.default" class="mt-2 rounded-lg bg-black/[0.03] px-3 py-2 text-sm whitespace-pre-line dark:bg-white/5"><slot /></Text>
    </li>
</template>
