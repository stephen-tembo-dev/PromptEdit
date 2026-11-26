<script setup lang="ts">
import { computed, inject, ref, useId, watch } from 'vue';
import { usePageErrors } from '../form/context';
import CheckboxIndicator from './CheckboxIndicator.vue';
import { checkboxGroupKey, type CheckboxValue, type CheckboxVariant } from './context';

const props = withDefaults(
    defineProps<{
        name?: string | null;
        label?: string | null;
        description?: string | null;
        /** The value this box adds to a <CheckboxGroup>'s array, and submits in a plain form. */
        value?: CheckboxValue | null;
        indeterminate?: boolean;
        disabled?: boolean;
        invalid?: boolean | null;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
        variant?: CheckboxVariant | null;
    }>(),
    { name: null, label: null, description: null, value: null, indeterminate: false, disabled: false, invalid: null, size: 'md', variant: null },
);

/** Standalone checked state. Inside a <CheckboxGroup>, the group's array is the state instead. */
const model = defineModel<boolean>({ default: false });

const group = inject(checkboxGroupKey, null);

const id = useId();
const isIndeterminate = ref(props.indeterminate);
watch(
    () => props.indeterminate,
    (value) => (isIndeterminate.value = value),
);

const pageErrors = usePageErrors(() => props.name);
const isInvalid = computed(() => props.invalid ?? pageErrors.value.length > 0);

const resolvedVariant = computed<CheckboxVariant>(() => props.variant ?? group?.variant.value ?? 'default');

const checked = computed(() => (group ? props.value !== null && group.model.value.includes(props.value) : model.value));

function toggle(): void {
    if (props.disabled) return;

    // Interacting clears the indeterminate state first.
    isIndeterminate.value = false;

    if (group) {
        if (props.value === null) return;

        const current = group.model.value;
        group.model.value = current.includes(props.value) ? current.filter((item) => item !== props.value) : [...current, props.value];
        return;
    }

    model.value = !model.value;
}

const labelSizeClass = computed(() => ({ xs: 'text-xs', sm: 'text-sm', md: 'text-sm', lg: 'text-base', xl: 'text-lg' })[props.size]);
</script>

<template>
    <div data-slot="checkbox-wrapper">
        <!-- Keeps the checkbox working in a plain HTML form submission. -->
        <input
            type="checkbox"
            hidden
            tabindex="-1"
            :name="name ?? undefined"
            :value="value === null ? undefined : String(value)"
            :checked="checked"
            :disabled="disabled"
        />

        <!-- Pill -->
        <span
            v-if="resolvedVariant === 'pills'"
            role="checkbox"
            :tabindex="disabled ? -1 : 0"
            :aria-checked="isIndeterminate ? 'mixed' : checked ? 'true' : 'false'"
            :aria-invalid="isInvalid ? 'true' : undefined"
            :aria-disabled="disabled ? 'true' : undefined"
            :data-checked="checked && !isIndeterminate ? '' : undefined"
            class="text-md w-fit cursor-pointer rounded-full bg-neutral-800/10 px-2 py-0.5 select-none data-[checked]:bg-[var(--color-primary)] data-[checked]:text-[var(--color-primary-fg)] dark:bg-white/10"
            @click="toggle"
            @keydown.space.prevent="toggle"
            @keydown.enter.prevent="toggle"
        >
            <slot>{{ label }}</slot>
        </span>

        <!-- Card -->
        <div
            v-else-if="resolvedVariant === 'cards'"
            role="checkbox"
            :tabindex="disabled ? -1 : 0"
            :aria-checked="isIndeterminate ? 'mixed' : checked ? 'true' : 'false'"
            :aria-invalid="isInvalid ? 'true' : undefined"
            :aria-disabled="disabled ? 'true' : undefined"
            :aria-labelledby="`${id}-label`"
            :data-checked="checked && !isIndeterminate ? '' : undefined"
            class="group isolate grid w-full cursor-pointer grid-cols-[1fr_24px] gap-3 rounded-box border border-black/10 bg-white p-4 text-start transition-colors duration-200 hover:bg-neutral-200 data-[checked]:bg-neutral-300 dark:border-white/15 dark:bg-neutral-800 dark:hover:bg-neutral-700 dark:data-[checked]:bg-neutral-700"
            @click="toggle"
            @keydown.space.prevent="toggle"
            @keydown.enter.prevent="toggle"
        >
            <div class="col-start-1 flex flex-col gap-2">
                <span :id="`${id}-label`" :class="['font-semibold select-none [:where(&)]:text-neutral-800 [:where(&)]:dark:text-white', labelSizeClass]">
                    <slot>{{ label }}</slot>
                </span>
                <span v-if="description" class="col-start-1 text-sm text-neutral-500 dark:text-neutral-400" data-slot="checkbox-description">
                    {{ description }}
                </span>
            </div>

            <CheckboxIndicator
                class="z-10 col-start-2 group-hover:bg-neutral-300"
                :checked="checked"
                :indeterminate="isIndeterminate"
                :disabled="disabled"
                :invalid="isInvalid"
                :size="size"
                :interactive="false"
            />
        </div>

        <!-- Default: the description lines up under the label, not under the box. -->
        <div v-else class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-start">
            <div class="col-span-2 grid grid-cols-subgrid items-center">
                <CheckboxIndicator
                    class="col-start-1"
                    :checked="checked"
                    :indeterminate="isIndeterminate"
                    :disabled="disabled"
                    :invalid="isInvalid"
                    :size="size"
                    :aria-labelledby="label || $slots.default ? `${id}-label` : undefined"
                    @toggle="toggle"
                />

                <label
                    v-if="label || $slots.default"
                    :id="`${id}-label`"
                    :class="['col-start-2 font-semibold select-none [:where(&)]:text-neutral-800 [:where(&)]:dark:text-white', labelSizeClass, disabled ? 'opacity-50' : 'cursor-pointer']"
                    data-slot="checkbox-label"
                    @click="toggle"
                >
                    <slot>{{ label }}</slot>
                </label>
            </div>

            <span v-if="description" data-slot="checkbox-description" class="col-start-2 text-sm text-neutral-500 dark:text-neutral-400">
                {{ description }}
            </span>
        </div>
    </div>
</template>
