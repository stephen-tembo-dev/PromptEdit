import type { ComputedRef, InjectionKey, Ref } from 'vue';

export interface DropdownContext {
    checkbox: ComputedRef<boolean>;
    radio: ComputedRef<boolean>;
    /** The selected value of a `radio` dropdown. */
    radioModel: Ref<unknown>;
    close: (focusTrigger?: boolean) => void;
}

export const dropdownKey: InjectionKey<DropdownContext> = Symbol('ui-dropdown');
