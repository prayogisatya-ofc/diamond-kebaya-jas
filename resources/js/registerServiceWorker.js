export function registerServiceWorker() {
    if (typeof window === 'undefined' || typeof navigator === 'undefined') {
        return
    }

    if (import.meta.env.DEV || !('serviceWorker' in navigator)) {
        return
    }

    const isSecureContext = window.location.protocol === 'https:' || window.location.hostname === 'localhost'

    if (!isSecureContext) {
        return
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // PWA support should never block the rental workflow.
        })
    })
}
