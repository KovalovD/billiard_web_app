import {computed, readonly, ref} from 'vue';
import type {Rating, User} from '@/types/api';
import {useAuth} from '@/composables/useAuth';
import {useUserStats} from '@/composables/useUserStats';

// Global user store to avoid prop drilling
const user = ref<User | null>(null);
const userRatings = ref<Rating[]>([]);
const isInitialized = ref(false);

export function useUserStore() {
    const auth = useAuth();
    const stats = useUserStats();

    const initialize = async () => {
        if (isInitialized.value) return;

        try {
            await auth.initializeAuth();
            user.value = auth.user.value;

            if (user.value) {
                const {data} = await stats.fetchUserRatings();
                userRatings.value = data.value || [];
            }

            isInitialized.value = true;
        } catch (error) {
            console.error('Failed to initialize user store:', error);
        }
    };

    const updateUser = (userData: Partial<User>) => {
        if (user.value) {
            user.value = {...user.value, ...userData};
        }
    };

    const highestRating = computed(() => {
        if (!userRatings.value.length) return 0;
        return Math.max(...userRatings.value.map(r => r.rating));
    });

    const activeLeaguesCount = computed(() => {
        return userRatings.value.filter(r => r.is_active).length;
    });

    return {
        // State
        user: readonly(user),
        userRatings: readonly(userRatings),
        isInitialized: readonly(isInitialized),

        // Actions
        initialize,
        updateUser,

        // Getters
        highestRating,
        activeLeaguesCount,
    };
}
