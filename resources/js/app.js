import './bootstrap';
import { createApp, h } from 'vue'
import {createInertiaApp, Head, Link} from '@inertiajs/vue3'
import { ZiggyVue, route } from 'ziggy-js';
import { Ziggy } from './ziggy.js';

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mixin({methods: {route}})
            .use(ZiggyVue, Ziggy)
            .component('Head', Head)
            .component('Link', Link)
            .mount(el)
    },
})
