<script setup lang="ts">
withDefaults(defineProps<{ orientation?: 'horizontal' | 'vertical' }>(), { orientation: 'horizontal' });
</script>

<template>
    <nav v-if="orientation === 'vertical'" class="flex flex-col gap-2">
        <slot />
    </nav>

    <!-- Preline's scroll nav keeps the current tab in view on narrow screens. -->
    <div v-else class="relative flex justify-center overflow-hidden md:justify-start" data-hs-scroll-nav='{"autoCentering": true}'>
        <nav class="hs-scroll-nav-body flex snap-x snap-mandatory flex-nowrap overflow-x-auto pb-1.5 [&::-webkit-scrollbar]:h-0">
            <slot />
        </nav>
    </div>
</template>
