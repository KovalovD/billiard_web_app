<script lang="ts" setup>
import type {Rating} from '@/types/api';
import {computed} from 'vue';
import {useLocale} from '@/composables/useLocale';
import PlayerListItem from './PlayerListItem.vue';

interface Props {
    players: Rating[];
    leagueId: number;
    currentUserId: number | null;
    isAuthenticated: boolean;
    authUserHaveOngoingMatch: boolean | undefined;
    authUserIsConfirmed: boolean | undefined;
    multiplayerGame: boolean | undefined;
    authUserRating: Rating | null | undefined;
}

const props = defineProps<Props>();
const { t } = useLocale();

const authUserPosition = computed((): number | null => {
    if (!props.currentUserId) return null;

    const currentUserRating = props.players.find((rating) => rating.player.id === props.currentUserId);

    return currentUserRating ? currentUserRating.position : null;
});
</script>

<template>
    <div v-if="!players || players.length === 0" class="py-4 text-center text-gray-500 dark:text-gray-400">
        {{ t('No players have joined this league yet.') }}
    </div>
    <ul v-else class="space-y-3">
        <PlayerListItem
            v-for="playerRating in players"
            :key="playerRating.id"
            :isAuthenticated="isAuthenticated"
            :isCurrentUser="playerRating.player.id === currentUserId"
            :leagueId="leagueId"
            :playerRating="playerRating"
            :authUserHaveOngoingMatch="authUserHaveOngoingMatch"
            :authUserPosition="authUserPosition"
            :authUserIsConfirmed="authUserIsConfirmed"
            :authUserRating="authUserRating"
            :multiplayerGame="multiplayerGame"
            @challenge="$emit('challenge', playerRating.player)"
        />
    </ul>
</template>
