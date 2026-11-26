<script setup lang="ts">
import { inject } from 'vue';
import { accordionItemKey } from './context';

const item = inject(accordionItemKey);

if (!item) {
    throw new Error('<AccordionContent> must be used inside <AccordionItem>.');
}
</script>

<template>
    <!-- Animating grid rows from 0fr to 1fr collapses the panel to its content's height. -->
    <div
        :id="item.contentId"
        role="region"
        :aria-labelledby="item.triggerId"
        :inert="!item.isVisible.value"
        :class="['grid transition-[grid-template-rows] duration-300 ease-in-out', item.isVisible.value ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]']"
    >
        <div class="overflow-hidden">
            <div class="px-6 pt-2 pb-4">
                <slot />
            </div>
        </div>
    </div>
</template>
