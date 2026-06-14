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
        class="w-full max-w-full rounded-[1.35rem] border border-white/70 bg-white sm:rounded-3xl"
        :class="padded ? 'p-4 sm:p-7' : ''"
    >
        <slot />
    </section>
</template>
