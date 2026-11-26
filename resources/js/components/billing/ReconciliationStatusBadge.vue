<script setup lang="ts">
import { computed } from 'vue';
import { Badge, type UiColor } from '@/components/ui';
import type { ReconciliationStatus } from '@/types/billing';

/**
 * How a day compares with Pixelforge's usage. Each state has an icon and a word,
 * so the meaning never rests on colour alone; emerald is reserved for "matched".
 */
const props = defineProps<{ status: ReconciliationStatus }>();

const display = computed<{ label: string; color: UiColor; icon: string | false }>(() => {
    switch (props.status) {
        case 'matched':
            return { label: 'Matched', color: 'emerald', icon: 'check' };
        case 'pending':
            return { label: 'Still settling', color: 'amber', icon: 'hourglass-medium' };
        case 'mismatched':
            return { label: 'Mismatch', color: 'red', icon: 'warning' };
        default:
            return { label: 'Not checked', color: 'neutral', icon: 'question' };
    }
});
</script>

<template>
    <Badge :color="display.color" :icon="display.icon" size="sm">{{ display.label }}</Badge>
</template>
