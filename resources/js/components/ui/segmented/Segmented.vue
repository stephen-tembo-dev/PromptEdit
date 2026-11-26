<script setup lang="ts" generic="T extends string | number">
import { computed } from 'vue';

/**
 * A row of mutually exclusive choices, such as a queue or a period, that scopes the content below it.
 *
 *   <Segmented v-model="queue" :options="{ mine: 'My projects', all: 'All projects' }" label="Show" />
 *   <Segmented v-model="queue" :options="{ awaiting: ['Awaiting my decision', 3] }" />   (with a count)
 */
const props = defineProps<{
    options: Record<T, string | [string, number | string | null]> | Array<{ value: T; label: string; count?: number | string | null }>;
    label?: string | null;
}>();

const model = defineModel<T | null>({ default: null });

const items = computed(() => {
    if (Array.isArray(props.options)) {
        return props.options.map((option) => ({ key: option.value, text: option.label, count: option.count ?? null }));
    }

    return (Object.entries(props.options) as [T, string | [string, number | string | null]][]).map(([key, option]) => {
        const [text, count] = Array.isArray(option) ? option : [option, null];
        return { key, text, count };
    });
});

const isActive = (key: T) => String(model.value) === String(key);
</script>

<template>
    <div
        class="inline-flex max-w-full overflow-x-auto rounded-lg border border-black/10 bg-white p-0.5 text-sm font-medium [scrollbar-width:none] dark:border-white/10 dark:bg-neutral-800"
        role="group"
        :aria-label="label ?? undefined"
        data-slot="segmented"
    >
        <button
            v-for="item in items"
            :key="String(item.key)"
            type="button"
            :aria-pressed="isActive(item.key) ? 'true' : 'false'"
            :class="[
                'inline-flex shrink-0 items-center gap-1.5 rounded-md px-3 py-1.5 whitespace-nowrap transition-colors',
                'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary-600',
                isActive(item.key) ? 'bg-primary-700 text-white dark:bg-primary-700' : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white',
            ]"
            @click="model = item.key"
        >
            {{ item.text }}
            <span v-if="item.count !== null" :class="['rounded-full px-1.5 text-xs tabular-nums', isActive(item.key) ? 'bg-white/25' : 'bg-black/5 dark:bg-white/10']">
                {{ item.count }}
            </span>
        </button>
    </div>
</template>
