<script lang="ts" setup>
import type { Rating } from '@/Types/api';
import { Button } from '@/Components/ui';
import { SwordsIcon } from 'lucide-vue-next';

interface Props {
    playerRating: Rating;
    leagueId: number;
    isCurrentUser: boolean;
    isAuthenticated: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['challenge']);

const handleChallenge = () => {
    if (props.isAuthenticated) {
        emit('challenge', props.playerRating.player);
    }
}
</script>

<template>
    <li class="flex justify-between items-center p-3 bg-white border rounded shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700/50">
        <div class="flex items-center space-x-3">
            <span class="font-semibold text-gray-500 dark:text-gray-400 w-6 text-right">{{ playerRating.position }}.</span>
            <span class="font-medium text-gray-800 dark:text-gray-200">{{ playerRating.player.name }}</span>
            <span v-if="isCurrentUser" class="text-xs text-blue-600 dark:text-blue-400 font-semibold">(You)</span>
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ playerRating.rating }}</span>
            <Button
                v-if="isAuthenticated && !isCurrentUser"
                size="sm"
                title="Challenge this player"
                variant="outline"
                @click="handleChallenge"
            >
                <SwordsIcon class="w-4 h-4 mr-1" />
                Challenge
            </Button>
        </div>
    </li>
</template>
