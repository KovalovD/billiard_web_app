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

    // Отримати рейтинги користувача в усіх лігах
    const fetchUserRatings = async () => {
        isLoadingRatings.value = true;
        errorRatings.value = null;

        try {
            const response = await apiClient<Rating[]>('/api/user/ratings');
            userRatings.value = response;
            return response;
        } catch (error: any) {
            errorRatings.value = error.message || 'Не вдалося завантажити рейтинги';
            throw error;
        } finally {
            isLoadingRatings.value = false;
        }
    };

    // Отримати історію матчів користувача
    const fetchUserMatches = async () => {
        isLoadingMatches.value = true;
        errorMatches.value = null;

        try {
            const response = await apiClient<MatchGame[]>('/api/user/matches');
            userMatches.value = response;
            return response;
        } catch (error: any) {
            errorMatches.value = error.message || 'Не вдалося завантажити матчі';
            throw error;
        } finally {
            isLoadingMatches.value = false;
        }
    };

    // Отримати загальну статистику користувача
    const fetchOverallStats = async () => {
        isLoadingStats.value = true;
        errorStats.value = null;

        try {
            const response = await apiClient<UserStats>('/api/user/stats');
            overallStats.value = response;
            return response;
        } catch (error: any) {
            errorStats.value = error.message || 'Не вдалося завантажити статистику';
            throw error;
        } finally {
            isLoadingStats.value = false;
        }
    };

    // Отримати статистику за типами ігор
    const fetchGameTypeStats = async () => {
        isLoadingGameTypeStats.value = true;
        errorGameTypeStats.value = null;

        try {
            const response = await apiClient<GameTypeStats>('/api/user/game-type-stats');
            gameTypeStats.value = response;
            return response;
        } catch (error: any) {
            errorGameTypeStats.value = error.message || 'Не вдалося завантажити статистику типів ігор';
            throw error;
        } finally {
            isLoadingGameTypeStats.value = false;
        }
    };

    // Допоміжний метод для отримання всіх статистик одночасно
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
        // Дані
        userRatings,
        userMatches,
        overallStats,
        gameTypeStats,

        // Стан завантаження
        isLoadingRatings,
        isLoadingMatches,
        isLoadingStats,
        isLoadingGameTypeStats,

        // Стан помилок
        errorRatings,
        errorMatches,
        errorStats,
        errorGameTypeStats,

        // Методи
        fetchUserRatings,
        fetchUserMatches,
        fetchOverallStats,
        fetchGameTypeStats,
        fetchAllStats,
    };
}
