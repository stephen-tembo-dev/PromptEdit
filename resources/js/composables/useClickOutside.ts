import { onBeforeUnmount, onMounted, toValue, type MaybeRefOrGetter } from 'vue';

/** Calls `handler` on a pointer press outside every one of `targets` (the Vue counterpart of Alpine's `@click.away`). */
export function useClickOutside(targets: MaybeRefOrGetter<HTMLElement | null | undefined>[], handler: (event: PointerEvent) => void): void {
    const listener = (event: PointerEvent) => {
        const path = event.composedPath();
        const inside = targets.some((target) => {
            const el = toValue(target);
            return el ? path.includes(el) : false;
        });

        if (!inside) handler(event);
    };

    onMounted(() => document.addEventListener('pointerdown', listener, true));
    onBeforeUnmount(() => document.removeEventListener('pointerdown', listener, true));
}
