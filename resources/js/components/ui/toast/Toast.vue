<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { useToast, type ToastPayload } from '@/composables/useToast';
import ToastItem from './ToastItem.vue';

/**
 * The toast stack. Mount it once in a layout. It shows:
 *   - toasts from useToast() or a `notify` window event, and
 *   - a `notify` flash from Laravel: Inertia::flash('notify', [...]) or ->with('notify', [...]).
 */
const props = withDefaults(
    defineProps<{
        position?: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left';
        maxToasts?: number;
        progressBarVariant?: 'full' | 'thin';
        progressBarAlignment?: 'bottom' | 'top';
    }>(),
    { position: 'bottom-right', maxToasts: 5, progressBarVariant: 'full', progressBarAlignment: 'bottom' },
);

const toast = useToast();
const page = usePage();

watch(
    () => props.maxToasts,
    (value) => (toast.state.maxToasts = value),
    { immediate: true },
);

// Each Inertia visit gives a new flash object, so the same message twice still shows twice.
watch(
    () => page.flash,
    (flash) => toast.add((flash as { notify?: ToastPayload } | undefined)?.notify),
    { immediate: true },
);

watch(
    () => (page.props as { notify?: ToastPayload | null }).notify,
    (notify) => toast.add(notify),
    { immediate: true },
);

const paused = reactive(new Set<number>());

// Hovering a toast pauses it and every toast above it.
function pauseFrom(id: number): void {
    const index = toast.toasts.findIndex((item) => item.id === id);
    paused.clear();
    for (let i = 0; i <= index; i++) paused.add(toast.toasts[i].id);
}

const placement = computed(
    () =>
        ({
            'bottom-right': 'right-0 bottom-0 pr-4 pb-4',
            'bottom-left': 'bottom-0 left-0 pb-4 pl-4',
            'top-right': 'top-0 right-0 pt-4 pr-4',
            'top-left': 'top-0 left-0 pt-4 pl-4',
        })[props.position],
);

const fromBottom = computed(() => props.position.startsWith('bottom'));
</script>

<template>
    <Teleport to="body">
        <div :class="['pointer-events-none fixed z-50 flex w-full max-w-xs gap-4 sm:justify-start', fromBottom ? 'flex-col-reverse' : 'flex-col', placement]" role="status" aria-live="polite">
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-2 scale-95 transform opacity-0"
                enter-to-class="translate-y-0 scale-100 transform opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-y-0 scale-100 transform opacity-100"
                leave-to-class="translate-y-2 scale-95 transform opacity-0"
            >
                <ToastItem
                    v-for="item in toast.toasts"
                    :key="item.id"
                    :toast="item"
                    :paused="paused.has(item.id)"
                    :progress-bar-variant="progressBarVariant"
                    :progress-bar-alignment="progressBarAlignment"
                    @dismiss="toast.remove(item.id)"
                    @mouseenter="pauseFrom(item.id)"
                    @mouseleave="paused.clear()"
                />
            </TransitionGroup>
        </div>
    </Teleport>
</template>
