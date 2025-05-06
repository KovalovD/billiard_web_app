// resources/js/composables/useUserStats.ts

import {apiClient} from '@/lib/apiClient';
import type {GameTypeStats, MatchGame, Rating, UserStats} from '@/types/api';
import {ref} from 'vue';

export function useUserStats() {
    const userRatings = ref<Rating[]>([]);
    const userMatches = ref<MatchGame[]>([]);
    const overallStats = ref<UserStats | null>(null);
    const gameTypeStats = ref<GameTypeStats | null>(null);

    const isLoadingRatings = ref(false);
    const isLoadingMatches = ref(false);
    const isLoadingStats = ref(false);
    const isLoadingGameTypeStats = ref(false);

    const errorRatings = ref<string | null>(null);
    const errorMatches = ref<string | null>(null);
    const errorStats = ref<string | null>(null);
    const errorGameTypeStats = ref<string | null>(null);

    // Fetch user ratings across all leagues
    const fetchUserRatings = async () => {
        isLoadingRatings.value = true;
        errorRatings.value = null;

        try {
            const response = await apiClient<Rating[]>('/api/user/ratings');
            userRatings.value = response;
            return response;
        } catch (error: any) {
            errorRatings.value = error.message || 'Failed to load ratings';
            throw error;
        } finally {
            isLoadingRatings.value = false;
        }
    };

    // Fetch user match history
    const fetchUserMatches = async () => {
        isLoadingMatches.value = true;
        errorMatches.value = null;

        try {
            const response = await apiClient<MatchGame[]>('/api/user/matches');
            userMatches.value = response;
            return response;
        } catch (error: any) {
            errorMatches.value = error.message || 'Failed to load matches';
            throw error;
        } finally {
            isLoadingMatches.value = false;
        }
    };

    // Fetch overall user stats
    const fetchOverallStats = async () => {
        isLoadingStats.value = true;
        errorStats.value = null;

        try {
            const response = await apiClient<UserStats>('/api/user/stats');
            overallStats.value = response;
            return response;
        } catch (error: any) {
            errorStats.value = error.message || 'Failed to load statistics';
            throw error;
        } finally {
            isLoadingStats.value = false;
        }
    };

    // Fetch game type statistics
    const fetchGameTypeStats = async () => {
        isLoadingGameTypeStats.value = true;
        errorGameTypeStats.value = null;

        try {
            const response = await apiClient<GameTypeStats>('/api/user/game-type-stats');
            gameTypeStats.value = response;
            return response;
        } catch (error: any) {
            errorGameTypeStats.value = error.message || 'Failed to load game type statistics';
            throw error;
        } finally {
            isLoadingGameTypeStats.value = false;
        }
    };

    // Utility method to fetch all stats at once
    const fetchAllStats = async () => {
        try {
            await Promise.all([fetchUserRatings(), fetchUserMatches(), fetchOverallStats(), fetchGameTypeStats()]);
            return true;
            // eslint-disable-next-line
        } catch (error) {
            return false;
        }
    };

    return {
        // Data
        userRatings,
        userMatches,
        overallStats,
        gameTypeStats,

        // Loading states
        isLoadingRatings,
        isLoadingMatches,
        isLoadingStats,
        isLoadingGameTypeStats,

        // Error states
        errorRatings,
        errorMatches,
        errorStats,
        errorGameTypeStats,

        // Methods
        fetchUserRatings,
        fetchUserMatches,
        fetchOverallStats,
        fetchGameTypeStats,
        fetchAllStats,
    };
}
