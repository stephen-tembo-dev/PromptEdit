<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { focusableWithin, useAnchored } from '@/composables/useAnchored';
import Icon from '../icon/Icon.vue';

defineProps<{ label: string; disabled?: boolean }>();

const isOpen = ref(false);
const trigger = ref<HTMLElement | null>(null);
const panel = ref<HTMLElement | null>(null);

const { floatingStyles } = useAnchored(trigger, panel, { placement: 'right-start', offset: 4, open: isOpen });

let closeTimer: ReturnType<typeof setTimeout> | undefined;

function show(): void {
    clearTimeout(closeTimer);
    isOpen.value = true;
}

// A short delay lets the pointer travel from the item to the panel.
function hide(): void {
    closeTimer = setTimeout(() => (isOpen.value = false), 120);
}

function items(): HTMLElement[] {
    return focusableWithin(panel.value).filter((el) => el.matches('[data-slot=dropdown-item]'));
}

function openWithKeyboard(): void {
    show();
    nextTick(() => items()[0]?.focus());
}

function closeToTrigger(): void {
    isOpen.value = false;
    trigger.value?.focus();
}

function onPanelKeydown(event: KeyboardEvent): void {
    const list = items();
    const index = list.indexOf(document.activeElement as HTMLElement);

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            event.stopPropagation();
            list[(index + 1) % list.length]?.focus();
            break;
        case 'ArrowUp':
            event.preventDefault();
            event.stopPropagation();
            list[(index - 1 + list.length) % list.length]?.focus();
            break;
        case 'ArrowLeft':
        case 'Escape':
            event.preventDefault();
            event.stopPropagation();
            closeToTrigger();
            break;
    }
}
</script>

<template>
    <!-- `contents` keeps the item in the menu's grid; the margins make up the spacing it loses. -->
    <div class="contents [&+[data-slot=dropdown-separator]]:mt-1 [&+[data-slot=dropdown-submenu]_[data-slot=dropdown-item]]:mt-1" data-slot="dropdown-submenu">
        <button
            ref="trigger"
            type="button"
            :disabled="disabled"
            :tabindex="disabled ? -1 : 0"
            role="menuitem"
            aria-haspopup="menu"
            :aria-expanded="isOpen ? 'true' : 'false'"
            data-active="true"
            data-slot="dropdown-item"
            class="group relative col-span-2 grid w-full cursor-pointer grid-cols-subgrid overflow-hidden rounded-[calc(var(--dropdown-radius)-var(--dropdown-padding))] px-2 py-1.5 text-start text-sm text-neutral-800 transition-colors duration-200 hover:bg-neutral-100 focus:bg-neutral-100 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:text-white dark:hover:bg-white/5 dark:focus:bg-white/5"
            @mouseenter="show"
            @mouseleave="hide"
            @focus="show"
            @keydown.right.prevent.stop="openWithKeyboard"
        >
            <span />
            <span class="col-start-2 flex items-center justify-between">
                {{ label }}
                <Icon name="hi:chevron-right" variant="mini" class="ml-2 inline-flex size-4 shrink-0" />
            </span>
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                ref="panel"
                :style="floatingStyles"
                role="menu"
                class="z-10 grid grid-cols-[auto_1fr_auto] rounded-(--dropdown-radius) border border-black/10 bg-white p-(--dropdown-padding) text-start shadow-md [--dropdown-padding:--spacing(.75)] [--dropdown-radius:var(--radius-box)] [:where(&)]:max-w-96 [:where(&)]:min-w-40 dark:border-white/10 dark:bg-neutral-900"
                @mouseenter="show"
                @mouseleave="hide"
                @keydown="onPanelKeydown"
            >
                <slot />
            </div>
        </Transition>
    </div>
</template>
