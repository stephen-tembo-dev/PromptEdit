<script setup lang="ts">
import { computed } from 'vue';
import { Icon, Text } from '@/components/ui';
import type { Generation } from '@/types/generations';

/**
 * The image frame at the generation's own aspect ratio. Until there is an image
 * it explains why: still running, failed, or expired (Pixelforge's links last 24
 * hours after completion).
 *
 * The history grid passes frame="square" so cards of different sizes line up.
 */
const props = withDefaults(
    defineProps<{
        generation: Pick<Generation, 'size' | 'status' | 'is_waiting_for_slot' | 'image_url' | 'image_expired' | 'prompt'>;
        frame?: 'natural' | 'square';
    }>(),
    { frame: 'natural' },
);

// Pixelforge prints the prompt across the bottom of every image. A one-line
// caption is about 140px tall and two lines about 180px, so we crop 190px off.
const CAPTION_STRIP_PX = 190;

const dimensions = computed(() => {
    const [width, height] = props.generation.size.split('x').map(Number);

    return width && height > CAPTION_STRIP_PX ? { width, visibleHeight: height - CAPTION_STRIP_PX, height } : null;
});

const frameRatio = computed(() => {
    if (props.frame === 'square' || !dimensions.value) {
        return 1;
    }

    return dimensions.value.width / dimensions.value.visibleHeight;
});

const aspect = computed(() => `${frameRatio.value} / 1`);

// Percentages rather than pixels so the crop holds at any card or page width.
const imageStyle = computed(() => {
    if (!dimensions.value) {
        return null;
    }

    const { width, visibleHeight, height } = dimensions.value;
    const frameHeight = 1 / frameRatio.value;
    const scale = Math.max(frameHeight / visibleHeight, 1 / width);

    return {
        width: `${width * scale * 100}%`,
        height: `${(height * scale * 100) / frameHeight}%`,
        left: `${((1 - width * scale) / 2) * 100}%`,
        top: `${((frameHeight - visibleHeight * scale) / 2 / frameHeight) * 100}%`,
    };
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
        <img
            v-if="generation.image_url"
            :src="generation.image_url"
            :alt="generation.prompt"
            :class="imageStyle ? 'absolute max-w-none' : 'size-full object-cover'"
            :style="imageStyle ?? undefined"
            loading="lazy"
        />

        <div v-else-if="placeholder" class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center">
            <Icon :name="placeholder.icon" :class="['size-7 text-neutral-400! dark:text-neutral-500!', placeholder.spin ? 'animate-spin motion-reduce:animate-none' : '']" />
            <Text class="text-sm text-neutral-500! dark:text-neutral-400!">{{ placeholder.text }}</Text>
        </div>
    </div>
</template>
