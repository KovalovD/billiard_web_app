import '../css/app.css'; // Tailwind styles

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { useAuth } from '@/composables/useAuth';
import { Ziggy } from './ziggy';

// Get initialization function from useAuth
const { initializeAuth } = useAuth();

// Make route globally available
declare global {
    interface Window {
        route: any;
        Ziggy: any;
    }
}

createInertiaApp({
    title: (title) => `${title} - B2B League`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    async setup({ el, App, props, plugin }) {
        // Check if we're on the login page to prevent auth redirect loops
        const isLoginPage = window.location.pathname === '/login' ||
            window.location.pathname === '/' && !document.cookie.includes('laravel_session');

        // Ensure we have the route data available
        const ziggyData = {
            ...Ziggy,
            ...(window.Ziggy || {}),
            location: window.location.origin,
        };

        // Create Vue app
        const vueApp = createApp({ render: () => h(App, props) });

        // Register global properties
        vueApp.config.globalProperties.route = (name: string, params?: any, absolute?: boolean) => {
            try {
                return window.route(name, params, absolute, ziggyData);
            } catch (error) {
                console.warn(`Route error for ${name}:`, error);
                // Return fallback URL if route can't be generated
                return name === 'login' ? '/login' :
                    name === 'dashboard' ? '/dashboard' :
                        name === 'register' ? '/register' : '/';
            }
        };

        // Apply plugins
        vueApp.use(plugin)
            .use(ZiggyVue, ziggyData);

        if (!isLoginPage) {
            try {
                // Initialize auth before mounting app, but only if not on login page
                console.log('[App] Initializing auth before app mount');
                await initializeAuth();
                console.log('[App] Auth initialized successfully');
            } catch (error) {
                // Log error but continue with app mount
                console.error('[App] Error during auth initialization:', error);
            }
        } else {
            console.log('[App] Skipping auth initialization on login page');
        }

        // Mount the app
        vueApp.mount(el);
        console.log('[App] App mounted');
    },
    progress: {
        color: '#4B5563',
    },
});
