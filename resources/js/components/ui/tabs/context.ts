import type { ComputedRef, InjectionKey, Ref } from 'vue';

export type TabsVariant = 'underline' | 'outlined' | 'non-contained' | 'pills';
export type TabKey = string | number;

export interface TabsContext {
    active: Ref<TabKey | null>;
    variant: ComputedRef<TabsVariant>;
    /** Tabs and panels without a `name` are matched by their order. */
    registerTab: () => number;
    registerPanel: () => number;
    select: (key: TabKey) => void;
    isActive: (name: string | null | undefined, index: number) => boolean;
}

export const tabsKey: InjectionKey<TabsContext> = Symbol('ui-tabs');
