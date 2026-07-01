<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const loading = ref(false)

router.on('start', () => {
    loading.value = true
})

router.on('finish', () => {
    loading.value = false
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="loading"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-white/40 backdrop-blur-[1px]"
            >
                <div class="flex flex-col items-center gap-3 rounded-3xl bg-white px-6 py-5 shadow-xl shadow-slate-900/10 ring-1 ring-slate-900/5">
                    <svg class="h-8 w-8 animate-spin text-diamond-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                        <path class="opacity-100" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <p class="text-xs font-semibold text-diamond-muted">Memuat...</p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
