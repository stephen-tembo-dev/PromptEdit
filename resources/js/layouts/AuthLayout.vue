<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Heading, Text, ThemeSwitcher, Toast } from '@/components/ui';
import AppLogo from '@/components/AppLogo.vue';

defineProps<{
    title: string;
    description?: string;
}>();

const page = usePage();
</script>

<template>
    <Head :title="title" />

    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full flex-wrap">
            <!-- Brand panel: stacks above the form on a phone. -->
            <section class="login-background-container flex flex-[1_1_26rem] flex-col justify-between gap-8 bg-primary-900 px-8 py-10 text-white sm:gap-12 sm:px-14 sm:py-14">
                <Link href="/" class="w-fit rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60" :aria-label="`${page.props.name} home`">
                    <AppLogo class="size-12 sm:size-14" inverted />
                </Link>

                <div class="max-w-md space-y-3.5">
                    <h1 class="text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ page.props.name }}</h1>
                    <p class="hidden text-lg/relaxed text-primary-100 sm:block">Sign in to pick up where you left off.</p>
                </div>

                <p class="hidden text-sm text-primary-200 lg:block">&copy; {{ new Date().getFullYear() }} {{ page.props.name }}</p>
            </section>

            <!-- The form -->
            <section class="relative flex flex-[1_1_30rem] items-center justify-center bg-gray-50 px-6 py-14 dark:bg-neutral-900">
                <div class="absolute top-4 right-4">
                    <ThemeSwitcher variant="dropdown" />
                </div>

                <div class="w-full max-w-sm space-y-6">
                    <div class="space-y-1.5">
                        <Heading level="h2" size="lg">{{ title }}</Heading>
                        <Text v-if="description" class="text-neutral-500! dark:text-neutral-400!">{{ description }}</Text>
                    </div>

                    <slot />

                    <p v-if="$slots.footer" class="text-center text-sm text-neutral-500 dark:text-neutral-400">
                        <slot name="footer" />
                    </p>
                </div>
            </section>
        </div>
    </div>

    <Toast position="top-right" :max-toasts="5" />
</template>
