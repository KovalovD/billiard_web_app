<script lang="ts" setup>
import { ArrowRightIcon, PlayIcon, UserIcon } from 'lucide-vue-next';
import type { MultiplayerGamePlayer } from '@/types/api';

interface Props {
    currentPlayer: MultiplayerGamePlayer | null;
    nextPlayer: MultiplayerGamePlayer | null;
}

const props = defineProps<Props>();
</script>

<template>
    <div v-if="props.currentPlayer || props.nextPlayer" class="mb-6 overflow-hidden rounded-lg border bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col sm:flex-row">
            <!-- Current Player Section -->
            <div
                v-if="props.currentPlayer"
                class="flex-1 bg-green-50 p-4 dark:bg-green-900/20 animate-pulse"
            >
                <div class="flex items-center">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200">
                        <PlayIcon class="h-4 w-4" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-300">Currently Playing</h3>
                        <p class="font-semibold text-green-900 dark:text-green-200">
                            {{ props.currentPlayer.user.firstname }} {{ props.currentPlayer.user.lastname }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Arrow Divider for Desktop -->
            <div v-if="props.currentPlayer && props.nextPlayer" class="hidden bg-gray-100 px-3 flex items-center justify-center dark:bg-gray-700 sm:flex">
                <ArrowRightIcon class="h-6 w-6 text-gray-400 dark:text-gray-500" />
            </div>

            <!-- Down Arrow for Mobile -->
            <div v-if="props.currentPlayer && props.nextPlayer" class="flex h-10 items-center justify-center bg-gray-100 dark:bg-gray-700 sm:hidden">
                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            <!-- Next Player Section -->
            <div
                v-if="props.nextPlayer"
                class="flex-1 bg-blue-50 p-4 dark:bg-blue-900/20"
            >
                <div class="flex items-center">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                        <UserIcon class="h-4 w-4" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Next Up</h3>
                        <p class="font-semibold text-blue-900 dark:text-blue-200">
                            {{ props.nextPlayer.user.firstname }} {{ props.nextPlayer.user.lastname }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
