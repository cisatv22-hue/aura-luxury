import { createPinia } from 'pinia';
import { defineAsyncComponent } from 'vue';

const AppLayout = defineAsyncComponent(() => import('@/layouts/AppLayout.vue'));
const AuthLayout = defineAsyncComponent(
    () => import('@/layouts/AuthLayout.vue'),
);
const StoreLayout = defineAsyncComponent(
    () => import('@/layouts/store/StoreLayout.vue'),
);
const SettingsLayout = defineAsyncComponent(
    () => import('@/layouts/settings/Layout.vue'),
);

export function layoutForPage(name: string) {
    switch (true) {
        case name === 'Welcome':
            return null;
        case [
            'Home',
            'Catalog/Index',
            'Product/Show',
            'Favorites',
            'Account/Index',
            'Order/Create',
            'Order/Show',
            'Info/Show',
        ].includes(name):
            return StoreLayout;
        case name.startsWith('auth/'):
            return AuthLayout;
        case name.startsWith('settings/'):
            return [AppLayout, SettingsLayout];
        default:
            return AppLayout;
    }
}

export function configureApp(app: import('vue').App) {
    app.use(createPinia());
    app.directive('focus', {
        mounted: (el: HTMLElement, shouldFocus) => {
            if (shouldFocus.value !== false) {
                el.focus();
            }
        },
    });
}
