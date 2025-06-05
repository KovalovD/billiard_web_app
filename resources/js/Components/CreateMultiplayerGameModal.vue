// resources/js/Components/CreateMultiplayerGameModal.vue
<script lang="ts" setup>
import InputError from '@/Components/InputError.vue';
import {Button, Input, Label, Modal, Spinner} from '@/Components/ui';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import {computed, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    leagueId: number | string;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'created']);

const {createMultiplayerGame, isLoading, error} = useMultiplayerGames();
const { t } = useLocale();

const form = ref({
    name: '',
    max_players: null as number | null,
    registration_ends_at: '' as string | null,
    allow_player_targeting: false,
    entrance_fee: 300,
    first_place_percent: 60,
    second_place_percent: 20,
    grand_final_percent: 20,
    penalty_fee: 50,
});

const validationErrors = ref<Record<string, string[]>>({});

const formattedError = computed(() => {
    if (!error.value) return null;
    return t('An error occurred while creating the game');
});

// Check if percentages add up to 100%
const percentageSum = computed(() => {
    return (form.value.first_place_percent || 0) +
        (form.value.second_place_percent || 0) +
        (form.value.grand_final_percent || 0);
});

const percentageError = computed(() => {
    if (percentageSum.value !== 100) {
        return t('Prize percentages must add up to 100%');
    }
    return null;
});

// Reset form when modal opens/closes
watch(() => props.show, (newVal) => {
    if (newVal) {
        resetForm();
    }
});

const resetForm = () => {
    form.value = {
        name: '',
        max_players: null,
        registration_ends_at: null,
        allow_player_targeting: false,
        entrance_fee: 300,
        first_place_percent: 60,
        second_place_percent: 20,
        grand_final_percent: 20,
        penalty_fee: 50,
    };
    validationErrors.value = {};
};

const handleSubmit = async () => {
    try {
        // Validate percentages
        if (percentageSum.value !== 100) {
            validationErrors.value = {
                ...validationErrors.value,
                prize_distribution: [t('Prize percentages must add up to 100%')]
            };
            return;
        }

        validationErrors.value = {};
        const gameData = {
            name: form.value.name,
            max_players: form.value.max_players,
            registration_ends_at: form.value.registration_ends_at,
            allow_player_targeting: form.value.allow_player_targeting,
            entrance_fee: form.value.entrance_fee,
            first_place_percent: form.value.first_place_percent,
            second_place_percent: form.value.second_place_percent,
            grand_final_percent: form.value.grand_final_percent,
            penalty_fee: form.value.penalty_fee,
        };

        const newGame = await createMultiplayerGame(props.leagueId, gameData);
        resetForm();
        emit('created', newGame);
        emit('close');
    } catch (err) {
        // Error is handled by the composable and displayed in the form
        if (err instanceof Error && (err as any).data?.errors) {
            validationErrors.value = (err as any).data.errors;
        }
    }
};
</script>

<template>
    <Modal :show="show" :title="t('Create Multiplayer Game')" @close="emit('close')">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <div v-if="formattedError"
                 class="rounded bg-red-100 p-3 text-sm text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ formattedError }}
            </div>

            <div class="space-y-2">
                <Label for="game_name">{{ t('Game Name') }}</Label>
                <Input
                    id="game_name"
                    v-model="form.name"
                    :disabled="isLoading"
                    class="mt-1"
                    :placeholder="t('Enter game name')"
                    required
                    type="text"
                />
                <InputError :message="validationErrors.name?.join(', ')"/>
            </div>

            <div class="space-y-2">
                <Label for="max_players">{{ t('Maximum Players (Optional)') }}</Label>
                <Input
                    id="max_players"
                    v-model.number="form.max_players"
                    :disabled="isLoading"
                    class="mt-1"
                    min="2"
                    :placeholder="t('Leave empty for unlimited')"
                    type="number"
                />
                <InputError :message="validationErrors.max_players?.join(', ')"/>
            </div>

            <div class="space-y-2">
                <Label for="registration_ends_at">{{ t('Registration End Date/Time (Optional)') }}</Label>
                <Input
                    id="registration_ends_at"
                    v-model="form.registration_ends_at"
                    :disabled="isLoading"
                    class="mt-1"
                    :placeholder="t('Leave empty for no end date')"
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
                    <Label for="allow_player_targeting">{{ t('Allow Player Targeting') }}</Label>
                </div>
                <p class="text-xs text-gray-500">
                    {{ t('If enabled, players can directly add or remove lives from other players.') }}
                    {{ t('Otherwise, only moderators can target other players.') }}
                </p>
                <InputError :message="validationErrors.allow_player_targeting?.join(', ')"/>
            </div>

            <h3 class="mt-4 font-medium">{{ t('Financial Settings') }}</h3>

            <div class="space-y-2">
                <Label for="entrance_fee">{{ t('Entrance Fee') }}</Label>
                <Input
                    id="entrance_fee"
                    v-model.number="form.entrance_fee"
                    :disabled="isLoading"
                    class="mt-1"
                    min="0"
                    type="number"
                />
                <InputError :message="validationErrors.entrance_fee?.join(', ')"/>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="space-y-2">
                    <Label for="first_place_percent">{{ t('1st Place %') }}</Label>
                    <Input
                        id="first_place_percent"
                        v-model.number="form.first_place_percent"
                        :disabled="isLoading"
                        class="mt-1"
                        max="100"
                        min="0"
                        type="number"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="second_place_percent">{{ t('2nd Place %') }}</Label>
                    <Input
                        id="second_place_percent"
                        v-model.number="form.second_place_percent"
                        :disabled="isLoading"
                        class="mt-1"
                        max="100"
                        min="0"
                        type="number"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="grand_final_percent">{{ t('Grand Final %') }}</Label>
                    <Input
                        id="grand_final_percent"
                        v-model.number="form.grand_final_percent"
                        :disabled="isLoading"
                        class="mt-1"
                        max="100"
                        min="0"
                        type="number"
                    />
                </div>
            </div>

            <div v-if="percentageError" class="mt-1 text-sm text-red-600">
                {{ percentageError }}
            </div>
            <InputError :message="validationErrors.prize_distribution?.join(', ')"/>

            <div class="space-y-2">
                <Label for="penalty_fee">{{ t('Penalty Fee') }}</Label>
                <Input
                    id="penalty_fee"
                    v-model.number="form.penalty_fee"
                    :disabled="isLoading"
                    class="mt-1"
                    min="0"
                    type="number"
                />
                <p class="text-xs text-gray-500">
                    {{ t('First half of eliminated players pay this fee, which goes to the time fund.') }}
                </p>
                <InputError :message="validationErrors.penalty_fee?.join(', ')"/>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <Button :disabled="isLoading" type="button" variant="outline" @click="emit('close')"> {{ t('Cancel') }}</Button>
                <Button :disabled="isLoading || percentageError !== null" type="submit">
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    {{ t('Create Game') }}
                </Button>
            </div>
        </form>
    </Modal>
</template>
