// resources/js/composables/useAuth.ts
import { computed, ref } from 'vue';
import { apiClient, setToken } from '@/lib/apiClient';
import type { User } from '@/types/api';
import { usePage } from '@inertiajs/vue3';
import type { SharedData } from '@/types';

// State
const user = ref<User | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);
const isAuthInitialized = ref(false);

// Get initial user from Inertia shared props if available
const initFromPage = () => {
    try {
        const page = usePage<SharedData>();
        if (page.props.auth?.user) {
            user.value = page.props.auth.user as unknown as User;
            isAuthInitialized.value = true;
            return true;
        }
    } catch (e) {
        // Failed to get from page props, will try API
    }
    return false;
};

// Fetch the current user from API
const fetchUser = async (): Promise<boolean> => {
    if (user.value) return true; // Already have user
    if (initFromPage()) return true; // Got from Inertia props

    const token = localStorage.getItem('authToken');
    if (!token) return false; // No token, not authenticated

    isLoading.value = true;
    error.value = null;

    try {
        // Set authorization header
        setToken(token, localStorage.getItem('authDeviceName'));

        // Fetch user
        user.value = await apiClient<User>('/api/auth/user');
        isAuthInitialized.value = true;
        return true;
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch user';
        setToken(null, null); // Clear invalid token
        return false;
    } finally {
        isLoading.value = false;
    }
};

// Login the user
const login = async (credentials: { email: string; password: string }) => {
    isLoading.value = true;
    error.value = null;

    try {
        // Get CSRF cookie first
        await apiClient('/sanctum/csrf-cookie');

        // Login request
        const response = await apiClient<{ user: User; token: string }>('/api/auth/login', {
            method: 'post',
            data: {
                ...credentials,
                deviceName: `web-${Date.now()}`
            }
        });

        // Store user and token
        user.value = response.user;
        setToken(response.token, `web-${Date.now()}`);
        isAuthInitialized.value = true;

        return response;
    } catch (err: any) {
        error.value = err.message || 'Login failed';
        throw err;
    } finally {
        isLoading.value = false;
    }
};

// Logout the user
const logout = async () => {
    isLoading.value = true;

    try {
        const deviceName = localStorage.getItem('authDeviceName');
        if (localStorage.getItem('authToken')) {
            await apiClient('/api/auth/logout', {
                method: 'post',
                data: { deviceName }
            });
        }
    } catch (err) {
        // Continue with logout even if API call fails
    } finally {
        // Always clear state
        user.value = null;
        setToken(null, null);
        isLoading.value = false;

        // Hard navigation to login
        window.location.href = '/login';
    }
};

// Computed properties
const isAuthenticated = computed(() => isAuthInitialized.value && !!user.value);
const isAdmin = computed(() => isAuthenticated.value && !!user.value?.is_admin);

export function useAuth() {
    return {
        // State
        user,
        isLoading,
        error,
        isAuthInitialized,

        // Computed
        isAuthenticated,
        isAdmin,

        // Methods
        fetchUser,
        login,
        logout
    };
}
