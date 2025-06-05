<script lang="ts" setup>
import LivesTracker from '@/Components/LivesTracker.vue';
import type {MultiplayerGamePlayer} from '@/types/api';
import {ArrowDownIcon, ArrowRightIcon, CheckIcon, HandIcon} from 'lucide-vue-next';
import {computed} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    players: MultiplayerGamePlayer[];
    title: string;
    emptyMessage?: string;
    showControls?: boolean;
    highlightCurrentTurn?: boolean;
    selectedPlayerId?: number | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['select-player', 'increment-lives', 'decrement-lives']);
const { t } = useLocale();

const sortedPlayers = computed(() => {
    return [...props.players].sort((a, b) => {
        // Otherwise, sort by turn order
        return (a.turn_order || 999) - (b.turn_order || 999);
    });
});

const selectPlayer = (player: MultiplayerGamePlayer) => {
    emit('select-player', player);
};

// Get available cards count for a player
const getAvailableCardsCount = (player: MultiplayerGamePlayer): number => {
    if (!player.cards) return 0;
    let count = 0;
    if (player.cards.skip_turn) count++;
    if (player.cards.pass_turn) count++;
    if (player.cards.hand_shot) count++;
    return count;
};

// Check if player has specific card
const hasCard = (player: MultiplayerGamePlayer, cardType: 'skip_turn' | 'pass_turn' | 'hand_shot'): boolean => {
    return player.cards && player.cards[cardType] === true;
};
</script>

<template>
    <div>
        <h3 class="mb-2 font-medium">{{ title }}</h3>

        <div v-if="players.length === 0" class="py-4 text-center text-gray-500 dark:text-gray-400">
            {{ emptyMessage || t('No players') }}
        </div>

        <div v-else class="divide-y">
            <div
                v-for="player in sortedPlayers"
                :key="player.id"
                :class="[
                    'flex items-center justify-between py-3',
                    player.is_current_turn && highlightCurrentTurn ? 'bg-green-50 dark:bg-green-900/10' : '',
                    selectedPlayerId === player.id ? 'border-l-4 border-blue-500 pl-2' : ''
                ]"
                class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50"
                @click="selectPlayer(player)"
            >
                <div class="flex items-center space-x-3">
                    <div
                        v-if="player.is_current_turn && highlightCurrentTurn"
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                    >
                        <CheckIcon class="h-4 w-4"/>
                    </div>
                    <div v-else class="h-6 w-6 text-center text-sm text-gray-500">
                        {{ player.turn_order || '-' }}
                    </div>
                    <div>
                        <p class="font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</p>

                        <!-- Card indicators -->
                        <div v-if="getAvailableCardsCount(player) > 0" class="mt-1 flex items-center gap-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">{{ t('Cards:') }}</span>

                            <!-- Pass Turn card -->
                            <div
                                v-if="hasCard(player, 'pass_turn')"
                                class="flex h-5 w-5 items-center justify-center rounded bg-blue-100 dark:bg-blue-900/30"
                                :title="t('Pass Turn')"
                            >
                                <ArrowRightIcon class="h-3 w-3 text-blue-600 dark:text-blue-400"/>
                            </div>

                            <!-- Skip Turn card -->
                            <div
                                v-if="hasCard(player, 'skip_turn')"
                                class="flex h-5 w-5 items-center justify-center rounded bg-orange-100 dark:bg-orange-900/30"
                                :title="t('Skip Turn')"
                            >
                                <ArrowDownIcon class="h-3 w-3 text-orange-600 dark:text-orange-400"/>
                            </div>

                            <!-- Hand Shot card -->
                            <div
                                v-if="hasCard(player, 'hand_shot')"
                                class="flex h-5 w-5 items-center justify-center rounded bg-purple-100 dark:bg-purple-900/30"
                                :title="t('Hand Shot')"
                            >
                                <HandIcon class="h-3 w-3 text-purple-600 dark:text-purple-400"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <LivesTracker :lives="player.lives" :size="'sm'"/>
                </div>
            </div>
        </div>
    </div>
</template>
