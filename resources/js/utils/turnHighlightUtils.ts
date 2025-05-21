import type { MultiplayerGamePlayer } from '@/types/api';

/**
 * Finds the current turn player in a list of players
 * @param players - Array of multiplayer game players
 * @returns The current turn player or null if not found
 */
export function getCurrentTurnPlayer(players: MultiplayerGamePlayer[]): MultiplayerGamePlayer | null {
    return players.find(player => player.is_current_turn) || null;
}

/**
 * Finds the next turn player based on the current turn player and turn order
 * @param players - Array of multiplayer game players
 * @returns The next turn player or null if not found
 */
export function getNextTurnPlayer(players: MultiplayerGamePlayer[]): MultiplayerGamePlayer | null {
    if (!players.length) return null;

    // Sort players by turn order
    const sortedPlayers = [...players].sort((a, b) => {
        if (a.turn_order === null && b.turn_order !== null) return 1;
        if (a.turn_order !== null && b.turn_order === null) return -1;
        if (a.turn_order === null && b.turn_order === null) return 0;
        return (a.turn_order || 0) - (b.turn_order || 0);
    });

    // Find the current turn player
    const currentPlayer = sortedPlayers.find(p => p.is_current_turn);
    if (!currentPlayer) return sortedPlayers[0]; // If no current player, return the first player

    // Find the index of the current player
    const currentIndex = sortedPlayers.findIndex(p => p.id === currentPlayer.id);
    if (currentIndex === -1) return sortedPlayers[0];

    // Next player is the one after current player in the circular list
    const nextIndex = (currentIndex + 1) % sortedPlayers.length;
    return sortedPlayers[nextIndex];
}

/**
 * Determines if a player is the next player in turn
 * @param player - The player to check
 * @param players - Array of all players
 * @returns True if the player is next in turn, false otherwise
 */
export function isNextTurn(player: MultiplayerGamePlayer, players: MultiplayerGamePlayer[]): boolean {
    const nextPlayer = getNextTurnPlayer(players);
    return nextPlayer?.id === player.id;
}

/**
 * Gets turn order status info for visual styling
 * @param player - The player to check
 * @param players - Array of all players
 * @returns Object with visual styling info
 */
export function getTurnOrderStatus(player: MultiplayerGamePlayer, players: MultiplayerGamePlayer[]): {
    borderClass: string,
    bgClass: string,
    textClass: string,
    badgeClass: string,
    iconClass: string,
    label: string
} {
    if (player.is_current_turn) {
        return {
            borderClass: 'border-green-300',
            bgClass: 'bg-green-50 dark:bg-green-900/20',
            textClass: 'text-green-800 dark:text-green-300',
            badgeClass: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            iconClass: 'text-green-500',
            label: 'Current Turn'
        };
    }

    if (isNextTurn(player, players)) {
        return {
            borderClass: 'border-blue-300',
            bgClass: 'bg-blue-50 dark:bg-blue-900/20',
            textClass: 'text-blue-800 dark:text-blue-300',
            badgeClass: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            iconClass: 'text-blue-500',
            label: 'Next Turn'
        };
    }

    return {
        borderClass: '',
        bgClass: '',
        textClass: '',
        badgeClass: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        iconClass: 'text-gray-400',
        label: ''
    };
}
