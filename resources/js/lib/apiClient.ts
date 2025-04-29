// resources/js/lib/apiClient.ts

import axiosInstance from '@/bootstrap';
import type { ApiError } from '@/Types/api';
import { ref } from 'vue';

// --- УПРАВЛЕНИЕ ТОКЕНОМ И DEVICENAME ---
const token = ref<string | null>(localStorage.getItem('authToken'));
const loggedInDeviceName = ref<string | null>(localStorage.getItem('authDeviceName'));

/**
 * Устанавливает/удаляет токен и deviceName в ref и localStorage.
 * Обновляет заголовок Authorization в axios.
 */
function setToken(newToken: string | null, deviceName: string | null) {
    console.log('[apiClient] Setting token and deviceName:', { hasToken: !!newToken, deviceName });
    token.value = newToken;
    loggedInDeviceName.value = deviceName;
    if (newToken && deviceName) {
        localStorage.setItem('authToken', newToken);
        localStorage.setItem('authDeviceName', deviceName);
        // Устанавливаем заголовок по умолчанию для будущих запросов axios
        axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
        console.log('[apiClient] Authorization header set in axios.');
    } else {
        localStorage.removeItem('authToken');
        localStorage.removeItem('authDeviceName');
        // Удаляем заголовок по умолчанию из axios
        delete axiosInstance.defaults.headers.common['Authorization'];
        console.log('[apiClient] Authorization header removed from axios.');
    }
}

/**
 * Возвращает сохраненное имя устройства.
 */
function getDeviceName(): string | null {
    return loggedInDeviceName.value;
}
// --- КОНЕЦ БЛОКА УПРАВЛЕНИЯ ТОКЕНОМ ---

/**
 * Функция для выполнения запросов к API с использованием axios.
 * Заголовок Authorization подставляется автоматически, если токен установлен через setToken.
 */
async function apiClient<T>(
    endpoint: string,
    options: import('axios').AxiosRequestConfig = {}
): Promise<T> {
    try {
        const config: import('axios').AxiosRequestConfig = {
            method: options.method || 'get',
            url: endpoint, // Убедись, что endpoint начинается с /api или baseURL в bootstrap.ts настроен на /api
            ...options,
            // Заголовок Authorization будет добавлен axios, если он есть в defaults
        };

        const response = await axiosInstance.request<T>(config);
        return response.data;

    } catch (error: any) {
        console.error('API Client Error (axios):', error);
        const apiError: ApiError = new Error( error.response?.data?.message || error.message || 'API error') as ApiError;
        apiError.response = error.response;
        apiError.data = error.response?.data;

        // Обработка 401 Unauthorized - Сброс токена
        if (error.response?.status === 401 && token.value) { // Сбрасываем только если токен БЫЛ
            console.error('[apiClient] Unauthorized (401). Clearing token.');
            setToken(null, null); // Сбрасываем токен и deviceName
            // Редирект на логин лучше делать из useAuth или компонента
            // чтобы избежать зацикливания или неожиданных редиректов
        }
        throw apiError;
    }
}

// Экспортируем токен для useAuth и функции управления им
export { apiClient, token as apiToken, setToken, getDeviceName }; // Экспортируем token как apiToken
