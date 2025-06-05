// resources/js/Components/SetModeratorModal.vue
<script lang="ts" setup>
import {Button, Modal, Spinner} from '@/Components/ui';
import type {MultiplayerGame} from '@/types/api';
import {ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    game: MultiplayerGame | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'set-moderator']);
const { t } = useLocale();

const isLoading = ref(false);
const selectedUserId = ref<number | null>(null);

// Reset selection when modal is opened
const onShowChange = () => {
    if (props.show && props.game) {
        selectedUserId.value = props.game.moderator_user_id;
    }
};

// Watch for show property changes
watch(() => props.show, onShowChange, {immediate: true});

const setModerator = () => {
    if (!selectedUserId.value) {
        alert(t('Please select a player to be the moderator'));
        return;
    }

    isLoading.value = true;
    emit('set-moderator', selectedUserId.value);
    // Modal will be closed by parent component after API call completes
};
</script>

<template>
    <Modal :show="show" :title="t('Set Game Moderator')" @close="emit('close')">
        <div class="space-y-4 p-2">
            <div class="mb-4 text-gray-700 dark:text-gray-300">
                <p>{{ t('Choose a player to be the game moderator. The moderator can perform actions on behalf of any player.') }}</p>
            </div>

            <div class="space-y-2">
                <div
                    v-for="player in game?.active_players"
                    :key="player.id"
                    :class="[
            'flex items-center gap-2 rounded-md border p-3 transition-colors',
            selectedUserId === player.user.id
              ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
              : 'border-gray-200 hover:border-gray-300 dark:border-gray-700'
          ]"
                    @click="selectedUserId = player.user.id"
                >
                    <div class="flex-1">
                        <p class="font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</p>
                        <p v-if="player.user.id === game?.moderator_user_id" class="text-xs text-purple-600">{{ t('Current Moderator') }}</p>
                    </div>

                    <div class="flex h-5 w-5 items-center justify-center rounded-full border border-gray-300">
                        <div
                            v-if="selectedUserId === player.user.id"
                            class="h-3 w-3 rounded-full bg-blue-500"
                        ></div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-2">
                <Button variant="outline" @click="emit('close')">{{ t('Cancel') }}</Button>
                <Button :disabled="!selectedUserId || isLoading" @click="setModerator">
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    {{ t('Set Moderator') }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
