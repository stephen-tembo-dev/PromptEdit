<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { focusableWithin } from '@/composables/useAnchored';

/**
 * The shared floating panel look for popover-like components. Shown with `v-model`; it moves focus into
 * itself when it opens (`autofocus`), which the Blade version did with a MutationObserver and x-trap.
 */
const props = withDefaults(defineProps<{ autofocus?: boolean }>(), { autofocus: true });

const shown = defineModel<boolean>({ default: false });

const panel = ref<HTMLElement | null>(null);

watch(shown, (value) => {
    if (value && props.autofocus) nextTick(() => focusableWithin(panel.value)[0]?.focus());
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="scale-95 transform opacity-0"
        enter-to-class="scale-100 transform opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="scale-100 transform opacity-100"
        leave-to-class="scale-95 transform opacity-0"
    >
        <div
            v-if="shown"
            ref="panel"
            class="absolute z-50 mt-1 w-full rounded-(--popup-round) border border-neutral-200 bg-white p-(--popup-padding) shadow-lg backdrop-blur-xl [--popup-padding:--spacing(1)] [--popup-round:var(--radius-box)] dark:border-neutral-700 dark:bg-neutral-800"
        >
            <slot />
        </div>
    </Transition>
</template>
