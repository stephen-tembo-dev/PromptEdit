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
 *   <Select v-model="country" placeholder="Choose a country" searchable clearable>
 *       <SelectOption value="ke">Kenya</SelectOption>
 *       <SelectOption value="ug">Uganda</SelectOption>
 *   </Select>
 *
 *   <Select v-model="tags" multiple pillbox :options="[{ value: 1, label: 'Urgent' }]" />
 */
const props = withDefaults(
    defineProps<{
        name?: string | null;
        /** Accessible name for the option list. */
        label?: string | null;
        placeholder?: string;
        /** Shorthand for simple lists instead of <SelectOption> children. */
        options?: Array<{ value: ListboxValue; label: string; disabled?: boolean; icon?: string }> | null;
        searchable?: boolean;
        multiple?: boolean;
        /** Show the selected values of a multiple select as removable tags. */
        pillbox?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        loading?: boolean;
        empty?: string;
        icon?: string | null;
        iconAfter?: string | null;
        checkIcon?: string;
        checkIconClass?: string | null;
        invalid?: boolean | null;
        triggerClass?: string | null;
        minSelection?: number | null;
        maxSelection?: number | null;
        /** Grey out unselected options once `maxSelection` is reached. */
        disableUnSelectedOptionWhenReachingMax?: boolean;
        size?: 'default' | 'sm';
    }>(),
    {
        name: null,
        label: null,
        placeholder: 'Select option',
        options: null,
        searchable: false,
        multiple: false,
        pillbox: false,
        clearable: false,
        disabled: false,
        loading: false,
        empty: 'No results found',
        icon: null,
        iconAfter: 'hi:chevron-up-down',
        checkIcon: 'check',
        checkIconClass: null,
        invalid: null,
        triggerClass: null,
        minSelection: null,
        maxSelection: null,
        disableUnSelectedOptionWhenReachingMax: false,
        size: 'default',
    },
);

const model = defineModel<ListboxValue | ListboxValue[] | null>({ default: null });

const attrs = useAttrs();
const id = useId();
const listId = `${id}-options`;

const isOpen = ref(false);
const trigger = ref<HTMLElement | null>(null);
const panel = ref<HTMLElement | null>(null);
const search = ref<HTMLInputElement | null>(null);

const listbox = useListbox({
    model,
    multiple: () => props.multiple,
    minSelection: () => props.minSelection,
    maxSelection: () => props.maxSelection,
    disableUnselectedAtMax: () => props.disableUnSelectedOptionWhenReachingMax,
    checkIcon: () => props.checkIcon,
    checkIconClass: () => props.checkIconClass,
    loading: () => props.loading,
    onSingleSelect: () => close(true),
});

const { floatingStyles } = useAnchored(trigger, panel, { placement: 'bottom-start', offset: 4, open: isOpen, matchWidth: true });

const pageErrors = usePageErrors(() => props.name);
const isInvalid = computed(() => props.invalid ?? pageErrors.value.length > 0);
const errorId = computed(() => (isInvalid.value ? errorIdFor(props.name) : undefined));

const triggerText = computed(() => {
    const values = listbox.selectedValues.value;

    if (values.length === 0) return props.placeholder;
    if (values.length === 1) return listbox.labelFor(values[0]) || props.placeholder;

    return `${values.length} items selected`;
});

const hiddenValue = computed(() => listbox.selectedValues.value.join(','));

const activeDescendant = computed(() => (isOpen.value ? (listbox.activeId.value ?? undefined) : undefined));

function open(): void {
    if (props.disabled) return;

    isOpen.value = true;

    nextTick(() => {
        if (props.searchable) search.value?.focus();
        else listbox.listEl.value?.focus();

        listbox.activateSelectedOrFirst();
    });
}

function close(focusTrigger = false): void {
    if (!isOpen.value) return;

    isOpen.value = false;
    listbox.query.value = '';

    if (focusTrigger) nextTick(() => trigger.value?.querySelector<HTMLElement>('[data-slot=select-control]')?.focus());
}

function toggle(): void {
    if (isOpen.value) close();
    else open();
}

function onTriggerKeydown(event: KeyboardEvent): void {
    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
        event.preventDefault();
        open();
    }
}

function onPanelKeydown(event: KeyboardEvent): void {
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            listbox.move(1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            listbox.move(-1);
            break;
        case 'Home':
            if (!props.searchable) {
                event.preventDefault();
                listbox.activateFirst();
            }
            break;
        case 'End':
            if (!props.searchable) {
                event.preventDefault();
                listbox.activateLast();
            }
            break;
        case 'Enter':
            event.preventDefault();
            listbox.selectActive();
            break;
        case 'Escape':
            event.preventDefault();
            event.stopPropagation();
            close(true);
            break;
        case 'Tab':
            close();
            break;
        default:
            if (!props.searchable && /^[a-z0-9]$/i.test(event.key)) listbox.typeahead(event.key);
    }
}

// Typing in the search box activates the first match.
watch(listbox.query, () => nextTick(() => listbox.activateFirst()));

useClickOutside([trigger, panel], () => close());

const sizeIconClass = computed(() => (props.size === 'sm' ? 'size-6 p-0.75' : 'size-8 p-1'));

const controlClasses = computed(() => [
    isInvalid.value
        ? 'border-2 border-red-600/30 data-open:border-red-600/30 data-open:ring-red-600/20 dark:border-red-400/30 dark:data-open:border-red-400/30 dark:data-open:ring-red-400/20'
        : 'border-black/10 data-open:border-black/15 data-open:ring-neutral-900/15 dark:border-white/15 dark:data-open:border-white/20 dark:data-open:ring-neutral-100/15',
    'rounded-box border bg-white text-start dark:bg-neutral-900 dark:text-gray-300',
    'data-open:ring-2 data-open:ring-offset-0 data-open:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900/15 focus-visible:outline-none dark:focus-visible:ring-neutral-100/15',
    'col-span-4 col-start-1 row-start-1 justify-self-stretch',
    'cursor-pointer overflow-hidden whitespace-nowrap disabled:cursor-auto disabled:opacity-60',
    props.triggerClass,
]);
</script>

<template>
    <div :class="['relative [--popup-padding:--spacing(1)] [--popup-round:var(--radius-box)]', attrs.class]" :data-invalid="isInvalid ? '' : undefined">
        <input v-if="name" type="hidden" :name="name" :value="hiddenValue" />

        <!-- Pillbox trigger: the selected values as removable tags -->
        <div
            v-if="multiple && pillbox"
            ref="trigger"
            :class="[
                'relative grid grid-cols-[1fr_auto] items-start justify-center',
                '[&:has([data-slot=select-control]+[data-slot=icon])>[data-slot=select-control]]:pr-9',
                disabled ? '[&_[data-slot=icon]]:cursor-auto [&_[data-slot=icon]]:opacity-40' : '',
            ]"
            data-slot="trigger"
        >
            <div
                role="combobox"
                :tabindex="disabled ? -1 : 0"
                aria-haspopup="listbox"
                :aria-expanded="isOpen ? 'true' : 'false'"
                :aria-controls="listId"
                :aria-activedescendant="activeDescendant"
                :aria-label="label ?? placeholder"
                :aria-invalid="isInvalid ? 'true' : undefined"
                :aria-describedby="errorId"
                :aria-disabled="disabled ? 'true' : undefined"
                :data-open="isOpen ? '' : undefined"
                :class="[controlClasses, 'flex flex-wrap truncate', size === 'sm' ? 'min-h-8 gap-0.5 p-0.5 text-xs' : 'min-h-10 gap-1 p-1 text-sm']"
                data-slot="select-control"
                @click="toggle"
                @keydown="onTriggerKeydown"
            >
                <div
                    v-for="value in listbox.selectedValues.value"
                    :key="String(value)"
                    :class="[
                        'inline-flex max-w-full items-center rounded-md border border-black/5 bg-neutral-100 font-medium text-neutral-800 dark:border-white/10 dark:bg-neutral-800 dark:text-neutral-200',
                        size === 'sm' ? 'px-1.5 py-0.5 text-xs' : 'px-2 py-1 text-sm',
                    ]"
                >
                    <span class="truncate">{{ listbox.labelFor(value) }}</span>
                    <button
                        type="button"
                        class="ml-1 rounded-sm hover:bg-black/5 focus:ring-2 focus:ring-neutral-400/30 focus:outline-none dark:hover:bg-white/10"
                        :aria-label="`Remove ${listbox.labelFor(value)}`"
                        data-slot="remove-btn"
                        @click.stop="listbox.deselect(value)"
                    >
                        <Icon name="hi:x-mark" class="size-3.5 opacity-60" />
                    </button>
                </div>

                <span v-if="!listbox.hasSelection.value" class="self-center pl-2 text-neutral-500 dark:text-neutral-400">{{ placeholder }}</span>
            </div>

            <div
                v-if="iconAfter && !(clearable && listbox.hasSelection.value)"
                class="col-span-1 col-start-3 row-start-1 h-full justify-self-center pt-1 pr-1"
                data-slot="icon"
                @click="toggle"
            >
                <Icon :name="iconAfter" :class="['rounded-md text-neutral-700 hover:bg-neutral-800/5 dark:text-neutral-300 dark:hover:bg-white/5', sizeIconClass]" />
            </div>

            <div
                v-if="clearable && listbox.hasSelection.value"
                class="col-span-1 col-start-3 row-start-1 h-full justify-self-center pt-1 pr-1"
                role="button"
                tabindex="0"
                aria-label="Clear selection"
                data-slot="select-clear"
                @click.stop="listbox.clear()"
                @keydown.enter.stop="listbox.clear()"
                @keydown.space.prevent.stop="listbox.clear()"
            >
                <Icon name="hi:x-mark" :class="['rounded-md text-neutral-700 hover:bg-neutral-800/5 dark:text-neutral-300 dark:hover:bg-white/5', sizeIconClass]" />
            </div>
        </div>

        <!-- Default trigger -->
        <div
            v-else
            ref="trigger"
            :class="[
                'relative grid grid-cols-[40px_1fr_auto] place-items-center',
                '[&>[data-slot=icon]+[data-slot=select-control]]:pl-10',
                disabled ? '[&_[data-slot=icon]]:cursor-auto [&_[data-slot=icon]]:opacity-40' : '',
            ]"
            data-slot="trigger"
        >
            <Icon v-if="icon" :name="icon" class="z-10 col-span-1 col-start-1 row-start-1 flex h-full w-full !size-[1.10rem] items-center justify-center" />

            <button
                type="button"
                role="combobox"
                aria-haspopup="listbox"
                :aria-expanded="isOpen ? 'true' : 'false'"
                :aria-controls="listId"
                :aria-activedescendant="activeDescendant"
                :aria-label="label ?? placeholder"
                :aria-invalid="isInvalid ? 'true' : undefined"
                :aria-describedby="errorId"
                :data-open="isOpen ? '' : undefined"
                :disabled="disabled"
                :class="[controlClasses, 'flex items-center truncate', size === 'sm' ? 'min-h-8 p-0.5 text-xs' : 'min-h-10 p-1 pl-2 text-sm']"
                data-slot="select-control"
                @click="toggle"
                @keydown="onTriggerKeydown"
            >
                <span :class="['w-full self-center truncate', listbox.hasSelection.value ? '' : 'text-neutral-500 dark:text-neutral-400']">{{ triggerText }}</span>
            </button>

            <div v-if="iconAfter && !(clearable && listbox.hasSelection.value)" class="col-start-3 row-start-1 justify-self-center pr-1" data-slot="icon" @click="toggle">
                <Icon :name="iconAfter" :class="['rounded-md text-neutral-700 hover:bg-neutral-800/5 dark:text-neutral-300 dark:hover:bg-white/5', sizeIconClass]" />
            </div>

            <div
                v-if="clearable && listbox.hasSelection.value"
                class="col-start-3 row-start-1 justify-self-center pr-1"
                role="button"
                tabindex="0"
                aria-label="Clear selection"
                data-slot="select-clear"
                @click.stop="listbox.clear()"
                @keydown.enter.stop="listbox.clear()"
                @keydown.space.prevent.stop="listbox.clear()"
            >
                <Icon name="hi:x-mark" :class="['rounded-md text-neutral-700 hover:bg-neutral-800/5 dark:text-neutral-300 dark:hover:bg-white/5', sizeIconClass]" />
            </div>
        </div>

        <!-- Options stay mounted while closed so the trigger can show the selected labels. -->
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
                @keydown="onPanelKeydown"
            >
                <div
                    v-if="searchable"
                    class="mb-(--popup-padding) grid w-full grid-cols-[20px_1fr] items-center justify-center border-b border-neutral-200 px-(--popup-padding) py-(--popup-padding) dark:border-neutral-700 [&>[data-slot=icon]+[data-slot=search-control]]:pl-8"
                >
                    <Icon name="magnifying-glass" class="col-span-1 col-start-1 row-start-1 ml-1.5 !size-5 !text-neutral-500 dark:!text-neutral-400" />
                    <input
                        ref="search"
                        v-model="listbox.query.value"
                        type="text"
                        placeholder="Search"
                        autocomplete="off"
                        :aria-controls="listId"
                        :aria-activedescendant="activeDescendant"
                        class="col-span-4 col-start-1 row-start-1 h-8 border-0 bg-transparent text-sm text-neutral-900 ring-0 ring-offset-0 outline-none placeholder:text-neutral-500 focus:ring-0 dark:text-neutral-50 dark:placeholder:text-neutral-400"
                        data-slot="search-control"
                    />
                </div>

                <ul
                    :id="listId"
                    :ref="(el) => (listbox.listEl.value = el as HTMLElement | null)"
                    role="listbox"
                    tabindex="-1"
                    :aria-multiselectable="multiple ? 'true' : 'false'"
                    :aria-label="label ?? 'Options'"
                    :aria-activedescendant="activeDescendant"
                    :data-loading="loading ? '' : undefined"
                    class="relative grid max-h-60 grid-cols-[auto_auto_1fr] gap-y-1 overflow-y-auto px-(--popup-padding) outline-none data-loading:h-24"
                >
                    <slot :query="listbox.query.value" />

                    <ListboxOption v-for="option in options ?? []" :key="String(option.value)" :value="option.value" :label="option.label" :icon="option.icon" :disabled="option.disabled" />

                    <li v-if="!loading && listbox.visibleOptions.value.length === 0 && !$slots.create" class="col-span-full" role="presentation">
                        <slot name="empty">
                            <Text class="flex h-14 items-center justify-center">{{ empty }}</Text>
                        </slot>
                    </li>

                    <slot name="create" :query="listbox.query.value" />

                    <li v-if="loading" class="absolute top-1/2 left-1/2 flex -translate-1/2" role="presentation">
                        <slot name="loading"><IconLoading class="opacity-50" /></slot>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>
