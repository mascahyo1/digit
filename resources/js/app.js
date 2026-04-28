import './bootstrap';
import '../css/app.css';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { initFlowbite } from 'flowbite';

const appName = import.meta.env.VITE_APP_NAME || 'ReverbApp';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#3b82f6',
    },
});

// Init Flowbite setelah setiap navigasi Inertia
document.addEventListener('inertia:finish', () => {
    initFlowbite();
});
