<script setup>
defineProps({
    eyebrow: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: null,
    },
})
</script>

<template>
    <header class="flex w-full min-w-0 max-w-full flex-col justify-between gap-5 overflow-hidden rounded-3xl bg-white/70 p-6 backdrop-blur sm:p-7 lg:flex-row lg:items-end">
        <div class="min-w-0">
            <p v-if="eyebrow" class="text-xs font-bold uppercase text-diamond-accent">{{ eyebrow }}</p>
            <h1 class="mt-2 text-2xl font-bold leading-tight text-diamond-text sm:text-3xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-2 max-w-2xl text-sm leading-6 text-diamond-muted">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex w-full min-w-0 flex-col gap-2 sm:flex-row sm:items-center lg:w-auto lg:shrink-0">
            <slot name="actions" />
        </div>
    </header>
</template>
