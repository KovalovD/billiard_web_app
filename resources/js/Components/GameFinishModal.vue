<script lang="ts" setup>
import {Button, Modal, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {MultiplayerGame, OfficialRating} from '@/types/api';
import {ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    game: MultiplayerGame | null;
    leagueId: string | number;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'finished']);
const { t } = useLocale();

const isLoading = ref(false);
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// New: Ratings state
const ratings = ref<OfficialRating[]>([]);
const isLoadingRatings = ref(false);
const ratingsError = ref<string | null>(null);
const selectedRatingId = ref<number | null>(null);

// Fetch ratings for the game type
const fetchRatings = async () => {
    if (!props.game) return;
    isLoadingRatings.value = true;
    ratingsError.value = null;

    try {
        // Use the game type from the game object if available
        const gameType = props.game.game_type;
        if (!gameType) {
            ratings.value = [];
            return;
        }
        // Fetch ratings filtered by game type
        ratings.value = await apiClient(`/api/official-ratings?game_type=${gameType}`);
    } catch (err: any) {
        ratingsError.value = err.message || t('Failed to load official ratings');
        ratings.value = [];
    } finally {
        isLoadingRatings.value = false;
    }
};

// Watch for modal open
watch(() => props.show, (show) => {
    if (show) {
        fetchRatings();
        selectedRatingId.value = null;
    }
});

const finishGame = async () => {
    if (!props.game) return;
    if (ratings.value.length > 0 && !selectedRatingId.value) {
        error.value = t('Please select an official rating');
        return;
    }
    isLoading.value = true;
    error.value = null;
    try {
        const response = await apiClient(`/api/leagues/${props.leagueId}/multiplayer-games/${props.game.id}/finish`, {
            method: 'post',
            data: selectedRatingId.value ? {official_rating_id: selectedRatingId.value} : {},
        });

        // Show success message
        successMessage.value = response.message || t('Game finished successfully');

        // Emit events after a short delay to show the success message
        setTimeout(() => {
            emit('finished', response.game);
            emit('close');
        }, 1000);
    } catch (err: any) {
        error.value = err.message || t('Failed to finish game');
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Modal :show="show" :title="t('Finish Game')" @close="emit('close')">
        <div class="space-y-4 p-4">
            <div v-if="error" class="rounded-md bg-red-50 p-3 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                {{ error }}
            </div>
            <div v-if="successMessage"
                 class="rounded-md bg-green-50 p-3 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ successMessage }}
            </div>
            <!-- New: Official Rating selection -->
            <div v-if="isLoadingRatings" class="text-sm text-gray-500 flex items-center">
                <Spinner class="mr-2 h-4 w-4"/>
                {{ t('Loading official ratings...') }}
            </div>
            <div v-else-if="ratingsError" class="text-sm text-red-600 dark:text-red-400">
                {{ ratingsError }}
            </div>
            <div v-else-if="ratings.length > 0" class="space-y-2">
                <label class="block text-sm font-medium" for="official-rating-select">{{
                        t('Select Official Rating')
                    }}</label>
                <select
                    id="official-rating-select"
                    v-model="selectedRatingId"
                    class="w-full rounded border px-3 py-2 text-sm"
                >
                    <option :value="null">{{ t('Select official rating...') }}</option>
                    <option v-for="rating in ratings" :key="rating.id" :value="rating.id">
                        {{ rating.name }} ({{ rating.game_type_name }})
                    </option>
                </select>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ t('Associate this game with an official rating system for tournament creation.') }}</p>
            </div>
            <div class="text-sm">
                <p class="mb-2">{{ t('Are you sure you want to finish this game?') }}</p>
                <p>{{ t("This will finalize the results and add rating points to all players' league ratings.") }}</p>
                <p class="mt-2 font-medium">{{ t('This action cannot be undone!') }}</p>
            </div>
            <div class="mt-6 flex justify-end space-x-2">
                <Button variant="outline" @click="emit('close')">{{ t('Cancel') }}</Button>
                <Button :disabled="isLoading" variant="destructive" @click="finishGame">
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    {{ t('Finish Game') }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
