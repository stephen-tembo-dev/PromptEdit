import { usePage } from '@inertiajs/vue3';
import { computed, inject, provide, type ComputedRef, type InjectionKey, type MaybeRefOrGetter, toValue } from 'vue';

export interface FieldContext {
    required: ComputedRef<boolean>;
    disabled: ComputedRef<boolean>;
}

const fieldKey: InjectionKey<FieldContext> = Symbol('ui-field');

export function provideField(context: FieldContext): void {
    provide(fieldKey, context);
}

export function useField(): FieldContext | null {
    return inject(fieldKey, null);
}

/** The id an error message gets, so a control can point aria-describedby at it. */
export function errorIdFor(name: string | null | undefined): string | undefined {
    if (!name) return undefined;

    return `error-${name.replace(/[^A-Za-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '')}`;
}

/**
 * Laravel's validation messages for one field, read from Inertia's `errors` page prop
 * (the Vue counterpart of Blade's `$errors->get($name)`).
 */
export function usePageErrors(name: MaybeRefOrGetter<string | null | undefined>) {
    const page = usePage();

    return computed<string[]>(() => {
        const key = toValue(name);
        if (!key) return [];

        const errors = (page.props.errors ?? {}) as Record<string, string | string[]>;
        const value = errors[key];

        if (value === undefined || value === null || value === '') return [];

        return Array.isArray(value) ? value : [value];
    });
}
