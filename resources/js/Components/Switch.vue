<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
})

defineEmits(['update:modelValue'])
</script>

<template>
    <div class="grid gap-2">
        <button
            class="flex min-h-12 w-full items-center justify-between gap-4 rounded-xl border border-diamond-border bg-white px-4 py-3 text-left transition focus:outline-none focus:ring-4 focus:ring-diamond-primary/10"
            type="button"
            role="switch"
            :aria-checked="modelValue"
            @click="$emit('update:modelValue', !modelValue)"
        >
            <span class="min-w-0">
                <span class="block text-sm font-semibold text-diamond-text">{{ label }}</span>
                <span v-if="description" class="mt-1 block text-xs leading-5 text-diamond-muted">{{ description }}</span>
            </span>
            <span
                class="relative flex h-7 w-12 shrink-0 items-center rounded-full p-1 transition"
                :class="modelValue ? 'bg-diamond-primary' : 'bg-diamond-border'"
            >
                <span
                    class="h-5 w-5 rounded-full bg-white transition"
                    :class="modelValue ? 'translate-x-5' : 'translate-x-0'"
                />
            </span>
        </button>
        <span v-if="error" class="text-sm text-diamond-danger">{{ error }}</span>
    </div>
</template>
