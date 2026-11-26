import { computed, nextTick, provide, ref, shallowReactive, type Ref } from 'vue';
import { listboxKey, type ListboxOptionRecord, type ListboxValue } from './context';

export interface ListboxOptions {
    model: Ref<ListboxValue | ListboxValue[] | null>;
    multiple: () => boolean;
    minSelection: () => number | null;
    maxSelection: () => number | null;
    disableUnselectedAtMax: () => boolean;
    checkIcon: () => string;
    checkIconClass: () => string | null;
    loading: () => boolean;
    /** Called after a pick in single mode, to close the panel. */
    onSingleSelect?: () => void;
    /** Called after any pick. */
    onSelect?: (value: ListboxValue) => void;
}

/**
 * The listbox engine shared by Select and Combobox: option registration, filtering, the active (keyboard)
 * option, and selection rules. It stands in for the @sheaf/rover Alpine plugin the Blade kit used.
 */
export function useListbox(options: ListboxOptions) {
    const records = shallowReactive(new Map<string, ListboxOptionRecord>());
    const query = ref('');
    const activeId = ref<string | null>(null);
    let createHandler: (() => void) | null = null;
    const listEl = ref<HTMLElement | null>(null);

    const selectedValues = computed<ListboxValue[]>(() => {
        const value = options.model.value;
        if (Array.isArray(value)) return value;

        return value === null || value === undefined || value === '' ? [] : [value];
    });

    const sameValue = (a: ListboxValue, b: ListboxValue) => String(a) === String(b);

    const isSelected = (value: ListboxValue) => selectedValues.value.some((selected) => sameValue(selected, value));

    const isVisible = (record: ListboxOptionRecord) => {
        const search = query.value.trim().toLowerCase();

        return search === '' || record.searchLabel.toLowerCase().includes(search);
    };

    const ordered = computed(() => [...records.values()].sort((a, b) => a.order - b.order));
    const visibleOptions = computed(() => ordered.value.filter(isVisible));
    const navigable = computed(() => visibleOptions.value.filter((record) => !record.disabled && !isBlockedByMax(record.value)));

    const hasSelection = computed(() => selectedValues.value.length > 0);

    const isAtMax = computed(() => {
        const max = options.maxSelection();
        return options.multiple() && max !== null && selectedValues.value.length >= max;
    });

    function isBlockedByMax(value: ListboxValue): boolean {
        return options.disableUnselectedAtMax() && isAtMax.value && !isSelected(value);
    }

    function labelFor(value: ListboxValue): string {
        for (const record of records.values()) {
            if (sameValue(record.value, value)) return record.label;
        }

        return String(value);
    }

    function select(value: ListboxValue): void {
        if (!options.multiple()) {
            const current = options.model.value;
            // Picking the selected option again clears it, as in the Blade kit.
            options.model.value = current !== null && !Array.isArray(current) && sameValue(current, value) ? null : value;
            options.onSelect?.(value);
            options.onSingleSelect?.();
            return;
        }

        const current = [...selectedValues.value];
        const index = current.findIndex((item) => sameValue(item, value));

        if (index !== -1) {
            const min = options.minSelection();
            if (min !== null && current.length <= min) return;

            current.splice(index, 1);
        } else {
            const max = options.maxSelection();
            if (max !== null && current.length >= max) return;

            current.push(value);
        }

        options.model.value = current;
        options.onSelect?.(value);
    }

    function deselect(value: ListboxValue): void {
        if (!options.multiple()) {
            options.model.value = null;
            return;
        }

        const min = options.minSelection();
        if (min !== null && selectedValues.value.length <= min) return;

        options.model.value = selectedValues.value.filter((item) => !sameValue(item, value));
    }

    function clear(): void {
        if (options.multiple()) {
            const min = options.minSelection();
            options.model.value = min ? selectedValues.value.slice(0, min) : [];
            return;
        }

        options.model.value = null;
    }

    function scrollActiveIntoView(): void {
        nextTick(() => listEl.value?.querySelector('[data-active]')?.scrollIntoView({ block: 'nearest' }));
    }

    function setActive(id: string | null): void {
        activeId.value = id;
        scrollActiveIntoView();
    }

    function move(step: 1 | -1): void {
        const list = navigable.value;
        if (list.length === 0) return;

        const index = list.findIndex((record) => record.id === activeId.value);
        const next = index === -1 ? (step === 1 ? 0 : list.length - 1) : (index + step + list.length) % list.length;

        setActive(list[next].id);
    }

    function activateFirst(): void {
        setActive(navigable.value[0]?.id ?? null);
    }

    function activateLast(): void {
        const list = navigable.value;
        setActive(list[list.length - 1]?.id ?? null);
    }

    function activateSelectedOrFirst(): void {
        const first = selectedValues.value[0];
        const selected = first === undefined ? undefined : navigable.value.find((record) => sameValue(record.value, first));

        if (selected) setActive(selected.id);
        else activateFirst();
    }

    /** Typing a letter jumps to the next option starting with it. */
    function typeahead(key: string): void {
        const list = navigable.value;
        const start = list.findIndex((record) => record.id === activeId.value);

        for (let i = 1; i <= list.length; i++) {
            const record = list[(start + i) % list.length];

            if (record.label.toLowerCase().startsWith(key.toLowerCase())) {
                setActive(record.id);
                return;
            }
        }
    }

    function selectActive(): boolean {
        const record = activeId.value ? records.get(activeId.value) : undefined;

        if (record && isVisible(record) && !record.disabled) {
            select(record.value);
            return true;
        }

        if (createHandler) {
            createHandler();
            return true;
        }

        return false;
    }

    provide(listboxKey, {
        register: (record) => {
            records.set(record.id, record);
            return () => {
                records.delete(record.id);
                if (activeId.value === record.id) activeId.value = null;
            };
        },
        isSelected,
        isActive: (id) => activeId.value === id,
        isVisible,
        isBlockedByMax,
        select,
        setActive,
        query,
        checkIcon: computed(options.checkIcon),
        checkIconClass: computed(options.checkIconClass),
        loading: computed(options.loading),
        setCreateHandler: (handler) => (createHandler = handler),
    });

    return {
        records,
        query,
        activeId,
        listEl,
        selectedValues,
        visibleOptions,
        hasSelection,
        isSelected,
        labelFor,
        select,
        deselect,
        clear,
        move,
        activateFirst,
        activateLast,
        activateSelectedOrFirst,
        typeahead,
        selectActive,
        setActive,
    };
}
