<script setup lang="ts">
import { computed, inject } from 'vue';
import { tabsKey } from './context';

const tabs = inject(tabsKey);

const variant = computed(() => tabs?.variant.value ?? 'underline');

const variantClasses = computed(() => {
    switch (variant.value) {
        case 'underline':
            return 'gap-4 overflow-x-auto border-b border-neutral-950/10 dark:border-neutral-300/15';
        case 'outlined':
            return 'gap-2 overflow-x-auto';
        case 'pills':
            return 'my-2';
        default:
            return '';
    }
});

// Arrow keys, Home and End move between tabs.
function onKeydown(event: KeyboardEvent): void {
    const list = event.currentTarget as HTMLElement;
    const items = Array.from(list.querySelectorAll<HTMLElement>('[data-ui-tab]'));
    const index = items.indexOf(document.activeElement as HTMLElement);

    let next: number | null = null;

    switch (event.key) {
        case 'ArrowRight':
            next = (index + 1) % items.length;
            break;
        case 'ArrowLeft':
            next = (index - 1 + items.length) % items.length;
            break;
        case 'Home':
        case 'PageUp':
            next = 0;
            break;
        case 'End':
        case 'PageDown':
            next = items.length - 1;
            break;
    }

    if (next !== null && items[next]) {
        event.preventDefault();
        event.stopPropagation();
        items[next].focus();
    }
}
</script>

<template>
    <div role="tablist" :class="['flex [:where(&)]:items-center [:where(&)]:justify-center', variantClasses]" data-slot="tabs-group" @keydown="onKeydown">
        <div
            v-if="variant === 'non-contained'"
            class="flex w-fit gap-1 rounded-(--noncontained-variant-radius) border-2 border-black/10 bg-white/5 p-(--noncontained-variant-padding) [--noncontained-variant-padding:--spacing(.75)] [--noncontained-variant-radius:var(--radius-box)] dark:border-white/10"
        >
            <slot />
        </div>
        <slot v-else />
    </div>
</template>
