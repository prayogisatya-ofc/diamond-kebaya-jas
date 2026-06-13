<script setup>
defineProps({
    padded: {
        type: Boolean,
        default: true,
    },
})
</script>

<template>
    <section
        class="w-full max-w-full rounded-3xl border border-white/70 bg-white"
        :class="padded ? 'p-6 sm:p-7' : ''"
    >
        <slot />
    </section>
</template>
