import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, h, type DefineComponent } from 'vue';
import { configureApp, layoutForPage } from '@/app-config';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob<{ default: DefineComponent }>(
    './pages/**/*.vue',
    { eager: true },
);

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        layout: layoutForPage,
        resolve: (name) => {
            return pages[`./pages/${name}.vue`];
        },
        setup({ App, props, plugin }) {
            const app = createSSRApp({
                render: () => h(App, props),
            });

            app.use(plugin);
            configureApp(app);

            return app;
        },
    }),
);
