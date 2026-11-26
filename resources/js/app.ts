import { createInertiaApp, router } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initPreline } from './lib/preline';
import { configureEcho } from '@laravel/echo-vue';

// Without a Reverb key the app still works; pages just don't update live.
if (import.meta.env.VITE_REVERB_APP_KEY) {
    configureEcho({
        broadcaster: 'reverb',
    });
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: async (name) => {
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Inertia page not found: ./pages/${name}.vue`);
        }

        return page();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);

        initPreline();
    },
    progress: {
        color: '#047857',
    },
});

// Inertia swaps pages without a reload, so Preline wires up the new page's markup after each visit.
router.on('navigate', () => initPreline());
