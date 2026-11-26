<script setup lang="ts">
import { computed, inject, provide, useId } from 'vue';
import AccordionContent from './AccordionContent.vue';
import AccordionTrigger from './AccordionTrigger.vue';
import { accordionItemKey, accordionKey } from './context';

const props = withDefaults(
    defineProps<{
        /** Shorthand: the trigger text. The default slot becomes the content. */
        trigger?: string | null;
        expanded?: boolean;
        disabled?: boolean;
    }>(),
    { trigger: null, expanded: false, disabled: false },
);

const accordion = inject(accordionKey);

if (!accordion) {
    throw new Error('<AccordionItem> must be used inside <Accordion>.');
}

const id = useId();

if (props.expanded) accordion.setOpen(id, true);

const isVisible = computed(() => !props.disabled && accordion.isOpen(id));

provide(accordionItemKey, {
    isVisible,
    disabled: computed(() => props.disabled),
    toggle: () => {
        if (!props.disabled) accordion.setOpen(id, !accordion.isOpen(id));
    },
    contentId: `${id}-content`,
    triggerId: `${id}-trigger`,
});
</script>

<template>
    <div :class="['border-black/10 text-start text-gray-800 not-last:border-b dark:border-white/10 dark:text-white', disabled ? 'opacity-50' : '']">
        <template v-if="trigger || $slots.trigger">
            <AccordionTrigger><slot name="trigger">{{ trigger }}</slot></AccordionTrigger>
            <AccordionContent><slot /></AccordionContent>
        </template>
        <slot v-else />
    </div>
</template>
