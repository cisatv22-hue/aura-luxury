import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import { initializeFlashToast } from '@/lib/flashToast';
import { configureApp, layoutForPage } from '@/app-config';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: layoutForPage,
    withApp: configureApp,
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
initializeFlashToast();
