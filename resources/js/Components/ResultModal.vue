<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'; // Добавил computed
import { apiClient } from '@/lib/apiClient';
import type { MatchGame, SendResultPayload, ApiError, User } from '@/Types/api';
import { Modal, Button, Input, Label, Spinner } from '@/Components/ui';
import InputError from '@/Components/InputError.vue';

interface Props {
    show: boolean;
    matchGame: MatchGame | null;
    currentUser: User | null; // Нужен для определения, кто первый/второй
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

// Определяем, кто sender/receiver для правильного отображения полей
// Используем computed для реактивности
const sender = computed<Player | null>(() => {
    if (!props.matchGame) return null;
    // TODO: Загрузить или получить данные User по ID, если они не вложены
    // Пока используем ID как плейсхолдер
    return props.matchGame.sender ?? { id: props.matchGame.sender_id, name: `Player ${props.matchGame.sender_id}` };
});
const receiver = computed<Player | null>(() => {
    if (!props.matchGame) return null;
    // TODO: Загрузить или получить данные User по ID
    return props.matchGame.receiver ?? { id: props.matchGame.receiver_id, name: `Player ${props.matchGame.receiver_id}` };
});


// Сброс формы при открытии
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.first_user_score = props.matchGame?.sender_score ?? 0; // Предзаполняем, если есть
        form.second_user_score = props.matchGame?.receiver_score ?? 0; // Предзаполняем, если есть
        formErrors.value = {};
        generalError.value = null;
    }
});

const submitResult = async () => {
    if (!props.matchGame) return;

    isLoading.value = true;
    formErrors.value = {};
    generalError.value = null;

    // Уточняем payload на основе SendResultRequest/DTO
    // Предполагаем, что бэкенд ожидает first_user_score / second_user_score
    const payload: SendResultPayload = {
        first_user_score: form.first_user_score,
        second_user_score: form.second_user_score,
    };

    try {
        await apiClient<boolean>(`/api/leagues/${props.matchGame.league_id}/players/match-games/${props.matchGame.id}/send-result`, {
            method: 'post', // Используем post для axios
            data: payload   // Используем data для axios
        });
        emit('success', `Result submitted for match!`);
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
                <strong class="text-gray-800 dark:text-gray-200">{{ sender?.name ?? 'Player 1' }}</strong> and
                <strong class="text-gray-800 dark:text-gray-200">{{ receiver?.name ?? 'Player 2' }}</strong>.
            </p>
            <p v-if="sender && receiver" class="text-xs text-gray-500 dark:text-gray-400">
                (Assuming {{ sender.name }} is Player 1 / Sender)
            </p>

            <div class="grid grid-cols-2 gap-4 items-start"> <div>
                <Label :for="`sender_score_${matchGame?.id}`">{{ sender?.name ?? 'Player 1' }} Score</Label>
                <Input :id="`sender_score_${matchGame?.id}`" class="mt-1" type="number" min="0" v-model.number="form.first_user_score" required :disabled="isLoading" />
                <InputError :message="formErrors.first_user_score?.join(', ')" class="mt-1" />
            </div>
                <div>
                    <Label :for="`receiver_score_${matchGame?.id}`">{{ receiver?.name ?? 'Player 2' }} Score</Label>
                    <Input :id="`receiver_score_${matchGame?.id}`" class="mt-1" type="number" min="0" v-model.number="form.second_user_score" required :disabled="isLoading" />
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

        </form> </Modal>
</template>
