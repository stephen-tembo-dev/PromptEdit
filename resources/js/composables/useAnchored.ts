import { autoUpdate, flip, offset, shift, size, useFloating, type Placement } from '@floating-ui/vue';
import { computed, type MaybeRefOrGetter, type Ref, toValue } from 'vue';

/**
 * Positions a floating panel against its trigger, the way Alpine's `x-anchor` did: it flips when there is
 * no room and stays on screen. `fixed` positioning lets panels escape `overflow: hidden` ancestors.
 */
export function useAnchored(
    reference: Ref<HTMLElement | null>,
    floating: Ref<HTMLElement | null>,
    options: {
        placement?: MaybeRefOrGetter<Placement>;
        offset?: MaybeRefOrGetter<number>;
        open: MaybeRefOrGetter<boolean>;
        /** Make the panel at least as wide as the trigger (selects and comboboxes). */
        matchWidth?: boolean;
    },
) {
    const { floatingStyles, placement } = useFloating(reference, floating, {
        placement: computed(() => toValue(options.placement) ?? 'bottom-start'),
        strategy: 'fixed',
        open: computed(() => toValue(options.open)),
        whileElementsMounted: autoUpdate,
        middleware: computed(() => [
            offset(toValue(options.offset) ?? 6),
            flip({ padding: 8 }),
            shift({ padding: 8 }),
            ...(options.matchWidth
                ? [
                      size({
                          apply({ rects, elements }) {
                              elements.floating.style.minWidth = `${rects.reference.width}px`;
                          },
                      }),
                  ]
                : []),
        ]),
    });

    return { floatingStyles, placement };
}

/** Alpine anchor names (`bottom-center`, `top-start`, …) mapped to Floating UI placements. */
export function toPlacement(position: string): Placement {
    const [side, align] = position.split('-') as [string, string | undefined];
    const validSide = ['top', 'bottom', 'left', 'right'].includes(side) ? side : 'bottom';

    if (!align || align === 'center') return validSide as Placement;

    // Preline and the Blade kit also used left/right for alignment.
    const alignment = align === 'left' ? 'start' : align === 'right' ? 'end' : align;

    return `${validSide}-${alignment}` as Placement;
}

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type=hidden]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function focusableWithin(root: HTMLElement | null): HTMLElement[] {
    if (!root) return [];

    return Array.from(root.querySelectorAll<HTMLElement>(FOCUSABLE)).filter((el) => !el.hasAttribute('inert') && el.getAttribute('aria-disabled') !== 'true');
}
