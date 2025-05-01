// resources/js/composables/useAuth.ts
import {computed, ref} from 'vue';
import {apiClient, getDeviceName, setToken} from '@/lib/apiClient'; // Use apiClient for requests
import {fetchCsrfToken} from '@/bootstrap'; // Import CSRF fetcher
import type {ApiError, LoginResponse, User} from '@/types/api';
import {usePage} from '@inertiajs/vue3';
import type {SharedData} from '@/types';

// --- Reactive State ---
const user = ref<User | null>(null);
const isLoading = ref(false); // General loading state for login/logout/fetch
const isInitializing = ref(true); // Specific state for the initial auth check
const error = ref<string | null>(null); // Error messages

// --- Internal state for Singleton pattern ---
let isInitialized = false; // Tracks if initial auth check has run

// --- Initialization Logic ---

// Attempt to load user from Inertia's shared props
const initUserFromPageProps = (): boolean => {
    try {
        // Check if usePage can be called (might fail if called outside setup)
        const page = usePage<SharedData>();
        if (page.props.auth?.user) {
            user.value = page.props.auth.user as unknown as User;
            console.log('[Auth] User initialized from page props.');
            return true;
        }
    } catch (e) {
        console.warn('[Auth] Failed to get user from page props (might be outside setup):', e);
    }
    return false;
};

// Fetch the current user from the API using the stored token
const fetchUserFromApi = async (): Promise<boolean> => {
    const token = localStorage.getItem('authToken');
    if (!token) {
        console.log('[Auth] No auth token found in storage.');
        return false; // No token, definitely not authenticated via API
    }

    // If user is already set (e.g., from page props), no need to fetch again
    if (user.value) {
        console.log('[Auth] User already loaded, skipping API fetch.');
        return true;
    }

    console.log('[Auth] Attempting to fetch user from API...');
    isLoading.value = true;
    error.value = null;

    try {
        setToken(token, localStorage.getItem('authDeviceName')); // Ensure token is set for apiClient
        const fetchedUser = await apiClient<User>('/api/auth/user'); // Use apiClient
        user.value = fetchedUser;
        console.log('[Auth] User fetched successfully from API.');
        return true;
    } catch (err: any) {
        console.error('[Auth] Failed to fetch user from API:', err);
        error.value = (err as ApiError).message || 'Failed to fetch user data.';
        // Important: If fetching user fails (e.g. invalid token), clear the token
        setToken(null, null);
        user.value = null; // Ensure user state is cleared
        return false;
    } finally {
        isLoading.value = false;
    }
};

// The main initialization function to be called once
const initializeAuth = async () => {
    if (isInitialized) {
        console.log('[Auth] Initialization already run, skipping.');
        isInitializing.value = false; // Ensure loading state is off if skipped
        return;
    }

    console.log('[Auth] Starting initialization...');
    isInitializing.value = true;

    // Try loading from page props first (synchronous attempt)
    const loadedFromProps = initUserFromPageProps();

    // If not loaded from props, try fetching from API based on stored token
    if (!loadedFromProps) {
        await fetchUserFromApi();
    }

    isInitialized = true; // Mark initialization as complete
    isInitializing.value = false;
    console.log('[Auth] Initialization complete. User:', user.value ? user.value.id : 'null');
};

// --- Actions ---

// Login the user
const login = async (credentials: { email: string; password: string }): Promise<LoginResponse> => {
    isLoading.value = true;
    error.value = null;
    const deviceName = `web-${Date.now()}`; // Generate device name

    try {
        // 1. Ensure CSRF cookie is fresh
        await fetchCsrfToken();
        console.log('[Auth] CSRF token fetched for login.');

        // 2. Perform login request
        const response = await apiClient<LoginResponse>('/api/auth/login', { // Use apiClient
            method: 'post',
            data: {
                ...credentials,
                deviceName: deviceName
            }
        });

        // 3. Store user and token
        user.value = response.user;
        setToken(response.token, deviceName); // Store token and device name
        isInitialized = true; // Mark as initialized after successful login
        isInitializing.value = false; // Ensure init loading state is off
        console.log('[Auth] Login successful. User:', response.user.id);
        return response; // Return the full response

    } catch (err: any) {
        console.error('[Auth] Login failed:', err);
        const apiError = err as ApiError;
        if (apiError.response?.status === 422 && apiError.data?.errors) {
            // Handle validation errors specifically
            const errorMessages = Object.values(apiError.data.errors).flat().join(' ');
            error.value = `Login failed: ${errorMessages}`;
        } else {
            error.value = apiError.message || 'Login failed due to an unknown error.';
        }
        // Clear any potentially partially set state
        user.value = null;
        setToken(null, null);
        throw apiError; // Re-throw the original error for the component to handle
    } finally {
        isLoading.value = false;
    }
};

// Logout the user
const logout = async () => {
    isLoading.value = true;
    error.value = null;
    const currentToken = localStorage.getItem('authToken');
    const currentDeviceName = getDeviceName(); // Get device name from apiClient state

    try {
        if (currentToken && currentDeviceName) {
            console.log(`[Auth] Logging out device: ${currentDeviceName}`);
            // Call API to invalidate token on the server
            await apiClient('/api/auth/logout', { // Use apiClient
                method: 'post',
                data: { deviceName: currentDeviceName } // Send device name to logout specific token
            });
            console.log('[Auth] Server logout successful.');
        } else {
            console.log('[Auth] No token/device found, skipping server logout call.');
        }
    } catch (err: any) {
        console.error('[Auth] API logout failed, proceeding with client-side logout:', err);
        // Even if API call fails, clear client-side state
    } finally {
        // Always clear client-side state regardless of API call success
        user.value = null;
        setToken(null, null); // Clear token and device name
        isInitialized = false; // Reset initialization status on logout
        isLoading.value = false;
        isInitializing.value = true; // Reset init loading state
        console.log('[Auth] Client-side logout complete.');

        // Redirect to login page using Inertia or window.location
        // Using window.location for a full page refresh might be safer after logout
        window.location.href = '/login'; // Force reload to clear all state
    }
};

// --- Computed Properties ---
const isAuthenticated = computed(() => !!user.value);
const isAdmin = computed(() => isAuthenticated.value && user.value.is_admin);

// --- Singleton instance ---
// We don't strictly need a singleton instance variable here anymore,
// Vue 3 composables are effectively singletons within the app scope when imported.
// The `isInitialized` flag prevents the core logic from running multiple times.

export function useAuth() {

    // If the composable is used outside of a setup function during initial load,
    // `initializeAuth` might need to be manually called or triggered via `onMounted`
    // in the root component or layout.

    // The core initialization logic is now inside `initializeAuth`.
    // It should be called once, e.g., from the main AppLayout's `onMounted`.

    return {
        // State
        user,
        isLoading,
        isInitializing, // Expose initialization loading state
        error,

        // Computed
        isAuthenticated,
        isAdmin,

        // Methods
        initializeAuth, // Expose initialization function
        login,
        logout,
        fetchUserFromApi // Expose potentially useful for manual refresh
    };
}
