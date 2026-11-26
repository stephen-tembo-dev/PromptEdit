<script setup lang="ts">
import { ref, useId } from 'vue';
import { toPlacement, useAnchored } from '@/composables/useAnchored';

/**
 *   <Tooltip content="Copy link"><Button icon="link" variant="ghost" /></Tooltip>
 *   <Tooltip><template #trigger>…</template> Rich <b>content</b></Tooltip>
 */
const props = withDefaults(
    defineProps<{
        content?: string | null;
        placement?: string;
        offset?: number;
        /** `button` also opens on click, for touch screens. */
        variant?: 'default' | 'button';
    }>(),
    { content: null, placement: 'top', offset: 3, variant: 'default' },
);

const show = ref(false);
const trigger = ref<HTMLElement | null>(null);
const panel = ref<HTMLElement | null>(null);
const id = useId();

const { floatingStyles } = useAnchored(trigger, panel, { placement: () => toPlacement(props.placement), offset: () => props.offset + 4, open: show });
</script>

<template>
    <div class="relative flex">
        <span
            ref="trigger"
            :role="variant === 'button' ? 'button' : undefined"
            :tabindex="variant === 'button' ? 0 : undefined"
            :aria-describedby="show ? id : undefined"
            class="cursor-pointer"
            @mouseenter="show = true"
            @mouseleave="show = false"
            @focusin="show = true"
            @focusout="show = false"
            @click="variant === 'button' && (show = !show)"
            @keydown.escape="show = false"
        >
            <slot name="trigger">
                <slot v-if="content" />
            </slot>
        </span>

        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-300" leave-to-class="opacity-0">
                <div
                    v-if="show"
                    :id="id"
                    ref="panel"
                    role="tooltip"
                    :style="floatingStyles"
                    class="pointer-events-none z-50 min-h-8 rounded px-2 py-1 text-sm whitespace-nowrap shadow-lg dark:backdrop-blur-lg [:where(&)]:bg-white [:where(&)]:text-black [:where(&)]:dark:bg-neutral-900 [:where(&)]:dark:text-white"
                >
                    <template v-if="content">{{ content }}</template>
                    <slot v-else />
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
