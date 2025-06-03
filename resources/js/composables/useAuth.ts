// resources/js/composables/useAuth.ts
import {fetchCsrfToken} from '@/bootstrap';
import {apiClient, getDeviceName, setToken} from '@/lib/apiClient';
import type {SharedData} from '@/types';
import type {ApiError, LoginResponse, RegisterCredentials, User} from '@/types/api';
import {usePage} from '@inertiajs/vue3';
import {computed, ref} from 'vue';

// --- Reactive State ---
const user = ref<User | null>(null);
const isLoading = ref(false);
const isInitializing = ref(true);
const error = ref<string | null>(null);
const isGuest = ref(true);

// --- Internal state for Singleton pattern ---
let isInitialized = false;

// --- Initialization Logic ---

const initUserFromPageProps = (): boolean => {
    try {
        const page = usePage<SharedData>();
        if (page.props.auth?.user) {
            user.value = page.props.auth.user as unknown as User;
            isGuest.value = false;
            return true;
        }
// eslint-disable-next-line
    } catch (e) {
        // Silent fail for guest users
    }
    return false;
};

const fetchUserFromApi = async (): Promise<boolean> => {
    const token = localStorage.getItem('authToken');
    if (!token) {
        isGuest.value = true;
        return false; // Continue as guest
    }

    if (user.value) {
        isGuest.value = false;
        return true;
    }

    isLoading.value = true;
    error.value = null;

    try {
        setToken(token, localStorage.getItem('authDeviceName'));
        user.value = await apiClient<User>('/api/auth/user');
        isGuest.value = false;
        return true;
// eslint-disable-next-line
    } catch (err: any) {
        setToken(null, null);
        user.value = null;
        isGuest.value = true; // Continue as guest
        return false;
    } finally {
        isLoading.value = false;
    }
};

const initializeAuth = async () => {
    if (isInitialized) {
        isInitializing.value = false;
        return;
    }

    isInitializing.value = true;

    const loadedFromProps = initUserFromPageProps();

    if (!loadedFromProps) {
        await fetchUserFromApi();
    }

    isInitialized = true;
    isInitializing.value = false;
};

// --- Actions ---

const login = async (credentials: { email: string; password: string }): Promise<LoginResponse> => {
    isLoading.value = true;
    error.value = null;
    const deviceName = `web-${Date.now()}`;

    try {
        await fetchCsrfToken();

        const response = await apiClient<LoginResponse>('/api/auth/login', {
            method: 'post',
            data: {
                ...credentials,
                deviceName: deviceName,
            },
        });

        user.value = response.user;
        isGuest.value = false;
        setToken(response.token, deviceName);
        isInitialized = true;
        isInitializing.value = false;
        return response;
    } catch (err: any) {
        const apiError = err as ApiError;
        if (apiError.response?.status === 422 && apiError.data?.errors) {
            const errorMessages = Object.values(apiError.data.errors).flat().join(' ');
            error.value = `Login failed: ${errorMessages}`;
        } else {
            error.value = apiError.message || 'Login failed due to an unknown error.';
        }
        user.value = null;
        isGuest.value = true;
        setToken(null, null);
        throw apiError;
    } finally {
        isLoading.value = false;
    }
};

const register = async (credentials: RegisterCredentials): Promise<User> => {
    isLoading.value = true;
    error.value = null;
    const deviceName = `web-${Date.now()}`;

    try {
        await fetchCsrfToken();

        const response = await apiClient<{ user: User; token: string }>('/api/auth/register', {
            method: 'post',
            data: {
                ...credentials,
                deviceName: deviceName,
            },
        });

        user.value = response.user;
        isGuest.value = false;
        if (response.token) {
            setToken(response.token, deviceName);
        }
        isInitialized = true;
        isInitializing.value = false;
        return response.user;
    } catch (err: any) {
        const apiError = err as ApiError;
        if (apiError.response?.status === 422 && apiError.data?.errors) {
            const errorMessages = Object.values(apiError.data.errors).flat().join(' ');
            error.value = `Registration failed: ${errorMessages}`;
        } else {
            error.value = apiError.message || 'Registration failed due to an unknown error.';
        }
        user.value = null;
        isGuest.value = true;
        setToken(null, null);
        throw apiError;
    } finally {
        isLoading.value = false;
    }
};

const logout = async () => {
    isLoading.value = true;
    error.value = null;
    const currentToken = localStorage.getItem('authToken');
    const currentDeviceName = getDeviceName();

    try {
        if (currentToken && currentDeviceName) {
            await apiClient('/api/auth/logout', {
                method: 'post',
                data: {deviceName: currentDeviceName},
            });
        }
// eslint-disable-next-line
    } catch (err: any) {
        // Even if API call fails, clear client-side state
    } finally {
        user.value = null;
        isGuest.value = true;
        setToken(null, null);
        isInitialized = false;
        isLoading.value = false;
        isInitializing.value = true;
        window.location.href = '/';
    }
};

// --- Computed Properties ---
const isAuthenticated = computed(() => !!user.value && !isGuest.value);
const isAdmin = computed(() => isAuthenticated.value && user.value?.is_admin);

export function useAuth() {
    return {
        // State
        user,
        isLoading,
        isInitializing,
        error,
        isGuest,

        // Computed
        isAuthenticated,
        isAdmin,

        // Methods
        initializeAuth,
        login,
        register,
        logout,
        fetchUserFromApi,
    };
}
