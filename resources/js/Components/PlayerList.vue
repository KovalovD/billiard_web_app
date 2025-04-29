<script setup lang="ts">
import type { Rating } from '@/Types/api';
import PlayerListItem from './PlayerListItem.vue';

interface Props {
    players: Rating[];
    leagueId: number;
    currentUserId: number | null;
    isAuthenticated: boolean; // <--- ДОБАВЛЯЕМ ПРОПС
}
defineProps<Props>();

const emit = defineEmits(['challenge']);
</script>

<template>
    <div v-if="!players || players.length === 0" class="text-center text-gray-500 py-4 dark:text-gray-400">
        No players have joined this league yet.
    </div>
    <ul v-else class="space-y-3">
        <PlayerListItem
            v-for="playerRating in players"
            :key="playerRating.id"
            :playerRating="playerRating"
            :leagueId="leagueId"
            :isCurrentUser="playerRating.player.id === currentUserId"
            :isAuthenticated="isAuthenticated" @challenge="$emit('challenge', playerRating.player)"
        />
    </ul>
</template>
