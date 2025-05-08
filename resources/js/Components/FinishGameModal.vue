// resources/js/Components/FinishGameModal.vue
<script lang="ts" setup>
import {Button, Input, Modal, Spinner} from '@/Components/ui';
import type {MultiplayerGame} from '@/types/api';
import {computed, ref, watch} from 'vue';
import {ArrowDownIcon, ArrowUpIcon} from 'lucide-vue-next';

interface Props {
    show: boolean;
    game: MultiplayerGame | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'finish']);

const isLoading = ref(false);
const playerPositions = ref<{ player_id: number; position: number; player_name: string }[]>([]);
const reorderMode = ref(false);

// Watch for changes in show or active players to initialize the positions
watch(
    [() => props.show, () => props.game],
    ([show, game]) => {
        if (show && game) {
            // Initialize player positions based on active players
            const activePlayers = [...game.active_players];

            // Reset positions array
            playerPositions.value = activePlayers.map((player, index) => ({
                player_id: player.id,
                position: index + 1, // Start with position 1 for the first player
                player_name: `${player.user.firstname} ${player.user.lastname}`
            }));

            reorderMode.value = false;
        }
    },
    {immediate: true}
);

// Sort players by position
const sortedPlayers = computed(() => {
    return [...playerPositions.value].sort((a, b) => a.position - b.position);
});

// Validate that positions are unique and sequential
const positionsValid = computed(() => {
    const positions = playerPositions.value.map(p => p.position);

    // Check if every position is unique
    const uniquePositions = new Set(positions);
    if (uniquePositions.size !== positions.length) {
        return false;
    }

    // Check if positions are sequential from 1 to n
    const expectedPositions = new Set(Array.from({length: positions.length}, (_, i) => i + 1));
    return positions.every(p => expectedPositions.has(p));
});

// Move a player up in the ranking (lower position number)
const movePlayerUp = (playerId: number) => {
    const playerIndex = playerPositions.value.findIndex(p => p.player_id === playerId);
    if (playerIndex <= 0) return; // Already at the top

    // Swap positions with the player above
    const currentPosition = playerPositions.value[playerIndex].position;
    const abovePosition = playerPositions.value[playerIndex - 1].position;

    playerPositions.value[playerIndex].position = abovePosition;
    playerPositions.value[playerIndex - 1].position = currentPosition;
};

// Move a player down in the ranking (higher position number)
const movePlayerDown = (playerId: number) => {
    const playerIndex = playerPositions.value.findIndex(p => p.player_id === playerId);
    if (playerIndex >= playerPositions.value.length - 1) return; // Already at the bottom

    // Swap positions with the player below
    const currentPosition = playerPositions.value[playerIndex].position;
    const belowPosition = playerPositions.value[playerIndex + 1].position;

    playerPositions.value[playerIndex].position = belowPosition;
    playerPositions.value[playerIndex + 1].position = currentPosition;
};

// Toggle between drag-drop reordering and manual position editing
const toggleReorderMode = () => {
    reorderMode.value = !reorderMode.value;
};

// Convert to the format expected by the API and submit
const finishGame = () => {
    if (!positionsValid.value) {
        alert('Please ensure all positions are unique and sequential (1 to ' + playerPositions.value.length + ')');
        return;
    }

    isLoading.value = true;

    // Format for API
    const positions = playerPositions.value.map(p => ({
        player_id: p.player_id,
        position: p.position
    }));

    emit('finish', positions);

    // Will be closed by parent component after API call completes
};
</script>

<template>
    <Modal :show="show" :title="'Finish Game'" @close="emit('close')">
        <div class="space-y-4 p-2">
            <div class="mb-4 text-gray-700 dark:text-gray-300">
                <p>Assign final positions to all active players. Position 1 is the winner.</p>
            </div>

            <div class="mb-4">
                <Button
                    variant="outline"
                    @click="toggleReorderMode"
                >
                    {{ reorderMode ? 'Edit Position Numbers' : 'Reorder Players' }}
                </Button>
            </div>

            <div class="space-y-2">
                <div v-if="reorderMode">
                    <!-- Reorder mode: Move players up/down in the list -->
                    <div
                        v-for="player in sortedPlayers"
                        :key="player.player_id"
                        class="flex items-center gap-2 rounded-md border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                            {{ player.position }}
                        </div>

                        <div class="flex-1">
                            {{ player.player_name }}
                        </div>

                        <div class="flex flex-col gap-1">
                            <Button
                                :disabled="player.position === 1"
                                size="sm"
                                variant="ghost"
                                @click="movePlayerUp(player.player_id)"
                            >
                                <ArrowUpIcon class="h-4 w-4"/>
                            </Button>

                            <Button
                                :disabled="player.position === playerPositions.length"
                                size="sm"
                                variant="ghost"
                                @click="movePlayerDown(player.player_id)"
                            >
                                <ArrowDownIcon class="h-4 w-4"/>
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <!-- Edit position numbers directly -->
                    <div
                        v-for="player in playerPositions"
                        :key="player.player_id"
                        class="flex items-center gap-2 rounded-md border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div class="w-20">
                            <Input
                                v-model.number="player.position"
                                :max="playerPositions.length"
                                :min="1"
                                class="w-16 text-center"
                                type="number"
                            />
                        </div>

                        <div class="flex-1">
                            {{ player.player_name }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!positionsValid"
                 class="rounded-md bg-yellow-50 p-3 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                Warning: Each player must have a unique position from 1 to {{ playerPositions.length }}
            </div>

            <div class="mt-6 flex justify-end space-x-2">
                <Button variant="outline" @click="emit('close')">Cancel</Button>
                <Button :disabled="!positionsValid || isLoading" @click="finishGame">
                    <Spinner v-if="isLoading" class="mr-2 h-4 w-4"/>
                    Finish Game
                </Button>
            </div>
        </div>
    </Modal>
</template>
