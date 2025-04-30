// resources/js/router-guards.ts
// This is a new file to add route protection logic

import {useAuth} from '@/composables/useAuth';

/**
 * Guards admin routes to ensure user has admin permission
 * Usage example:
 * onMounted(() => {
 *   guardAdminRoute();
 * });
 *
 * @param redirectTo Route to redirect to if not admin (defaults to dashboard)
 * @param options.silent If true, won't log a warning
 * @returns Promise<boolean> - true if user is admin, false if not
 */
export async function guardAdminRoute(
    redirectTo: string = 'dashboard',
    options: { silent?: boolean } = {}
): Promise<boolean> {
    const {isAuthInitialized, isAdmin} = useAuth();

// Wait for auth
