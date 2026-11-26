<script setup lang="ts">
import type { ECharts } from 'echarts/core';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { buildOption, type ChartMode, type ChartSpec } from './options';

/**
 * An interactive chart from a chart description (see options.js for the format).
 *
 *   <Chart :spec="{ kind: 'bar', x_label: 'Month', categories: ['Jan', 'Feb'], series: [{ name: 'Submitted', values: [4, 7] }] }" :height="260" label="Applications by month" />
 *
 * ECharts loads on demand, and follows the light/dark theme.
 */
const props = withDefaults(defineProps<{ spec: ChartSpec; height?: number; label?: string | null }>(), { height: 280, label: null });

const canvas = ref<HTMLElement | null>(null);
const ready = ref(false);

// The instance stays outside Vue's reactivity, whose proxies would break it.
let chart: ECharts | null = null;
let resizeObserver: ResizeObserver | null = null;
let themeObserver: MutationObserver | null = null;

const currentMode = (): ChartMode => (document.documentElement.classList.contains('dark') ? 'dark' : 'light');

function draw(): void {
    const option = buildOption(props.spec, currentMode());
    if (option) chart?.setOption(option, { notMerge: true });
}

onMounted(async () => {
    if (!props.spec || !buildOption(props.spec, 'light') || !canvas.value) return;

    const { default: echarts } = await import('./echarts');
    if (!canvas.value) return;

    chart = echarts.init(canvas.value, null, { renderer: 'svg' });
    draw();
    ready.value = true;
    requestAnimationFrame(() => chart?.resize());

    resizeObserver = new ResizeObserver(() => chart?.resize());
    resizeObserver.observe(canvas.value);

    // The theme switch toggles a class on <html>; redraw with that mode's palette.
    themeObserver = new MutationObserver(draw);
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

watch(() => props.spec, draw, { deep: true });

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    themeObserver?.disconnect();
    chart?.dispose();
    chart = null;
});

defineExpose({
    resetZoom: () => {
        chart?.dispatchAction({ type: 'dataZoom', dataZoomIndex: 0, start: 0, end: 100 });
        chart?.dispatchAction({ type: 'dataZoom', dataZoomIndex: 1, start: 0, end: 100 });
    },
});
</script>

<template>
    <div class="relative" data-slot="chart">
        <div v-if="!ready" class="absolute inset-0 animate-pulse rounded-lg bg-black/[0.03] dark:bg-white/[0.04]" aria-hidden="true" />
        <div ref="canvas" :style="{ height: `${height}px` }" role="img" :aria-label="label ?? undefined" />
    </div>
</template>
