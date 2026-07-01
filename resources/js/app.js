import { createApp, createSSRApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import { registerServiceWorker } from './registerServiceWorker'
import '../css/app.css'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue')
        return pages[`./Pages/${name}.vue`]()
    },
    setup({ el, App, props, plugin }) {
        const ziggy = props.initialPage.props.ziggy
        const app = (el ? createApp : createSSRApp)({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, {
                ...ziggy,
                location: new URL(ziggy.location),
            })

        if (el) {
            app.mount(el)
        }

        return app
    },
})

if (typeof window !== 'undefined') {
    registerServiceWorker()
}
