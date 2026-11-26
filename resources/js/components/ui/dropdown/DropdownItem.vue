<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, inject } from 'vue';
import Icon from '../icon/Icon.vue';
import Kbd from '../typography/Kbd.vue';
import { dropdownKey } from './context';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        disabled?: boolean;
        icon?: string | null;
        iconVariant?: string;
        shortcut?: string | null;
        variant?: 'soft' | 'danger';
        /** Turns the item into an Inertia link. */
        href?: string | null;
        method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
        active?: boolean;
        /** In a `radio` dropdown, the value this item selects. */
        value?: unknown;
        /** Plain text row, not interactive. */
        readOnly?: boolean;
        /** Shows a checkbox square instead of a check mark in a `checkbox` dropdown. */
        checkboxVariant?: boolean;
        /** Close the menu after a click. */
        closeOnClick?: boolean;
    }>(),
    {
        disabled: false,
        icon: null,
        iconVariant: 'mini',
        shortcut: null,
        variant: 'soft',
        href: null,
        method: 'get',
        active: false,
        value: undefined,
        readOnly: false,
        checkboxVariant: false,
        closeOnClick: true,
    },
);

const emit = defineEmits<{ click: [event: MouseEvent] }>();

/** Checked state in a `checkbox` dropdown. */
const checkedModel = defineModel<boolean>({ default: false });

const dropdown = inject(dropdownKey, null);

const isToggle = computed(() => !!dropdown && (dropdown.checkbox.value || dropdown.radio.value));

const checked = computed(() => {
    if (!dropdown) return false;
    if (dropdown.radio.value) return dropdown.radioModel.value === props.value;

    return checkedModel.value;
});

const classes = computed(() => [
    'group relative grid grid-cols-subgrid overflow-hidden',
    isToggle.value ? 'col-span-3' : 'col-span-2',
    'w-full px-2 py-1.5 text-start text-sm transition-colors duration-200',
    'text-neutral-800 dark:text-white',
    'rounded-[calc(var(--dropdown-radius)-var(--dropdown-padding))]',
    'focus:outline-none',
    'data-active:bg-neutral-950/[3%] dark:data-active:bg-white/[3%]',
    props.disabled
        ? 'cursor-not-allowed text-neutral-500 opacity-50 dark:text-neutral-400'
        : [
              'cursor-pointer',
              props.variant === 'danger'
                  ? 'hover:bg-red-50 hover:text-red-600 focus:bg-red-50 focus:text-red-600 dark:hover:bg-red-400/20 dark:hover:text-red-400 dark:focus:bg-red-400/20 dark:focus:text-red-400'
                  : 'hover:bg-neutral-100 focus:bg-neutral-100 dark:hover:bg-white/5 dark:focus:bg-white/5',
          ],
]);

const iconClasses = computed(() => [
    'mr-2 inline-flex shrink-0',
    props.variant === 'danger' ? 'group-hover:text-red-500! group-focus:text-red-500! dark:group-hover:text-red-400! dark:group-focus:text-red-400!' : '',
]);

function activate(event: MouseEvent): void {
    if (props.disabled) {
        event.preventDefault();
        return;
    }

    if (dropdown?.radio.value) {
        dropdown.radioModel.value = props.value;
    } else if (dropdown?.checkbox.value) {
        checkedModel.value = !checkedModel.value;
    }

    emit('click', event);

    // Toggles keep the menu open so several can be set at once.
    if (props.closeOnClick && !isToggle.value) dropdown?.close();
}

function onKeydown(event: KeyboardEvent): void {
    if (props.href) return;

    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        (event.currentTarget as HTMLElement).click();
    }
}
</script>

<template>
    <div v-if="readOnly" v-bind="$attrs" class="col-span-full w-full px-3 py-1.5 text-center text-sm text-neutral-800 dark:text-white">
        <slot />
    </div>

    <component
        :is="href ? Link : 'button'"
        v-else
        v-bind="$attrs"
        :href="href ?? undefined"
        :method="href ? method : undefined"
        :type="href ? undefined : 'button'"
        :class="classes"
        :role="dropdown?.checkbox.value ? 'menuitemcheckbox' : dropdown?.radio.value ? 'menuitemradio' : 'menuitem'"
        :aria-checked="isToggle ? (checked ? 'true' : 'false') : undefined"
        :aria-disabled="disabled ? 'true' : 'false'"
        :tabindex="disabled ? -1 : 0"
        :data-active="active ? 'true' : undefined"
        data-slot="dropdown-item"
        @click="activate"
        @keydown="onKeydown"
    >
        <template v-if="isToggle">
            <div v-if="checkboxVariant" class="relative mr-2 inline-grid size-5 place-items-center">
                <span :class="['size-4.5 rounded shadow ring-neutral-100 transition dark:ring-white/10', checked ? 'bg-[var(--color-primary)] ring-2' : 'ring-1']" />
                <Icon name="check" :class="['absolute size-4 text-(--color-primary-fg)! transition-all duration-200', checked ? 'scale-100 opacity-100' : 'scale-75 opacity-0']" />
            </div>
            <Icon v-else name="check" variant="bold" :class="['mr-2 size-4 transition-opacity duration-150', checked ? 'opacity-100' : 'opacity-0']" aria-hidden="true" />
        </template>

        <Icon v-if="icon" :name="icon" :variant="iconVariant" :class="iconClasses" aria-hidden="true" />
        <span v-else-if="!isToggle" />

        <span :class="['flex items-center justify-between gap-4 whitespace-nowrap', isToggle ? 'col-start-3' : 'col-start-2']">
            <span class="flex-1"><slot /></span>
            <Kbd v-if="shortcut">{{ shortcut }}</Kbd>
        </span>
    </component>
</template>
