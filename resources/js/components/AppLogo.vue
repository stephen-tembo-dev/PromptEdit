<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/** The app mark: its first letter in a rounded square. Replace it with the real logo image once there is one. */
const props = withDefaults(defineProps<{ inverted?: boolean }>(), { inverted: false });

const page = usePage();
const initial = computed(() => (page.props.name ?? 'A').trim().charAt(0).toUpperCase());
</script>

<template>
    <span
        :class="[
            'inline-grid shrink-0 place-items-center rounded-lg font-bold [:where(&)]:size-8',
            props.inverted ? 'bg-white/10 text-white ring-1 ring-white/20' : 'bg-primary-700 text-white dark:bg-primary-400 dark:text-primary-950',
        ]"
        aria-hidden="true"
    >
        {{ initial }}
    </span>
</template>
