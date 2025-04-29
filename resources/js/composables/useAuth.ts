// resources/js/composables/useAuth.ts

import { ref, computed, readonly } from 'vue';
import { apiClient, setToken, apiToken, getDeviceName } from '@/lib/apiClient';
import type { User, LoginResponse, ApiError } from '@/Types/api';
import { router } from '@inertiajs/vue3';
import axiosInstance from '@/bootstrap';

const user = ref<User | null>(null);
const isLoading = ref(false);
const isActing = ref(false);
const error = ref<string | null>(null);
const isAuthInitialized = ref(false);

// isAuthenticated depends on both initialization flag and user.value
const isAuthenticated = computed(() => isAuthInitialized.value && !!user.value);
const isAdmin = computed(() => isAuthenticated.value && !!user.value?.is_admin);

/**
 * Loads user data via API (/api/auth/user), using Bearer token from apiClient.
 */
async function fetchUser(): Promise<boolean> {
    // Check token via ref from apiClient
    if (!apiToken.value) {
        console.log('[useAuth] fetchUser: No token found in apiClient ref.');
        user.value = null;
        return false;
    }
    isLoading.value = true; error.value = null;
    try {
        console.log('[useAuth] Fetching user data (API token auth)...');
        // apiClient automatically adds Authorization header
        const fetchedUser = await apiClient<User>('/api/auth/user');
        user.value = fetchedUser;
        console.log('[useAuth] User data fetched successfully:', user.value);
        return true;
    } catch (err) {
        console.error("[useAuth] Failed to fetch user:", err);
        user.value = null; // Reset on error
        // If error is 401, token is already reset in apiClient
        if ((err as ApiError).response?.status !== 401) {
            error.value = (err as ApiError).message || 'Failed fetch user.';
        }
        return false;
    } finally { isLoading.value = false; }
}

/**
 * Performs login via API (/api/auth/login), gets and saves token/deviceName.
 */
async function login(credentials: { email: string; password: string }) {
    isActing.value = true; error.value = null; const deviceName = `web-${Date.now()}`;
    try {
        console.log('[useAuth] Attempting login (API token auth)...');
        // Expect response { user: User, token: string }
        const response = await apiClient<LoginResponse>('/api/auth/login', {
            method: 'post',
            data: { ...credentials, deviceName },
        });
        // Check structure { user: ..., token: ... }
        if (response.token && response.user) {
            console.log('[useAuth] Login successful');
            // Save token and deviceName via setToken
            setToken(response.token, deviceName);
            user.value = response.user; // Set user
            isAuthInitialized.value = true; // Set flag
            console.log(`[useAuth] State after login: User set, Initialized=${isAuthInitialized.value}, Authenticated=${isAuthenticated.value}`);
            console.log('[useAuth] Redirecting after login...');
            try {
                const dashboardUrl = route('dashboard');
                router.visit(dashboardUrl, { replace: true });
                console.log('[useAuth] router.visit(dashboard) called.');
            } catch (e) { console.error('[useAuth] Error during route generation or router.visit:', e); }
        } else { throw new Error('Login response from API has unexpected structure.'); }
    } catch (err) {
        console.error("[useAuth] Login failed:", err);
        error.value = (err as ApiError).data?.message || (err as ApiError).message || 'Login failed.';
        setToken(null, null); user.value = null; isAuthInitialized.value = false; throw err;
    } finally { isActing.value = false; }
}

/**
 * Performs logout via API (/api/auth/logout), removing the token.
 */
async function logout() {
    isActing.value = true; error.value = null;
    const currentDeviceName = getDeviceName(); // Get deviceName from apiClient

    // Always reset frontend state immediately
    console.log('[useAuth] Clearing local state for logout...');
    setToken(null, null); // Removes token/deviceName from ref, localStorage and axios headers
    user.value = null;    // Clears user
    isAuthInitialized.value = true; // We know the state - user is null
    console.log(`[useAuth] State after local clear: Initialized=${isAuthInitialized.value}, Authenticated=${isAuthenticated.value}`);

    // Try to call API if we had deviceName
    if (currentDeviceName) {
        try {
            console.log(`[useAuth] Attempting API logout for device: ${currentDeviceName}...`);
            await apiClient<void>('/api/auth/logout', { // Call API logout
                method: 'post',
                data: { deviceName: currentDeviceName } // Pass deviceName
            });
            console.log('[useAuth] API logout successful.');
        } catch (err: any) { console.error("[useAuth] Logout API call failed:", err); error.value = (err as ApiError).message || 'Logout failed on server.'; }
    } else { console.warn("[useAuth] Cannot perform API logout: device name not found."); }

    isActing.value = false;

    // IMPORTANT CHANGE: Try to visit login page with a query param to avoid redirect loops
    try {
        // Add a timestamp to prevent redirect loops due to cached auth state
        const timestamp = new Date().getTime();
        const loginUrl = `/login?t=${timestamp}`;
        console.log('[useAuth] Redirecting to login after logout using window.location:', loginUrl);
        window.location.href = loginUrl;
    } catch (e) {
        console.error('[useAuth] Error redirecting to login, using fallback', e);
        window.location.href = '/login';
    }
}

/**
 * Initialization: check token in apiClient and load user.
 */
async function initializeAuth() {
    console.log('[useAuth] Initializing authentication state (API token auth)...');
    isAuthInitialized.value = false; error.value = null;
    try {
        // Check token that might already be loaded from localStorage in apiClient
        if (apiToken.value) {
            console.log('[useAuth] Token found in apiClient ref. Verifying...');
            // Set header in axios in case it's not set yet
            axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${apiToken.value}`;
            await fetchUser(); // Try to load user with this token
        } else {
            console.log('[useAuth] No token found in apiClient ref.');
            user.value = null;
        }
    } catch (initializationError) {
        console.error('[useAuth] Error during authentication initialization:', initializationError);
        error.value = 'Failed init state.';
        user.value = null;
        setToken(null, null);
    }
    finally {
        isAuthInitialized.value = true;
        console.log('[useAuth] Authentication initialized. Final state - User:', !!user.value, 'Initialized:', isAuthInitialized.value, 'Authenticated:', isAuthenticated.value);
    }
}

export function useAuth() {
    return {
        user: readonly(user), isLoading: readonly(isLoading), isActing: readonly(isActing),
        error: readonly(error), isAuthInitialized: readonly(isAuthInitialized),
        isAuthenticated, // Use updated computed
        isAdmin, login, logout, fetchUser, initializeAuth
    };
}
