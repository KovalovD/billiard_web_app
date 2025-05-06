// resources/js/bootstrap.ts

import axios from 'axios';

// Make axios available globally (can also import where needed)
// window.axios = axios;

// Set base URL for API requests, if they go to a separate port/domain
// If API is on the same domain as frontend, this may not be needed
// axios.defaults.baseURL = 'http://localhost:8001'; // Specify if API is on different URL

// IMPORTANT: Enable cookie and CSRF token transmission
axios.defaults.withCredentials = true; // Required for CSRF to work - sends cookies with requests
axios.defaults.withXSRFToken = true; // Uses XSRF-TOKEN cookie for X-XSRF-TOKEN header
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
// Standard headers
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

/**
 * Function to fetch CSRF cookie. Must be called BEFORE first login
 * or other POST/PUT/DELETE requests, if session not yet established.
 * Usually called once during app initialization or before login form.
 */
export const fetchCsrfToken = async () => {
    try {
        // Make sure your backend is set up to serve this route (standard for Sanctum)
        await axios.get('/sanctum/csrf-cookie');
        console.log('[Bootstrap] CSRF cookie fetched successfully.');
        return true;
    } catch (error) {
        console.error('[Bootstrap] Failed to fetch CSRF cookie:', error);
        // Log detailed error information
        if (axios.isAxiosError(error) && error.response) {
            console.error(`[Bootstrap] Server response: ${error.response.status}`);
        } else {
            console.error('[Bootstrap] Network or other error:', error);
        }
        return false;
    }
};

// Add a response interceptor to handle common errors
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        // Handle network errors
        if (!error.response) {
            console.error('[Axios] Network error:', error.message);
        }

        // Handle 419 errors (CSRF token mismatch)
        if (error.response?.status === 419) {
            console.error('[Axios] CSRF token mismatch. Will attempt to refresh on next request.');
        }

        return Promise.reject(error);
    },
);

// Initialize - can fetch CSRF token when bootstrap is imported
// Commented out to allow manual control of when to fetch CSRF token
// (async () => {
//   await fetchCsrfToken();
// })();

console.log('[Bootstrap] Axios configured for SPA authentication.');

// Export configured axios instance
export default axios;
