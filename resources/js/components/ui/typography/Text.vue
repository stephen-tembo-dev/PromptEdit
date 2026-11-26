<template>
    <!-- Alignment is inherited from the container, so text centres inside empty states without overrides. -->
    <div class="text-neutral-950 [:where(&)]:text-sm dark:text-neutral-50" data-slot="text"><slot /></div>
</template>
