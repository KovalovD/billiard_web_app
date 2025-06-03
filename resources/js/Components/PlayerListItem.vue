<script lang="ts" setup>
import {Button} from '@/Components/ui';
import type {Rating} from '@/types/api';
import {LogInIcon, SwordsIcon} from 'lucide-vue-next';

interface Props {
    playerRating: Rating;
    leagueId: number;
    isCurrentUser: boolean;
    isAuthenticated: boolean;
    authUserPosition: number | null;
    authUserHaveOngoingMatch: boolean | undefined;
    authUserIsConfirmed: boolean | undefined;
    multiplayerGame: boolean | undefined;
    authUserRating: Rating | null | undefined;
}

const props = defineProps<Props>();
const emit = defineEmits(['challenge']);

const handleChallenge = () => {
    if (props.isAuthenticated) {
        emit('challenge', props.playerRating.player);
    }
};

// Check if player is within challenge range (±10 positions)
const isWithinChallengeRange = (): boolean => {
    if (!props.authUserPosition) return false;

    const positionDiff = Math.abs(props.playerRating.position - props.authUserPosition);
    return positionDiff <= 10;
};

// Check if both players are confirmed
const canChallenge = (playerRating: Rating): boolean => {
    return (
        props.isAuthenticated &&
        props.authUserIsConfirmed &&
        props.playerRating.is_confirmed &&
        !props.playerRating.hasOngoingMatches &&
        isWithinChallengeRange() &&
        !props.authUserHaveOngoingMatch &&
        playerRating.id !== props.authUserRating?.last_player_rating_id
    );
};
</script>

<template>
    <li
        class="flex items-center justify-between rounded border bg-white p-3 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700/50"
    >
        <div class="flex items-center space-x-3">
            <span class="w-6 text-right font-semibold text-gray-500 dark:text-gray-400">{{
                    playerRating.position
                }}.</span>
            <span class="font-medium text-gray-800 dark:text-gray-200">{{ playerRating.player.name }}</span>
            <span v-if="isCurrentUser" class="text-xs font-semibold text-blue-600 dark:text-blue-400">(You)</span>
            <span
                v-if="!playerRating.is_confirmed"
                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-400"
            >
                Pending
            </span>
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ playerRating.rating }}</span>

            <!-- Challenge button for authenticated users -->
            <Button
                v-if="canChallenge(playerRating) && !multiplayerGame"
                size="sm"
                title="Challenge this player"
                variant="outline"
                @click="handleChallenge"
            >
                <SwordsIcon class="mr-1 h-4 w-4"/>
                Challenge
            </Button>

            <!-- Login prompt for guests -->
            <Button
                v-else-if="!isAuthenticated && !isCurrentUser && !multiplayerGame"
                size="sm"
                title="Login to challenge players"
                variant="outline"
                @click="$router.push(route('login'))"
            >
                <LogInIcon class="mr-1 h-4 w-4"/>
                Login
            </Button>

            <!-- Status messages for authenticated users -->
            <span v-else-if="isAuthenticated && !isCurrentUser && !playerRating.is_confirmed"
                  class="text-xs text-amber-600 dark:text-amber-400">
                Waiting for admin confirmation
            </span>
            <span
                v-else-if="isAuthenticated && !isCurrentUser && !isWithinChallengeRange() && !authUserHaveOngoingMatch"
                class="text-xs text-gray-500 dark:text-gray-400"
            >
                Not in challenge range
            </span>
        </div>
    </li>
</template>
