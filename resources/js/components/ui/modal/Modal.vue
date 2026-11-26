<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { focusableWithin } from '@/composables/useAnchored';

/**
 *   <Modal v-model="open">  (or leave v-model off and use the trigger slot)
 *       <template #trigger><Button>Edit</Button></template>
 *       <ModalHeading>Edit project</ModalHeading>
 *       …
 *       <template #footer="{ close }"><Button variant="outline" color="neutral" @click="close">Cancel</Button></template>
 *   </Modal>
 */
const props = withDefaults(
    defineProps<{
        /** Clicking the backdrop closes the dialog. */
        autoClose?: boolean;
        size?: 'md' | 'lg' | 'full';
    }>(),
    { autoClose: true, size: 'md' },
);

const open = defineModel<boolean>({ default: false });

const emit = defineEmits<{ close: [] }>();

const panel = ref<HTMLElement | null>(null);
let previouslyFocused: HTMLElement | null = null;

function close(): void {
    open.value = false;
    emit('close');
}

const panelSizeClasses = computed(() => {
    switch (props.size) {
        case 'full':
            return 'flex h-[92vh] max-h-[92vh] w-[96vw] max-w-[96vw] flex-col';
        // Forms need room for two fields side by side.
        case 'lg':
            return 'max-h-[90vh] max-w-xl overflow-y-auto';
        default:
            return 'max-w-md';
    }
});

// Keep Tab inside the dialog while it is open.
function trapFocus(event: KeyboardEvent): void {
    if (event.key !== 'Tab') return;

    const items = focusableWithin(panel.value);
    if (items.length === 0) {
        event.preventDefault();
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

watch(open, (value) => {
    if (value) {
        previouslyFocused = document.activeElement as HTMLElement | null;
        document.body.style.overflow = 'hidden';
        nextTick(() => (focusableWithin(panel.value)[0] ?? panel.value)?.focus());
    } else {
        document.body.style.overflow = '';
        previouslyFocused?.focus?.();
    }
});

onBeforeUnmount(() => {
    if (open.value) document.body.style.overflow = '';
});
</script>

<template>
    <div v-if="$slots.trigger" @click="open = true">
        <slot name="trigger" :open="() => (open = true)" />
    </div>

    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape="close" @keydown="trapFocus">
                <div class="fixed inset-0 bg-neutral-900/50" aria-hidden="true" @click="autoClose && close()" />

                <div
                    ref="panel"
                    tabindex="-1"
                    role="dialog"
                    aria-modal="true"
                    :class="[
                        'relative w-full rounded-box border border-black/10 bg-white p-6 shadow-xl outline-none dark:border-white/10 dark:bg-neutral-900',
                        panelSizeClasses,
                        size === 'full' ? '' : 'space-y-4',
                    ]"
                >
                    <div :class="size === 'full' ? 'min-h-0 flex-1 space-y-4 overflow-auto' : 'space-y-5'">
                        <slot :close="close" />
                    </div>

                    <div
                        v-if="$slots.footer"
                        :class="['flex items-center justify-end gap-2 pt-2', size === 'full' ? 'mt-3 shrink-0 border-t border-black/10 dark:border-white/10' : '']"
                    >
                        <slot name="footer" :close="close" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
