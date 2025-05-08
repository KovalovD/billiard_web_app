// resources/js/Components/CreateMultiplayerGameModal.vue
<script lang="ts" setup>
import InputError from '@/Components/InputError.vue';
import {Button, Input, Label, Modal, Spinner} from '@/Components/ui';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import {computed, ref} from 'vue';

interface Props {
    show: boolean;
    leagueId: number | string;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'created']);

const {createMultiplayerGame, isLoading, error} = useMultiplayerGames();

const form = ref({
    name: '',
    max_players: null as number | null,
    registration_ends_at: '' as string | null,
    allow_player_targeting: false,
});

const validationErrors = ref<Record<string, string[]>>({});

const formattedError = computed(() => {
    if (!error.value) return null;

    return 'An error occurred while creating the game';
});

const resetForm = () => {
    form.value = {
        name: '',
        max_players: null,
        registration_ends_at: null,
        allow_player_targeting: false,
    };
    validationErrors.value = {};
};

const handleSubmit = async () => {
    try {
        validationErrors.value = {};
        const gameData = {
            name: form.value.name,
            max_players: form.value.max_players,
            registration_ends_at: form.value.registration_ends_at,
            allow_player_targeting: form.value.allow_player_targeting,
        };

        const newGame = await createMultiplayerGame(props.leagueId, gameData);
        resetForm();
        emit('created', newGame);
        emit('close');
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable and displayed in the form
    }
};
</script>

<template>
    <Modal :show="show" title="Create Multiplayer Game" @close="emit('close')">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <div v-if="formattedError"
                 class="rounded bg-red-100 p-3 text-sm text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ formattedError }}
            </div>

            <div class="space-y-2">
                <Label for="game_name">Game Name</Label>
                <Input
                    id="game_name"
                    v-model="form.name"
                    :disabled="isLoading"
                    class="mt-1"
                    placeholder="Enter game name"
                    required
                    type="text"
                />
                <InputError :message="validationErrors.name?.join(', ')"/>
            </div>

            <div class="space-y-2">
                <Label for="max_players">Maximum Players (Optional)</Label>
                <Input
                    id="max_players"
                    v-model.number="form.max_players"
                    :disabled="isLoading"
                    class="mt-1"
                    min="2"
                    placeholder="Leave empty for unlimited"
                    type="number"
                />
                <InputError :message="validationErrors.max_players?.join(', ')"/>
            </div>

            <div class="space-y-2">
                <Label for="registration_ends_at">Registration End Date/Time (Optional)</Label>
                <Input
                    id="registration_ends_at"
                    v-model="form.registration_ends_at"
                    :disabled="isLoading"
                    class="mt-1"
                    placeholder="Leave empty for no end date"
                    type="datetime-local"
                />
                <InputError :message="validationErrors.registration_ends_at?.join(', ')"/>
            </div>

            <div class="space-y-2">
                <div class="flex items-center space-x-2">
                    <input
                        id="allow_player_targeting"
                        v-model="form.allow_player_targeting"
                        :disabled="isLoading"
                        class="text-primary focus:ring-primary focus:border-primary focus:ring-opacity-50 h-4 w-4 rounded border-gray-300 shadow-sm"
                        type="checkbox"
                    />
                    <Label for="allow_player_targeting">Allow Player Targeting</Label>
                </div>
                <p class="text-xs text-gray-500">
                    If enabled, players can directly add or remove lives from other players.
                    Otherwise, only moderators can target other players.
                </p>
                <InputError :message="validationErrors.allow_player_targeting?.join(', ')"/>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <Button :disabled="isLoading" type="button" variant="outline" @click="emit('close')"> Cancel</Button>
                <Button :disabled="isLoading" type="submit">
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    Create Game
                </Button>
            </div>
        </form>
    </Modal>
</template>
