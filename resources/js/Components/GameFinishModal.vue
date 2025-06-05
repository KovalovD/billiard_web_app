<script lang="ts" setup>
import {Button, Modal, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {MultiplayerGame} from '@/types/api';
import {ref} from 'vue';
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

const finishGame = async () => {
    if (!props.game) return;

    isLoading.value = true;
    error.value = null;

    try {
        await apiClient(`/api/leagues/${props.leagueId}/multiplayer-games/${props.game.id}/finish`, {
            method: 'post'
        });

        emit('finished');
        emit('close');
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
