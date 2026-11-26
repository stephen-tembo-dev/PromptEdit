<script setup lang="ts">
import { computed } from 'vue';
import { Icon, Text } from '@/components/ui';
import type { Generation } from '@/types/generations';

/**
 * The image frame at the generation's own aspect ratio. Until there is an image
 * it explains why: still running, failed, or expired (Pixelforge's links last 24
 * hours after completion).
 *
 * The history grid passes frame="square" so cards of different sizes line up;
 * the image is cropped to fit there and shown whole on the detail page.
 */
const props = withDefaults(
    defineProps<{
        generation: Pick<Generation, 'size' | 'status' | 'is_waiting_for_slot' | 'image_url' | 'image_expired' | 'prompt'>;
        frame?: 'natural' | 'square';
    }>(),
    { frame: 'natural' },
);

const aspect = computed(() => {
    if (props.frame === 'square') {
        return '1 / 1';
    }

    const [width, height] = props.generation.size.split('x').map(Number);

    return width && height ? `${width} / ${height}` : '1 / 1';
});

const placeholder = computed<{ icon: string; text: string; spin?: boolean } | null>(() => {
    const generation = props.generation;

    if (generation.image_url) {
        return null;
    }

    if (generation.image_expired) {
        return { icon: 'image-broken', text: 'Image link expired' };
    }

    if (generation.status === 'failed') {
        return { icon: 'x-circle', text: 'No image: this generation failed' };
    }

    if (generation.is_waiting_for_slot) {
        return { icon: 'hourglass', text: 'Waiting for a free slot' };
    }

    return generation.status === 'processing'
        ? { icon: 'circle-notch', text: 'Generating…', spin: true }
        : { icon: 'hourglass-medium', text: 'Queued at Pixelforge' };
});
</script>

<template>
    <div class="relative w-full overflow-hidden rounded-box border border-black/10 bg-neutral-100 dark:border-white/10 dark:bg-neutral-900" :style="{ aspectRatio: aspect }">
        <img v-if="generation.image_url" :src="generation.image_url" :alt="generation.prompt" class="size-full object-cover" loading="lazy" />

        <div v-else-if="placeholder" class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center">
            <Icon :name="placeholder.icon" :class="['size-7 text-neutral-400! dark:text-neutral-500!', placeholder.spin ? 'animate-spin motion-reduce:animate-none' : '']" />
            <Text class="text-sm text-neutral-500! dark:text-neutral-400!">{{ placeholder.text }}</Text>
        </div>
    </div>
</template>
