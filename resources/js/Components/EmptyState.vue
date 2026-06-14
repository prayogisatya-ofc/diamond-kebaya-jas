<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div class="rounded-2xl border border-dashed border-diamond-border bg-diamond-surface-soft p-4 text-center sm:p-6">
        <p class="text-xs font-semibold text-diamond-text sm:text-sm">{{ title }}</p>
        <p v-if="description" class="mt-1 text-xs leading-5 text-diamond-muted sm:text-sm sm:leading-6">{{ description }}</p>
    </div>
</template>
