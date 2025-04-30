<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue';
import { apiClient } from '@/lib/apiClient';
import type { MatchGame, SendResultPayload, ApiError, User, Player } from '@/Types/api';
import { Modal, Button, Input, Label, Spinner } from '@/Components/ui';
import InputError from '@/Components/InputError.vue';

interface Props {
    show: boolean;
    matchGame: MatchGame | null;
    currentUser: User | null;
}
const props = defineProps<Props>();
const emit = defineEmits(['close', 'success', 'error']);

const form = reactive<SendResultPayload>({
    first_user_score: 0,
    second_user_score: 0,
});

const isLoading = ref(false);
const formErrors = ref<Record<string, string[]>>({});
const generalError = ref<string | null>(null);

// Get first and second player info for display
const firstPlayer = computed(() => {
    if (!props.matchGame) return null;

    // Try to get from firstRating if available
    if (props.matchGame.firstRating?.user) {
        return {
            id: props.matchGame.firstRating.user.id,
            name: `${props.matchGame.firstRating.user.firstname} ${props.matchGame.firstRating.user.lastname}`
        };
    }

    // Fallback to basic info
    return { id: props.matchGame.first_rating_id, name: `Player ${props.matchGame.first_rating_id}` };
});

const secondPlayer = computed(() => {
    if (!props.matchGame) return null;

    // Try to get from secondRating if available
    if (props.matchGame.secondRating?.user) {
        return {
            id: props.matchGame.secondRating.user.id,
            name: `${props.matchGame.secondRating.user.firstname} ${props.matchGame.secondRating.user.lastname}`
        };
    }

    // Fallback to basic info
    return { id: props.matchGame.second_rating_id, name: `Player ${props.matchGame.second_rating_id}` };
});

// Reset form when the modal is opened
watch(() => props.show, (newVal) => {
    if (newVal && props.matchGame) {
        form.first_user_score = props.matchGame.first_user_score ?? 0;
        form.second_user_score = props.matchGame.second_user_score ?? 0;
        formErrors.value = {};
        generalError.value = null;
    }
});

const submitResult = async () => {
    if (!props.matchGame) return;

    isLoading.value = true;
    formErrors.value = {};
    generalError.value = null;

    try {
        await apiClient<boolean>(`/api/leagues/${props.matchGame.league_id}/players/match-games/${props.matchGame.id}/send-result`, {
            method: 'post',
            data: {
                first_user_score: form.first_user_score,
                second_user_score: form.second_user_score
            }
        });

        emit('success', `Result submitted successfully!`);
        emit('close');
    } catch (error) {
        const apiError = error as ApiError;
        console.error("Failed to send result:", apiError);

        if (apiError.data?.errors) {
            formErrors.value = apiError.data.errors;
        } else {
            generalError.value = apiError.message || "Failed to send result.";
        }

        emit('error', apiError);
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <Modal :show="show" title="Submit Match Result" @close="$emit('close')">
        <form @submit.prevent="submitResult" class="space-y-4">
            <div v-if="generalError" class="text-red-600 text-sm bg-red-100 p-3 rounded dark:bg-red-900/30 dark:text-red-400">
                {{ generalError }}
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">Enter the final score for the match between
                <strong class="text-gray-800 dark:text-gray-200">{{ firstPlayer?.name || 'Player 1' }}</strong> and
                <strong class="text-gray-800 dark:text-gray-200">{{ secondPlayer?.name || 'Player 2' }}</strong>.
            </p>

            <div class="grid grid-cols-2 gap-4 items-start">
                <div>
                    <Label :for="`first_score_${matchGame?.id}`">{{ firstPlayer?.name || 'Player 1' }} Score</Label>
                    <Input
                        :id="`first_score_${matchGame?.id}`"
                        class="mt-1"
                        type="number"
                        min="0"
                        v-model.number="form.first_user_score"
                        required
                        :disabled="isLoading"
                    />
                    <InputError :message="formErrors.first_user_score?.join(', ')" class="mt-1" />
                </div>
                <div>
                    <Label :for="`second_score_${matchGame?.id}`">{{ secondPlayer?.name || 'Player 2' }} Score</Label>
                    <Input
                        :id="`second_score_${matchGame?.id}`"
                        class="mt-1"
                        type="number"
                        min="0"
                        v-model.number="form.second_user_score"
                        required
                        :disabled="isLoading"
                    />
                    <InputError :message="formErrors.second_user_score?.join(', ')" class="mt-1" />
                </div>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <Button type="button" variant="outline" @click="$emit('close')" :disabled="isLoading">
                    Cancel
                </Button>
                <Button type="submit" :disabled="isLoading">
                    <Spinner v-if="isLoading" class="w-4 h-4 mr-2" />
                    Submit Result
                </Button>
            </div>
        </form>
    </Modal>
</template>
