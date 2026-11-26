<script setup lang="ts">
import { computed, inject, onBeforeUnmount, onMounted, reactive, ref, useId, watch } from 'vue';
import Icon from '../icon/Icon.vue';
import { listboxKey, type ListboxOptionRecord, type ListboxValue } from './context';

/**
 * One option in a <Select> or <Combobox> (exported as SelectOption and ComboboxOption).
 *
 *   <SelectOption value="ke">Kenya</SelectOption>
 *   <SelectOption :value="3" label="Three" search-label="three 3 iii" icon="hash" />
 *
 * Without a `label`, the option's text is its label; without a `value`, its label is its value.
 */
const props = withDefaults(
    defineProps<{
        value?: ListboxValue | null;
        label?: string | null;
        searchLabel?: string | null;
        icon?: string | null;
        iconClass?: string | null;
        disabled?: boolean;
        /** Render the slot as the whole option (no check mark column). Needs a `label`. */
        allowCustomSlots?: boolean;
    }>(),
    { value: null, label: null, searchLabel: null, icon: null, iconClass: null, disabled: false, allowCustomSlots: false },
);

const listbox = inject(listboxKey);

if (!listbox) {
    throw new Error('<SelectOption> must be used inside a <Select> or <Combobox>.');
}

const id = useId();
const el = ref<HTMLElement | null>(null);
const slotText = ref('');

let order = 0;
let unregister: (() => void) | null = null;

const record = reactive<ListboxOptionRecord>({ id, value: '', label: '', searchLabel: '', disabled: false, order: 0 });

function sync(): void {
    const label = props.label ?? slotText.value;

    record.label = label;
    record.value = props.value ?? label;
    record.searchLabel = props.searchLabel ?? label;
    record.disabled = props.disabled;
}

watch(() => [props.value, props.label, props.searchLabel, props.disabled, slotText.value], sync, { immediate: true });

onMounted(() => {
    slotText.value = (el.value?.querySelector('[data-option-label]')?.textContent ?? el.value?.textContent ?? '').trim();

    // Options are numbered by their place on screen.
    const siblings = el.value?.closest('[role=listbox]')?.querySelectorAll('[data-slot=option]');
    order = siblings ? Array.from(siblings).indexOf(el.value!) : 0;
    record.order = order;

    sync();
    unregister = listbox.register(record);
});

onBeforeUnmount(() => unregister?.());

const selected = computed(() => listbox.isSelected(record.value));
const active = computed(() => listbox.isActive(id));
const visible = computed(() => listbox.isVisible(record));
const blocked = computed(() => props.disabled || listbox.isBlockedByMax(record.value));

function choose(): void {
    if (!blocked.value) listbox!.select(record.value);
}
</script>

<template>
    <li
        v-show="visible && !listbox.loading.value"
        :id="id"
        ref="el"
        role="option"
        :aria-selected="selected ? 'true' : 'false'"
        :aria-disabled="blocked ? 'true' : undefined"
        :data-selected="selected ? 'true' : undefined"
        :data-active="active ? 'true' : undefined"
        :class="[
            'group w-full cursor-pointer items-center gap-x-2 self-center rounded-[calc(var(--popup-round)-var(--popup-padding))] px-3 py-2 text-sm',
            'data-active:bg-neutral-800/5 dark:data-active:bg-white/5',
            allowCustomSlots ? 'col-span-full' : 'col-span-full grid grid-cols-subgrid',
            '[&[aria-disabled=true]]:pointer-events-none [&[aria-disabled=true]]:opacity-50',
        ]"
        data-slot="option"
        @click="choose"
        @mousemove="!active && listbox.setActive(id)"
    >
        <template v-if="!allowCustomSlots">
            <Icon
                :name="listbox.checkIcon.value"
                :class="['z-10 size-[1.15rem] place-self-center opacity-0 group-data-selected:opacity-100', listbox.checkIconClass.value]"
            />
            <Icon v-if="icon" :name="icon" :class="['z-10 pl-1.5', iconClass]" />
            <span v-else />
            <span class="col-start-3 text-start text-neutral-950 dark:text-neutral-50" data-option-label><slot>{{ label }}</slot></span>
        </template>
        <slot v-else />
    </li>
</template>
