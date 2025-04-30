// resources/js/app.ts
import '../css/app.css'; // Tailwind styles

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';
import axios from 'axios';

// Simple auth token management
const getAuthToken = () => localStorage.getItem('authToken');
const getDeviceName = () => localStorage.getItem('authDeviceName');

/**
 * Set authorization header if token exists
 */
const setupAuthHeader = () => {
    const token = getAuthToken();
    if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        return true;
    }
    return false;
};

/**
 * Initialize the auth state based on current page
 */
const initializeAuth = async () => {
    // Get current path
    const currentPath = window.location.pathname;

    // Skip auth init for auth routes
    if (
        currentPath === '/login' ||
        currentPath === '/register' ||
        currentPath === '/' ||
        currentPath.includes('password') ||
        !document.cookie.includes('laravel_session')
    ) {
        console.log('[App] Skipping auth initialization for non-protected route');
        return false;
    }

    // Setup auth header if token exists
    const hasToken = setupAuthHeader();
    if (!hasToken) {
        console.log('[App] No auth token found');
        return false;
    }

    // We only set the header here - the component will handle fetching user data
    console.log('[App] Auth header set from stored token');
    return true;
};

// Make route globally available
declare global {
    interface Window {
        route: any;
        Ziggy: any;
    }
}

createInertiaApp({
    title: (title) => `${title} - B2B League`,

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
        const vueApp = createApp({ render: () => h(App, props) });

        // Register global properties
        vueApp.config.globalProperties.route = (name: string, params?: any, absolute?: boolean) => {
            try {
                return window.route(name, params, absolute, ziggyData);
            } catch (error) {
                console.warn(`Route error for ${name}:`, error);
                // Return fallback URL if route can't be generated
                if (name === 'login') return '/login';
                if (name === 'dashboard') return '/dashboard';
                if (name === 'register') return '/register';
                return '/';
            }
        };

        // Apply plugins
        vueApp.use(plugin);
        vueApp.use(ZiggyVue, ziggyData);

        // Initialize auth before mounting
        initializeAuth()
            .then(() => {
                // Mount the app
                vueApp.mount(el);
                console.log('[App] App mounted after auth initialization');
            })
            .catch(error => {
                console.error('[App] Error during auth initialization:', error);
                // Mount the app anyway, let components handle auth state
                vueApp.mount(el);
                console.log('[App] App mounted with auth initialization error');
            });
    },

    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
});
