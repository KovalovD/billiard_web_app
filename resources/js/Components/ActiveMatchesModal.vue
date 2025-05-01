<script lang="ts" setup>
import {computed, ref} from 'vue';
import {Button, Modal} from '@/Components/ui';
import type {MatchGame} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth';
import {apiClient} from '@/lib/apiClient';

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

// Decline a challenge
const declineChallenge = async (match: MatchGame) => {
    if (!match || !match.league_id || isProcessing.value) return;

    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${match.league_id}/players/match-games/${match.id}/decline`, {
            method: 'post'
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
        <div class="p-6 space-y-4">
            <p v-if="matchesCount === 0" class="text-center text-gray-500">
                No active matches at the moment.
            </p>

            <div v-else class="space-y-6">
                <div v-for="match in activeMatches" :key="match.id"
                     class="border rounded-lg p-5 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <div class="flex justify-between items-start mb-5">
                        <h3 class="font-medium text-lg">
                            {{ isChallengeSender(match) ? 'Your challenge to' : 'Challenge from' }}
                            {{
                                isChallengeSender(match)
                                    ? formatPlayerName(match.secondPlayer?.user)
                                    : formatPlayerName(match.firstPlayer?.user)
                            }}
                        </h3>

                        <div class="text-right">
                          <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ new Date(match.invitation_sent_at || match.created_at).toLocaleDateString() }}
                          </span>
                        </div>
                    </div>

                    <!-- Player Stats Comparison -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-md shadow-sm">
                            <h4 class="font-medium mb-2 text-blue-600 dark:text-blue-400">
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
                                        {{
                                            match.firstPlayer?.rating?.wins_count || 0
                                        }}/{{
                                            (match.firstPlayer?.rating?.matches_count || 0) - (match.firstPlayer?.rating?.wins_count || 0)
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 p-4 rounded-md shadow-sm">
                            <h4 class="font-medium mb-2 text-green-600 dark:text-green-400">
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
                                        {{
                                            match.secondPlayer?.rating?.wins_count || 0
                                        }}/{{
                                            (match.secondPlayer?.rating?.matches_count || 0) - (match.secondPlayer?.rating?.wins_count || 0)
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Match Analysis -->
                    <div class="bg-gray-100 dark:bg-gray-700/50 p-3 rounded-md mb-5">
                        <div class="flex flex-wrap justify-between">
                            <div class="mr-4">
                                <span class="text-gray-600 dark:text-gray-300">Rating Difference:</span>
                                <span :class="{
                                  'text-green-600 dark:text-green-400': getRatingDifference(match.firstPlayer?.rating.rating || match.first_rating_before_game, match.secondPlayer?.rating.rating || match.second_rating_before_game) <= 0,
                                  'text-red-600 dark:text-red-400': getRatingDifference(match.firstPlayer?.rating.rating || match.first_rating_before_game, match.secondPlayer?.rating.rating || match.second_rating_before_game) > 0
                                }" class="ml-1 font-medium">
                                  {{
                                        getRatingDifference(match.firstPlayer?.rating.rating || match.first_rating_before_game, match.secondPlayer?.rating.rating || match.second_rating_before_game)
                                    }}
                                </span>
                            </div>
                            <div class="mr-4">
                                <span class="text-gray-600 dark:text-gray-300">Win Probability:</span>
                                <span class="ml-1 font-medium">
                                  {{
                                        getWinProbability(
                                            isChallengeSender(match)
                                                ? (match.secondPlayer?.rating.rating || match.second_rating_before_game)
                                                : (match.firstPlayer?.rating.rating || match.first_rating_before_game),
                                            isChallengeSender(match)
                                                ? (match.firstPlayer?.rating.rating || match.first_rating_before_game)
                                                : (match.secondPlayer?.rating.rating || match.second_rating_before_game)
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
                    <div v-if="match.details" class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-md mb-5">
                        <p class="text-gray-700 dark:text-gray-300 italic">
                            "{{ match.details }}"
                        </p>
                    </div>

                    <!-- Action Buttons - Different actions based on sender/receiver -->
                    <div class="flex justify-end space-x-3">
                        <Link :href="`/leagues/${match.league_id}`">
                            <Button class="bg-gray-200 dark:bg-gray-700 border-0" variant="outline">
                                View League
                            </Button>
                        </Link>

                        <!-- Receiver-only: Decline button -->
                        <Button
                            v-if="!isChallengeSender(match)"
                            :disabled="isProcessing"
                            class="border border-red-300 text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20"
                            variant="outline"
                            @click="declineChallenge(match)"
                        >
                            {{ isProcessing ? 'Processing...' : 'Decline Match' }}
                        </Button>

                        <!-- Both sender and receiver: Submit Result -->
                        <Link :href="`/leagues/${match.league_id}?matchId=${match.id}`">
                            <Button class="bg-black text-white dark:bg-white dark:text-black">
                                Submit Result
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <Button class="rounded-md border border-gray-300" variant="outline" @click="emit('close')">
                    Close
                </Button>
            </div>
        </div>
    </Modal>
</template>
