<script setup lang="ts">
import { computed, ref, useId } from 'vue';
import Button from '../button/Button.vue';
import Icon from '../icon/Icon.vue';
import Modal from '../modal/Modal.vue';
import ModalHeading from '../modal/ModalHeading.vue';
import Text from '../typography/Text.vue';

/**
 * A confirmation dialog in front of an action, in place of the browser's confirm().
 *
 *   <Confirm :action="() => router.delete(`/projects/${id}`)" tone="danger" title="Delete this project?" confirm-label="Delete">
 *       <Button color="red" variant="outline" icon="trash">Delete</Button>
 *   </Confirm>
 *
 * When `action` returns a promise the dialog shows a spinner until it settles, then closes. To wait for an
 * Inertia visit, wrap it: `() => new Promise((resolve) => router.delete(url, { onFinish: resolve }))`.
 * Anything in the `body` slot (a reason field, say) sits between the description and the buttons.
 */
const props = withDefaults(
    defineProps<{
        action: () => unknown;
        title?: string;
        description?: string | null;
        confirmLabel?: string;
        cancelLabel?: string;
        tone?: 'primary' | 'danger' | 'warning' | 'success';
        icon?: string | null;
    }>(),
    { title: 'Are you sure?', description: null, confirmLabel: 'Confirm', cancelLabel: 'Cancel', tone: 'primary', icon: null },
);

const open = ref(false);
const running = ref(false);
const titleId = `confirm-${useId()}`;

const toneConfig = computed(
    () =>
        ({
            danger: { color: 'red' as const, icon: 'warning-circle', classes: 'bg-red-500/10 text-red-600 dark:text-red-400' },
            warning: { color: 'amber' as const, icon: 'warning', classes: 'bg-amber-500/15 text-amber-600 dark:text-amber-400' },
            success: { color: 'emerald' as const, icon: 'check-circle', classes: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' },
            primary: { color: null, icon: 'question', classes: 'bg-primary-500/10 text-primary-600 dark:text-primary-400' },
        })[props.tone],
);

async function confirm(): Promise<void> {
    running.value = true;

    try {
        await props.action();
    } finally {
        running.value = false;
        open.value = false;
    }
}
</script>

<template>
    <Modal v-model="open">
        <template #trigger><slot /></template>

        <div class="flex flex-col items-center gap-3 text-center" role="alertdialog" :aria-labelledby="titleId">
            <span :class="['grid size-11 place-items-center rounded-full', toneConfig.classes]">
                <Icon :name="icon ?? toneConfig.icon" variant="bold" class="size-5.5 text-current!" />
            </span>
            <ModalHeading :id="titleId" class="text-center! text-balance">{{ title }}</ModalHeading>
            <Text v-if="description" class="text-center! text-sm text-pretty opacity-70">{{ description }}</Text>
        </div>

        <div v-if="$slots.body" class="text-start"><slot name="body" /></div>

        <template #footer>
            <Button class="flex-1" variant="outline" color="neutral" :disabled="running" @click="open = false">{{ cancelLabel }}</Button>
            <Button class="flex-1" :color="toneConfig.color" :loading="running" @click="confirm">{{ confirmLabel }}</Button>
        </template>
    </Modal>
</template>
