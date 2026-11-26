<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import GenerationStatusBadge from '@/components/generations/GenerationStatusBadge.vue';
import DevelopingImage from '@/components/welcome/DevelopingImage.vue';
import { Badge, Button, Text } from '@/components/ui';
import { paintings } from '@/lib/paintings';
import type { GenerationStatus } from '@/types/generations';

/**
 * The landing page's moving picture: a prompt is typed, the job goes from
 * queued to processing to succeeded, and the painting comes out of the noise
 * while the cost fills in, the same way a card on the History page does.
 * With reduced motion it shows the finished state and nothing moves.
 */
const TYPING = 1600;
const QUEUED = 700;
const PROCESSING = 3400;
const SHOWING = 3800;
const STEPS = 30;
const DONE = TYPING + QUEUED + PROCESSING;

const index = ref(0);
const elapsed = ref(0);
const reducedMotion = ref(false);

const painting = computed(() => paintings[index.value]);
const typed = computed(() => painting.value.prompt.slice(0, Math.ceil((Math.min(elapsed.value, TYPING) / TYPING) * painting.value.prompt.length)));
const progress = computed(() => Math.min(1, Math.max(0, (elapsed.value - TYPING - QUEUED) / PROCESSING)));
const status = computed<GenerationStatus>(() => (elapsed.value < TYPING + QUEUED ? 'queued' : elapsed.value < DONE ? 'processing' : 'succeeded'));
const step = computed(() => (status.value === 'processing' ? Math.floor(progress.value * STEPS) : Math.floor(elapsed.value / 140)));

let frame = 0;
let last = 0;

const tick = (now: number) => {
    // Capped so coming back to a background tab doesn't skip a whole picture.
    elapsed.value += Math.min(now - (last || now), 100);
    last = now;

    if (elapsed.value > DONE + SHOWING) {
        index.value = (index.value + 1) % paintings.length;
        elapsed.value = 0;
    }

    frame = requestAnimationFrame(tick);
};

const show = (next: number) => {
    index.value = next;
    elapsed.value = reducedMotion.value ? DONE : 0;
};

onMounted(() => {
    reducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reducedMotion.value) {
        elapsed.value = DONE;
    } else {
        frame = requestAnimationFrame(tick);
    }
});

onBeforeUnmount(() => cancelAnimationFrame(frame));
</script>

<template>
    <figure class="space-y-3">
        <div class="space-y-3 rounded-[15px] border border-black/10 bg-white p-3 dark:border-white/10 dark:bg-neutral-800">
            <div class="flex items-center gap-2 rounded-box border border-black/10 bg-gray-50 px-3 py-2 text-sm text-neutral-800 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200">
                <span class="min-w-0 truncate" aria-hidden="true">{{ typed }}</span>
                <span v-if="status === 'queued' && !reducedMotion" class="-ms-1.5 h-4 w-px shrink-0 animate-pulse bg-neutral-800 dark:bg-neutral-200" aria-hidden="true" />
                <span class="sr-only">Example prompt: {{ painting.prompt }}</span>
            </div>

            <DevelopingImage
                :key="painting.key"
                :src="painting.src"
                :alt="`${painting.title} by ${painting.artist}`"
                :progress="progress"
                :step="step"
                :aspect="4 / 5"
                class="rounded-box border border-black/10 dark:border-white/10"
            />

            <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2">
                    <GenerationStatusBadge :generation="{ status, is_waiting_for_slot: false }" />
                    <Text v-if="status === 'processing'" class="text-xs text-neutral-500! tabular-nums dark:text-neutral-400!">Step {{ step }} of {{ STEPS }}</Text>
                </span>
                <Text class="text-xs text-neutral-500! tabular-nums dark:text-neutral-400!">Portrait · Medium</Text>
            </div>

            <dl class="flex items-start justify-between gap-2 border-t border-black/10 pt-3 text-sm dark:border-white/10" aria-live="polite">
                <div>
                    <dt class="text-xs text-neutral-500 dark:text-neutral-400">Estimated</dt>
                    <dd class="text-neutral-800 tabular-nums dark:text-neutral-200">{{ elapsed >= TYPING ? painting.estimated : '…' }}</dd>
                </div>
                <div class="text-end">
                    <dt class="text-xs text-neutral-500 dark:text-neutral-400">Actual</dt>
                    <dd class="font-medium text-neutral-950 tabular-nums dark:text-neutral-50">
                        <template v-if="status === 'succeeded'">{{ painting.actual }}</template>
                        <span v-else class="font-normal text-neutral-500 dark:text-neutral-400">Pending</span>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="flex items-end justify-between gap-3">
            <div class="flex items-end gap-2" role="group" aria-label="Examples">
                <Button
                    v-for="(example, position) in paintings"
                    :key="example.key"
                    variant="none"
                    :aria-pressed="position === index"
                    :aria-label="`Show ${example.title}`"
                    :class="[
                        'h-auto! rounded-md! border p-1! transition-colors',
                        position === index ? 'border-primary-600 dark:border-primary-400' : 'border-black/10 hover:border-black/25 dark:border-white/10 dark:hover:border-white/30',
                    ]"
                    @click="show(position)"
                >
                    <img :src="example.src" alt="" class="block h-12 w-8 rounded-sm object-cover object-[50%_25%]" loading="lazy" decoding="async" />
                </Button>
            </div>
            <Badge size="sm" color="neutral" variant="outline">Example prices</Badge>
        </div>

        <figcaption class="text-xs text-neutral-500 dark:text-neutral-400">
            Shown with famous public-domain paintings: {{ painting.title }}, {{ painting.artist }}. Your images are made from your own prompts.
        </figcaption>
    </figure>
</template>
