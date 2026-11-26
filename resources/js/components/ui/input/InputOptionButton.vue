<template>
    <button
        type="button"
        data-slot="input-option"
        class="mx-0.5 flex cursor-pointer [&_[data-slot=icon]]:!text-neutral-500 [&_[data-slot=icon]]:transition dark:[&_[data-slot=icon]]:!text-neutral-500 hover:dark:[&_[data-slot=icon]]:!text-neutral-400"
    >
        <slot />
    </button>
</template>
