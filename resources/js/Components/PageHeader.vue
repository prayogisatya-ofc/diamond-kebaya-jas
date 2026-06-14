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
    <header class="flex w-full min-w-0 max-w-full flex-col justify-between gap-3 overflow-hidden rounded-[1.35rem] bg-white/70 p-4 backdrop-blur sm:gap-5 sm:rounded-3xl sm:p-7 lg:flex-row lg:items-end">
        <div class="min-w-0">
            <p v-if="eyebrow" class="text-[10px] font-bold uppercase tracking-wide text-diamond-accent sm:text-xs">{{ eyebrow }}</p>
            <h1 class="mt-1.5 text-xl font-bold leading-tight text-diamond-text sm:mt-2 sm:text-3xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1.5 max-w-2xl text-xs leading-5 text-diamond-muted sm:mt-2 sm:text-sm sm:leading-6">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex w-full min-w-0 flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-2 lg:w-auto lg:shrink-0">
            <slot name="actions" />
        </div>
    </header>
</template>
