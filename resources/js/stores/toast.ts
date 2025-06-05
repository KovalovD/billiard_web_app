import {defineStore} from 'pinia';
import {ref} from 'vue';

interface Toast {
    id: string;
    type: 'success' | 'error' | 'info';
    title: string;
    message?: string;
    duration?: number;
}

export const useToastStore = defineStore('toast', () => {
    const toasts = ref<Toast[]>([]);

    const addToast = (toast: Omit<Toast, 'id'>) => {
        const id = Date.now().toString() + Math.random().toString(36).substr(2, 9);
        const newToast: Toast = {
            id,
            duration: 5000,
            ...toast
        };

        toasts.value.push(newToast);

        // Автоматичне видалення через заданий час
        if (newToast.duration && newToast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, newToast.duration);
        }

        return id;
    };

    const removeToast = (id: string) => {
        const index = toasts.value.findIndex(toast => toast.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    const clearAllToasts = () => {
        toasts.value = [];
    };

    // Зручні методи для різних типів повідомлень
    const success = (title: string, message?: string, duration?: number) => {
        return addToast({type: 'success', title, message, duration});
    };

    const error = (title: string, message?: string, duration?: number) => {
        return addToast({type: 'error', title, message, duration});
    };

    const info = (title: string, message?: string, duration?: number) => {
        return addToast({type: 'info', title, message, duration});
    };

    return {
        toasts,
        addToast,
        removeToast,
        clearAllToasts,
        success,
        error,
        info
    };
});
