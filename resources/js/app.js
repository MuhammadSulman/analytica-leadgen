import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Antd, { ConfigProvider } from 'ant-design-vue';
import 'ant-design-vue/dist/reset.css';
import { antdTheme, brand } from './theme';
import { rememberTimezone } from './timezone';

rememberTimezone();

const appName = import.meta.env.VITE_APP_NAME || 'Analytica';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () =>
                h(ConfigProvider, { theme: antdTheme.value }, () =>
                    h(App, props),
                ),
        })
            .use(plugin)
            .use(Antd)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: brand.sky,
    },
});
