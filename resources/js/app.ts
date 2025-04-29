//import './bootstrap';
import '../css/app.css'; // Подключаем стили Tailwind

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js'; // Убедись, что путь верный
import { useAuth } from '@/composables/useAuth'; // Импортируем наш composable

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Получаем функцию инициализации из useAuth
const { initializeAuth } = useAuth();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    async setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp.use(plugin)
            .use(ZiggyVue);

        // ЖДЕМ завершения инициализации auth перед монтированием
        await initializeAuth();

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
