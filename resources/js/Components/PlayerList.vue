<script lang="ts" setup>
import type {Rating} from '@/types/api';
import PlayerListItem from './PlayerListItem.vue';

interface Props {
    players: Rating[];
    leagueId: number;
    currentUserId: number | null;
    isAuthenticated: boolean;
}

defineProps<Props>();

</script>

<template>
    <div v-if="!players || players.length === 0" class="text-center text-gray-500 py-4 dark:text-gray-400">
        No players have joined this league yet.
    </div>
    <ul v-else class="space-y-3">
        <PlayerListItem
            v-for="playerRating in players"
            :key="playerRating.id"
            :isAuthenticated="isAuthenticated"
            :isCurrentUser="playerRating.player.id === currentUserId"
            :leagueId="leagueId"
            :playerRating="playerRating"
            @challenge="$emit('challenge', playerRating.player)"
        />
    </ul>
</template>
