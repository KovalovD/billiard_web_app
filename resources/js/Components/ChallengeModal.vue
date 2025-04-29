<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { apiClient } from '@/lib/apiClient';
import type { Player, League, SendGamePayload, ApiError } from '@/Types/api';
// Импортируем нужные компоненты UI
import { Modal, Button, Input, Label, Textarea, Spinner } from '@/Components/ui';
import InputError from '@/Components/InputError.vue';

interface Props {
    show: boolean;
    league: League | null;
    targetPlayer: Player | null;
}
const props = defineProps<Props>();
const emit = defineEmits(['close', 'success', 'error']);

const form = reactive<SendGamePayload>({
    stream_url: '',
    details: '',
    club_id: null, // TODO: Поле для выбора клуба
});

const isLoading = ref(false);
const formErrors = ref<Record<string, string[]>>({});
const generalError = ref<string | null>(null);

// Сброс формы при открытии/смене игрока
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.stream_url = '';
        form.details = '';
        form.club_id = null;
        formErrors.value = {};
        generalError.value = null;
    }
});

const submitChallenge = async () => {
    if (!props.league || !props.targetPlayer) return;

    isLoading.value = true;
    formErrors.value = {};
    generalError.value = null;

    const payload: Partial<SendGamePayload> = {};
    if (form.stream_url) payload.stream_url = form.stream_url;
    if (form.details) payload.details = form.details;
    if (form.club_id) payload.club_id = form.club_id;

    try {
        // Используем axios из apiClient
        await apiClient<boolean>(`/api/leagues/${props.league.id}/players/${props.targetPlayer.id}/send-match-game`, {
            method: 'post',
            data: payload
        });
        emit('success', `Challenge sent to ${props.targetPlayer.name}!`);
        emit('close');
    } catch (error) {
        const apiError = error as ApiError;
        console.error("Failed to send challenge:", apiError);
        if (apiError.data?.errors) {
            formErrors.value = apiError.data.errors;
        } else {
            generalError.value = apiError.message || "Failed to send challenge.";
        }
        emit('error', apiError);
    } finally {
        isLoading.value = false;
    }
}

</script>

<template>
    <Modal :show="show" :title="`Challenge ${targetPlayer?.name ?? 'Player'}`" @close="$emit('close')">
        <form @submit.prevent="submitChallenge" class="space-y-4">
            <div v-if="generalError" class="text-red-600 text-sm bg-red-100 p-3 rounded dark:bg-red-900/30 dark:text-red-400">
                {{ generalError }}
            </div>

            <div>
                <Label for="challenge_stream_url">Stream URL (Optional)</Label>
                <Input id="challenge_stream_url" class="mt-1" type="url" v-model="form.stream_url" placeholder="https://twitch.tv/..." :disabled="isLoading" />
                <InputError :message="formErrors.stream_url?.join(', ')" class="mt-1" />
            </div>

            <div>
                <Label for="challenge_details">Details (Optional)</Label>
                <Textarea id="challenge_details" class="mt-1" v-model="form.details" placeholder="Any specific challenge details..." :disabled="isLoading" rows="3" />
                <InputError :message="formErrors.details?.join(', ')" class="mt-1" />
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <Button type="button" variant="outline" @click="$emit('close')" :disabled="isLoading">
                    Cancel
                </Button>
                <Button type="submit" :disabled="isLoading">
                    <Spinner v-if="isLoading" class="w-4 h-4 mr-2" />
                    Send Challenge
                </Button>
            </div>
        </form> </Modal>
</template>
