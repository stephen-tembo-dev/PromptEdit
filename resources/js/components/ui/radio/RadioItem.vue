<script setup lang="ts">
import { computed, inject, useId } from 'vue';
import Icon from '../icon/Icon.vue';
import { radioGroupKey, type RadioValue } from './context';

const props = withDefaults(
    defineProps<{
        value: Exclude<RadioValue, null>;
        label?: string | null;
        description?: string | null;
        icon?: string | null;
        iconVariant?: string;
        iconClass?: string;
        disabled?: boolean;
    }>(),
    { label: null, description: null, icon: null, iconVariant: 'regular', iconClass: '', disabled: false },
);

const group = inject(radioGroupKey);

if (!group) {
    throw new Error('<RadioItem> must be used inside a <RadioGroup>.');
}

const id = useId();

const variant = computed(() => group.variant.value);
const isSegmented = computed(() => variant.value === 'segmented');
const isCards = computed(() => variant.value === 'cards');
const isPills = computed(() => variant.value === 'pills');
const showIcon = computed(() => (isSegmented.value || isCards.value) && !!props.icon);
const showIndicator = computed(() => group.indicator.value && !isPills.value && !isSegmented.value);

const isDisabled = computed(() => props.disabled || group.disabled.value);

const model = computed({
    get: () => group.model.value,
    set: (value) => (group.model.value = value),
});
</script>

<template>
    <div
        :class="[
            'relative isolate transition duration-200',
            isCards
                ? 'flex-1 rounded-field border border-black/5 bg-white px-6 py-4 hover:bg-neutral-100 has-[:checked]:border-neutral-950/10 has-[:checked]:bg-white/5 dark:border-white/5 dark:bg-neutral-900 dark:hover:bg-neutral-700 dark:has-[:checked]:border-white/10 dark:has-[:checked]:bg-neutral-700'
                : '',
        ]"
    >
        <input :id="id" v-model="model" class="peer sr-only" type="radio" :name="group.name.value" :value="value" :disabled="isDisabled" data-slot="radio-item-control" />

        <label
            :for="id"
            :class="[
                'flex flex-1 cursor-pointer items-center gap-2 text-sm font-medium transition duration-200',
                'text-neutral-900 hover:text-neutral-800 dark:text-neutral-200 dark:hover:text-white',
                'peer-checked:[&>[data-slot=radio-item-indicator]]:border-black peer-checked:[&>[data-slot=radio-item-indicator]]:bg-black peer-checked:[&>[data-slot=radio-item-indicator]]:ring-white',
                'peer-checked:[&>[data-slot=radio-item-indicator]]:after:block',
                'dark:peer-checked:[&>[data-slot=radio-item-indicator]]:border-white dark:peer-checked:[&>[data-slot=radio-item-indicator]]:bg-white',
                'peer-focus-visible:[&>[data-slot=radio-item-indicator]]:ring-2 peer-focus-visible:[&>[data-slot=radio-item-indicator]]:ring-neutral-900/20 dark:peer-focus-visible:[&>[data-slot=radio-item-indicator]]:ring-white/30',
                'peer-disabled:cursor-auto peer-disabled:opacity-50',
                'peer-disabled:[&>[data-slot=radio-item-indicator]]:opacity-50 peer-disabled:[&>[data-slot=radio-item-indicator]]:shadow-none',
                isSegmented
                    ? 'rounded-field p-2 text-neutral-500 peer-checked:bg-white/80 peer-checked:text-neutral-950 peer-checked:shadow-xs peer-focus-visible:ring-2 peer-focus-visible:ring-neutral-900/20 hover:bg-white hover:text-neutral-950 dark:text-white/70 dark:peer-checked:bg-neutral-700 dark:peer-checked:text-white dark:hover:bg-neutral-700'
                    : '',
                isPills
                    ? 'rounded-full px-2 py-0.5 peer-checked:bg-primary-content peer-checked:text-primary-fg peer-checked:hover:text-primary-fg peer-focus-visible:ring-2 peer-focus-visible:ring-primary-600'
                    : '',
                isCards ? '[&>[data-slot=radio-item-indicator]]:order-3 [&>[data-slot=radio-item-indicator]]:ml-auto' : '',
            ]"
        >
            <span
                v-if="showIndicator"
                class="relative size-[1.125rem] shrink-0 rounded-full border border-gray-300 bg-white text-sm shadow-xs after:absolute after:top-1/2 after:left-1/2 after:hidden after:size-[.5rem] after:-translate-1/2 after:rounded-full after:bg-white after:content-[''] dark:border-white/10 dark:bg-white/5 dark:after:bg-black"
                data-slot="radio-item-indicator"
            />
            <span v-if="isCards" class="absolute inset-0 size-full" />

            <Icon v-if="showIcon" :name="icon" :variant="iconVariant" :class="iconClass" />

            <span class="font-semibold"><slot>{{ label }}</slot></span>
        </label>

        <p
            v-if="description && !isPills"
            :class="['w-full text-start text-sm text-neutral-700 dark:text-neutral-200', isCards ? 'pl-0!' : 'pl-[1.625rem]']"
            data-slot="radio-item-control-description"
        >
            {{ description }}
        </p>
    </div>
</template>
