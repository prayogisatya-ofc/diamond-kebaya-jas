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
    <div class="grid gap-1.5 sm:gap-2">
        <button
            class="flex min-h-10 w-full items-center justify-between gap-3 rounded-xl border border-diamond-border bg-white px-3 py-2 text-left transition focus:outline-none focus:ring-4 focus:ring-diamond-primary/10 sm:min-h-12 sm:gap-4 sm:px-4 sm:py-3"
            type="button"
            role="switch"
            :aria-checked="modelValue"
            @click="$emit('update:modelValue', !modelValue)"
        >
            <span class="min-w-0">
                <span class="block text-xs font-semibold text-diamond-text sm:text-sm">{{ label }}</span>
                <span v-if="description" class="mt-0.5 block text-[11px] leading-4 text-diamond-muted sm:mt-1 sm:text-xs sm:leading-5">{{ description }}</span>
            </span>
            <span
                class="relative flex h-6 w-10 shrink-0 items-center rounded-full p-1 transition sm:h-7 sm:w-12"
                :class="modelValue ? 'bg-diamond-primary' : 'bg-diamond-border'"
            >
                <span
                    class="h-4 w-4 rounded-full bg-white transition sm:h-5 sm:w-5"
                    :class="modelValue ? 'translate-x-4 sm:translate-x-5' : 'translate-x-0'"
                />
            </span>
        </button>
        <span v-if="error" class="text-xs text-diamond-danger sm:text-sm">{{ error }}</span>
    </div>
</template>
