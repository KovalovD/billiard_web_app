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
const { t } = useLocale();

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

    return props.matchGame.result_confirmed.find((confirmation) => confirmation && typeof confirmation === 'object' && confirmation.key === ratingId);
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
        newErrors.general = [t('Tie games are not allowed. One player must have a higher score.')];
        isValid = false;
    }

    // Check max score if defined
    if (props.maxScore !== null && props.maxScore !== undefined && props.maxScore > 0) {
        // Check if one player has reached the max score
        const isMaxScoreReached = form.first_user_score === props.maxScore || form.second_user_score === props.maxScore;

        // Check if either player exceeded max score
        if (form.first_user_score > props.maxScore) {
            newErrors.first_user_score = [t('Maximum score is :max', {max: props.maxScore})];
            isValid = false;
        }
        if (form.second_user_score > props.maxScore) {
            newErrors.second_user_score = [t('Maximum score is :max', {max: props.maxScore})];
            isValid = false;
        }

        // Check if valid match result (one player must have exactly max score)
        if (isValid && !isMaxScoreReached) {
            newErrors.general = [t('One player must reach exactly :max points to win', {max: props.maxScore})];
            isValid = false;
        }

        // Ensure the player with higher score has exactly max score
        if (isValid) {
            const winnerScore = Math.max(form.first_user_score, form.second_user_score);
            if (winnerScore !== props.maxScore) {
                newErrors.general = [t('The winner must have exactly :max points', {max: props.maxScore})];
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
    (newVal) => {
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
                ? t('Result confirmed! Game completed.')
                : t('Result submitted. Waiting for opponent to confirm.');

        emit('success', message);
        emit('close');
    } catch (error) {
        const apiError = error as ApiError;

        if (apiError.data?.errors) {
            formErrors.value = apiError.data.errors;
        } else {
            generalError.value = apiError.message || t('Failed to send result.');
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
    <Modal :show="show" @close="emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ t('Submit Match Result') }}
            </h2>

            <div class="mt-4">
                <div v-if="isConfirmationNeeded" class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ t('Your opponent has submitted the following result:') }}
                    </p>
                    <div class="mt-2 rounded-md bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-sm">
                            {{ firstPlayer?.name }}: {{ otherPlayerSubmission?.first_user_score }}
                            {{ t('vs') }}
                            {{ secondPlayer?.name }}: {{ otherPlayerSubmission?.second_user_score }}
                        </p>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ t('Please confirm if this result is correct.') }}
                    </p>
                </div>

                <div v-else-if="isUserConfirmed" class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ t('You have already submitted a result for this match.') }}
                    </p>
                </div>

                <div v-else>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label :for="'first_user_score'">{{ firstPlayer?.name }}</Label>
                            <Input
                                id="first_user_score"
                                v-model.number="form.first_user_score"
                                :disabled="isLoading"
                                class="mt-1 block w-full"
                                min="0"
                                type="number"
                            />
                            <InputError :message="formErrors.first_user_score?.[0]" class="mt-2"/>
                        </div>

                        <div>
                            <Label :for="'second_user_score'">{{ secondPlayer?.name }}</Label>
                            <Input
                                id="second_user_score"
                                v-model.number="form.second_user_score"
                                :disabled="isLoading"
                                class="mt-1 block w-full"
                                min="0"
                                type="number"
                            />
                            <InputError :message="formErrors.second_user_score?.[0]" class="mt-2"/>
                        </div>
                    </div>

                    <InputError :message="formErrors.general?.[0]" class="mt-2"/>
                    <InputError :message="generalError" class="mt-2"/>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <Button
                    :disabled="isLoading || isUserConfirmed"
                    class="ml-3"
                    @click="submitResult"
                >
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    {{ isConfirmationNeeded ? t('Confirm Result') : t('Submit Result') }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
