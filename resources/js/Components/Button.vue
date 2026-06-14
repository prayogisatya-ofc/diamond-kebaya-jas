<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    href: {
        type: String,
        default: null,
    },
    method: {
        type: String,
        default: 'get',
    },
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
    },
    full: {
        type: Boolean,
        default: false,
    },
})

const component = computed(() => (props.href ? Link : 'button'))

const classes = computed(() => [
    'inline-flex min-h-10 min-w-0 max-w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-xl px-3 py-2 text-xs font-semibold transition focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-11 sm:gap-2 sm:px-4 sm:py-3 sm:text-sm',
    props.full ? 'w-full' : 'w-full sm:w-auto',
    {
        primary: 'bg-diamond-primary text-white hover:bg-diamond-primary-dark focus:ring-diamond-primary/20',
        secondary: 'border border-diamond-border bg-white text-diamond-text hover:bg-diamond-surface-soft focus:ring-diamond-primary/10',
        ghost: 'bg-transparent text-diamond-muted hover:bg-white/60 focus:ring-diamond-primary/10',
        danger: 'bg-diamond-danger text-white hover:bg-red-600 focus:ring-diamond-danger/20',
        accent: 'bg-diamond-accent text-white hover:bg-orange-500 focus:ring-diamond-accent/20',
    }[props.variant],
])
</script>

<template>
    <component
        :is="component"
        :href="href"
        :method="href ? method : undefined"
        :as="href && method !== 'get' ? 'button' : undefined"
        :type="href ? undefined : type"
        :class="classes"
    >
        <slot />
    </component>
</template>
