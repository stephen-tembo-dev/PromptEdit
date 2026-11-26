<script setup lang="ts">
import { computed, shallowRef, useAttrs, watch, type Component } from 'vue';
import { getLoadedIcon, resolveIcon } from './icons';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        /** Phosphor name (`house`), Heroicons name (`hi:x-mark`), or an icon component. */
        name?: string | Component | null;
        variant?: string | null;
        asButton?: boolean;
    }>(),
    { name: null, variant: null, asButton: false },
);

const attrs = useAttrs();

const resolved = computed(() => (typeof props.name === 'string' && props.name !== '' ? resolveIcon(props.name, props.variant) : null));

const component = shallowRef<Component | null>(null);

watch(
    resolved,
    (icon) => {
        if (!icon) {
            component.value = typeof props.name === 'object' ? props.name : null;
            return;
        }

        const ready = getLoadedIcon(icon.key);
        component.value = ready ?? null;

        if (!ready) {
            icon.load().then((loadedComponent) => {
                if (resolved.value?.key === icon.key) component.value = loadedComponent;
            });
        }
    },
    { immediate: true },
);

const userClass = computed(() => String(attrs.class ?? ''));

// Phosphor icons carry no size of their own, so size-5 is the fallback unless the caller sized it.
const classes = computed(() => [
    '[:where(&)]:text-neutral-700 [:where(&)]:dark:text-neutral-300',
    resolved.value?.isPhosphor && !/(^|\s|:)(size-|w-|h-)/.test(userClass.value) ? 'size-5' : '',
    attrs.class,
]);

const iconProps = computed(() => (resolved.value?.isPhosphor ? { weight: resolved.value.weight, size: '1em' } : {}));

const passthrough = computed(() => {
    const { class: _class, ...rest } = attrs;
    return rest;
});
</script>

<template>
    <button v-if="asButton" type="button" class="cursor-pointer">
        <component :is="component" v-if="component" v-bind="{ ...iconProps, ...passthrough }" :class="classes" data-slot="icon" />
        <span v-else :class="classes" class="inline-block" data-slot="icon" aria-hidden="true" />
    </button>
    <template v-else>
        <component :is="component" v-if="component" v-bind="{ ...iconProps, ...passthrough }" :class="classes" data-slot="icon" />
        <span v-else :class="classes" class="inline-block" data-slot="icon" aria-hidden="true" />
    </template>
</template>
