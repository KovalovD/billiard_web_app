import {apiClient} from '@/lib/apiClient';
import type {ApiError, RegisterCredentials, User} from '@/types/api';
import {ref} from 'vue';

export function useRegister() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const validationErrors = ref<Record<string, string[]>>({});

    const register = async (credentials: RegisterCredentials): Promise<User | null> => {
        isLoading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            // Виклик API для реєстрації користувача
            const response = await apiClient<{ user: User; token: string }>('/api/auth/register', {
                method: 'post',
                data: credentials,
            });

            // У разі успіху зберігаємо токен і оновлюємо стан автентифікації
            if (response.token) {
                localStorage.setItem('authToken', response.token);
                localStorage.setItem('authDeviceName', 'web-' + Date.now());

                // Встановлюємо заголовок авторизації для майбутніх запитів
                if (apiClient.defaults && apiClient.defaults.headers) {
                    apiClient.defaults.headers.common['Authorization'] = `Bearer ${response.token}`;
                }

                return response.user;
            }

            return null;
        } catch (err: any) {
            const apiError = err as ApiError;

            // Обробка помилок валідації
            if (apiError.data?.errors) {
                validationErrors.value = apiError.data.errors;
                error.value = 'Будь ласка, виправте помилки у формі.';
            } else {
                error.value = apiError.message || 'Не вдалося зареєструватися. Спробуйте ще раз.';
            }

            return null;
        } finally {
            isLoading.value = false;
        }
    };

    const hasError = (field: string): boolean => {
        return !!validationErrors.value[field];
    };

    const getError = (field: string): string => {
        if (!validationErrors.value[field]) {
            return '';
        }
        return validationErrors.value[field].join(', ');
    };

    return {
        isLoading,
        error,
        validationErrors,
        register,
        hasError,
        getError,
    };
}
