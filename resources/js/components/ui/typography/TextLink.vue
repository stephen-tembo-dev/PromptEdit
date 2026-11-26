<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        href: string;
        openInNewTab?: boolean;
        primary?: boolean;
        variant?: 'ghost' | 'soft' | null;
        /** Use a plain <a> instead of an Inertia visit (other sites, file downloads). */
        external?: boolean;
        method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    }>(),
    { openInNewTab: false, primary: true, variant: null, external: false, method: 'get' },
);

const classes = computed(() => [
    'inline font-medium text-start',
    'underline-offset-[6px] hover:decoration-current',
    props.variant === 'ghost' ? 'no-underline hover:underline' : props.variant === 'soft' ? 'no-underline' : 'underline',
    props.variant === 'soft'
        ? 'text-neutral-500 dark:text-white/70 hover:text-neutral-800 dark:hover:text-white'
        : props.primary
          ? 'text-[var(--color-primary-content)] decoration-[color-mix(in_oklab,var(--color-primary-content),transparent_80%)]'
          : 'text-neutral-800 dark:text-white decoration-neutral-800/20 dark:decoration-white/20',
]);

const plain = computed(() => props.external || props.openInNewTab);
</script>

<template>
    <a v-if="plain" :href="href" :class="classes" :target="openInNewTab ? '_blank' : undefined" :rel="openInNewTab ? 'noopener' : undefined" data-slot="link"><slot /></a>
    <Link v-else :href="href" :method="method" :class="classes" data-slot="link"><slot /></Link>
</template>
