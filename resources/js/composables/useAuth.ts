// resources/js/composables/useAuth.ts

import { ref, computed, readonly, watch } from 'vue';
// Импортируем обновленные функции, включая apiToken
import { apiClient, setToken, apiToken, getDeviceName } from '@/lib/apiClient';
// Убираем fetchCsrfToken
// import { fetchCsrfToken } from '@/bootstrap';
import type { User, LoginResponse, ApiError } from '@/Types/api'; // LoginResponse { user: User, token: string }
import { router } from '@inertiajs/vue3';

const user = ref<User | null>(null);
const isLoading = ref(false); // Загрузка данных юзера
const isActing = ref(false); // Выполнение логина/логаута
const error = ref<string | null>(null); // Ошибки аутентификации
const isAuthInitialized = ref(false); // Флаг инициализации

// isAuthenticated теперь снова зависит и от флага инициализации, и от user.value
const isAuthenticated = computed(() => isAuthInitialized.value && !!user.value);
const isAdmin = computed(() => isAuthenticated.value && !!user.value?.is_admin);

/**
 * Загружает данные пользователя через API (/api/auth/user), используя Bearer токен из apiClient.
 */
async function fetchUser(): Promise<boolean> {
    // Проверяем токен через ref из apiClient
    if (!apiToken.value) {
        console.log('[useAuth] fetchUser: No token found in apiClient ref.');
        user.value = null;
        return false;
    }
    isLoading.value = true; error.value = null;
    try {
        console.log('[useAuth] Fetching user data (API token auth)...');
        // apiClient автоматически подставит заголовок Authorization
        const fetchedUser = await apiClient<User>('/api/auth/user');
        user.value = fetchedUser;
        console.log('[useAuth] User data fetched successfully:', user.value);
        return true;
    } catch (err) {
        console.error("[useAuth] Failed to fetch user:", err);
        user.value = null; // Сброс при ошибке
        // Если ошибка 401, токен уже сброшен в apiClient
        if ((err as ApiError).response?.status !== 401) {
            error.value = (err as ApiError).message || 'Failed fetch user.';
        }
        return false;
    } finally { isLoading.value = false; }
}

/**
 * Выполняет вход через API (/api/auth/login), получает и сохраняет токен/deviceName.
 */
async function login(credentials: { email: string; password: string }) {
    isActing.value = true; error.value = null; const deviceName = `web-${Date.now()}`;
    try {
        console.log('[useAuth] Attempting login (API token auth)...');
        // Ожидаем ответ { user: User, token: string }
        const response = await apiClient<LoginResponse>('/api/auth/login', {
            method: 'post',
            data: { ...credentials, deviceName },
        });
        // Проверяем структуру { user: ..., token: ... }
        if (response.token && response.user) {
            console.log('[useAuth] Login successful');
            // Сохраняем токен и deviceName через setToken
            setToken(response.token, deviceName);
            user.value = response.user; // Устанавливаем юзера
            isAuthInitialized.value = true; // Ставим флаг
            console.log(`[useAuth] State after login: User set, Initialized=${isAuthInitialized.value}, Authenticated=${isAuthenticated.value}`);
            console.log('[useAuth] Redirecting after login...');
            try {
                const dashboardUrl = route('dashboard'); router.visit(dashboardUrl, { replace: true }); console.log('[useAuth] router.visit(dashboard) called.');
            } catch (e) { console.error('[useAuth] Error during route generation or router.visit:', e); }
        } else { throw new Error('Login response from API has unexpected structure.'); }
    } catch (err) {
        console.error("[useAuth] Login failed:", err);
        error.value = (err as ApiError).data?.message || (err as ApiError).message || 'Login failed.';
        setToken(null, null); user.value = null; isAuthInitialized.value = false; throw err;
    } finally { isActing.value = false; }
}

/**
 * Выполняет выход через API (/api/auth/logout), удаляя токен.
 */
async function logout() {
    isActing.value = true; error.value = null;
    const currentDeviceName = getDeviceName(); // Получаем deviceName из apiClient

    // Всегда сбрасываем состояние на фронте немедленно
    console.log('[useAuth] Clearing local state for logout...');
    setToken(null, null); // Удалит токен/deviceName из ref, localStorage и axios headers
    user.value = null;    // Очистит юзера
    isAuthInitialized.value = true; // Мы знаем состояние - юзера нет
    console.log(`[useAuth] State after local clear: Initialized=${isAuthInitialized.value}, Authenticated=${isAuthenticated.value}`);

    // Пытаемся вызвать API, если был deviceName
    if (currentDeviceName) {
        try {
            console.log(`[useAuth] Attempting API logout for device: ${currentDeviceName}...`);
            await apiClient<void>('/api/auth/logout', { // Вызываем API логаут
                method: 'post',
                data: { deviceName: currentDeviceName } // Передаем deviceName
            });
            console.log('[useAuth] API logout successful.');
        } catch (err: any) { console.error("[useAuth] Logout API call failed:", err); error.value = (err as ApiError).message || 'Logout failed on server.'; }
    } else { console.warn("[useAuth] Cannot perform API logout: device name not found."); }

    isActing.value = false;
    // Редирект на логин
    console.log('[useAuth] Redirecting to login after logout.');
    router.visit(route('login'), { replace: true });
}

/**
 * Инициализация: проверяем токен в apiClient и загружаем пользователя.
 */
async function initializeAuth() {
    console.log('[useAuth] Initializing authentication state (API token auth)...');
    isAuthInitialized.value = false; error.value = null;
    try {
        // Проверяем токен, который уже мог быть загружен из localStorage в apiClient
        if (apiToken.value) {
            console.log('[useAuth] Token found in apiClient ref. Verifying...');
            // Устанавливаем заголовок в axios на случай, если он еще не установлен
            // (хотя setToken в apiClient должен был это сделать)
            axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${apiToken.value}`;
            await fetchUser(); // Пытаемся загрузить юзера по этому токену
        } else {
            console.log('[useAuth] No token found in apiClient ref.');
            user.value = null;
        }
    } catch (initializationError) { console.error('[useAuth] Error during authentication initialization:', initializationError); error.value = 'Failed init state.'; user.value = null; setToken(null, null); }
    finally { isAuthInitialized.value = true; console.log('[useAuth] Authentication initialized. Final state - User:', user.value, 'Initialized:', isAuthInitialized.value, 'Authenticated:', isAuthenticated.value); }
}

// Переимпортируем axiosInstance для установки заголовка в initializeAuth
import axiosInstance from '@/bootstrap';

export function useAuth() {
    return {
        user: readonly(user), isLoading: readonly(isLoading), isActing: readonly(isActing),
        error: readonly(error), isAuthInitialized: readonly(isAuthInitialized),
        isAuthenticated, // Используем обновленный computed
        isAdmin, login, logout, fetchUser, initializeAuth
    };
}
