import type { ComputedRef, InjectionKey } from 'vue';

export interface AccordionContext {
    multiple: ComputedRef<boolean>;
    reverse: ComputedRef<boolean>;
    isOpen: (id: string) => boolean;
    setOpen: (id: string, open: boolean) => void;
}

export interface AccordionItemContext {
    isVisible: ComputedRef<boolean>;
    disabled: ComputedRef<boolean>;
    toggle: () => void;
    contentId: string;
    triggerId: string;
}

export const accordionKey: InjectionKey<AccordionContext> = Symbol('ui-accordion');
export const accordionItemKey: InjectionKey<AccordionItemContext> = Symbol('ui-accordion-item');
