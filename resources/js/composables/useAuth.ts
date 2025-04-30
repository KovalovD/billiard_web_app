// resources/js/composables/useAuth.ts
import { ref, computed } from 'vue';
import axios from 'axios';

// State management
const user = ref(null);
const isAuthInitialized = ref(false);
const isLoading = ref(false);
const error = ref(null);

// Computed properties
const isAuthenticated = computed(() => isAuthInitialized.value && !!user.value);
const isAdmin = computed(() => isAuthenticated.value && user.value?.is_admin);

/**
 * Simple login function using direct axios calls
 */
async function login(credentials) {
    isLoading.value = true;
    error.value = null;

    try {
        // Get CSRF cookie first
        await axios.get('/sanctum/csrf-cookie');

        // Attempt login
        const response = await axios.post('/api/auth/login', {
            ...credentials,
            deviceName: `web-${Date.now()}`
        });

        // Store user and token
        if (response.data.user && response.data.token) {
            user.value = response.data.user;
            localStorage.setItem('authToken', response.data.token);
            localStorage.setItem('authDeviceName', `web-${Date.now()}`);
            axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        }

        isAuthInitialized.value = true;
        return response.data;
    } catch (err) {
        error.value = err.response?.data?.message || 'Login failed';
        throw err;
    } finally {
        isLoading.value = false;
    }
}

/**
 * Simple logout function
 */
async function logout() {
    isLoading.value = true;

    try {
        // Try to call API for logout
        if (localStorage.getItem('authToken')) {
            await axios.post('/api/auth/logout', {
                deviceName: localStorage.getItem('authDeviceName')
            });
        }
    } catch (err) {
        console.error('Logout API call failed:', err);
    } finally {
        // Always clear state regardless of API result
        user.value = null;
        localStorage.removeItem('authToken');
        localStorage.removeItem('authDeviceName');
        delete axios.defaults.headers.common['Authorization'];
        isLoading.value = false;

        // Hard navigation to login
        window.location.href = '/login';
    }
}

// Simple export without complex circular references
export function useAuth() {
    return {
        user,
        isLoading,
        error,
        isAuthInitialized,
        isAuthenticated,
        isAdmin,
        login,
        logout
    };
}
