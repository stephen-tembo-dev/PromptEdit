<script setup lang="ts">
import Icon from '../icon/Icon.vue';
import Heading from '../typography/Heading.vue';

defineProps<{ icon?: string | null }>();
</script>

<template>
    <Heading size="xs" class="mb-1 flex items-center !text-(--heading-color)" data-slot="alert-heading">
        <Icon v-if="icon" :name="icon" class="mr-2 inline-block text-[var(--icon-color)]" />
        <slot />
    </Heading>
</template>
