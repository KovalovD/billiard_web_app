// resources/js/composables/useMultiplayerGames.ts
import {ref} from 'vue';
import {apiClient} from '@/lib/apiClient';
import type {CreateMultiplayerGamePayload, MultiplayerGame} from '@/types/api';

export function useMultiplayerGames() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Отримати всі мультиплеєрні ігри для ліги
    const getMultiplayerGames = async (leagueId: number | string): Promise<MultiplayerGame[]> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame[]>(`/api/leagues/${leagueId}/multiplayer-games`);
        } catch (err: any) {
            error.value = err.message || 'Не вдалося завантажити мультиплеєрні ігри';
            return [];
        } finally {
            isLoading.value = false;
        }
    };

    // Отримати конкретну мультиплеєрну гру
    const getMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}`);
        } catch (err: any) {
            error.value = err.message || 'Не вдалося завантажити мультиплеєрну гру';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Створити нову мультиплеєрну гру
    const createMultiplayerGame = async (
        leagueId: number | string,
        payload: CreateMultiplayerGamePayload
    ): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games`, {
                method: 'post',
                data: payload
            });
        } catch (err: any) {
            error.value = err.message || 'Не вдалося створити мультиплеєрну гру';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Приєднатися до мультиплеєрної гри
    const joinMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/join`, {
                method: 'post'
            });
        } catch (err: any) {
            error.value = err.message || 'Не вдалося приєднатися до мультиплеєрної гри';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Вийти з мультиплеєрної гри
    const leaveMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/leave`, {
                method: 'post'
            });
        } catch (err: any) {
            error.value = err.message || 'Не вдалося вийти з мультиплеєрної гри';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Почати мультиплеєрну гру (лише адміністратор)
    const startMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/start`, {
                method: 'post'
            });
        } catch (err: any) {
            error.value = err.message || 'Не вдалося почати мультиплеєрну гру';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Скасувати мультиплеєрну гру (лише адміністратор)
    const cancelMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<void> => {
        isLoading.value = true;
        error.value = null;

        try {
            await apiClient(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/cancel`, {
                method: 'post'
            });
        } catch (err: any) {
            error.value = err.message || 'Не вдалося скасувати мультиплеєрну гру';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Виконати дію гри (збільшити/зменшити життя, використати карту, зафіксувати хід)
    const performGameAction = async (
        leagueId: number | string,
        gameId: number | string,
        action: 'increment_lives' | 'decrement_lives' | 'use_card' | 'record_turn' | 'set_turn',
        targetUserId?: number,
        cardType?: 'skip_turn' | 'pass_turn' | 'hand_shot' | 'handicap',
        actingUserId?: number,
        handicapAction?: 'skip_turn' | 'pass_turn' | 'take_life' | string | undefined
    ): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            const payload: Record<string, any> = {action};

            if (targetUserId) payload.target_user_id = targetUserId;
            if (cardType) payload.card_type = cardType;
            if (actingUserId) payload.acting_user_id = actingUserId;
            if (handicapAction) payload.handicap_action = handicapAction;

            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/action`, {
                method: 'post',
                data: payload
            });
        } catch (err: any) {
            error.value = err.message || 'Не вдалося виконати дію гри';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Завершити гру та встановити фінальні позиції
    const finishGame = async (
        leagueId: number | string,
        gameId: number | string,
        positions: { player_id: number, position: number }[]
    ): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/finish`, {
                method: 'post',
                data: {positions}
            });
        } catch (err: any) {
            error.value = err.message || 'Не вдалося завершити гру';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Призначити користувача модератором гри
    const setGameModerator = async (
        leagueId: number | string,
        gameId: number | string,
        userId: number
    ): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/set-moderator`, {
                method: 'post',
                data: {user_id: userId}
            });
        } catch (err: any) {
            error.value = err.message || 'Не вдалося призначити модератора гри';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Отримати фінансовий звіт
    const getFinancialSummary = async (
        leagueId: number | string,
        gameId: number | string
    ): Promise<any> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<any>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/financial-summary`);
        } catch (err: any) {
            error.value = err.message || 'Не вдалося отримати фінансовий звіт';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Отримати підсумок рейтингу
    const getRatingSummary = async (
        leagueId: number | string,
        gameId: number | string
    ): Promise<any> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<any>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/rating-summary`);
        } catch (err: any) {
            error.value = err.message || 'Не вдалося отримати рейтинг';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        isLoading,
        error,
        getMultiplayerGames,
        getMultiplayerGame,
        createMultiplayerGame,
        joinMultiplayerGame,
        leaveMultiplayerGame,
        startMultiplayerGame,
        cancelMultiplayerGame,
        performGameAction,
        finishGame,
        setGameModerator,
        getFinancialSummary,
        getRatingSummary
    };
}
