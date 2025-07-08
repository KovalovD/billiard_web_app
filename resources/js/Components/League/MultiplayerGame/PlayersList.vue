<script lang="ts" setup>
import LivesTracker from '@/Components/League/MultiplayerGame/LivesTracker.vue';
import type {MultiplayerGamePlayer} from '@/types/api';
import {ArrowDownIcon, ArrowRightIcon, CheckIcon, HandHelpingIcon, HandIcon} from 'lucide-vue-next';
import {computed} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    players: MultiplayerGamePlayer[];
    title: string;
    emptyMessage?: string;
    showControls?: boolean;
    maxLives?: number;
    highlightCurrentTurn?: boolean;
    selectedPlayerId?: number | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['select-player', 'increment-lives', 'decrement-lives']);
const {t} = useLocale();

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
    if (player.cards.handicap) count++;
    return count;
};

// Check if player has specific card
const hasCard = (player: MultiplayerGamePlayer, cardType: 'skip_turn' | 'pass_turn' | 'hand_shot' | 'handicap'): boolean => {
    return player.cards && player.cards[cardType] === true;
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('uk-UA', {style: 'currency', currency: 'UAH'})
        .format(amount)
        .replace('UAH', '₴');
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
                        <p class="font-medium">
                            {{ player.user.firstname }} {{ player.user.lastname }}
                            ({{ player.division }})
                            <span v-if="player.is_rebuy" class="ml-1 text-xs text-purple-600 dark:text-purple-400">
                                {{ t('R') }}{{ player.rebuy_count }}
                            </span>
                        </p>

                        <!-- Card indicators -->
                        <div v-if="getAvailableCardsCount(player) > 0" class="mt-1 flex items-center gap-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">{{ t('Cards:') }}</span>

                            <!-- Pass Turn card -->
                            <div
                                v-if="hasCard(player, 'pass_turn')"
                                :title="t('Pass Turn')"
                                class="flex h-5 w-5 items-center justify-center rounded bg-blue-100 dark:bg-blue-900/30"
                            >
                                <ArrowRightIcon class="h-3 w-3 text-blue-600 dark:text-blue-400"/>
                            </div>

                            <!-- Skip Turn card -->
                            <div
                                v-if="hasCard(player, 'skip_turn')"
                                :title="t('Skip Turn')"
                                class="flex h-5 w-5 items-center justify-center rounded bg-orange-100 dark:bg-orange-900/30"
                            >
                                <ArrowDownIcon class="h-3 w-3 text-orange-600 dark:text-orange-400"/>
                            </div>

                            <!-- Hand Shot card -->
                            <div
                                v-if="hasCard(player, 'hand_shot')"
                                :title="t('Hand Shot')"
                                class="flex h-5 w-5 items-center justify-center rounded bg-purple-100 dark:bg-purple-900/30"
                            >
                                <HandIcon class="h-3 w-3 text-purple-600 dark:text-purple-400"/>
                            </div>

                            <!-- Hand Shot card -->
                            <div
                                v-if="hasCard(player, 'handicap')"
                                :title="t('Handicap')"
                                class="flex h-5 w-5 items-center justify-center rounded bg-green-100 dark:bg-green-900/30"
                            >
                                <HandHelpingIcon class="h-3 w-3 text-green-600 dark:text-green-400"/>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span v-if="player.game_stats?.shots_taken > 0">
                                    {{ t('Shots') }}: {{ player.game_stats.shots_taken }}
                                </span>
                            <span v-if="player.game_stats?.balls_potted > 0">
                                    {{ t('Potted') }}: {{ player.game_stats.balls_potted }}
                                </span>
                            <span v-if="player.rounds_played">
                                    {{ t('Rounds') }}: {{ player.rounds_played }}
                                </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <LivesTracker :lives="player.lives" :max-lives="props.maxLives" :size="'sm'"/>
                    <div v-if="player.total_paid > 0" class="text-xs text-gray-500">
                        {{ formatCurrency(player.total_paid) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
