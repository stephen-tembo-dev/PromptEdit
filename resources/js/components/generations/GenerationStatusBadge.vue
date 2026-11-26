<script setup lang="ts">
import { computed } from 'vue';
import { Badge, type UiColor } from '@/components/ui';
import type { Generation } from '@/types/generations';

/**
 * Live status of a generation. Every state carries an icon and a word, so the
 * meaning never rests on colour alone; emerald is reserved for "done".
 */
const props = defineProps<{ generation: Pick<Generation, 'status' | 'is_waiting_for_slot'> }>();

const display = computed<{ label: string; color: UiColor; icon: string | false }>(() => {
    if (props.generation.is_waiting_for_slot) {
        return { label: 'Waiting for a slot', color: 'neutral', icon: 'hourglass' };
    }

    switch (props.generation.status) {
        case 'queued':
            return { label: 'Queued', color: 'amber', icon: 'hourglass-medium' };
        case 'processing':
            return { label: 'Processing', color: 'sky', icon: 'spinner-gap' };
        case 'succeeded':
            return { label: 'Succeeded', color: 'emerald', icon: 'check' };
        default:
            return { label: 'Failed', color: 'red', icon: 'x-circle' };
    }
});
</script>

<template>
    <Badge :color="display.color" :icon="display.icon" size="sm">{{ display.label }}</Badge>
</template>
