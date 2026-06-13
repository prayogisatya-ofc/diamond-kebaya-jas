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
    <label class="grid gap-2">
        <span v-if="label" class="text-sm font-semibold text-diamond-text">{{ label }}</span>
        <input
            v-bind="$attrs"
            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
            :type="type"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <span v-if="error" class="text-sm text-diamond-danger">{{ error }}</span>
    </label>
</template>
