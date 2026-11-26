/**
 * Preline wires its plugins (overlays, dropdowns, tooltips, scroll navs…) to `data-hs-*` markup.
 * Inertia swaps pages without a reload, so the new page's markup is wired again after every navigation.
 *
 * Preline is large and the Vue ui kit does not need it to render, so it loads in its own chunk after the
 * first paint instead of holding up the app bundle.
 */
let loading: Promise<unknown> | null = null;

export function initPreline(): void {
    loading ??= import('preline');

    loading.then(() => requestAnimationFrame(() => window.HSStaticMethods?.autoInit()));
}
