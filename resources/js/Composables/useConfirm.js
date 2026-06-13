import { reactive } from 'vue'

const state = reactive({
    open: false,
    title: 'Konfirmasi aksi',
    message: 'Lanjutkan aksi ini?',
    confirmLabel: 'Ya, lanjutkan',
    cancelLabel: 'Batal',
    tone: 'danger',
    resolver: null,
})

function reset() {
    state.open = false
    state.title = 'Konfirmasi aksi'
    state.message = 'Lanjutkan aksi ini?'
    state.confirmLabel = 'Ya, lanjutkan'
    state.cancelLabel = 'Batal'
    state.tone = 'danger'
    state.resolver = null
}

export function useConfirm() {
    function confirmAction(options = {}) {
        state.title = options.title ?? state.title
        state.message = options.message ?? state.message
        state.confirmLabel = options.confirmLabel ?? state.confirmLabel
        state.cancelLabel = options.cancelLabel ?? state.cancelLabel
        state.tone = options.tone ?? state.tone
        state.open = true

        return new Promise((resolve) => {
            state.resolver = resolve
        })
    }

    function confirm() {
        state.resolver?.(true)
        reset()
    }

    function cancel() {
        state.resolver?.(false)
        reset()
    }

    return {
        confirm,
        confirmAction,
        cancel,
        state,
    }
}
