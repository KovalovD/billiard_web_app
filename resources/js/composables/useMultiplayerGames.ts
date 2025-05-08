// resources/js/composables/useMultiplayerGames.ts
import {ref} from 'vue';
import {apiClient} from '@/lib/apiClient';
import type {CreateMultiplayerGamePayload, MultiplayerGame} from '@/types/api';

export function useMultiplayerGames() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Get all multiplayer games for a league
    const getMultiplayerGames = async (leagueId: number | string): Promise<MultiplayerGame[]> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame[]>(`/api/leagues/${leagueId}/multiplayer-games`);
        } catch (err: any) {
            error.value = err.message || 'Failed to load multiplayer games';
            return [];
        } finally {
            isLoading.value = false;
        }
    };

    // Get a specific multiplayer game
    const getMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to load multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Create a new multiplayer game
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
            error.value = err.message || 'Failed to create multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Join a multiplayer game
    const joinMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/join`, {
                method: 'post'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to join multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Leave a multiplayer game
    const leaveMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/leave`, {
                method: 'post'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to leave multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Start a multiplayer game (admin only)
    const startMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/start`, {
                method: 'post'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to start multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Cancel a multiplayer game (admin only)
    const cancelMultiplayerGame = async (leagueId: number | string, gameId: number | string): Promise<void> => {
        isLoading.value = true;
        error.value = null;

        try {
            await apiClient(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/cancel`, {
                method: 'post'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to cancel multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Perform a game action (increment/decrement lives, use card, record turn)
    const performGameAction = async (
        leagueId: number | string,
        gameId: number | string,
        action: 'increment_lives' | 'decrement_lives' | 'use_card' | 'record_turn',
        targetUserId?: number,
        cardType?: 'skip_turn' | 'pass_turn' | 'hand_shot',
        actingUserId?: number
    ): Promise<MultiplayerGame> => {
        isLoading.value = true;
        error.value = null;

        try {
            const payload: Record<string, any> = {action};

            if (targetUserId) payload.target_user_id = targetUserId;
            if (cardType) payload.card_type = cardType;
            if (actingUserId) payload.acting_user_id = actingUserId;

            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/action`, {
                method: 'post',
                data: payload
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to perform game action';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Finish a game and set final positions
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
            error.value = err.message || 'Failed to finish game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Set a user as game moderator
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
            error.value = err.message || 'Failed to set game moderator';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Get financial summary
    const getFinancialSummary = async (
        leagueId: number | string,
        gameId: number | string
    ): Promise<any> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<any>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/financial-summary`);
        } catch (err: any) {
            error.value = err.message || 'Failed to get financial summary';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Get rating summary
    const getRatingSummary = async (
        leagueId: number | string,
        gameId: number | string
    ): Promise<any> => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<any>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/rating-summary`);
        } catch (err: any) {
            error.value = err.message || 'Failed to get rating summary';
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
