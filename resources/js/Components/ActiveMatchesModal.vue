//resources/js/Components/ActiveMatchesModal.vue
<script lang="ts" setup>
import {Button, Modal} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {apiClient} from '@/lib/apiClient';
import type {MatchGame} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {computed, ref} from 'vue';

interface Props {
    show: boolean;
    activeMatches: MatchGame[];
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'declined']);

const {user} = useAuth();
const isProcessing = ref(false);

const matchesCount = computed(() => props.activeMatches.length);

// Format player name with first initial and last name
const formatPlayerName = (player: any) => {
    if (!player) return 'Unknown Player';

    const firstName = player.firstname || '';
    const lastName = player.lastname || '';
    const firstInitial = firstName.charAt(0);

    return `${lastName} ${firstInitial}.`;
};

// Get the rating difference as a string with sign
const getRatingDifference = (challengerRating: number, yourRating: number) => {
    const diff = challengerRating - yourRating;
    return diff > 0 ? `+${diff}` : `${diff}`;
};

// Calculate win probability using Elo formula
const getWinProbability = (challengerRating: number, yourRating: number) => {
    const ratingDiff = yourRating - challengerRating;
    const exponent = ratingDiff / 400;
    const probability = 1 / (1 + Math.pow(10, -exponent));
    return Math.round(probability * 100);
};

// Check if current user is the sender or receiver
const isChallengeSender = (match: MatchGame) => {
    return match.firstPlayer?.user?.id === user.value?.id;
};

// Check if a match needs confirmation from the current user
const needsConfirmation = (match: MatchGame): boolean => {
    if (!user.value || match.status !== 'must_be_confirmed' || !match.result_confirmed || !Array.isArray(match.result_confirmed)) return false;

    // Get current user's rating ID
    let userRatingId: number | null = null;
    if (match.firstPlayer?.user?.id === user.value.id) {
        userRatingId = match.first_rating_id;
    } else if (match.secondPlayer?.user?.id === user.value.id) {
        userRatingId = match.second_rating_id;
    }

    if (!userRatingId) return false;

    // Check if user has NOT confirmed yet (not found in result_confirmed array)
    const userConfirmation = match.result_confirmed.find(
        (confirmation) => confirmation && typeof confirmation === 'object' && confirmation.key === userRatingId,
    );

    return !userConfirmation;
};

// Get match status display text
const getMatchStatusDisplay = (status: string): string => {
    switch (status) {
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        case 'must_be_confirmed':
            return 'Needs Confirmation';
        default:
            return status;
    }
};

// Decline a challenge
const declineChallenge = async (match: MatchGame) => {
    if (!match || !match.league_id || isProcessing.value) return;

    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${match.league_id}/players/match-games/${match.id}/decline`, {
            method: 'post',
        });
        // Close modal and tell parent to refresh
        emit('close');
        emit('declined');
    } catch (error) {
        console.error('Failed to decline challenge:', error);
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <Modal :show="show" :title="`Active Matches (${matchesCount})`" maxWidth="2xl" @close="emit('close')">
        <div class="space-y-4 p-6">
            <p v-if="matchesCount === 0" class="text-center text-gray-500">No active matches at the moment.</p>

            <div v-else class="space-y-6">
                <div
                    v-for="match in activeMatches"
                    :key="match.id"
                    :class="[
                        'rounded-lg border bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800',
                        needsConfirmation(match)
                            ? 'animate-pulse-slow border-2 border-amber-400 bg-amber-50 shadow-md dark:border-amber-500 dark:bg-amber-900/20'
                            : '',
                    ]"
                >
                    <div class="mb-5 flex items-start justify-between">
                        <h3 class="text-lg font-medium">
                            {{ isChallengeSender(match) ? 'Your challenge to' : 'Challenge from' }}
                            {{
                                isChallengeSender(match) ? formatPlayerName(match.secondPlayer?.user) : formatPlayerName(match.firstPlayer?.user)
                            }}
                            <span
                                v-if="match.status === 'must_be_confirmed'"
                                class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-300"
                            >
                                {{
                                    needsConfirmation(match) ? 'Needs your confirmation' : getMatchStatusDisplay(match.status)
                                }}
                            </span>
                        </h3>

                        <div class="text-right">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ new Date(match.invitation_sent_at || match.created_at).toLocaleDateString() }}
                            </span>
                        </div>
                    </div>

                    <!-- Player Stats Comparison -->
                    <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-md bg-white p-4 shadow-sm dark:bg-gray-700">
                            <h4 class="mb-2 font-medium text-blue-600 dark:text-blue-400">
                                {{ isChallengeSender(match) ? 'You' : 'Challenger' }}
                            </h4>
                            <div class="space-y-2">
                                <p class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Player:</span>
                                    <span class="font-medium">{{ formatPlayerName(match.firstPlayer?.user) }}</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Rating:</span>
                                    <span class="font-medium">{{
                                            match.firstPlayer?.rating.rating || match.first_rating_before_game
                                        }}</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Position:</span>
                                    <span class="font-medium">#{{ match.firstPlayer?.rating.position || 'N/A' }}</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Win/Loss:</span>
                                    <span class="font-medium">
                                        {{ match.firstPlayer?.rating?.wins_count || 0 }}/{{
                                            (match.firstPlayer?.rating?.matches_count || 0) - (match.firstPlayer?.rating?.wins_count || 0)
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="rounded-md bg-white p-4 shadow-sm dark:bg-gray-700">
                            <h4 class="mb-2 font-medium text-green-600 dark:text-green-400">
                                {{ isChallengeSender(match) ? 'Opponent' : 'You' }}
                            </h4>
                            <div class="space-y-2">
                                <p class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Player:</span>
                                    <span class="font-medium">{{ formatPlayerName(match.secondPlayer?.user) }}</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Rating:</span>
                                    <span class="font-medium">{{
                                            match.secondPlayer?.rating.rating || match.second_rating_before_game
                                        }}</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Position:</span>
                                    <span class="font-medium">#{{ match.secondPlayer?.rating.position || 'N/A' }}</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Win/Loss:</span>
                                    <span class="font-medium">
                                        {{ match.secondPlayer?.rating?.wins_count || 0 }}/{{
                                            (match.secondPlayer?.rating?.matches_count || 0) - (match.secondPlayer?.rating?.wins_count || 0)
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Current Score (if match has scores) -->
                    <div
                        v-if="match.first_user_score !== null && match.second_user_score !== null"
                        class="mb-5 rounded-md bg-blue-50 p-3 dark:bg-blue-900/20"
                    >
                        <h4 class="mb-2 font-medium text-blue-800 dark:text-blue-300">Current Score</h4>
                        <div class="flex items-center justify-center text-xl font-bold">
                            <span
                                :class="{ 'text-green-600 dark:text-green-400': match.first_user_score > match.second_user_score }">
                                {{ match.first_user_score }}
                            </span>
                            <span class="mx-2 text-gray-600 dark:text-gray-400">-</span>
                            <span
                                :class="{ 'text-green-600 dark:text-green-400': match.second_user_score > match.first_user_score }">
                                {{ match.second_user_score }}
                            </span>
                        </div>
                        <p
                            v-if="match.status === 'must_be_confirmed' && needsConfirmation(match)"
                            class="mt-2 text-center text-sm text-amber-700 dark:text-amber-400"
                        >
                            This score needs your confirmation
                        </p>
                    </div>

                    <!-- Match Analysis -->
                    <div class="mb-5 rounded-md bg-gray-100 p-3 dark:bg-gray-700/50">
                        <div class="flex flex-wrap justify-between">
                            <div class="mr-4">
                                <span class="text-gray-600 dark:text-gray-300">Rating Difference:</span>
                                <span
                                    :class="{
                                        'text-green-600 dark:text-green-400':
                                            getRatingDifference(
                                                match.firstPlayer?.rating.rating || match.first_rating_before_game,
                                                match.secondPlayer?.rating.rating || match.second_rating_before_game,
                                            ) <= 0,
                                        'text-red-600 dark:text-red-400':
                                            getRatingDifference(
                                                match.firstPlayer?.rating.rating || match.first_rating_before_game,
                                                match.secondPlayer?.rating.rating || match.second_rating_before_game,
                                            ) > 0,
                                    }"
                                    class="ml-1 font-medium"
                                >
                                    {{
                                        getRatingDifference(
                                            match.firstPlayer?.rating.rating || match.first_rating_before_game,
                                            match.secondPlayer?.rating.rating || match.second_rating_before_game,
                                        )
                                    }}
                                </span>
                            </div>
                            <div class="mr-4">
                                <span class="text-gray-600 dark:text-gray-300">Win Probability:</span>
                                <span class="ml-1 font-medium">
                                    {{
                                        getWinProbability(
                                            isChallengeSender(match)
                                                ? match.secondPlayer?.rating.rating || match.second_rating_before_game
                                                : match.firstPlayer?.rating.rating || match.first_rating_before_game,
                                            isChallengeSender(match)
                                                ? match.firstPlayer?.rating.rating || match.first_rating_before_game
                                                : match.secondPlayer?.rating.rating || match.second_rating_before_game,
                                        )
                                    }}%
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-300">League:</span>
                                <span class="ml-1 font-medium">{{ match.league?.name || 'Unknown' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Challenge Message if provided -->
                    <div v-if="match.details" class="mb-5 rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
                        <p class="text-gray-700 italic dark:text-gray-300">"{{ match.details }}"</p>
                    </div>

                    <!-- Action Buttons - Different actions based on sender/receiver -->
                    <div class="flex justify-end space-x-3">
                        <Link :href="`/leagues/${match.league_id}`">
                            <Button class="border-0 bg-gray-200 dark:bg-gray-700" variant="outline"> View League
                            </Button>
                        </Link>

                        <!-- Receiver-only: Decline button (only for in_progress status) -->
                        <Button
                            v-if="!isChallengeSender(match) && match.status === 'in_progress'"
                            :disabled="isProcessing"
                            class="border border-red-300 text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20"
                            variant="outline"
                            @click="declineChallenge(match)"
                        >
                            {{ isProcessing ? 'Processing...' : 'Decline Match' }}
                        </Button>

                        <!-- Both sender and receiver: Submit Result -->
                        <Link :href="`/leagues/${match.league_id}?matchId=${match.id}`">
                            <Button
                                :class="{ 'animate-pulse bg-amber-500 hover:bg-amber-600': needsConfirmation(match) }"
                                class="bg-black text-white dark:bg-white dark:text-black"
                            >
                                {{ needsConfirmation(match) ? 'Confirm Result' : 'Submit Result' }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                <Button class="rounded-md border border-gray-300" variant="outline" @click="emit('close')"> Close
                </Button>
            </div>
        </div>
    </Modal>
</template>
