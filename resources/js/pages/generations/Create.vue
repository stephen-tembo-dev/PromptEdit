<script setup lang="ts">
import { useForm, useHttp, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import QuoteSummary from '@/components/generations/QuoteSummary.vue';
import ReferenceUrlsField from '@/components/generations/ReferenceUrlsField.vue';
import { Alert, Button, Card, Description, Field, FieldError, Label, PageHeader, Segmented, Textarea } from '@/components/ui';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Option, Quote } from '@/types/generations';

const props = defineProps<{
    sizes: Option[];
    qualities: Option[];
    maxReferenceImages: number;
    maxPromptCharacters: number;
}>();

const page = usePage();

const form = useForm({
    prompt: '',
    size: props.sizes[0]?.value ?? '1024x1024',
    quality: props.qualities[1]?.value ?? 'medium',
    reference_images: [] as string[],
});

/*
 * The live estimate. Pricing stays on the backend: the page sends only what changes
 * the price and shows the answer, debounced so typing does not flood the server.
 */
const quoteRequest = useHttp<{ prompt: string; size: string; quality: string; reference_image_count: number }, Quote>({
    prompt: '',
    size: '',
    quality: '',
    reference_image_count: 0,
});
const quote = ref<Quote | null>(null);
const quoteUnavailable = ref(false);
const referenceImageCount = computed(() => form.reference_images.filter((url) => url.trim() !== '').length);

let debounce: ReturnType<typeof setTimeout> | undefined;

const refreshQuote = async () => {
    // Nothing to price until there's a prompt.
    if (form.prompt.trim() === '') {
        quote.value = null;
        quoteUnavailable.value = false;
        return;
    }

    quoteRequest.prompt = form.prompt;
    quoteRequest.size = form.size;
    quoteRequest.quality = form.quality;
    quoteRequest.reference_image_count = referenceImageCount.value;

    try {
        quote.value = (await quoteRequest.post('/generations/quote')) ?? quote.value;
        quoteUnavailable.value = false;
    } catch {
        quoteUnavailable.value = true;
    }
};

watch(
    () => [form.prompt.length, form.size, form.quality, referenceImageCount.value],
    () => {
        clearTimeout(debounce);
        debounce = setTimeout(refreshQuote, 300);
    },
    { immediate: true },
);

onBeforeUnmount(() => clearTimeout(debounce));

const promptLength = computed(() => [...form.prompt].length);

/** Set by the server when the balance can't cover the hold. */
const creditsError = computed(() => (form.errors as Record<string, string | undefined>).credits);

/** Set by the server when Pixelforge's prices couldn't be loaded, so nothing was charged. */
const pricingError = computed(() => (form.errors as Record<string, string | undefined>).pricing);

const submit = () => form.post('/generations', { preserveScroll: true });
</script>

<template>
    <AppLayout title="Generate">
        <PageHeader title="Generate" description="Describe an image and see what it will cost before you send it.">
            <template v-if="page.props.auth.can.viewHistory" #actions>
                <Button href="/generations" variant="outline" color="neutral" icon="images">History</Button>
            </template>
        </PageHeader>

        <form class="grid items-start gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <Card size="full" class="space-y-6 lg:col-span-2">
                <Field required>
                    <Label for="prompt">Prompt</Label>
                    <Textarea id="prompt" v-model="form.prompt" name="prompt" :rows="6" placeholder="A lighthouse on a cliff at dawn, oil painting" required autofocus />
                    <div class="flex items-start justify-between gap-4">
                        <FieldError name="prompt" />
                        <Description class="ms-auto shrink-0 tabular-nums" :class="promptLength > maxPromptCharacters ? 'text-red-600! dark:text-red-400!' : ''">
                            {{ promptLength.toLocaleString() }} / {{ maxPromptCharacters.toLocaleString() }}
                        </Description>
                    </div>
                </Field>

                <div class="grid gap-6 sm:grid-cols-2">
                    <Field>
                        <Label>Size</Label>
                        <Segmented v-model="form.size" :options="sizes" label="Size" />
                        <Description class="tabular-nums">{{ form.size.replace('x', ' × ') }} px</Description>
                        <FieldError name="size" />
                    </Field>

                    <Field>
                        <Label>Quality</Label>
                        <Segmented v-model="form.quality" :options="qualities" label="Quality" />
                        <Description>Higher quality costs more and takes longer.</Description>
                        <FieldError name="quality" />
                    </Field>
                </div>

                <ReferenceUrlsField v-model="form.reference_images" :max="maxReferenceImages" />
            </Card>

            <div class="space-y-4 lg:sticky lg:top-36">
                <Card size="full">
                    <QuoteSummary :quote="quote" :loading="quoteRequest.processing" :unavailable="quoteUnavailable" />
                </Card>

                <Alert v-if="creditsError" variant="error">
                    {{ creditsError }}
                    <template v-if="page.props.auth.can.manageCredits" #controls>
                        <Button href="/credits" size="xs" variant="outline" color="red">Buy credits</Button>
                    </template>
                </Alert>

                <Alert v-if="pricingError" variant="error">
                    {{ pricingError }}
                </Alert>

                <Button type="submit" size="md" class="w-full" icon="magic-wand" :loading="form.processing" :disabled="quote !== null && !quote.can_afford">
                    Generate image
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
