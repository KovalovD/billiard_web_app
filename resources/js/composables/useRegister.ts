import {ref} from 'vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, RegisterCredentials, User} from '@/types/api';

export function useRegister() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const validationErrors = ref<Record<string, string[]>>({});

    const register = async (credentials: RegisterCredentials): Promise<User | null> => {
        isLoading.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
            // Call the API to register the user
            const response = await apiClient<{ user: User; token: string }>('/api/auth/register', {
                method: 'post',
                data: credentials
            });

            // If successful, store the token and update the auth state
            if (response.token) {
                localStorage.setItem('authToken', response.token);
                localStorage.setItem('authDeviceName', 'web-' + Date.now());

                // Set auth header for future requests
                if (apiClient.defaults && apiClient.defaults.headers) {
                    apiClient.defaults.headers.common['Authorization'] = `Bearer ${response.token}`;
                }

                return response.user;
            }

            return null;
        } catch (err: any) {
            const apiError = err as ApiError;

            // Handle validation errors
            if (apiError.data?.errors) {
                validationErrors.value = apiError.data.errors;
                error.value = 'Please correct the errors in the form.';
            } else {
                error.value = apiError.message || 'Registration failed. Please try again.';
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
        getError
    };
}
