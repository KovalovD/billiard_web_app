// resources/js/app.ts
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import axios from 'axios';

// Simple token retrieval
const getStoredToken = () => localStorage.getItem('authToken');

// Simple auth setup - no composables to avoid circular deps
const setupAuth = () => {
    const token = getStoredToken();
    if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        return true;
    }
    return false;
};

createInertiaApp({
    title: (title) => `${title} - B2B League`,

    resolve: (name) => {
        // Simple component resolution without complex handling
        return resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue'));
    },

    setup({ el, App, props, plugin }) {
        // Create the app first
        const app = createApp({ render: () => h(App, props) });

        // Apply plugins
        app.use(plugin);
        app.use(ZiggyVue);

        // Configure global route function
        app.config.globalProperties.route = function(name, params, absolute) {
            try {
                return window.route(name, params, absolute);
            } catch (e) {
                console.warn('Route error:', e);
                // Simple fallbacks
                return name === 'login' ? '/login' :
                    name === 'dashboard' ? '/dashboard' :
                        `/${name}`;
            }
        };

        // Setup auth headers
        setupAuth();

        // Mount without delays or complex conditionals
        app.mount(el);
        console.log('[App] Mounted');
    },

    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
});
