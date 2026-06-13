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
    <div class="rounded-2xl border border-dashed border-diamond-border bg-diamond-surface-soft p-6 text-center">
        <p class="text-sm font-semibold text-diamond-text">{{ title }}</p>
        <p v-if="description" class="mt-1 text-sm leading-6 text-diamond-muted">{{ description }}</p>
    </div>
</template>
