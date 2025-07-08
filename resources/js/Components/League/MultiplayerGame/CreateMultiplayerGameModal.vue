<script lang="ts" setup>
import InputError from '@/Components/ui/form/InputError.vue';
import {
    Button,
    Input,
    Label,
    Modal,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner
} from '@/Components/ui';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import type {CreateMultiplayerGamePayload, OfficialRating} from '@/types/api';
import {apiClient} from '@/lib/apiClient';
import {computed, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    leagueId: number | string;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'created']);
const {t} = useLocale();

const {createMultiplayerGame, error, isLoading} = useMultiplayerGames();

const form = ref<CreateMultiplayerGamePayload & {
    official_rating_id?: number | null;
    allow_rebuy?: boolean;
    rebuy_rounds?: number;
    lives_per_new_player?: number;
    enable_penalties?: boolean;
    penalty_rounds_threshold?: number;
}>({
    name: '',
    max_players: null as number | null,
    official_rating_id: null,
    registration_ends_at: '' as string | null,
    allow_player_targeting: false,
    entrance_fee: 300,
    first_place_percent: 60,
    second_place_percent: 20,
    grand_final_percent: 20,
    penalty_fee: 50,
    allow_rebuy: false,
    rebuy_rounds: 5,
    lives_per_new_player: 0,
    enable_penalties: false,
    penalty_rounds_threshold: 3,
});

const validationErrors = ref<Record<string, string[]>>({});
const availableRatings = ref<OfficialRating[]>([]);
const isLoadingRatings = ref(false);

// Computed properties
const percentageSum = computed(() => {
    return form.value.first_place_percent + form.value.second_place_percent + form.value.grand_final_percent;
});

const formattedError = computed(() => {
    if (!error.value) return null;
    return error.value.message || t('An error occurred while creating the game');
});

// Load available ratings when modal opens
const loadRatings = async () => {
    isLoadingRatings.value = true;
    try {
        availableRatings.value = await apiClient<OfficialRating[]>(`/api/official-ratings`);
    } catch (err) {
        console.error('Failed to load ratings:', err);
        availableRatings.value = [];
    } finally {
        isLoadingRatings.value = false;
    }
};

// Reset form when modal opens/closes
watch(() => props.show, (newVal, oldVal) => {
    if (newVal && !oldVal) {
        resetForm();
        loadRatings();
    }
}, {immediate: true});

const resetForm = () => {
    form.value = {
        name: '',
        official_rating_id: null,
        max_players: null,
        registration_ends_at: null,
        allow_player_targeting: false,
        entrance_fee: 300,
        first_place_percent: 60,
        second_place_percent: 20,
        grand_final_percent: 20,
        penalty_fee: 50,
        allow_rebuy: false,
        rebuy_rounds: 5,
        lives_per_new_player: 0,
        enable_penalties: false,
        penalty_rounds_threshold: 3,
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
            official_rating_id: form.value.official_rating_id,
            max_players: form.value.max_players,
            registration_ends_at: form.value.registration_ends_at,
            allow_player_targeting: form.value.allow_player_targeting,
            entrance_fee: form.value.entrance_fee,
            first_place_percent: form.value.first_place_percent,
            second_place_percent: form.value.second_place_percent,
            grand_final_percent: form.value.grand_final_percent,
            penalty_fee: form.value.penalty_fee,
            allow_rebuy: form.value.allow_rebuy,
            rebuy_rounds: form.value.allow_rebuy ? form.value.rebuy_rounds : null,
            lives_per_new_player: form.value.allow_rebuy ? form.value.lives_per_new_player : 0,
            enable_penalties: form.value.enable_penalties,
            penalty_rounds_threshold: form.value.enable_penalties ? form.value.penalty_rounds_threshold : null,
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
                    :placeholder="t('Enter game name')"
                    class="mt-1"
                    required
                    type="text"
                />
                <InputError :message="validationErrors.name?.join(', ')"/>
            </div>

            <!-- Official Rating Selection -->
            <div class="space-y-2">
                <Label for="official_rating">{{ t('Official Rating (Optional)') }}</Label>
                <Select v-model="form.official_rating_id" :disabled="isLoading || isLoadingRatings">
                    <SelectTrigger>
                        <SelectValue
                            :placeholder="isLoadingRatings ? t('Loading ratings...') : t('Select rating or leave empty')"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">{{ t('No rating') }}</SelectItem>
                        <SelectItem
                            v-for="rating in availableRatings"
                            :key="rating.id"
                            :value="rating.id"
                        >
                            {{ rating.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-xs text-gray-500">
                    {{ t('If selected, only players in B or C divisions can join this game') }}
                </p>
                <InputError :message="validationErrors.official_rating_id?.join(', ')"/>
            </div>

            <div class="space-y-2">
                <Label for="max_players">{{ t('Maximum Players (Optional)') }}</Label>
                <Input
                    id="max_players"
                    v-model.number="form.max_players"
                    :disabled="isLoading"
                    :placeholder="t('Leave empty for unlimited')"
                    class="mt-1"
                    min="2"
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
                    :placeholder="t('Leave empty for no end date')"
                    class="mt-1"
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

            <!-- New Rebuy Section -->
            <div class="space-y-2 border-t pt-4">
                <div class="flex items-center space-x-2">
                    <input
                        id="allow_rebuy"
                        v-model="form.allow_rebuy"
                        :disabled="isLoading"
                        class="text-primary focus:ring-primary focus:border-primary focus:ring-opacity-50 h-4 w-4 rounded border-gray-300 shadow-sm"
                        type="checkbox"
                    />
                    <Label for="allow_rebuy">{{ t('Allow Rebuy (Players can join during game)') }}</Label>
                </div>
                <p class="text-xs text-gray-500">
                    {{ t('If enabled, players can join the game while it\'s in progress') }}
                </p>
            </div>

            <div v-if="form.allow_rebuy" class="ml-6 space-y-4">
                <div class="space-y-2">
                    <Label for="rebuy_rounds">{{ t('Number of Rebuy Rounds') }}</Label>
                    <Input
                        id="rebuy_rounds"
                        v-model.number="form.rebuy_rounds"
                        :disabled="isLoading"
                        class="mt-1"
                        min="1"
                        max="20"
                        type="number"
                    />
                    <p class="text-xs text-gray-500">
                        {{ t('How many times players can rebuy into the game') }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="lives_per_new_player">{{ t('Lives Added Per New Player') }}</Label>
                    <Input
                        id="lives_per_new_player"
                        v-model.number="form.lives_per_new_player"
                        :disabled="isLoading"
                        class="mt-1"
                        min="0"
                        max="10"
                        type="number"
                    />
                    <p class="text-xs text-gray-500">
                        {{ t('Number of lives added to ALL players when someone joins (0-10)') }}
                    </p>
                </div>
            </div>

            <!-- Penalties Section -->
            <div class="space-y-2 border-t pt-4">
                <div class="flex items-center space-x-2">
                    <input
                        id="enable_penalties"
                        v-model="form.enable_penalties"
                        :disabled="isLoading"
                        class="text-primary focus:ring-primary focus:border-primary focus:ring-opacity-50 h-4 w-4 rounded border-gray-300 shadow-sm"
                        type="checkbox"
                    />
                    <Label for="enable_penalties">{{ t('Enable Penalties for Early Exit') }}</Label>
                </div>
                <p class="text-xs text-gray-500">
                    {{ t('Players who play less than half the minimum rounds pay a penalty') }}
                </p>
            </div>

            <div v-if="form.enable_penalties" class="ml-6 space-y-4">
                <div class="space-y-2">
                    <Label for="penalty_rounds_threshold">{{ t('Minimum Rounds for No Penalty') }}</Label>
                    <Input
                        id="penalty_rounds_threshold"
                        v-model.number="form.penalty_rounds_threshold"
                        :disabled="isLoading"
                        class="mt-1"
                        min="1"
                        max="10"
                        type="number"
                    />
                    <p class="text-xs text-gray-500">
                        {{ t('Players must play at least half this many rounds to avoid penalty') }}
                    </p>
                </div>
            </div>

            <h3 class="mt-4 font-medium border-t pt-4">{{ t('Financial Settings') }}</h3>

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
                    <InputError :message="validationErrors.first_place_percent?.join(', ')"/>
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
                    <InputError :message="validationErrors.second_place_percent?.join(', ')"/>
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
                    <InputError :message="validationErrors.grand_final_percent?.join(', ')"/>
                </div>
            </div>

            <div v-if="percentageSum !== 100" class="text-sm text-red-600">
                {{ t('Total percentage:') }} {{ percentageSum }}% - {{ t('must equal 100%') }}
            </div>

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
                <InputError :message="validationErrors.penalty_fee?.join(', ')"/>
            </div>

            <div class="flex justify-end space-x-2 pt-4">
                <Button type="button" variant="outline" @click="emit('close')">
                    {{ t('Cancel') }}
                </Button>
                <Button :disabled="isLoading || percentageSum !== 100" type="submit">
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    {{ t('Create Game') }}
                </Button>
            </div>
        </form>
    </Modal>
</template>
