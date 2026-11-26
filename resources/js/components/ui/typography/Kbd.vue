<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{ keys?: string | null; separator?: string }>(), { keys: null, separator: '+' });

const keyList = computed(() => (props.keys ? props.keys.split(props.separator).map((key) => key.trim()) : []));

const kbdClasses =
    'pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded border border-neutral-200 bg-neutral-100 px-1.5 font-sans text-[10px] font-medium text-neutral-700 opacity-100 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
</script>

<template>
    <span class="inline-flex items-center gap-1 font-mono text-sm">
        <template v-if="keyList.length">
            <template v-for="(key, index) in keyList" :key="index">
                <kbd :class="kbdClasses">{{ key }}</kbd>
                <span v-if="index < keyList.length - 1" class="text-xs text-neutral-400">{{ separator }}</span>
            </template>
        </template>
        <kbd v-else :class="kbdClasses"><slot /></kbd>
    </span>
</template>
