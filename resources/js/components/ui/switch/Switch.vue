<script setup lang="ts">
import { computed, useId } from 'vue';
import Icon from '../icon/Icon.vue';

const props = withDefaults(
    defineProps<{
        name?: string | null;
        label?: string;
        description?: string | null;
        /** The toggle sits on the right of its label (default) or the left. */
        align?: 'left' | 'right';
        disabled?: boolean;
        maxWidth?: string;
        size?: 'sm' | 'md' | 'lg';
        switchClass?: string;
        thumbClass?: string;
        iconOn?: string | null;
        iconOff?: string | null;
        onClass?: string;
        offClass?: string;
        thumbOnClass?: string;
        thumbOffClass?: string;
    }>(),
    {
        name: null,
        label: '',
        description: null,
        align: 'right',
        disabled: false,
        maxWidth: 'max-w-md',
        size: 'md',
        switchClass: '',
        thumbClass: '',
        iconOn: null,
        iconOff: null,
        onClass: '',
        offClass: '',
        thumbOnClass: '',
        thumbOffClass: '',
    },
);

const checked = defineModel<boolean>({ default: false });

const id = useId();

const sizeConfig = computed(() => {
    switch (props.size) {
        case 'sm':
            return { switch: 'h-4 w-7', thumb: 'size-3', activeTranslate: 'translate-x-3', iconSize: 'size-2.5' };
        case 'lg':
            return { switch: 'h-8 w-14', thumb: 'size-7', activeTranslate: 'translate-x-6', iconSize: 'size-6' };
        default:
            return { switch: 'h-6 w-11', thumb: 'size-5', activeTranslate: 'translate-x-5', iconSize: 'size-3' };
    }
});

function toggle(): void {
    if (!props.disabled) checked.value = !checked.value;
}
</script>

<template>
    <!-- Full width up to its max, so a column of switches lines its toggles up. -->
    <div :class="['w-full', maxWidth]">
        <div :class="['flex items-center gap-x-2', align === 'left' ? 'flex-row' : 'flex-row-reverse']">
            <div class="flex flex-shrink-0 items-center">
                <button
                    type="button"
                    role="switch"
                    :aria-checked="checked ? 'true' : 'false'"
                    :aria-labelledby="label ? `${id}-label` : undefined"
                    :disabled="disabled"
                    :class="[
                        'relative inline-flex flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                        sizeConfig.switch,
                        switchClass,
                        checked ? ['[:where(&)]:bg-primary-700 [:where(&)]:dark:bg-primary-400', onClass] : [offClass, '[:where(&)]:bg-neutral-300 [:where(&)]:dark:bg-white/10'],
                    ]"
                    @click="toggle"
                >
                    <span
                        :class="[
                            'pointer-events-none inline-flex transform items-center justify-center rounded-full shadow ring-0 transition duration-200 ease-in-out',
                            sizeConfig.thumb,
                            thumbClass,
                            checked
                                ? [sizeConfig.activeTranslate, thumbOnClass, '[:where(&)]:bg-white [:where(&)]:dark:bg-neutral-950']
                                : ['translate-x-[0.05rem] [:where(&)]:bg-white', thumbOffClass],
                        ]"
                    >
                        <Icon v-if="iconOn && checked" :name="iconOn" :class="[sizeConfig.iconSize, 'text-black! dark:text-white!']" />
                        <Icon v-if="iconOff && !checked" :name="iconOff" :class="[sizeConfig.iconSize, 'text-black!']" />
                    </span>
                </button>

                <input v-if="name" type="hidden" :name="name" :value="checked ? '1' : '0'" />
            </div>

            <label
                v-if="label || $slots.default"
                :id="`${id}-label`"
                class="block flex-1 cursor-pointer text-start text-sm font-medium text-black select-none dark:text-white"
                @click="toggle"
            >
                <slot>{{ label }}</slot>
            </label>
        </div>

        <p v-if="description" class="mt-1 text-start text-sm text-gray-500">{{ description }}</p>
    </div>
</template>
