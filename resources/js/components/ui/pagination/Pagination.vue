<script setup lang="ts">
import { computed } from 'vue';
import Button from '../button/Button.vue';
import Select from '../select/Select.vue';

/**
 * Pager for the bottom of a table. It only emits changes; the page decides
 * whether to fetch or slice. Hidden when everything fits on one page.
 *
 *   <Pagination :page="users.meta.current_page" :per-page="users.meta.per_page" :total="users.meta.total"
 *               @update:page="go({ page: $event })" @update:per-page="go({ per_page: $event })" />
 */
const props = withDefaults(
    defineProps<{
        page: number;
        perPage: number;
        total: number;
        /** Same as Controller::PER_PAGE_OPTIONS. */
        perPageOptions?: number[];
        label?: string;
    }>(),
    { perPageOptions: () => [10, 25, 50], label: 'Pages' },
);

const emit = defineEmits<{
    'update:page': [page: number];
    'update:perPage': [perPage: number];
}>();

const lastPage = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)));
const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1));
const to = computed(() => Math.min(props.page * props.perPage, props.total));

const visible = computed(() => props.total > Math.min(...props.perPageOptions));

const sizes = computed(() => props.perPageOptions.map((size) => ({ value: size, label: String(size) })));

const perPageModel = computed({
    get: () => props.perPage,
    set: (value) => emit('update:perPage', Number(value)),
});

/** First, last and the neighbours of the current page. null is a gap. */
const pages = computed<Array<number | null>>(() => {
    const current = props.page;
    const last = lastPage.value;
    const shown = [...new Set([1, current - 1, current, current + 1, last])].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b);

    return shown.flatMap((page, index) => (index > 0 && page - shown[index - 1] > 1 ? [null, page] : [page]));
});

const go = (page: number) => {
    if (page !== props.page && page >= 1 && page <= lastPage.value) {
        emit('update:page', page);
    }
};
</script>

<template>
    <nav v-if="visible" class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3" :aria-label="label" data-slot="pagination">
        <div class="flex items-center gap-4 text-sm text-neutral-500 dark:text-neutral-400">
            <span class="tabular-nums">{{ from }}–{{ to }} of {{ total }}</span>
            <span class="flex items-center gap-2">
                <span aria-hidden="true">Rows</span>
                <Select v-model="perPageModel" :options="sizes" size="sm" label="Rows per page" class="w-20" />
            </span>
        </div>

        <div class="flex items-center gap-1">
            <Button variant="ghost" color="neutral" size="sm" icon="caret-left" :disabled="page <= 1" aria-label="Previous page" @click="go(page - 1)" />
            <template v-for="(item, index) in pages" :key="item ?? `gap-${index}`">
                <span v-if="item === null" class="w-6 text-center text-sm text-neutral-400 dark:text-neutral-500" aria-hidden="true">…</span>
                <Button
                    v-else
                    :variant="item === page ? 'outline' : 'ghost'"
                    color="neutral"
                    size="sm"
                    class="min-w-9 justify-center tabular-nums"
                    :aria-current="item === page ? 'page' : undefined"
                    :aria-label="`Page ${item}`"
                    @click="go(item)"
                >
                    {{ item }}
                </Button>
            </template>
            <Button variant="ghost" color="neutral" size="sm" icon="caret-right" :disabled="page >= lastPage" aria-label="Next page" @click="go(page + 1)" />
        </div>
    </nav>
</template>
