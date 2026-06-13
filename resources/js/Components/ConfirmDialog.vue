<script setup>
import { computed } from 'vue'
import { AlertTriangle, LogOut, Trash2, X } from '@lucide/vue'
import { useConfirm } from '@/Composables/useConfirm'

const { state, confirm, cancel } = useConfirm()

const icon = computed(() => (state.tone === 'logout' ? LogOut : state.tone === 'warning' ? AlertTriangle : Trash2))

const confirmClasses = computed(() => ({
    danger: 'bg-diamond-danger text-white hover:bg-red-600 focus:ring-diamond-danger/20',
    logout: 'bg-diamond-primary text-white hover:bg-diamond-primary-dark focus:ring-diamond-primary/20',
    warning: 'bg-diamond-accent text-white hover:bg-orange-500 focus:ring-diamond-accent/20',
}[state.tone] ?? 'bg-diamond-danger text-white hover:bg-red-600 focus:ring-diamond-danger/20'))

const iconClasses = computed(() => ({
    danger: 'bg-red-50 text-diamond-danger',
    logout: 'bg-diamond-primary-soft text-diamond-primary',
    warning: 'bg-orange-50 text-diamond-accent',
}[state.tone] ?? 'bg-red-50 text-diamond-danger'))
</script>

<template>
    <Teleport to="body">
        <div
            v-if="state.open"
            class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
            role="presentation"
            @click.self="cancel"
        >
            <section
                class="w-full max-w-md rounded-[2rem] border border-white/80 bg-white p-5 sm:p-6"
                role="dialog"
                aria-modal="true"
                :aria-label="state.title"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" :class="iconClasses">
                            <component :is="icon" :size="22" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-diamond-text">{{ state.title }}</h2>
                            <p class="mt-2 text-sm leading-6 text-diamond-muted">{{ state.message }}</p>
                        </div>
                    </div>

                    <button
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                        type="button"
                        aria-label="Tutup"
                        @click="cancel"
                    >
                        <X :size="19" />
                    </button>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <button
                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm font-semibold text-diamond-text transition hover:bg-diamond-surface-soft focus:outline-none focus:ring-4 focus:ring-diamond-primary/10"
                        type="button"
                        @click="cancel"
                    >
                        {{ state.cancelLabel }}
                    </button>
                    <button
                        class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold transition focus:outline-none focus:ring-4"
                        :class="confirmClasses"
                        type="button"
                        @click="confirm"
                    >
                        {{ state.confirmLabel }}
                    </button>
                </div>
            </section>
        </div>
    </Teleport>
</template>
