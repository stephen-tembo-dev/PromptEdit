<script setup lang="ts">
import { computed, ref, useAttrs, useSlots } from 'vue';
import { errorIdFor, usePageErrors } from '../form/context';
import Icon from '../icon/Icon.vue';
import InputExtraSlot from './InputExtraSlot.vue';
import InputOptionButton from './InputOptionButton.vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        /** Field name: also used to read Laravel's validation error for this field. */
        name?: string | null;
        type?: string;
        prefix?: string | null;
        suffix?: string | null;
        prefixIcon?: string | null;
        suffixIcon?: string | null;
        leftIcon?: string | null;
        rightIcon?: string | null;
        clearable?: boolean;
        copyable?: boolean;
        revealable?: boolean;
        /** Forces the invalid style. Defaults to whether the page has an error for `name`. */
        invalid?: boolean | null;
    }>(),
    {
        name: null,
        type: 'text',
        prefix: null,
        suffix: null,
        prefixIcon: null,
        suffixIcon: null,
        leftIcon: null,
        rightIcon: null,
        clearable: false,
        copyable: false,
        revealable: false,
        invalid: null,
    },
);

const model = defineModel<string | number | null>({ default: '' });

const attrs = useAttrs();
const slots = useSlots();

const pageErrors = usePageErrors(() => props.name);
const isInvalid = computed(() => props.invalid ?? pageErrors.value.length > 0);
const errorId = computed(() => (isInvalid.value ? errorIdFor(props.name) : undefined));

const revealed = ref(false);
const copied = ref(false);
const inputType = computed(() => (props.revealable && revealed.value ? 'text' : props.type));

const hasLeftIcon = computed(() => !!props.leftIcon || !!slots.leftIcon);
const hasRightIcon = computed(() => !!props.rightIcon || !!slots.rightIcon);
const hasPrefix = computed(() => !!props.prefix || !!props.prefixIcon || !!slots.prefix);
const hasSuffix = computed(() => !!props.suffix || !!props.suffixIcon || !!slots.suffix);

const iconCount = computed(() => [props.clearable, props.copyable, props.revealable, hasRightIcon.value].filter(Boolean).length);

const gridStyle = computed(() => ({
    '--icon-count': String(iconCount.value),
    '--icon-width': '2rem',
    gridTemplateColumns: hasLeftIcon.value
        ? '2.3rem 1fr calc(var(--icon-width) * var(--icon-count))'
        : '1fr calc(var(--icon-width) * var(--icon-count))',
}));

const inputAttrs = computed(() => {
    const { class: _class, style: _style, ...rest } = attrs;
    return rest;
});

function clear(): void {
    model.value = '';
}

async function copy(): Promise<void> {
    const value = model.value == null ? '' : String(model.value);
    if (!value) return;

    try {
        await navigator.clipboard.writeText(value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch (error) {
        console.warn('Failed to copy to clipboard:', error);
    }
}
</script>

<template>
    <div
        :class="[
            'isolate relative flex w-full items-stretch rounded-box shadow-xs transition-colors duration-200 disabled:shadow-none',
            '[&:has([data-slot=input-prefix])_input]:rounded-l-none',
            '[&:has([data-slot=input-suffix])_input]:rounded-r-none',
            '[&:has([data-slot=input-prefix]):has([data-slot=input-suffix])_input]:rounded-none',
            attrs.class,
        ]"
        :style="attrs.style as any"
    >
        <InputExtraSlot v-if="hasPrefix" data-slot="input-prefix">
            <slot name="prefix">
                <Icon v-if="prefixIcon" :name="prefixIcon" />
                <template v-else>{{ prefix }}</template>
            </slot>
        </InputExtraSlot>

        <div
            :class="[
                // A grid lets the icons and action buttons sit on top of the input in their own columns.
                'isolate grid w-full',
                '[&:not(:has([data-slot=left-icon]))>[data-slot=input-actions]]:col-start-2',
                '[&:has([data-slot=left-icon])>[data-slot=input-actions]]:col-start-3',
                '[&>[data-slot=input-actions]]:row-start-1 [&>[data-slot=input-actions]]:place-self-center [&>[data-slot=input-actions]]:z-10',
                '[&>[data-slot=control]]:col-span-3 [&>[data-slot=control]]:col-start-1 [&>[data-slot=control]]:row-start-1',
                '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:col-start-1 [&:has([data-slot=left-icon])>[data-slot=left-icon]]:row-start-1 [&:has([data-slot=left-icon])>[data-slot=left-icon]]:place-self-center [&:has([data-slot=left-icon])>[data-slot=left-icon]]:!z-20',
                // Padding so text never runs under an icon: one step per action button.
                '[&:has([data-slot=left-icon])>[data-slot=control]]:pl-[2.2rem]',
                '[&:has([data-slot=input-actions]):has([data-slot=input-option])>[data-slot=control]]:pr-[1.9rem]',
                '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[3.8rem]',
                '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[5.7rem]',
                '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[7.6rem]',
            ]"
            :style="gridStyle"
        >
            <div v-if="hasLeftIcon" class="!text-neutral-500 dark:!text-neutral-500" data-slot="left-icon">
                <slot name="leftIcon">
                    <Icon :name="leftIcon" class="!size-[1.15rem]" />
                </slot>
            </div>

            <input
                v-model="model"
                :name="name ?? undefined"
                :type="inputType"
                v-bind="inputAttrs"
                :aria-invalid="isInvalid ? 'true' : undefined"
                :aria-describedby="errorId"
                :class="[
                    'z-10 inline-block w-full border p-2 text-sm text-neutral-800 placeholder-neutral-400 disabled:text-neutral-500 disabled:placeholder-neutral-400/70 dark:text-neutral-300 dark:placeholder-neutral-400 dark:disabled:text-neutral-400 dark:disabled:placeholder-neutral-500',
                    'bg-white dark:bg-neutral-900 dark:disabled:bg-neutral-800',
                    'transition-colors duration-200 disabled:cursor-not-allowed',
                    'rounded-box shadow-none disabled:shadow-none dark:shadow-sm',
                    'focus:ring-2 focus:ring-offset-0 focus:outline-none',
                    isInvalid
                        ? 'border-2 border-red-600/30 focus:border-red-600/30 focus:ring-red-600/20 dark:border-red-400/30 dark:focus:border-red-400/30 dark:focus:ring-red-400/20'
                        : 'border-black/10 focus:border-black/15 focus:ring-neutral-900/15 dark:border-white/15 dark:focus:border-white/20 dark:focus:ring-neutral-100/15',
                ]"
                data-slot="control"
                data-control-id="input"
            />

            <div class="mr-1 flex h-full items-center justify-center" data-slot="input-actions">
                <InputOptionButton
                    v-if="copyable"
                    :aria-label="copied ? 'Copied!' : 'Copy to clipboard'"
                    :title="copied ? 'Copied!' : 'Copy to clipboard'"
                    @click="copy"
                >
                    <Icon :name="copied ? 'hi:clipboard-document-check' : 'hi:clipboard-document'" class="size-5" aria-hidden="true" />
                </InputOptionButton>

                <InputOptionButton v-if="clearable" aria-label="Clear" @click="clear">
                    <Icon name="x" />
                </InputOptionButton>

                <InputOptionButton
                    v-if="revealable"
                    :aria-label="revealed ? 'Hide password' : 'Show password'"
                    :title="revealed ? 'Hide password' : 'Show password'"
                    @click="revealed = !revealed"
                >
                    <Icon :name="revealed ? 'eye-slash' : 'eye'" aria-hidden="true" />
                </InputOptionButton>

                <!-- Not an action, just an icon slotted as one so the padding rules count it. -->
                <div v-if="hasRightIcon" class="!text-neutral-500 dark:!text-neutral-500" data-slot="input-option">
                    <slot name="rightIcon">
                        <Icon :name="rightIcon" />
                    </slot>
                </div>
            </div>
        </div>

        <InputExtraSlot v-if="hasSuffix" data-slot="input-suffix">
            <slot name="suffix">
                <Icon v-if="suffixIcon" :name="suffixIcon" />
                <template v-else>{{ suffix }}</template>
            </slot>
        </InputExtraSlot>
    </div>
</template>
