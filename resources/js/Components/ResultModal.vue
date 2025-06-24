// resources/js/Components/ResultModal.vue
<script lang="ts" setup>
import InputError from '@/Components/InputError.vue';
import {Button, Input, Label, Modal, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, MatchGame, Rating, SendResultPayload, User} from '@/types/api';
import {computed, reactive, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    matchGame: MatchGame | null;
    currentUser: User | null;
    maxScore: number | null | undefined;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'success', 'error']);
const {t} = useLocale();

const form = reactive<SendResultPayload>({
    first_user_score: 0,
    second_user_score: 0,
});

const isLoading = ref(false);
const formErrors = ref<Record<string, string[]>>({});
const generalError = ref<string | null>(null);
const isConfirmationNeeded = ref(false);
const isUserConfirmed = ref(false); // Track if the current user has confirmed

// Helper to find a user confirmation in the array
const findUserConfirmation = (ratingId: number) => {
    if (!props.matchGame?.result_confirmed || !Array.isArray(props.matchGame.result_confirmed)) {
        return null;
    }

    return props.matchGame.result_confirmed.find((confirmation: any) => confirmation && typeof confirmation === 'object' && confirmation.key === ratingId);
};

// Determine if the current user has already submitted a result
const hasUserSubmittedResult = computed(() => {
    if (!props.matchGame?.result_confirmed || !props.currentUser) return false;

    const userRatingId = getUserRatingId();
    if (!userRatingId) return false;

    return !!findUserConfirmation(userRatingId);
});

// Get the current user's rating ID for this match
const getUserRatingId = (): number | null => {
    if (!props.matchGame || !props.currentUser) return null;

    if (props.matchGame.firstPlayer?.user?.id === props.currentUser.id) {
        return props.matchGame.first_rating_id;
    } else if (props.matchGame.secondPlayer?.user?.id === props.currentUser.id) {
        return props.matchGame.second_rating_id;
    }

    return null;
};

const firstPlayer = computed(() => {
    if (!props.matchGame) return null;

    // Try to get from firstRating if available
    if (props.matchGame.firstPlayer?.user) {
        return {
            id: props.matchGame.firstPlayer.user.id,
            name: formatPlayerName(props.matchGame.firstPlayer),
        };
    }

    // Fallback to basic info
    return {id: props.matchGame.first_rating_id, name: `Player ${props.matchGame.first_rating_id}`};
});

const secondPlayer = computed(() => {
    if (!props.matchGame) return null;

    // Try to get from secondRating if available
    if (props.matchGame.secondPlayer?.user) {
        return {
            id: props.matchGame.secondPlayer.user.id,
            name: formatPlayerName(props.matchGame.secondPlayer),
        };
    }

    // Fallback to basic info
    return {id: props.matchGame.second_rating_id, name: `Player ${props.matchGame.second_rating_id}`};
});

// Get the other player's rating ID
const getOtherPlayerRatingId = (): number | null => {
    if (!props.matchGame || !props.currentUser) return null;

    const userRatingId = getUserRatingId();
    if (!userRatingId) return null;

    return userRatingId === props.matchGame.first_rating_id ? props.matchGame.second_rating_id : props.matchGame.first_rating_id;
};

// Function to format player name as "Lastname F."
const formatPlayerName = (player: { user: User; rating: Rating }) => {
    if (!player?.user) return null;

    const firstname = player.user.firstname || '';
    const lastname = player.user.lastname || '';
    const firstInitial = firstname.charAt(0);

    return `${lastname} ${firstInitial}.`;
};

// Check if the other player has already submitted a result
const otherPlayerSubmission = computed(() => {
    if (!props.matchGame?.result_confirmed) return null;

    const otherRatingId = getOtherPlayerRatingId();
    if (!otherRatingId) return null;

    const otherConfirmation = findUserConfirmation(otherRatingId);
    if (!otherConfirmation || !otherConfirmation.score) return null;

    const scoreStr = otherConfirmation.score;
    if (!scoreStr.includes('-')) return null;

    const [firstScore, secondScore] = scoreStr.split('-').map(Number);
    return {first_user_score: firstScore, second_user_score: secondScore};
});

// Validate scores
const validateScores = (): boolean => {
    let isValid = true;
    const newErrors: Record<string, string[]> = {};

    // Check tie game
    if (form.first_user_score === form.second_user_score) {
        newErrors.general = ['Tie games are not allowed. One player must have a higher score.'];
        isValid = false;
    }

    // Check max score if defined
    if (props.maxScore !== null && props.maxScore !== undefined && props.maxScore > 0) {
        // Check if one player has reached the max score
        const isMaxScoreReached = form.first_user_score === props.maxScore || form.second_user_score === props.maxScore;

        // Check if either player exceeded max score
        if (form.first_user_score > props.maxScore) {
            newErrors.first_user_score = [`Maximum score is ${props.maxScore}`];
            isValid = false;
        }
        if (form.second_user_score > props.maxScore) {
            newErrors.second_user_score = [`Maximum score is ${props.maxScore}`];
            isValid = false;
        }

        // Check if valid match result (one player must have exactly max score)
        if (isValid && !isMaxScoreReached) {
            newErrors.general = [`One player must reach exactly ${props.maxScore} points to win`];
            isValid = false;
        }

        // Ensure the player with higher score has exactly max score
        if (isValid) {
            const winnerScore = Math.max(form.first_user_score, form.second_user_score);
            if (winnerScore !== props.maxScore) {
                newErrors.general = [`The winner must have exactly ${props.maxScore} points`];
                isValid = false;
            }
        }
    }

    formErrors.value = newErrors;
    return isValid;
};

// Reset form when the modal is opened
watch(
    () => props.show,
    (newVal: any) => {
        if (newVal && props.matchGame) {
            // Use existing scores if available
            form.first_user_score = props.matchGame.first_user_score ?? 0;
            form.second_user_score = props.matchGame.second_user_score ?? 0;
            formErrors.value = {};
            generalError.value = null;

            // Check if current user has confirmed
            isUserConfirmed.value = hasUserSubmittedResult.value;

            // Check if we need to show confirmation UI
            isConfirmationNeeded.value =
                props.matchGame.status === 'must_be_confirmed' && !isUserConfirmed.value && otherPlayerSubmission.value !== null;
        }
    },
);

// Watch for input changes to validate in real-time
watch([() => form.first_user_score, () => form.second_user_score], () => {
    if (!isConfirmationNeeded.value && !isUserConfirmed.value) {
        validateScores();
    }
});

const submitResult = async () => {
    if (!props.matchGame) return;

    // Validate before submitting
    if (!validateScores()) {
        return;
    }

    isLoading.value = true;
    formErrors.value = {};
    generalError.value = null;

    try {
        await apiClient<boolean>(`/api/leagues/${props.matchGame.league_id}/players/match-games/${props.matchGame.id}/send-result`, {
            method: 'post',
            data: {
                first_user_score: form.first_user_score,
                second_user_score: form.second_user_score,
            },
        });

        // Update local state to reflect that this user has confirmed the result
        isUserConfirmed.value = true;

        // Handle different message based on state
        const message =
            props.matchGame.status === 'must_be_confirmed'
                ? 'Result confirmed! Game completed.'
                : 'Result submitted. Waiting for opponent to confirm.';

        emit('success', message);
        emit('close');
    } catch (error) {
        const apiError = error as ApiError;

        if (apiError.data?.errors) {
            formErrors.value = apiError.data.errors;
        } else {
            generalError.value = apiError.message || 'Failed to send result.';
        }

        emit('error', apiError);
    } finally {
        isLoading.value = false;
    }
};

// Handle accepting the other player's result
const acceptOtherPlayerResult = async () => {
    if (!props.matchGame || !otherPlayerSubmission.value) return;

    // Copy the other player's submission
    form.first_user_score = otherPlayerSubmission.value.first_user_score;
    form.second_user_score = otherPlayerSubmission.value.second_user_score;

    // Update local state to reflect that this user has confirmed the result
    isUserConfirmed.value = true;

    // Submit the form with the accepted result
    await submitResult();
};

// Clear confirmation UI state
const rejectOtherPlayerResult = () => {
    isConfirmationNeeded.value = false;
};
</script>

<template>
    <Modal :show="show" :title="t('Submit Match Result')" @close="$emit('close')">
        <form class="space-y-4" @submit.prevent="submitResult">
            <div v-if="generalError"
                 class="rounded bg-red-100 p-3 text-sm text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ generalError }}
            </div>

            <!-- If user has already confirmed, show this message -->
            <div v-if="isUserConfirmed" class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <h3 class="mb-2 font-medium text-green-800 dark:text-green-300">{{
                        t('Your Result Has Been Submitted')
                    }}</h3>
                <p class="mb-3 text-sm text-green-700 dark:text-green-400">
                    {{
                        t('You have already submitted your score for this match. Waiting for your opponent to confirm or the match to be completed.')
                    }}
                </p>
            </div>

            <!-- Show confirmation dialog if needed -->
            <div v-else-if="isConfirmationNeeded && otherPlayerSubmission"
                 class="mb-4 rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                <h3 class="mb-2 font-medium text-blue-800 dark:text-blue-300">{{ t('Confirm Opponent\'s Result') }}</h3>
                <p class="mb-3 text-sm text-blue-700 dark:text-blue-400">{{
                        t('Your opponent has submitted a result:')
                    }}</p>
                <p class="mb-3 text-sm text-blue-700 dark:text-blue-400">
                    {{ firstPlayer?.name }}
                    <strong>{{ otherPlayerSubmission.first_user_score }} - {{
                            otherPlayerSubmission.second_user_score
                        }}</strong>
                    {{ secondPlayer?.name }}
                </p>
                <p class="mb-3 text-sm text-blue-700 dark:text-blue-400">{{ t('Do you confirm this result?') }}</p>
                <div class="mt-4 flex gap-3">
                    <Button class="border-red-300 text-red-600 hover:bg-red-50" type="button" variant="outline"
                            @click="rejectOtherPlayerResult">
                        {{ t('Reject') }}
                    </Button>
                    <Button type="button" @click="acceptOtherPlayerResult">{{ t('Confirm Result') }}</Button>
                </div>
            </div>

            <!-- Hide form input when waiting for confirmation or already submitted -->
            <div v-if="!isConfirmationNeeded && !isUserConfirmed">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t('Enter the final score for the match between') }}
                    <strong class="text-gray-800 dark:text-gray-200">{{ firstPlayer?.name || t('Player 1') }}</strong>
                    {{ t('and') }}
                    <strong class="text-gray-800 dark:text-gray-200">{{ secondPlayer?.name || t('Player 2') }}</strong>.
                </p>

                <div v-if="maxScore" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                    {{
                        t('This match is played to :maxScore wins. One player must reach exactly :maxScore points to win.', {maxScore})
                    }}
                </div>

                <div class="mt-4 grid grid-cols-2 items-start gap-4">
                    <div>
                        <Label :for="`first_score_${matchGame?.id}`">{{ firstPlayer?.name || t('Player 1') }}
                            {{ t('Score') }}</Label>
                        <Input
                            :id="`first_score_${matchGame?.id}`"
                            v-model.number="form.first_user_score"
                            :disabled="isLoading"
                            :max="maxScore || undefined"
                            class="mt-1"
                            min="0"
                            required
                            type="number"
                        />
                        <InputError :message="formErrors.first_user_score?.join(', ')" class="mt-1"/>
                    </div>
                    <div>
                        <Label :for="`second_score_${matchGame?.id}`">{{ secondPlayer?.name || t('Player 2') }}
                            {{ t('Score') }}</Label>
                        <Input
                            :id="`second_score_${matchGame?.id}`"
                            v-model.number="form.second_user_score"
                            :disabled="isLoading"
                            :max="maxScore || undefined"
                            class="mt-1"
                            min="0"
                            required
                            type="number"
                        />
                        <InputError :message="formErrors.second_user_score?.join(', ')" class="mt-1"/>
                    </div>
                </div>

                <!-- Show warning about max score -->
                <div
                    v-if="formErrors.general"
                    class="mt-3 rounded-md bg-red-50 p-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400"
                >
                    {{ formErrors.general[0] }}
                </div>

                <!-- Show warning about status -->
                <div
                    v-if="matchGame?.status === 'must_be_confirmed' && otherPlayerSubmission"
                    class="mt-3 rounded-md bg-amber-50 p-2 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400"
                >
                    {{
                        t('Your opponent submitted a different result. If you submit this, their result will be discarded.')
                    }}
                </div>
            </div>

            <!-- Footer buttons -->
            <div class="flex justify-end space-x-3 pt-4">
                <Button :disabled="isLoading" type="button" variant="outline" @click="$emit('close')">{{
                        t('Close')
                    }}
                </Button>
                <Button
                    v-if="!isConfirmationNeeded && !isUserConfirmed"
                    :disabled="isLoading || form.first_user_score === form.second_user_score || Object.keys(formErrors).length > 0"
                    type="submit"
                >
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    {{ t('Submit Result') }}
                </Button>
            </div>
        </form>
    </Modal>
</template>
