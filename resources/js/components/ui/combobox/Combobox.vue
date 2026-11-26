<script setup lang="ts">
import { computed, nextTick, ref, useAttrs, useId, watch } from 'vue';
import { useAnchored } from '@/composables/useAnchored';
import { useClickOutside } from '@/composables/useClickOutside';
import { errorIdFor, usePageErrors } from '../form/context';
import Icon from '../icon/Icon.vue';
import IconLoading from '../icon/IconLoading.vue';
import type { ListboxValue } from '../listbox/context';
import ListboxOption from '../listbox/ListboxOption.vue';
import { useListbox } from '../listbox/useListbox';
import Text from '../typography/Text.vue';

defineOptions({ inheritAttrs: false });

/**
 * A text input that filters a list of options as you type.
 *
 *   <Combobox v-model="city" placeholder="Search cities…" clearable>
 *       <ComboboxOption v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</ComboboxOption>
 *   </Combobox>
 *
 * For server-side search, listen to `@search` (the typed text) and set `:loading` while results load.
 */
const props = withDefaults(
    defineProps<{
        name?: string | null;
        label?: string | null;
        placeholder?: string;
        options?: Array<{ value: ListboxValue; label: string; disabled?: boolean; icon?: string }> | null;
        multiple?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        loading?: boolean;
        empty?: string;
        icon?: string | null;
        iconAfter?: string | null;
        checkIcon?: string;
        checkIconClass?: string | null;
        invalid?: boolean | null;
        minSelection?: number | null;
        maxSelection?: number | null;
        size?: 'default' | 'sm';
        /** Filter options in the browser. Turn off when the server returns already-filtered options. */
        filter?: boolean;
    }>(),
    {
        name: null,
        label: null,
        placeholder: 'Type to search...',
        options: null,
        multiple: false,
        clearable: false,
        disabled: false,
        loading: false,
        empty: 'No results found',
        icon: null,
        iconAfter: 'hi:chevron-up-down',
        checkIcon: 'check',
        checkIconClass: null,
        invalid: null,
        minSelection: null,
        maxSelection: null,
        size: 'default',
        filter: true,
    },
);

const model = defineModel<ListboxValue | ListboxValue[] | null>({ default: null });

const emit = defineEmits<{ search: [query: string] }>();

const attrs = useAttrs();
const id = useId();
const listId = `${id}-options`;

const isOpen = ref(false);
const trigger = ref<HTMLElement | null>(null);
const panel = ref<HTMLElement | null>(null);
const input = ref<HTMLInputElement | null>(null);
const inputValue = ref('');

const listbox = useListbox({
    model,
    multiple: () => props.multiple,
    minSelection: () => props.minSelection,
    maxSelection: () => props.maxSelection,
    disableUnselectedAtMax: () => false,
    checkIcon: () => props.checkIcon,
    checkIconClass: () => props.checkIconClass,
    loading: () => props.loading,
    onSingleSelect: () => close(),
    onSelect: () => {
        if (props.multiple) {
            inputValue.value = '';
            listbox.query.value = '';
            input.value?.focus();
        }
    },
});

const { floatingStyles } = useAnchored(trigger, panel, { placement: 'bottom-start', offset: 4, open: isOpen, matchWidth: true });

const pageErrors = usePageErrors(() => props.name);
const isInvalid = computed(() => props.invalid ?? pageErrors.value.length > 0);
const errorId = computed(() => (isInvalid.value ? errorIdFor(props.name) : undefined));

const hiddenValue = computed(() => listbox.selectedValues.value.join(','));
const activeDescendant = computed(() => (isOpen.value ? (listbox.activeId.value ?? undefined) : undefined));

// In single mode the input shows the chosen option's label whenever the user is not typing.
function syncInputToSelection(): void {
    if (props.multiple) return;

    const value = listbox.selectedValues.value[0];
    inputValue.value = value === undefined ? '' : listbox.labelFor(value);
}

watch(() => listbox.selectedValues.value, () => nextTick(syncInputToSelection), { immediate: true });
watch(() => listbox.records.size, () => !isOpen.value && syncInputToSelection());

function open(): void {
    if (props.disabled || isOpen.value) return;

    isOpen.value = true;
    nextTick(() => listbox.activateSelectedOrFirst());
}

function close(): void {
    if (!isOpen.value) return;

    isOpen.value = false;
    listbox.query.value = '';

    if (props.multiple) inputValue.value = '';
    else syncInputToSelection();
}

function onInput(): void {
    listbox.query.value = props.filter ? inputValue.value : '';
    emit('search', inputValue.value);
    open();
    nextTick(() => listbox.activateFirst());
}

function onKeydown(event: KeyboardEvent): void {
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            if (!isOpen.value) open();
            else listbox.move(1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            if (!isOpen.value) open();
            else listbox.move(-1);
            break;
        case 'Enter':
            if (isOpen.value) {
                event.preventDefault();
                listbox.selectActive();
            }
            break;
        case 'Escape':
            if (isOpen.value) {
                event.preventDefault();
                event.stopPropagation();
                close();
            }
            break;
        case 'Tab':
            close();
            break;
        case 'Backspace': {
            // Backspace in an empty input removes the last tag.
            const values = listbox.selectedValues.value;
            if (props.multiple && inputValue.value === '' && values.length > 0) listbox.deselect(values[values.length - 1]);
            break;
        }
    }
}

function clearAll(): void {
    listbox.clear();
    inputValue.value = '';
    listbox.query.value = '';
    close();
}

useClickOutside([trigger, panel], () => close());

const sizeIconClass = computed(() => (props.size === 'sm' ? 'size-6 p-0.75' : 'size-8 p-1'));
</script>

<template>
    <div :class="['relative [--popup-padding:--spacing(1)] [--popup-round:var(--radius-box)]', attrs.class]">
        <input v-if="name" type="hidden" :name="name" :value="hiddenValue" />

        <div
            ref="trigger"
            :class="['relative grid grid-cols-[1fr_auto] items-start', disabled ? '[&_[data-slot=icon]]:cursor-auto [&_[data-slot=icon]]:opacity-40' : '']"
            :data-open="isOpen ? '' : undefined"
            data-slot="trigger"
            @click="input?.focus()"
        >
            <div
                :class="[
                    isInvalid
                        ? 'border-2 border-red-600/30 focus-within:border-red-600/30 focus-within:ring-red-600/20 dark:border-red-400/30 dark:focus-within:border-red-400/30 dark:focus-within:ring-red-400/20'
                        : 'border-black/10 focus-within:border-black/15 focus-within:ring-neutral-900/15 dark:border-white/15 dark:focus-within:border-white/20 dark:focus-within:ring-neutral-100/15',
                    'focus-within:ring-2 focus-within:ring-offset-0 focus-within:outline-none',
                    'rounded-box border bg-white dark:bg-neutral-900 dark:text-gray-300',
                    'col-span-4 col-start-1 row-start-1 justify-self-stretch',
                    'flex cursor-text flex-wrap items-center transition-shadow duration-150',
                    disabled ? 'opacity-60' : '',
                    size === 'sm' ? 'min-h-8 gap-0.5 p-0.5 pr-4' : 'min-h-10 gap-1 p-1 pr-8',
                ]"
                data-slot="combobox-control"
            >
                <Icon v-if="icon" :name="icon" :class="['shrink-0 text-neutral-400 dark:text-neutral-500', size === 'sm' ? 'ml-1 size-4' : 'ml-1.5 size-4']" />

                <template v-if="multiple">
                    <div
                        v-for="value in listbox.selectedValues.value"
                        :key="String(value)"
                        class="inline-flex max-w-full shrink-0 items-center rounded-md border border-black/5 bg-neutral-100 p-1 text-sm font-medium text-neutral-800 dark:border-white/10 dark:bg-neutral-800 dark:text-neutral-200"
                    >
                        <span class="truncate">{{ listbox.labelFor(value) }}</span>
                        <button
                            type="button"
                            class="ml-1 rounded-sm hover:bg-black/5 focus:ring-2 focus:ring-neutral-400/30 focus:outline-none dark:hover:bg-white/10"
                            :aria-label="`Remove ${listbox.labelFor(value)}`"
                            @click.stop="listbox.deselect(value)"
                        >
                            <Icon name="hi:x-mark" class="size-3.5 opacity-60" />
                        </button>
                    </div>
                </template>

                <slot name="search" :input-value="inputValue">
                    <input
                        ref="input"
                        v-model="inputValue"
                        type="text"
                        role="combobox"
                        autocomplete="off"
                        spellcheck="false"
                        aria-autocomplete="list"
                        :aria-expanded="isOpen ? 'true' : 'false'"
                        :aria-controls="listId"
                        :aria-activedescendant="activeDescendant"
                        :aria-label="label ?? placeholder"
                        :aria-invalid="isInvalid ? 'true' : undefined"
                        :aria-describedby="errorId"
                        :placeholder="multiple && listbox.hasSelection.value ? '' : placeholder"
                        :disabled="disabled"
                        :class="[
                            'min-w-[6rem] flex-1 border-0 bg-transparent ring-0 outline-none focus:ring-0',
                            'text-neutral-900 placeholder:text-neutral-400 dark:text-neutral-50 dark:placeholder:text-neutral-500',
                            size === 'sm' ? 'px-1.5 py-0.5 text-sm' : 'px-2 py-1 text-sm',
                        ]"
                        data-slot="combobox-input"
                        @input="onInput"
                        @click="open"
                        @keydown="onKeydown"
                    />
                </slot>
            </div>

            <div
                v-if="iconAfter && !(clearable && listbox.hasSelection.value)"
                class="col-span-1 col-start-2 row-start-1 ml-1 h-full justify-self-center pt-1 pr-1"
                data-slot="icon"
                @click.stop="isOpen ? close() : (input?.focus(), open())"
            >
                <Icon :name="iconAfter" :class="['rounded-md hover:bg-neutral-800/5 dark:hover:bg-white/5', sizeIconClass]" />
            </div>

            <div
                v-if="clearable && listbox.hasSelection.value"
                class="col-span-1 col-start-2 row-start-1 ml-1 h-full justify-self-center pt-1 pr-1"
                role="button"
                tabindex="0"
                aria-label="Clear selection"
                data-slot="combobox-clear"
                @click.stop="clearAll"
                @keydown.enter.stop="clearAll"
                @keydown.space.prevent.stop="clearAll"
            >
                <Icon name="hi:x-mark" :class="['rounded-md hover:bg-neutral-800/5 dark:hover:bg-white/5', sizeIconClass]" />
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="scale-95 transform opacity-0"
            enter-to-class="scale-100 transform opacity-100"
            leave-active-class="pointer-events-none transition duration-150 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-show="isOpen"
                ref="panel"
                :style="floatingStyles"
                class="z-50 rounded-(--popup-round) border border-neutral-200 bg-white py-(--popup-padding) shadow-lg backdrop-blur-xl dark:border-neutral-700 dark:bg-neutral-800"
            >
                <ul
                    :id="listId"
                    :ref="(el) => (listbox.listEl.value = el as HTMLElement | null)"
                    role="listbox"
                    :aria-multiselectable="multiple ? 'true' : 'false'"
                    :aria-label="label ?? 'Options'"
                    :data-loading="loading ? '' : undefined"
                    class="relative grid max-h-60 grid-cols-[auto_auto_1fr] gap-y-1 overflow-y-auto px-(--popup-padding) data-loading:h-24"
                >
                    <slot :query="listbox.query.value" />

                    <ListboxOption v-for="option in options ?? []" :key="String(option.value)" :value="option.value" :label="option.label" :icon="option.icon" :disabled="option.disabled" />

                    <li v-if="!loading && listbox.visibleOptions.value.length === 0 && !$slots.create" class="col-span-full" role="presentation">
                        <slot name="empty">
                            <Text class="flex h-14 items-center justify-center">{{ empty }}</Text>
                        </slot>
                    </li>

                    <slot name="create" :query="inputValue" />

                    <li v-if="loading" class="absolute top-1/2 left-1/2 flex -translate-1/2" role="presentation">
                        <slot name="loading"><IconLoading class="opacity-50" /></slot>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>
