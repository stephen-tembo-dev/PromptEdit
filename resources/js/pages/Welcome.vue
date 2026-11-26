<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import GenerationStatusBadge from '@/components/generations/GenerationStatusBadge.vue';
import DevelopingImage from '@/components/welcome/DevelopingImage.vue';
import ForgeDemo from '@/components/welcome/ForgeDemo.vue';
import { Alert, Badge, Button, Heading, Icon, Text, ThemeSwitcher } from '@/components/ui';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { paintings } from '@/lib/paintings';
import type { GenerationStatus } from '@/types/generations';

defineProps<{
    appName: string;
    /** Signed in, but no role or permission opens any page yet. Everyone else signed in is redirected before this renders. */
    hasNoAccess: boolean;
}>();

const filmPainting = paintings.find((painting) => painting.key === 'girl-with-a-pearl-earring') ?? paintings[0];

/** The film strip: one picture at three moments. */
const stages: { status: GenerationStatus; progress: number; step: number; text: string }[] = [
    { status: 'queued', progress: 0, step: 3, text: 'Waiting for a free slot. The most it can cost is set aside from your credits.' },
    { status: 'processing', progress: 0.45, step: 13, text: 'Being made. The status changes on its own, so there is nothing to refresh.' },
    { status: 'succeeded', progress: 1, step: 30, text: 'Done. History shows what it was estimated to cost next to what it actually cost.' },
];

const promises = [
    { icon: 'calculator', text: 'A live estimate as you type, along with the most it can cost.' },
    { icon: 'lock-simple', text: 'That maximum is held while the image is made, so you never spend credits you do not have.' },
    { icon: 'receipt', text: 'You are charged what it actually cost. If it fails, the hold is released and you pay nothing.' },
];

const year = new Date().getFullYear();
</script>

<template>
    <GuestLayout title="Welcome">
        <div class="px-4 sm:px-6 md:px-8">
            <header class="flex items-center justify-between gap-4 py-4">
                <span class="flex items-center gap-2 font-semibold text-neutral-800 dark:text-neutral-200">
                    <AppLogo />
                    {{ appName }}
                </span>

                <nav class="flex items-center gap-2" aria-label="Account">
                    <ThemeSwitcher variant="dropdown" />
                    <Button v-if="hasNoAccess" href="/logout" method="post" as="button" variant="outline" color="neutral" icon="sign-out">Log out</Button>
                    <template v-else>
                        <Button href="/login" variant="ghost" color="neutral">Log in</Button>
                        <Button href="/register">Create an account</Button>
                    </template>
                </nav>
            </header>

            <Alert v-if="hasNoAccess" variant="info" class="mt-2">Your account doesn't have access to any pages yet. Ask an administrator to give you a role.</Alert>

            <!-- Hero: the offer on the left, the picture being made on the right. -->
            <section class="grid items-center gap-12 py-12 lg:grid-cols-2 lg:gap-16 lg:py-20">
                <div class="space-y-8">
                    <div class="space-y-5">
                        <Heading level="h1" size="" class="text-5xl leading-[1.05] font-bold tracking-tight text-balance sm:text-6xl">
                            Type a sentence. Watch it come out of the noise.
                        </Heading>
                        <Text class="max-w-xl text-lg text-pretty text-neutral-600! dark:text-neutral-300!">
                            {{ appName }} turns a short description into an image. You see what it will cost before you send it, and you pay what it actually cost, or nothing if
                            it fails.
                        </Text>
                    </div>

                    <div v-if="!hasNoAccess" class="flex flex-wrap gap-3">
                        <Button href="/register" size="md" icon-after="arrow-right">Create an account</Button>
                        <Button href="/login" size="md" variant="outline" color="neutral">Log in</Button>
                    </div>
                </div>

                <ForgeDemo class="w-full max-w-lg justify-self-center lg:justify-self-end" />
            </section>

            <!-- One picture at three moments, on a strip of film. -->
            <section class="space-y-8 py-12 lg:py-20" aria-labelledby="stages-heading">
                <div class="max-w-2xl space-y-3">
                    <Heading id="stages-heading" level="h2" size="" class="text-3xl font-bold tracking-tight text-balance">From prompt to picture, in plain sight</Heading>
                    <Text class="text-pretty text-neutral-600! dark:text-neutral-400!">
                        Each image waits for a free slot, then gets made, and your History page follows along as it moves through its three states.
                    </Text>
                </div>

                <div>
                    <div class="rounded-box bg-neutral-950 px-3 py-2 sm:px-4">
                        <div class="flex justify-between gap-2 px-1" aria-hidden="true">
                            <span v-for="hole in 24" :key="`top-${hole}`" class="h-2 w-3 shrink-0 rounded-[2px] bg-neutral-800 max-sm:[&:nth-child(n+13)]:hidden" />
                        </div>
                        <div class="grid gap-3 py-2 sm:grid-cols-3">
                            <DevelopingImage
                                v-for="stage in stages"
                                :key="stage.status"
                                :src="filmPainting.src"
                                :alt="`${filmPainting.title} while ${stage.status}`"
                                :progress="stage.progress"
                                :step="stage.step"
                                :aspect="1"
                                :position="[0.5, 0.35]"
                                class="rounded-sm"
                            />
                        </div>
                        <div class="flex justify-between gap-2 px-1" aria-hidden="true">
                            <span v-for="hole in 24" :key="`bottom-${hole}`" class="h-2 w-3 shrink-0 rounded-[2px] bg-neutral-800 max-sm:[&:nth-child(n+13)]:hidden" />
                        </div>
                    </div>

                    <ol class="mt-4 grid gap-6 sm:grid-cols-3">
                        <li v-for="stage in stages" :key="stage.status" class="space-y-2 px-1">
                            <GenerationStatusBadge :generation="{ status: stage.status, is_waiting_for_slot: false }" />
                            <Text class="text-sm text-pretty text-neutral-600! dark:text-neutral-400!">{{ stage.text }}</Text>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- The money: what is promised, and a receipt that shows it. -->
            <section class="grid items-center gap-12 py-12 lg:grid-cols-2 lg:gap-16 lg:py-20" aria-labelledby="price-heading">
                <div class="space-y-6">
                    <div class="space-y-3">
                        <Heading id="price-heading" level="h2" size="" class="text-3xl font-bold tracking-tight text-balance">Know the price before you press Generate</Heading>
                        <Text class="text-pretty text-neutral-600! dark:text-neutral-400!">Credits are US dollars, and every image is paid from them.</Text>
                    </div>

                    <ul class="space-y-4">
                        <li v-for="promise in promises" :key="promise.icon" class="flex items-start gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary-500/10">
                                <Icon :name="promise.icon" class="size-5 text-primary-700! dark:text-primary-400!" />
                            </span>
                            <Text class="pt-1.5 text-pretty text-neutral-700! dark:text-neutral-300!">{{ promise.text }}</Text>
                        </li>
                    </ul>
                </div>

                <div class="justify-self-center lg:justify-self-end">
                    <div class="w-full max-w-sm -rotate-1 space-y-4 rounded-[15px] border border-black/10 bg-white p-5 dark:border-white/10 dark:bg-neutral-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <Text class="text-sm font-medium">A fox in the snow, watercolour</Text>
                                <Text class="text-xs text-neutral-500! tabular-nums dark:text-neutral-400!">1536 × 1024 · Medium</Text>
                            </div>
                            <Badge size="sm" color="neutral" variant="outline">Example</Badge>
                        </div>

                        <dl class="space-y-2 border-y border-dashed border-black/15 py-4 text-sm dark:border-white/15">
                            <div class="flex justify-between gap-4">
                                <dt class="text-neutral-600 dark:text-neutral-400">Estimated before sending</dt>
                                <dd class="tabular-nums">$0.0998</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-neutral-600 dark:text-neutral-400">Charged when it finished</dt>
                                <dd class="font-semibold tabular-nums">$0.0961</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-neutral-600 dark:text-neutral-400">Rest of the hold</dt>
                                <dd>Released</dd>
                            </div>
                        </dl>

                        <div class="flex justify-between gap-4 text-sm">
                            <span class="text-neutral-600 dark:text-neutral-400">Had it failed</span>
                            <span class="tabular-nums">$0.0000</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Close: the paintings hung side by side like a gallery wall, then the way in. -->
            <section class="space-y-12 border-t border-black/10 py-16 text-center lg:py-24 dark:border-white/10" aria-labelledby="closing-heading">
                <div class="grid grid-cols-2 items-end gap-x-4 gap-y-8 sm:flex sm:justify-center sm:gap-8">
                    <figure v-for="painting in paintings" :key="painting.key" class="mx-auto w-full max-w-44 space-y-3 sm:w-1/4">
                        <div class="rounded-sm border border-black/10 bg-white p-2 sm:p-2.5 dark:border-white/10 dark:bg-neutral-800">
                            <img
                                :src="painting.src"
                                :alt="`${painting.title} by ${painting.artist}`"
                                class="block w-full"
                                :style="{ aspectRatio: painting.aspect }"
                                loading="lazy"
                                decoding="async"
                            />
                        </div>
                        <figcaption class="space-y-0.5">
                            <Text class="text-sm font-medium">{{ painting.title }}</Text>
                            <Text class="text-xs text-neutral-500! dark:text-neutral-400!">{{ painting.artist }}</Text>
                        </figcaption>
                    </figure>
                </div>

                <div class="space-y-6">
                    <Heading id="closing-heading" level="h2" size="" class="text-3xl font-bold tracking-tight text-balance sm:text-4xl">Your next picture is one sentence away.</Heading>
                    <div v-if="!hasNoAccess" class="flex flex-wrap justify-center gap-3">
                        <Button href="/register" size="md" icon-after="arrow-right">Create an account</Button>
                        <Button href="/login" size="md" variant="outline" color="neutral">Log in</Button>
                    </div>
                </div>
            </section>

            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-black/10 py-6 text-xs text-neutral-500 dark:border-white/10 dark:text-neutral-400">
                <span>© {{ year }} {{ appName }}</span>
                <span>Paintings shown are in the public domain, via Wikimedia Commons. Download links for your images last 24 hours.</span>
            </footer>
        </div>
    </GuestLayout>
</template>
