<script setup lang="ts">
import { Button, Description, Field, FieldError, Input, Label } from '@/components/ui';

/**
 * Up to `max` optional reference image links. Pixelforge never downloads them,
 * but each one is billed, so the estimate updates as rows are added or removed.
 */
const props = defineProps<{ max: number }>();

const urls = defineModel<string[]>({ required: true });

/** Bound from script: a literal "//" in a template attribute breaks vue-tsc. */
const examplePlaceholder = 'https://example.com/style.png';

const add = () => {
    if (urls.value.length < props.max) {
        urls.value = [...urls.value, ''];
    }
};

const remove = (index: number) => {
    urls.value = urls.value.filter((_, position) => position !== index);
};

const update = (index: number, value: string) => {
    urls.value = urls.value.map((url, position) => (position === index ? value : url));
};
</script>

<template>
    <Field>
        <Label>Reference images</Label>
        <Description>Optional. Up to {{ max }} http(s) links; each one adds to the cost.</Description>

        <div class="mt-2 space-y-2">
            <div v-for="(url, index) in urls" :key="index">
                <div class="flex items-center gap-2">
                    <Input
                        :model-value="url"
                        :name="`reference_images.${index}`"
                        type="url"
                        inputmode="url"
                        left-icon="link"
                        :placeholder="examplePlaceholder"
                        :aria-label="`Reference image ${index + 1}`"
                        class="flex-1"
                        @update:model-value="update(index, String($event ?? ''))"
                    />
                    <Button variant="ghost" color="neutral" icon="trash" :aria-label="`Remove reference image ${index + 1}`" @click="remove(index)" />
                </div>
                <FieldError :name="`reference_images.${index}`" />
            </div>

            <Button v-if="urls.length < max" variant="outline" color="neutral" size="sm" icon="plus" @click="add">Add reference image</Button>
        </div>

        <FieldError name="reference_images" />
    </Field>
</template>
