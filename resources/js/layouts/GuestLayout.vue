<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Toast } from '@/components/ui';

defineProps<{ title?: string }>();
</script>

<template>
    <Head v-if="title" :title="title" />

    <main class="h-full min-h-screen w-full bg-gray-50 dark:bg-neutral-900">
        <div class="relative m-auto h-full max-w-7xl">
            <slot />
        </div>
    </main>

    <Toast position="top-right" :max-toasts="5" />
</template>
