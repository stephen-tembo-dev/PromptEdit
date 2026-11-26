<script setup lang="ts">
import { computed, onMounted, ref, shallowRef, watch } from 'vue';
import { develop, GRID_WIDTH, loadPixels, type PixelSource } from '@/lib/developImage';

/**
 * A painting part of the way out of the noise. The real image sits underneath
 * at full resolution; a small canvas of grain and blocks covers it while it's
 * "forming" and fades away as it finishes.
 */
const props = withDefaults(
    defineProps<{
        src: string;
        alt: string;
        /** 0 is pure grain, 1 is the finished picture. */
        progress?: number;
        /** Changing this reseeds the grain. */
        step?: number;
        /** Width / height of the frame, e.g. 2 / 3. */
        aspect?: number;
        /** Where the crop sits, as object-position fractions. */
        position?: [number, number];
    }>(),
    { progress: 1, step: 0, aspect: 2 / 3, position: () => [0.5, 0.25] },
);

const canvas = ref<HTMLCanvasElement | null>(null);
const source = shallowRef<PixelSource | null>(null);

const size = computed(() => ({ width: GRID_WIDTH, height: Math.round(GRID_WIDTH / props.aspect) }));

/** Fully covering until the last stretch, then fading to the real picture. */
const coverOpacity = computed(() => (props.progress >= 1 ? 0 : Math.min(1, (1 - props.progress) / 0.15)));

const draw = () => {
    const context = canvas.value?.getContext('2d');

    if (context && coverOpacity.value > 0) {
        develop(context, source.value, size.value, props.progress, props.step);
    }
};

onMounted(() => {
    draw();
    loadPixels(props.src, props.aspect, props.position)
        .then((pixels) => {
            source.value = pixels;
            draw();
        })
        .catch(() => {
            // Without the pixels the grain still plays; the real image shows at the end.
        });
});

watch(() => [props.progress, props.step], draw);
watch(
    () => props.src,
    (src) => {
        source.value = null;
        draw();
        loadPixels(src, props.aspect, props.position).then((pixels) => {
            if (src === props.src) {
                source.value = pixels;
                draw();
            }
        });
    },
);
</script>

<template>
    <div class="relative overflow-hidden" :style="{ aspectRatio: aspect }">
        <img
            :src="src"
            :alt="alt"
            class="absolute inset-0 size-full object-cover"
            :style="{ objectPosition: `${position[0] * 100}% ${position[1] * 100}%` }"
            decoding="async"
        />
        <canvas
            v-show="coverOpacity > 0"
            ref="canvas"
            :width="size.width"
            :height="size.height"
            class="absolute inset-0 size-full [image-rendering:pixelated]"
            :style="{ opacity: coverOpacity }"
            aria-hidden="true"
        />
    </div>
</template>
