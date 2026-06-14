<script setup>
const props = defineProps({
    tone: {
        type: String,
        default: 'neutral',
    },
})

const tones = {
    neutral: 'bg-slate-100 text-slate-600',
    primary: 'bg-diamond-primary-soft text-diamond-primary-dark',
    success: 'bg-diamond-success-soft text-emerald-700',
    warning: 'bg-diamond-warning-soft text-amber-700',
    danger: 'bg-diamond-danger-soft text-red-700',
    info: 'bg-diamond-info-soft text-blue-700',
    accent: 'bg-diamond-accent-soft text-orange-700',
}
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold leading-5 sm:px-3 sm:py-1 sm:text-xs" :class="tones[props.tone] || tones.neutral">
        <slot />
    </span>
</template>
