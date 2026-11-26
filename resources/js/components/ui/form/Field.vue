<script setup lang="ts">
import { computed } from 'vue';
import { provideField } from './context';

const props = withDefaults(defineProps<{ required?: boolean; disabled?: boolean }>(), { required: false, disabled: false });

provideField({
    required: computed(() => props.required),
    disabled: computed(() => props.disabled),
});
</script>

<template>
    <div
        :class="[
            'w-full min-w-0',
            // Label spacing: default margin, reduced when followed by a description.
            'text-start [&>[data-slot=label]]:mb-2',
            '[&>[data-slot=label]:has(+[data-slot=description])]:mb-1.5',
            // Description spacing: tight after the label, normal after other elements.
            '[&>[data-slot=label]+[data-slot=description]]:mt-0',
            '[&>[data-slot=label]+[data-slot=description]]:mb-2',
            '[&>*:not([data-slot=label])+[data-slot=description]]:mt-2',
            '[&>[data-slot=error]]:mt-1.5',
            // Dim the label when the control is disabled.
            disabled ? '[&>[data-slot=label]]:opacity-50' : '',
            '[&:has([data-slot=control][disabled])>[data-slot=label]]:opacity-50',
        ]"
        data-slot="field"
    >
        <slot />
    </div>
</template>
