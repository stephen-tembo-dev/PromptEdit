import { computed, reactive } from 'vue';

/**
 * Light / dark / system theme, ported from Sheaf's `$theme` magic.
 *
 * It stores the choice under Preline's `hs_theme` key with Preline's values (`light`, `dark`, `auto`),
 * so Preline's own `data-hs-theme-click-value` switches and this composable always agree.
 */
export type ThemePreference = 'light' | 'dark' | 'system';
export type ResolvedTheme = 'light' | 'dark';

const STORAGE_KEY = 'hs_theme';

const darkQuery = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)') : null;

const systemTheme = (): ResolvedTheme => (darkQuery?.matches ? 'dark' : 'light');

const readStored = (): ThemePreference => {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored === 'dark') return 'dark';
        if (stored === 'auto') return 'system';
    } catch {
        // Storage can be blocked; fall back to light.
    }

    return 'light';
};

const state = reactive({
    stored: readStored(),
    system: systemTheme(),
});

const resolve = (preference: ThemePreference): ResolvedTheme => (preference === 'system' ? state.system : preference);

function apply(): void {
    const html = document.documentElement;
    const resolved = resolve(state.stored);

    html.classList.remove('light', 'dark', 'default', 'auto');
    html.classList.add(resolved);

    if (state.stored === 'system') {
        html.classList.add('auto');
    }

    document.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: resolved } }));
}

darkQuery?.addEventListener('change', (event) => {
    state.system = event.matches ? 'dark' : 'light';

    if (state.stored === 'system') {
        apply();
    }
});

// Preline's theme switch fires this when one of its own buttons changes the theme.
if (typeof window !== 'undefined') {
    window.addEventListener('on-hs-appearance-change', () => {
        state.stored = readStored();
    });
}

function setTheme(preference: ThemePreference): void {
    state.stored = preference;

    try {
        localStorage.setItem(STORAGE_KEY, preference === 'system' ? 'auto' : preference);
    } catch {
        // Storage can be blocked; the choice still applies for this page.
    }

    apply();
}

export function useTheme() {
    const current = computed<ResolvedTheme>(() => resolve(state.stored));

    return {
        /** The saved preference: light, dark or system. */
        stored: computed(() => state.stored),
        /** What is on screen right now: light or dark. */
        current,
        isLight: computed(() => state.stored === 'light'),
        isDark: computed(() => state.stored === 'dark'),
        isSystem: computed(() => state.stored === 'system'),
        isResolvedToLight: computed(() => current.value === 'light'),
        isResolvedToDark: computed(() => current.value === 'dark'),
        setTheme,
        setLight: () => setTheme('light'),
        setDark: () => setTheme('dark'),
        setSystem: () => setTheme('system'),
        toggle: () => setTheme(current.value === 'dark' ? 'light' : 'dark'),
    };
}
