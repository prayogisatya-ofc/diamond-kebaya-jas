<script setup>
import { X } from '@lucide/vue'

defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    maxWidth: {
        type: String,
        default: 'max-w-2xl',
    },
})

defineEmits(['close'])
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[95] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
            role="presentation"
            @click.self="$emit('close')"
        >
            <section
                class="flex max-h-[92vh] min-h-0 w-full flex-col overflow-hidden rounded-[2rem] border border-white/80 bg-white"
                :class="maxWidth"
                role="dialog"
                aria-modal="true"
            >
                <!-- Header slot -->
                <div v-if="$slots.header" class="flex items-start justify-between gap-4 border-b border-diamond-border p-5 sm:p-6">
                    <slot name="header" />
                    <button
                        class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                        type="button"
                        aria-label="Tutup"
                        @click="$emit('close')"
                    >
                        <X :size="20" />
                    </button>
                </div>

                <!-- Body -->
                <div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden p-5 sm:p-6">
                    <slot />
                </div>

                <!-- Footer slot -->
                <div v-if="$slots.footer" class="grid gap-3 border-t border-diamond-border p-5 sm:flex sm:items-center sm:justify-between sm:p-6">
                    <slot name="footer" />
                </div>
            </section>
        </div>
    </Teleport>
</template>
