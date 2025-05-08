// resources/js/composables/useMultiplayerGames.ts

import {apiClient} from '@/lib/apiClient';
import type {MultiplayerGame} from '@/types/api';
import {ref} from 'vue';

export function useMultiplayerGames() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Fetch all multiplayer games for a league
    const getMultiplayerGames = async (leagueId: number | string) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame[]>(`/api/leagues/${leagueId}/multiplayer-games`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch multiplayer games';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Fetch a specific multiplayer game
    const getMultiplayerGame = async (leagueId: number | string, gameId: number | string) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Create a new multiplayer game (admin only)
    const createMultiplayerGame = async (leagueId: number | string, data: {
        name: string;
        max_players?: number;
        registration_ends_at?: string;
        allow_player_targeting?: boolean;
    }) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games`, {
                method: 'post',
                data,
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to create multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Join a multiplayer game
    const joinMultiplayerGame = async (leagueId: number | string, gameId: number | string) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/join`, {
                method: 'post',
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to join multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Leave a multiplayer game
    const leaveMultiplayerGame = async (leagueId: number | string, gameId: number | string) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/leave`, {
                method: 'post',
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to leave multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Start a multiplayer game (admin only)
    const startMultiplayerGame = async (leagueId: number | string, gameId: number | string) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/start`, {
                method: 'post',
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to start multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Cancel a multiplayer game (admin only)
    const cancelMultiplayerGame = async (leagueId: number | string, gameId: number | string) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/cancel`, {
                method: 'post',
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to cancel multiplayer game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Perform a game action
    const performGameAction = async (
        leagueId: number | string,
        gameId: number | string,
        action: 'increment_lives' | 'decrement_lives' | 'use_card' | 'record_turn',
        targetUserId?: number,
        cardType?: 'skip_turn' | 'pass_turn' | 'hand_shot',
        actingUserId?: number
    ) => {
        isLoading.value = true;
        error.value = null;

        try {
            const data: any = {action};
            if (targetUserId) data.target_user_id = targetUserId;
            if (cardType) data.card_type = cardType;
            if (actingUserId) data.acting_user_id = actingUserId;

            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/action`, {
                method: 'post',
                data,
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to perform game action';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Finish game and set final positions
    const finishGame = async (
        leagueId: number | string,
        gameId: number | string,
        positions: { player_id: number, position: number }[]
    ) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/finish`, {
                method: 'post',
                data: {positions},
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to finish game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Set a game moderator
    const setGameModerator = async (
        leagueId: number | string,
        gameId: number | string,
        userId: number
    ) => {
        isLoading.value = true;
        error.value = null;

        try {
            return await apiClient<MultiplayerGame>(`/api/leagues/${leagueId}/multiplayer-games/${gameId}/set-moderator`, {
                method: 'post',
                data: {user_id: userId},
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to set game moderator';
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
    };
}
