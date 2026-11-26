<script setup lang="ts">
import { computed, nextTick, provide, ref, useId } from 'vue';
import { focusableWithin, toPlacement, useAnchored } from '@/composables/useAnchored';
import { useClickOutside } from '@/composables/useClickOutside';
import { dropdownKey } from './context';

/**
 *   <Dropdown position="bottom-end">
 *       <template #button><Button icon="dots-three" variant="ghost" /></template>
 *       <template #menu>
 *           <DropdownItem icon="pencil-simple" href="/edit">Edit</DropdownItem>
 *           <DropdownSeparator />
 *           <DropdownItem icon="trash" variant="danger" @click="remove">Delete</DropdownItem>
 *       </template>
 *   </Dropdown>
 *
 * `checkbox` turns items into toggles (v-model on each item); `radio` turns them into a single choice (v-model here).
 */
const props = withDefaults(
    defineProps<{
        position?: string;
        offset?: number;
        checkbox?: boolean;
        radio?: boolean;
        /** Teleport the menu to <body>. */
        portal?: boolean;
        teleport?: string;
        /** Return focus to the trigger when the menu closes. */
        resetFocus?: boolean;
        menuClass?: string;
    }>(),
    { position: 'bottom-center', offset: 6, checkbox: false, radio: false, portal: false, teleport: 'body', resetFocus: false, menuClass: '' },
);

/** The selected value when `radio` is set. */
const radioModel = defineModel<string | number | boolean | null>({ default: null });

const open = ref(false);
const trigger = ref<HTMLElement | null>(null);
const panel = ref<HTMLElement | null>(null);
const id = useId();

const { floatingStyles } = useAnchored(trigger, panel, {
    placement: () => toPlacement(props.position),
    offset: () => props.offset,
    open,
});

const items = () => focusableWithin(panel.value).filter((el) => el.matches('[data-slot=dropdown-item]'));

function focusItem(index: number): void {
    const list = items();
    if (list.length === 0) return;

    list[(index + list.length) % list.length].focus();
}

function openMenu(focusFirst = false): void {
    open.value = true;
    if (focusFirst) nextTick(() => focusItem(0));
}

function close(focusTrigger = false): void {
    if (!open.value) return;

    open.value = false;

    if (focusTrigger || props.resetFocus) {
        nextTick(() => focusableWithin(trigger.value)[0]?.focus() ?? trigger.value?.focus());
    }
}

function toggle(): void {
    if (open.value) close();
    else openMenu();
}

function onTriggerKeydown(event: KeyboardEvent): void {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        openMenu(true);
    } else if (event.key === 'Escape') {
        close(true);
    }
}

function onPanelKeydown(event: KeyboardEvent): void {
    const list = items();
    const index = list.indexOf(document.activeElement as HTMLElement);

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            focusItem(index + 1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            focusItem(index - 1);
            break;
        case 'Home':
        case 'PageUp':
            event.preventDefault();
            focusItem(0);
            break;
        case 'End':
        case 'PageDown':
            event.preventDefault();
            focusItem(list.length - 1);
            break;
        case 'Escape':
            event.preventDefault();
            event.stopPropagation();
            close(true);
            break;
        case 'Tab':
            close();
            break;
    }
}

useClickOutside([trigger, panel], () => close());

provide(dropdownKey, {
    checkbox: computed(() => props.checkbox),
    radio: computed(() => props.radio),
    radioModel,
    close,
});

const panelClasses = computed(() => [
    'isolate z-50',
    props.checkbox || props.radio ? 'grid grid-cols-[auto_auto_1fr_auto]' : 'grid grid-cols-[auto_1fr_auto]',
    'text-start [:where(&)]:max-w-96 [:where(&)]:min-w-40',
    'border border-black/10 bg-white shadow-lg dark:border-white/10 dark:bg-neutral-900',
    'space-y-1 rounded-(--dropdown-radius) p-(--dropdown-padding) [--dropdown-padding:--spacing(.75)] [--dropdown-radius:var(--radius-box)]',
    props.menuClass,
]);
</script>

<template>
    <div class="relative">
        <div
            ref="trigger"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-controls="id"
            aria-haspopup="menu"
            :data-open="open ? '' : undefined"
            @click="toggle"
            @keydown="onTriggerKeydown"
        >
            <slot name="button" :open="open" :close="close" :toggle="toggle" />
        </div>

        <Teleport :to="teleport" :disabled="!portal">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
            >
                <div
                    v-if="open"
                    :id="id"
                    ref="panel"
                    :style="floatingStyles"
                    :role="radio ? 'radiogroup' : 'menu'"
                    :class="panelClasses"
                    @keydown="onPanelKeydown"
                >
                    <slot name="menu" :close="close" />
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
