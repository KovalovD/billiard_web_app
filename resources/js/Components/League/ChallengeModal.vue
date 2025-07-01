<script lang="ts" setup>
import InputError from '@/Components/ui/form/InputError.vue';
import {Button, Input, Label, Modal, Spinner, Textarea} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, League, Player, SendGamePayload} from '@/types/api';
import {reactive, ref, watch} from 'vue';

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
    club_id: null,
});

const isLoading = ref(false);
const formErrors = ref<Record<string, string[]>>({});
const generalError = ref<string | null>(null);

// Reset form when the modal is opened or player changes
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            form.stream_url = '';
            form.details = '';
            form.club_id = null;
            formErrors.value = {};
            generalError.value = null;
        }
    },
);

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
        await apiClient<boolean>(`/api/leagues/${props.league.id}/players/${props.targetPlayer.id}/send-match-game`, {
            method: 'post',
            data: payload,
        });
        emit('success', `Challenge sent to ${props.targetPlayer.name}!`);
        emit('close');
    } catch (error) {
        const apiError = error as ApiError;
        if (apiError.data?.errors) {
            formErrors.value = apiError.data.errors;
        } else {
            generalError.value = apiError.message || 'Failed to send challenge.';
        }
        emit('error', apiError);
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Modal :show="show" :title="`Challenge ${targetPlayer?.name ?? 'Player'}`" @close="$emit('close')">
        <form class="space-y-4" @submit.prevent="submitChallenge">
            <div v-if="generalError"
                 class="rounded bg-red-100 p-3 text-sm text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ generalError }}
            </div>

            <div>
                <Label for="challenge_stream_url">Stream URL (Optional)</Label>
                <Input
                    id="challenge_stream_url"
                    v-model="form.stream_url"
                    :disabled="isLoading"
                    class="mt-1"
                    placeholder="https://twitch.tv/..."
                    type="url"
                />
                <InputError :message="formErrors.stream_url?.join(', ')" class="mt-1"/>
            </div>

            <div>
                <Label for="challenge_details">Details (Optional)</Label>
                <Textarea
                    id="challenge_details"
                    v-model="form.details"
                    :disabled="isLoading"
                    class="mt-1"
                    placeholder="Any specific challenge details..."
                    rows="3"
                />
                <InputError :message="formErrors.details?.join(', ')" class="mt-1"/>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <Button :disabled="isLoading" type="button" variant="outline" @click="$emit('close')"> Cancel</Button>
                <Button :disabled="isLoading" type="submit">
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    Send Challenge
                </Button>
            </div>
        </form>
    </Modal>
</template>
