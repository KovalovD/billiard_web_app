// resources/js/Components/LivesEditorView.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import type {MultiplayerGamePlayer} from '@/types/api';
import {CheckIcon} from 'lucide-vue-next';
import {computed} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    players: MultiplayerGamePlayer[];
    currentTurnPlayerId?: number | null;
    isLoading?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['increment-lives', 'decrement-lives', 'set-turn']);
const { t } = useLocale();

// Sort players by turn order
const sortedPlayers = computed(() => {
    return [...props.players].sort((a, b) => {
        // Then sort by turn order
        return (a.turn_order || 999) - (b.turn_order || 999);
    });
});

const incrementLives = (player: MultiplayerGamePlayer) => {
    if (props.isLoading) return;
    emit('increment-lives', player.user.id);
};

const decrementLives = (player: MultiplayerGamePlayer) => {
    if (props.isLoading) return;
    emit('decrement-lives', player.user.id);
};

const setCurrentTurn = (player: MultiplayerGamePlayer) => {
    if (props.isLoading) return;
    emit('set-turn', player.user.id);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ t('Lives Editor') }}</CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="players.length === 0" class="py-4 text-center text-gray-500 dark:text-gray-400">
                {{ t('No active players') }}
            </div>

            <div v-else class="divide-y">
                <div
                    v-for="player in sortedPlayers"
                    :key="player.id"
                    :class="[
                        'flex items-center justify-between py-3',
                        player.user.id === currentTurnPlayerId ? 'bg-green-50 dark:bg-green-900/10' : ''
                    ]"
                >
                    <div class="flex items-center space-x-3">
                        <div
                            v-if="player.user.id === currentTurnPlayerId"
                            class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                        >
                            <CheckIcon class="h-4 w-4"/>
                        </div>
                        <div v-else class="h-6 w-6 text-center text-sm text-gray-500">
                            {{ player.turn_order || '-' }}
                        </div>
                        <div>
                            <p class="font-medium">{{ player.user.firstname }} {{ player.user.lastname }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium">{{ t('Lives') }}: {{ player.lives }}</span>

                            <Button
                                :disabled="isLoading"
                                size="sm"
                                variant="outline"
                                @click="decrementLives(player)"
                            >
                                -
                            </Button>

                            <Button
                                :disabled="isLoading"
                                size="sm"
                                variant="outline"
                                @click="incrementLives(player)"
                            >
                                +
                            </Button>
                        </div>

                        <Button
                            v-if="player.user.id !== currentTurnPlayerId"
                            :disabled="isLoading"
                            size="sm"
                            :title="t('Set as current turn')"
                            variant="outline"
                            @click="setCurrentTurn(player)"
                        >
                            {{ t('Set Turn') }}
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
