import { ref, readonly } from 'vue';
import { apiClient } from '@/lib/apiClient';
import type { ApiError } from '@/Types/api';

/**
 * Composable для упрощения выполнения API запросов с отслеживанием состояния.
 * @param endpoint URL API эндпоинта.
 * @param options Опции для apiClient (fetch).
 * @returns Объект с состоянием загрузки, ошибкой, данными и функцией для выполнения запроса.
 */
export function useApi<T>(
    // Функция, возвращающая Promise с результатом (например, вызов apiClient)
    requestFn: () => Promise<T>
) {
    const data = ref<T | null>(null);
    const isLoading = ref(false);
    const error = ref<ApiError | null>(null);

    const execute = async () => {
        isLoading.value = true;
        error.value = null;
        data.value = null; // Сбрасываем предыдущие данные

        try {
            const result = await requestFn();
            data.value = result;
            return result; // Возвращаем результат для возможной цепочки .then()
        } catch (err) {
            console.error('useApi error:', err);
            error.value = err as ApiError; // Сохраняем ошибку (уже типа ApiError из apiClient)
            throw err; // Перебрасываем дальше
        } finally {
            isLoading.value = false;
        }
    };

    return {
        data: readonly(data), // Данные только для чтения
        isLoading: readonly(isLoading),
        error: readonly(error),
        execute, // Функция для запуска запроса
    };
}

/**
 * Composable для выполнения API запроса, который не возвращает данные (POST, PUT, DELETE).
 * @param requestFn Функция, возвращающая Promise<void> (например, вызов apiClient).
 */
export function useApiAction(
    requestFn: () => Promise<void | any> // void или что-то неважное
) {
    const isActing = ref(false);
    const error = ref<ApiError | null>(null);

    const execute = async (): Promise<boolean> => { // Возвращает true при успехе, false при ошибке
        isActing.value = true;
        error.value = null;
        try {
            await requestFn();
            return true; // Успех
        } catch (err) {
            console.error('useApiAction error:', err);
            error.value = err as ApiError;
            return false; // Ошибка
        } finally {
            isActing.value = false;
        }
    };

    return {
        isActing: readonly(isActing),
        error: readonly(error),
        execute,
    };
}
