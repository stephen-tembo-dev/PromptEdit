import { reactive } from 'vue';

export type ToastType = 'info' | 'success' | 'error' | 'warning';

export interface ToastPayload {
    type?: ToastType;
    content: string;
    /** Milliseconds before it dismisses itself. 0 keeps it until closed. Default 4000. */
    duration?: number;
    showProgress?: boolean;
}

export interface ToastMessage {
    id: number;
    type: ToastType;
    content: string;
    duration: number;
    showProgress: boolean;
}

const state = reactive({
    toasts: [] as ToastMessage[],
    maxToasts: 5,
});

let nextId = 1;

function addToast(details: ToastPayload | null | undefined): void {
    if (!details?.content) return;

    state.toasts.unshift({
        id: nextId++,
        type: details.type ?? 'info',
        content: details.content,
        duration: details.duration ?? 4000,
        showProgress: details.showProgress !== false,
    });

    if (state.toasts.length > state.maxToasts) {
        state.toasts.splice(state.maxToasts);
    }
}

function removeToast(id: number): void {
    const index = state.toasts.findIndex((toast) => toast.id === id);

    if (index !== -1) state.toasts.splice(index, 1);
}

// Same contract as the Blade toast: window.dispatchEvent(new CustomEvent('notify', { detail: { type, content } }))
if (typeof window !== 'undefined') {
    window.addEventListener('notify', (event) => addToast((event as CustomEvent<ToastPayload>).detail));
}

/**
 * Show a toast from anywhere:
 *
 *   const toast = useToast();
 *   toast.success('Saved');
 *
 * From Laravel, flash it: `Inertia::flash('notify', ['type' => 'success', 'content' => 'Saved'])`
 * or `->with('notify', [...])` on a redirect.
 */
export function useToast() {
    return {
        toasts: state.toasts,
        state,
        add: addToast,
        remove: removeToast,
        info: (content: string, options: Omit<ToastPayload, 'content' | 'type'> = {}) => addToast({ ...options, content, type: 'info' }),
        success: (content: string, options: Omit<ToastPayload, 'content' | 'type'> = {}) => addToast({ ...options, content, type: 'success' }),
        error: (content: string, options: Omit<ToastPayload, 'content' | 'type'> = {}) => addToast({ ...options, content, type: 'error' }),
        warning: (content: string, options: Omit<ToastPayload, 'content' | 'type'> = {}) => addToast({ ...options, content, type: 'warning' }),
    };
}
