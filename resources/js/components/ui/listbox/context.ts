import type { ComputedRef, InjectionKey, Ref } from 'vue';

export type ListboxValue = string | number;

export interface ListboxOptionRecord {
    id: string;
    value: ListboxValue;
    label: string;
    searchLabel: string;
    disabled: boolean;
    /** Registration order, so keyboard movement follows the order on screen. */
    order: number;
}

export interface ListboxContext {
    register: (record: ListboxOptionRecord) => () => void;
    isSelected: (value: ListboxValue) => boolean;
    isActive: (id: string) => boolean;
    isVisible: (record: ListboxOptionRecord) => boolean;
    isBlockedByMax: (value: ListboxValue) => boolean;
    select: (value: ListboxValue) => void;
    setActive: (id: string | null) => void;
    query: Ref<string>;
    checkIcon: ComputedRef<string>;
    checkIconClass: ComputedRef<string | null>;
    loading: ComputedRef<boolean>;
    /** A <SelectCreateOption> registers here so Enter can trigger it when nothing else is active. */
    setCreateHandler: (handler: (() => void) | null) => void;
}

export const listboxKey: InjectionKey<ListboxContext> = Symbol('ui-listbox');
