// resources/js/app.ts
import '../css/app.css'; // Tailwind styles
import { createApp, DefineComponent, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';
import { setToken } from './lib/apiClient';
import { fetchCsrfToken } from './bootstrap';

/**
 * Initialize authentication from localStorage
 */
const initializeAuth = async () => {
    const token = localStorage.getItem('authToken');
    const deviceName = localStorage.getItem('authDeviceName');

    if (token && deviceName) {
        // Set token for API requests
        setToken(token, deviceName);
        return true;
    }

    return false;
};

/**
 * Initialize the CSRF protection
 */
const initializeCsrf = async () => {
    try {
        // Fetch CSRF cookie to enable CSRF protection for non-GET requests
        await fetchCsrfToken();
        return true;
    } catch (error) {
        console.error('Failed to fetch CSRF token:', error);
        return false;
    }
};

// Make route globally available
declare global {
    interface Window {
        route: any;
        Ziggy: any;
    }
}

// Initialize the app
(async () => {
    // Initialize authentication and CSRF before mounting the app
    await Promise.all([
        initializeAuth(),
        initializeCsrf()
    ]);

    createInertiaApp({
        title: (title) => title ? `${title} - B2B League` : 'B2B League',

        resolve: (name) => resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue')
        ),

        setup({ el, App, props, plugin }) {
            // Ensure we have the route data available
            const ziggyData = {
                ...Ziggy,
                ...(window.Ziggy || {}),
                location: window.location.origin,
            };

            // Create Vue app
            const app = createApp({
                render: () => h(App, props)
            });

            // Register global properties
            app.config.globalProperties.route = (name: string, params?: any, absolute?: boolean) => {
                try {
                    return window.route(name, params, absolute, ziggyData);
                } catch (error) {
                    console.warn(`Route error for ${name}:`, error);
                    // Return fallback URL if route can't be generated
                    return '/';
                }
            };

            // Apply plugins
            app.use(plugin);
            app.use(ZiggyVue, ziggyData);

            // Mount the app
            app.mount(el);
            console.log('App mounted and initialized');
        },

        progress: {
            color: '#4F46E5', // Indigo-600
            showSpinner: true,
            delay: 100,
        },
    });
})();
