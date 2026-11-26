import type { ComputedRef, InjectionKey, Ref } from 'vue';

export type RadioVariant = 'default' | 'segmented' | 'cards' | 'pills';
export type RadioValue = string | number | boolean | null;

export interface RadioGroupContext {
    model: Ref<RadioValue>;
    name: ComputedRef<string>;
    variant: ComputedRef<RadioVariant>;
    disabled: ComputedRef<boolean>;
    indicator: ComputedRef<boolean>;
}

export const radioGroupKey: InjectionKey<RadioGroupContext> = Symbol('ui-radio-group');
