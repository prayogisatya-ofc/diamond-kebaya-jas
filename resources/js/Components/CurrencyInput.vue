<script setup>
import { computed } from 'vue'

defineOptions({
    inheritAttrs: false,
})

const props = defineProps({
    modelValue: {
        type: [String, Number],
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
    placeholder: {
        type: String,
        default: '0',
    },
})

const emit = defineEmits(['update:modelValue'])

const displayValue = computed(() => formatCurrencyDisplay(props.modelValue))

function normalizeCurrencyValue(value) {
    const stringValue = String(value ?? '').trim()

    if (stringValue === '') {
        return ''
    }

    const integerValue = /^\d+\.\d{1,2}$/.test(stringValue) ? stringValue.split('.')[0] : stringValue

    return integerValue
        .replace(/\D/g, '')
        .replace(/^0+(?=\d)/, '')
}

function formatCurrencyDisplay(value) {
    const normalizedValue = normalizeCurrencyValue(value)

    if (normalizedValue === '') {
        return ''
    }

    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Number(normalizedValue))
}

function updateValue(event) {
    const value = normalizeCurrencyValue(event.target.value)

    emit('update:modelValue', value)

    event.target.value = formatCurrencyDisplay(value)
}
</script>

<template>
    <label class="grid min-w-0 gap-1.5 sm:gap-2">
        <span v-if="label" class="text-xs font-semibold text-diamond-text sm:text-sm">{{ label }}</span>
        <div class="flex min-h-10 min-w-0 overflow-hidden rounded-xl border border-diamond-border bg-white transition focus-within:border-diamond-primary focus-within:ring-4 focus-within:ring-diamond-primary/10 sm:min-h-12">
            <span class="flex shrink-0 items-center border-r border-diamond-border bg-diamond-surface-soft px-3 text-xs font-bold text-diamond-muted sm:px-4 sm:text-sm">
                Rp
            </span>
            <input
                v-bind="$attrs"
                class="min-w-0 flex-1 bg-white px-3 py-2 text-xs font-semibold text-diamond-text outline-none placeholder:text-diamond-soft sm:px-4 sm:py-3 sm:text-sm"
                inputmode="numeric"
                min="0"
                :placeholder="placeholder"
                step="1"
                type="text"
                :value="displayValue"
                @input="updateValue"
            >
        </div>
        <span v-if="error" class="text-xs text-diamond-danger sm:text-sm">{{ error }}</span>
    </label>
</template>
