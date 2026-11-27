import { router } from '@inertiajs/vue3';
import { computed, ref, watch, type MaybeRefOrGetter, toValue } from 'vue';

/** Same default as Controller::PER_PAGE_OPTIONS. */
export const DEFAULT_PER_PAGE = 10;

/**
 * Server-paged tables. Updates the current URL so any search or filter in
 * the query string is kept, and reloads only the given props.
 */
export function useServerPagination(only: string[]) {
    return (change: { page?: number; per_page?: number }) => {
        const url = new URL(window.location.href);

        if (change.per_page !== undefined) {
            // Changing the page size goes back to page 1.
            url.searchParams.delete('page');

            if (change.per_page === DEFAULT_PER_PAGE) {
                url.searchParams.delete('per_page');
            } else {
                url.searchParams.set('per_page', String(change.per_page));
            }
        }

        if (change.page !== undefined) {
            url.searchParams.set('page', String(change.page));
        }

        router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: true, only });
    };
}

/**
 * Tables that already have all their rows (roles, admin report). Slices them
 * locally and resets to page 1 when the rows change.
 */
export function useClientPagination<T>(items: MaybeRefOrGetter<T[]>) {
    const page = ref(1);
    const perPage = ref(DEFAULT_PER_PAGE);

    const total = computed(() => toValue(items).length);
    const rows = computed(() => toValue(items).slice((page.value - 1) * perPage.value, page.value * perPage.value));

    watch([() => toValue(items), perPage], () => {
        page.value = 1;
    });

    return { page, perPage, total, rows };
}
