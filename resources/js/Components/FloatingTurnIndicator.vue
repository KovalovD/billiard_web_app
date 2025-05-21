<script lang="ts" setup>
import { ArrowRightIcon, PlayIcon } from 'lucide-vue-next';
import type { MultiplayerGamePlayer } from '@/types/api';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    currentPlayer: MultiplayerGamePlayer | null;
    nextPlayer: MultiplayerGamePlayer | null;
    show?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    show: true
});

const isVisible = ref(false);
const lastScrollY = ref(0);
const scrollThreshold = 200;

// Show floating indicator once user scrolls down past the threshold
const handleScroll = () => {
    const currentScrollY = window.scrollY;

    // Show indicator when scrolling down past threshold
    isVisible.value = currentScrollY > scrollThreshold;

    lastScrollY.value = currentScrollY;
};

// Computed property to control visibility
const shouldShow = computed(() => {
    return props.show && isVisible.value && (props.currentPlayer || props.nextPlayer);
});

// Add and remove scroll listener
onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <transition
        enter-active-class="transform transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="shouldShow"
            class="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg border bg-white px-4 py-3 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <!-- Current Player -->
            <div v-if="props.currentPlayer" class="flex items-center">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                    <PlayIcon class="h-4 w-4 animate-pulse" />
                </div>
                <span class="ml-2 font-medium text-green-800 dark:text-green-300">
          {{ props.currentPlayer.user.firstname }} {{ props.currentPlayer.user.lastname }}
        </span>
            </div>

            <!-- Arrow separator -->
            <div v-if="props.currentPlayer && props.nextPlayer">
                <ArrowRightIcon class="h-5 w-5 text-gray-400" />
            </div>

            <!-- Next Player -->
            <div v-if="props.nextPlayer" class="flex items-center">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                    <span class="text-xs font-bold">NEXT</span>
                </div>
                <span class="ml-2 font-medium text-blue-800 dark:text-blue-300">
          {{ props.nextPlayer.user.firstname }} {{ props.nextPlayer.user.lastname }}
        </span>
            </div>
        </div>
    </transition>
</template>
