<script setup lang="ts">
import { inject } from 'vue';
import Icon from '../icon/Icon.vue';
import { accordionItemKey, accordionKey } from './context';

const accordion = inject(accordionKey);
const item = inject(accordionItemKey);

if (!item) {
    throw new Error('<AccordionTrigger> must be used inside <AccordionItem>.');
}
</script>

<template>
    <h3>
        <button
            :id="item.triggerId"
            type="button"
            :disabled="item.disabled.value"
            :aria-expanded="item.isVisible.value ? 'true' : 'false'"
            :aria-controls="item.contentId"
            :class="[
                'flex w-full items-center justify-start gap-2 px-6 py-4 text-xl font-bold dark:text-white',
                item.disabled.value ? '' : 'cursor-pointer',
                accordion?.reverse.value ? 'flex-row-reverse' : '',
            ]"
            @click="item.toggle()"
        >
            <span class="flex-1 text-start font-normal"><slot /></span>
            <Icon :name="item.isVisible.value ? 'hi:chevron-up' : 'hi:chevron-down'" class="size-5" />
        </button>
    </h3>
</template>
