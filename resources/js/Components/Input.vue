<script setup>
defineOptions({
    inheritAttrs: false,
})

defineProps({
    modelValue: {
        type: [String, Number, Boolean],
        default: '',
    },
    error: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'text',
    },
})

defineEmits(['update:modelValue'])
</script>

<template>
    <label class="grid gap-1.5 sm:gap-2">
        <span v-if="label" class="text-xs font-semibold text-diamond-text sm:text-sm">{{ label }}</span>
        <input
            v-bind="$attrs"
            class="min-h-10 w-full rounded-xl border border-diamond-border bg-white px-3 py-2 text-xs text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10 sm:min-h-12 sm:px-4 sm:py-3 sm:text-sm"
            :type="type"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <span v-if="error" class="text-xs text-diamond-danger sm:text-sm">{{ error }}</span>
    </label>
</template>
