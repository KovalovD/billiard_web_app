// resources/js/lib/apiClient.ts

import axiosInstance from '@/bootstrap';
import type { ApiError } from '@/Types/api';
import { ref } from 'vue';

// --- TOKEN AND DEVICENAME MANAGEMENT ---
export const apiToken = ref<string | null>(localStorage.getItem('authToken'));
export const loggedInDeviceName = ref<string | null>(localStorage.getItem('authDeviceName'));

/**
 * Sets/removes token and deviceName in ref and localStorage.
 * Updates Authorization header in axios.
 */
export function setToken(newToken: string | null, deviceName: string | null) {
    console.log('[apiClient] Setting token and deviceName:', { hasToken: !!newToken, deviceName });
    apiToken.value = newToken;
    loggedInDeviceName.value = deviceName;
    if (newToken && deviceName) {
        localStorage.setItem('authToken', newToken);
        localStorage.setItem('authDeviceName', deviceName);
        // Set default header for future axios requests
        axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
        console.log('[apiClient] Authorization header set in axios.');
    } else {
        localStorage.removeItem('authToken');
        localStorage.removeItem('authDeviceName');
        // Remove default header from axios
        delete axiosInstance.defaults.headers.common['Authorization'];
        console.log('[apiClient] Authorization header removed from axios.');
    }
}

/**
 * Returns the stored device name.
 */
export function getDeviceName(): string | null {
    return loggedInDeviceName.value;
}
// --- END OF TOKEN MANAGEMENT BLOCK ---

/**
 * Function for making API requests using axios.
 * Authorization header is added automatically if token is set via setToken.
 */
export async function apiClient<T>(
    endpoint: string,
    options: import('axios').AxiosRequestConfig = {}
): Promise<T> {
    try {
        const config: import('axios').AxiosRequestConfig = {
            method: options.method || 'get',
            url: endpoint, // Make sure endpoint starts with /api or baseURL in bootstrap.ts is set to /api
            ...options,
            // Authorization header will be added by axios if it exists in defaults
        };

        const response = await axiosInstance.request<T>(config);
        return response.data;

    } catch (error: any) {
        console.error('API Client Error (axios):', error);
        const apiError: ApiError = new Error(error.response?.data?.message || error.message || 'API error') as ApiError;
        apiError.response = error.response;
        apiError.data = error.response?.data;
        apiError.status = error.response?.status;

        // Handle 401 Unauthorized - Reset token
        if (error.response?.status === 401 && apiToken.value) { // Only reset if token WAS present
            console.error('[apiClient] Unauthorized (401). Clearing token.');
            setToken(null, null); // Reset token and deviceName
            // Redirect to login is better done from useAuth or component
            // to avoid loops or unexpected redirects
        }

        // Handle validation errors (422)
        if (error.response?.status === 422) {
            apiError.type = 'validation_error';
            console.error('[apiClient] Validation error:', error.response?.data);
        }

        // Handle server errors (500+)
        if (error.response?.status >= 500) {
            apiError.type = 'server_error';
            console.error('[apiClient] Server error:', error.response?.status);
        }

        throw apiError;
    }
}

// Helper functions for common methods
export function get<T>(endpoint: string, params?: object): Promise<T> {
    return apiClient<T>(endpoint, { method: 'get', params });
}

export function post<T>(endpoint: string, data?: object): Promise<T> {
    return apiClient<T>(endpoint, { method: 'post', data });
}

export function put<T>(endpoint: string, data?: object): Promise<T> {
    return apiClient<T>(endpoint, { method: 'put', data });
}

export function del<T>(endpoint: string): Promise<T> {
    return apiClient<T>(endpoint, { method: 'delete' });
}
