// resources/js/composables/useApi.ts
import {ref, Ref} from 'vue';
import type {ApiError} from '@/types/api';

/**
 * Generic composable for handling API data fetching with loading and error states
 * @param fetchFunction Function that makes the API call and returns a promise
 */
export function useApi<T>(fetchFunction: () => Promise<T>) {
    const data = ref<T | null>(null) as Ref<T | null>;
    const isLoading = ref(false);
    const error = ref<ApiError | null>(null);

    const execute = async (): Promise<boolean> => {
        isLoading.value = true;
        error.value = null;
        try {
            data.value = await fetchFunction();
            return true;
        } catch (err) {
            error.value = err as ApiError;
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        data,
        isLoading,
        error,
        execute
    };
}

/**
 * Generic composable for handling API actions (POST, PUT, DELETE) with loading and error states
 * @param actionFunction Function that performs the API action and returns a promise
 */
export function useApiAction<T, P = any>(
    actionFunction: (payload?: P) => Promise<T>
) {
    const isActing = ref(false);
    const error = ref<ApiError | null>(null);

    const execute = async (payload?: P): Promise<boolean> => {
        isActing.value = true;
        error.value = null;
        try {
            await actionFunction(payload);
            return true;
        } catch (err) {
            error.value = err as ApiError;
            return false;
        } finally {
            isActing.value = false;
        }
    };

    return {
        isActing,
        error,
        execute
    };
}
