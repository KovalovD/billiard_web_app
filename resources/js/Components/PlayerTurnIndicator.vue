<script lang="ts" setup>
import { ArrowRightIcon, PlayIcon } from 'lucide-vue-next';

interface Props {
    isCurrentTurn: boolean;
    isNextTurn: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="flex items-center space-x-1">
        <div v-if="props.isCurrentTurn"
             class="flex items-center space-x-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 px-2 py-1 rounded-full text-xs font-medium animate-pulse">
            <PlayIcon class="h-3 w-3" />
            <span>Now Playing</span>
        </div>
        <div v-else-if="props.isNextTurn"
             class="flex items-center space-x-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-1 rounded-full text-xs font-medium">
            <ArrowRightIcon class="h-3 w-3" />
            <span>Next Turn</span>
        </div>
    </div>
</template>
