// resources/js/lib/apiClient.ts

import axiosInstance from '@/bootstrap';
import type { ApiError } from '@/Types/api';
import { ref } from 'vue';

// Token and deviceName management with reactivity
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
        axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
        console.log('[apiClient] Authorization header set in axios.');
    } else {
        localStorage.removeItem('authToken');
        localStorage.removeItem('authDeviceName');
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

/**
 * Function for making API requests using axios.
 * Authorization header is added automatically if token is set via setToken.
 */
export async function apiClient<T>(
    endpoint: string,
    options: import('axios').AxiosRequestConfig = {}
): Promise<T> {
    try {
        // Ensure auth header is set if we have a token
        const headers: Record<string, string> = {
            ...options.headers
        };

        if (apiToken.value) {
            headers['Authorization'] = `Bearer ${apiToken.value}`;
        }

        const config: import('axios').AxiosRequestConfig = {
            method: options.method || 'get',
            url: endpoint,
            headers,
            withCredentials: true, // Important for CSRF protection
            ...options,
        };

        const response = await axiosInstance.request<T>(config);
        return response.data;

    } catch (error: any) {
        console.error('API Client Error:', error);
        const apiError: ApiError = new Error(error.response?.data?.message || error.message || 'API error') as ApiError;
        apiError.response = error.response;
        apiError.data = error.response?.data;
        apiError.status = error.response?.status;

        // Handle 401 Unauthorized - Reset token
        if (error.response?.status === 401 && apiToken.value) {
            console.error('[apiClient] Unauthorized (401). Clearing token.');
            setToken(null, null);
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
