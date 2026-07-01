import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import AppShell from './Layout/AppShell.vue';
import { initTheme } from './composables/useTheme';
import { initTelegram } from './composables/useTelegram';

initTelegram();
initTheme();

const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

createInertiaApp({
    progress: { color: '#FF5C5C', showSpinner: false },
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];
        page.default.layout = page.default.layout || AppShell;
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
