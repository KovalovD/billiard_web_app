// resources/js/Components/League/MultiplayerGame/RebuyPlayerModal.vue

<script lang="ts" setup>
import {Button, Checkbox, Input, Label, Modal, Spinner} from '@/Components/ui';
import InputError from '@/Components/ui/form/InputError.vue';
import {apiClient} from '@/lib/apiClient';
import type {MultiplayerGame} from '@/types/api';
import {computed, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    game: MultiplayerGame | null;
    leagueId: string | number;
}

interface AvailablePlayer {
    user: {
        id: number;
        name: string;
        email: string;
    };
    status: 'new' | 'active' | 'eliminated';
    rebuy_count: number;
    suggested_fee: number;
    can_add: boolean;
    reason: string | null;
    lives: number | null;
    eliminated_at: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'added']);
const {t} = useLocale();

const isLoading = ref(false);
const isLoadingPlayers = ref(false);
const error = ref<string | null>(null);
const availablePlayers = ref<AvailablePlayer[]>([]);
const selectedPlayerId = ref<number | null>(null);
const customFee = ref<number>(0);
const isNewPlayer = ref(false);
const validationErrors = ref<Record<string, string[]>>({});

// Computed
const selectedPlayer = computed(() => {
    if (!selectedPlayerId.value) return null;
    return availablePlayers.value.find(p => p.user.id === selectedPlayerId.value);
});

const suggestedFee = computed(() => {
    return selectedPlayer.value?.suggested_fee || props.game?.entrance_fee || 0;
});

const canAddPlayer = computed(() => {
    return selectedPlayer.value?.can_add || false;
});

const playerStatusText = computed(() => {
    if (!selectedPlayer.value) return '';

    switch (selectedPlayer.value.status) {
        case 'new':
            return t('New player - never joined this game');
        case 'active':
            return t('Currently active in game');
        case 'eliminated':
            return t('Eliminated - Rebuy :count of :max', {
                count: selectedPlayer.value.rebuy_count,
                max: props.game?.rebuy_rounds || 0
            });
        default:
            return '';
    }
});

// Load available players
const loadAvailablePlayers = async () => {
    if (!props.game) return;

    isLoadingPlayers.value = true;
    error.value = null;

    try {
        const response = await apiClient<{ players: AvailablePlayer[], game_info: any }>(
            `/api/leagues/${props.leagueId}/multiplayer-games/${props.game.id}/available-players`
        );
        availablePlayers.value = response.players;
    } catch (err: any) {
        error.value = err.message || t('Failed to load available players');
    } finally {
        isLoadingPlayers.value = false;
    }
};

// Watch for modal open
watch(() => props.show, (newVal) => {
    if (newVal) {
        resetForm();
        loadAvailablePlayers();
    }
});

// Watch for player selection
watch(selectedPlayerId, (newVal) => {
    if (newVal) {
        const player = availablePlayers.value.find(p => p.user.id === newVal);
        if (player) {
            customFee.value = player.suggested_fee;
            isNewPlayer.value = player.status === 'new';
        }
    }
});

const resetForm = () => {
    selectedPlayerId.value = null;
    customFee.value = 0;
    isNewPlayer.value = false;
    error.value = null;
    validationErrors.value = {};
};

const handleSubmit = async () => {
    if (!props.game || !selectedPlayerId.value) return;

    isLoading.value = true;
    error.value = null;
    validationErrors.value = {};

    try {
        await apiClient(`/api/leagues/${props.leagueId}/multiplayer-games/${props.game.id}/add-player`, {
            method: 'post',
            data: {
                user_id: selectedPlayerId.value,
                rebuy_fee: customFee.value,
                is_new_player: isNewPlayer.value
            }
        });

        emit('added');
        emit('close');
    } catch (err: any) {
        if (err.data?.errors) {
            validationErrors.value = err.data.errors;
        } else {
            error.value = err.message || t('Failed to add player');
        }
    } finally {
        isLoading.value = false;
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('uk-UA', {style: 'currency', currency: 'UAH'})
        .format(amount)
        .replace('UAH', '₴');
};
</script>

<template>
    <Modal :show="show" :title="t('Add Player During Game')" @close="emit('close')">
        <div class="space-y-4 p-4">
            <!-- Error display -->
            <div v-if="error" class="rounded-md bg-red-50 p-3 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                {{ error }}
            </div>

            <!-- Loading state -->
            <div v-if="isLoadingPlayers" class="text-center py-8">
                <Spinner class="mx-auto h-8 w-8 text-indigo-600"/>
                <p class="mt-2 text-gray-500">{{ t('Loading available players...') }}</p>
            </div>

            <!-- Player selection -->
            <div v-else class="space-y-4">
                <!-- Game info -->
                <div v-if="game" class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">{{ t('Base entrance fee:') }}</span>
                            <span class="font-medium ml-1">{{ formatCurrency(game.entrance_fee) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">{{ t('Max rebuys:') }}</span>
                            <span class="font-medium ml-1">{{ game.rebuy_rounds }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">{{ t('Lives per new player:') }}</span>
                            <span class="font-medium ml-1">{{ game.lives_per_new_player }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">{{ t('Current prize pool:') }}</span>
                            <span class="font-medium ml-1">{{ formatCurrency(game.current_prize_pool) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Player list -->
                <div class="space-y-2">
                    <Label>{{ t('Select Player') }}</Label>
                    <div class="max-h-60 overflow-y-auto space-y-2 border rounded-lg p-2">
                        <label
                            v-for="player in availablePlayers"
                            :key="player.user.id"
                            :class="[
                                'flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors',
                                selectedPlayerId === player.user.id
                                    ? 'bg-indigo-50 border-indigo-500 dark:bg-indigo-900/30'
                                    : 'bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700',
                                !player.can_add ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                        >
                            <input
                                type="radio"
                                :value="player.user.id"
                                v-model="selectedPlayerId"
                                :disabled="!player.can_add"
                                class="text-indigo-600"
                            />
                            <div class="flex-1">
                                <p class="font-medium">{{ player.user.name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span v-if="player.status === 'new'" class="text-green-600 dark:text-green-400">
                                        {{ t('New player') }}
                                    </span>
                                    <span v-else-if="player.status === 'active'"
                                          class="text-blue-600 dark:text-blue-400">
                                        {{ t('Active') }} - {{ player.lives }} {{ t('lives') }}
                                    </span>
                                    <span v-else class="text-red-600 dark:text-red-400">
                                        {{ t('Eliminated') }} - {{ t('Rebuy') }} {{
                                            player.rebuy_count
                                        }}/{{ game?.rebuy_rounds }}
                                    </span>
                                </p>
                                <p v-if="!player.can_add" class="text-xs text-red-600 dark:text-red-400">
                                    {{ player.reason }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium">{{ formatCurrency(player.suggested_fee) }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Selected player details -->
                <div v-if="selectedPlayer && canAddPlayer" class="space-y-4 border-t pt-4">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="text-sm mb-2">{{ playerStatusText }}</p>

                        <!-- Fee input -->
                        <div class="space-y-2">
                            <Label for="custom_fee">{{ t('Entry Fee') }}</Label>
                            <Input
                                id="custom_fee"
                                v-model.number="customFee"
                                type="number"
                                :min="0"
                                :placeholder="t('Enter custom fee')"
                            />
                            <p class="text-xs text-gray-500">
                                {{ t('Suggested fee: :amount', {amount: formatCurrency(suggestedFee)}) }}
                            </p>
                            <InputError :message="validationErrors.rebuy_fee?.join(', ')"/>
                        </div>

                        <!-- Player type checkbox -->
                        <div class="mt-3 flex items-center space-x-2">
                            <Checkbox
                                id="is_new_player"
                                v-model="isNewPlayer"
                                :disabled="selectedPlayer.status !== 'eliminated'"
                            />
                            <Label for="is_new_player" class="text-sm">
                                {{ t('Treat as new player (not rebuy)') }}
                            </Label>
                        </div>

                        <!-- Info about lives -->
                        <div v-if="game?.lives_per_new_player > 0 && isNewPlayer"
                             class="mt-3 rounded bg-blue-100 p-2 text-xs text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                            {{
                                t('Adding a new player will give :lives extra lives to all active players', {
                                    lives: game.lives_per_new_player
                                })
                            }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-2 pt-4 border-t">
                <Button variant="outline" @click="emit('close')">{{ t('Cancel') }}</Button>
                <Button
                    :disabled="isLoading || !selectedPlayerId || !canAddPlayer"
                    @click="handleSubmit"
                >
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    {{ isNewPlayer ? t('Add New Player') : t('Process Rebuy') }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
