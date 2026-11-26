import type { ComputedRef, InjectionKey, Ref } from 'vue';

export type CheckboxVariant = 'default' | 'pills' | 'cards';
export type CheckboxValue = string | number | boolean;

export interface CheckboxGroupContext {
    model: Ref<CheckboxValue[]>;
    variant: ComputedRef<CheckboxVariant>;
}

export const checkboxGroupKey: InjectionKey<CheckboxGroupContext> = Symbol('ui-checkbox-group');
