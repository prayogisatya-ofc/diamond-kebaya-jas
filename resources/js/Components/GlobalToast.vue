<script setup>
import { computed, onBeforeUnmount, reactive, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from '@lucide/vue'

const page = usePage()
const toasts = reactive([])
const timers = new Map()
let toastId = 0
let lastValidationSignature = ''

const icons = {
    success: CheckCircle2,
    error: AlertCircle,
    warning: AlertTriangle,
    info: Info,
}

const labels = {
    success: 'Berhasil',
    error: 'Gagal',
    warning: 'Perhatian',
    info: 'Info',
}

const toneClasses = {
    success: {
        icon: 'bg-diamond-success-soft text-diamond-success',
        border: 'border-diamond-success/20',
    },
    error: {
        icon: 'bg-diamond-danger-soft text-diamond-danger',
        border: 'border-diamond-danger/20',
    },
    warning: {
        icon: 'bg-diamond-warning-soft text-diamond-warning',
        border: 'border-diamond-warning/20',
    },
    info: {
        icon: 'bg-diamond-info-soft text-diamond-info',
        border: 'border-diamond-info/20',
    },
}

const flash = computed(() => page.props.flash || {})
const errors = computed(() => page.props.errors || {})

function addToast(type, message, options = {}) {
    if (!message) {
        return
    }

    const id = ++toastId
    const toast = {
        id,
        type,
        title: options.title || labels[type] || labels.info,
        message,
    }

    toasts.unshift(toast)

    if (toasts.length > 4) {
        removeToast(toasts[toasts.length - 1].id)
    }

    if (typeof window !== 'undefined') {
        timers.set(id, window.setTimeout(() => removeToast(id), options.duration || 4200))
    }
}

function removeToast(id) {
    const index = toasts.findIndex((toast) => toast.id === id)

    if (index === -1) {
        return
    }

    toasts.splice(index, 1)
    if (typeof window !== 'undefined') {
        window.clearTimeout(timers.get(id))
    }
    timers.delete(id)
}

watch(
    flash,
    (value) => {
        Object.entries({
            success: value.success,
            error: value.error,
            warning: value.warning,
            info: value.info,
        }).forEach(([type, message]) => addToast(type, message))
    },
    { deep: true, immediate: true },
)

watch(
    errors,
    (value) => {
        const messages = Object.values(value || {}).filter(Boolean)
        const signature = messages.join('|')

        if (!messages.length || signature === lastValidationSignature) {
            return
        }

        lastValidationSignature = signature
        addToast('error', messages[0] || 'Periksa kembali input yang belum valid.', {
            title: 'Periksa form',
        })
    },
    { deep: true },
)

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        timers.forEach((timer) => window.clearTimeout(timer))
    }

    timers.clear()
})
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed inset-x-0 top-4 z-[80] flex flex-col gap-3 px-4 sm:left-auto sm:right-5 sm:top-5 sm:w-[24rem] sm:px-0">
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
            >
                <article
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex gap-3 rounded-3xl border bg-white p-4 text-diamond-text"
                    :class="toneClasses[toast.type]?.border || toneClasses.info.border"
                >
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl" :class="toneClasses[toast.type]?.icon || toneClasses.info.icon">
                        <component :is="icons[toast.type] || icons.info" :size="21" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold">{{ toast.title }}</p>
                        <p class="mt-1 text-sm leading-6 text-diamond-muted">{{ toast.message }}</p>
                    </div>
                    <button
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                        type="button"
                        @click="removeToast(toast.id)"
                    >
                        <X :size="18" />
                    </button>
                </article>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
