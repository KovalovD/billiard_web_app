// resources/js/bootstrap.ts

import axios from 'axios';

// Делаем axios доступным глобально (можно и импортировать по месту)
// window.axios = axios;

// Устанавливаем базовый URL для API запросов, если они идут на отдельный порт/домен
// Если API на том же домене, что и фронтенд, это может быть не нужно
// axios.defaults.baseURL = 'http://localhost:8001'; // Укажи, если API на другом URL

// ВАЖНО: Включаем передачу куки и заголовков типа CSRF
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true; // Использует XSRF-TOKEN куку для X-XSRF-TOKEN заголовка

// Стандартные заголовки
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

/**
 * Функция для получения CSRF cookie. Ее нужно вызывать ПЕРЕД первым логином
 * или другими POST/PUT/DELETE запросами, если сессия еще не установлена.
 * Обычно вызывается один раз при инициализации приложения или перед формой логина.
 */
export const fetchCsrfToken = async () => {
    try {
        // Убедись, что твой бэкенд настроен обслуживать этот роут (стандартно для Sanctum)
        await axios.get('/sanctum/csrf-cookie');
        console.log('[Bootstrap] CSRF cookie fetched successfully.');
    } catch (error) {
        console.error('[Bootstrap] Failed to fetch CSRF cookie:', error);
        // Обработай ошибку, возможно, аутентификация не будет работать
    }
};


/**
 * Echo / WebSockets (если используешь - оставь и настрой)
 */
// import Echo from 'laravel-echo';
// import Pusher from 'pusher-js';
// window.Pusher = Pusher;
// window.Echo = new Echo({ ... });

console.log('[Bootstrap] Axios configured for SPA authentication.');

// Экспортируем настроенный axios для использования в других модулях
export default axios;
