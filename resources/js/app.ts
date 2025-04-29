//import './bootstrap';
import '../css/app.css'; // Tailwind styles

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { useAuth } from '@/composables/useAuth';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Get initialization function from useAuth
const { initializeAuth } = useAuth();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    async setup({ el, App, props, plugin }) {
        // If we're on the login page, create the app without checking auth
        // This prevents redirect loops
        const isLoginPage = window.location.pathname === '/login' ||
            window.location.pathname === '/' && !document.cookie.includes('laravel_session');

        const vueApp = createApp({ render: () => h(App, props) });

        // Apply plugins
        vueApp.use(plugin)
            .use(ZiggyVue);

        if (!isLoginPage) {
            try {
                // Initialize auth BEFORE mounting app (but only if not on login page)
                console.log('[App] Initializing auth before app mount');
                await initializeAuth();
                console.log('[App] Auth initialized successfully. Authenticated:',
                    useAuth().isAuthenticated.value);
            } catch (error) {
                // Log error but continue with app mount
                console.error('[App] Error during auth initialization:', error);
                console.log('[App] Continuing with app mount despite auth error');
            }
        } else {
            console.log('[App] Skipping auth initialization on login page to prevent loops');
        }

        // Mount the app
        vueApp.mount(el);
        console.log('[App] App mounted');
    },
    progress: {
        color: '#4B5563',
    },
});
