// resources/js/Components/Tournament/TournamentMainEvent.vue
<script lang="ts" setup>
import {useLocale} from '@/composables/useLocale';
import type {Tournament} from '@/types/api';
import {Link} from '@inertiajs/vue3';
import {CalendarIcon, MapPinIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {computed, ref} from 'vue';

const props = defineProps<{
    tournament: Tournament;
}>();

const { t } = useLocale();
const isVisible = ref(true);

const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('uk-UK', { month: 'short', day: 'numeric' });
};

const participantsText = computed(() => {
    const confirmed = props.tournament.confirmed_players_count || 0;
    const max = props.tournament.max_participants;

    if (max) {
        return `${confirmed}/${max} ${t('players')}`;
    }
    return `${confirmed} ${t('players')}`;
});

// Get confirmed players with ratings for marquee
const confirmedPlayersData = computed(() => {
    if (!props.tournament.players) return [];

    return props.tournament.players
        .filter(player => player.status === 'confirmed' && player.user)
        .map(player => ({
            name: player.user?.name || t('Player'),
            rating: player.rating || 0
        }));
});

// Calculate top 25% threshold
const top25PercentThreshold = computed(() => {
    const ratings = confirmedPlayersData.value
        .map(p => Number(p.rating))
        .sort((a, b) => b - a);

    if (ratings.length === 0) return 0;

    // For top 25%, we need the rating at the 25th percentile position
    const topCount = Math.ceil(ratings.length * 0.3);
    const thresholdIndex = Math.min(topCount - 1, ratings.length - 1);
    return ratings[thresholdIndex] || 0;
});

// Create marquee content with top players highlighted
const marqueeElements = computed(() => {
    if (confirmedPlayersData.value.length === 0) return [];

    const elements = confirmedPlayersData.value.map(player => ({
        name: player.name,
        isTop: Number(player.rating) >= Number(top25PercentThreshold.value)
    }));

    // Triple for seamless scrolling
    return [...elements, ...elements, ...elements];
});

// Check if we should show marquee with player names
const showPlayersMarquee = computed(() => {
    return confirmedPlayersData.value.length > 0;
});

</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
    >
        <div v-if="isVisible" class="relative bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 dark:from-indigo-700 dark:via-purple-700 dark:to-indigo-700">
            <!-- Animated background pattern -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -inset-10 opacity-20">
                    <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
                    <div class="absolute top-0 -right-4 w-72 h-72 bg-indigo-500 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-2000"></div>
                    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-4000"></div>
                </div>
            </div>

            <div class="relative">
                <Link :href="`/tournaments/${tournament.slug}`" class="block group">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="py-2 sm:py-2">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-center gap-3 sm:gap-6">
                                <!-- Tournament picture or icon -->
                                <div class="flex items-center gap-3">
                                    <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-lg bg-white/10 ring-2 ring-white/20">
                                        <TrophyIcon class="h-6 w-6 text-white" />
                                    </div>

                                    <!-- Title and description -->
                                    <div class="flex-1 sm:flex-none">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-full bg-yellow-400/20 px-2 py-0.5 text-xs font-medium text-yellow-200 ring-1 ring-yellow-400/30">
                                                {{ t('Main Event') }}
                                            </span>
                                            <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-yellow-200 transition-colors">
                                                {{ tournament.name }}
                                            </h3>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tournament details -->
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 sm:gap-4 text-xs sm:text-sm">
                                    <!-- Date -->
                                    <div v-if="tournament.start_date" class="flex items-center gap-1.5 text-indigo-100">
                                        <CalendarIcon class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        <span>{{ formatDate(tournament.start_date) }}</span>
                                    </div>

                                    <!-- Location -->
                                    <div v-if="tournament.city || tournament.club" class="flex items-center gap-1.5 text-indigo-100">
                                        <MapPinIcon class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        <span>{{ tournament.club?.name || tournament.city?.name }}</span>
                                    </div>

                                    <!-- Participants count -->
                                    <div class="flex items-center gap-1.5 text-indigo-100">
                                        <UsersIcon class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        <span>{{ participantsText }}</span>
                                    </div>

                                    <!-- Prize pool -->
                                    <div v-if="tournament.prize_pool" class="flex items-center gap-1.5 font-medium text-yellow-200">
                                        <span class="text-lg">🏆</span>
                                        <span>{{ tournament.prize_pool }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Full width marquee section -->
                    <div v-if="showPlayersMarquee" class="relative overflow-hidden bg-black/10 backdrop-blur-sm py-2">
                        <div class="flex items-center gap-2 text-xs">
                            <div class="marquee-mask">
                                <div class="marquee-content">
                                    <span v-for="(player, index) in marqueeElements" :key="`player-${index}`" class="inline-flex items-center">
                                        <span v-if="index > 0" class="mx-2 text-indigo-200/50">•</span>
                                        <span
                                            :class="[
                                                player.isTop
                                                    ? 'font-bold text-yellow-200 bg-yellow-400/20 px-2 py-0.5 rounded-full ring-1 ring-yellow-400/30'
                                                    : 'text-indigo-100'
                                            ]"
                                        >
                                            <span v-if="player.isTop" class="mr-1">⭐</span>
                                            {{ player.name }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

/* Marquee styles */
.marquee-mask {
    position: relative;
    overflow: hidden;
    width: 100%;
}

.marquee-content {
    display: flex;
    align-items: center;
    white-space: nowrap;
    animation: marquee 30s linear infinite;
    padding-right: 2rem;
}

@keyframes marquee {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-33.33%);
    }
}

/* Pause marquee on hover */
.group:hover .marquee-content {
    animation-play-state: paused;
}

/* Smooth edges for marquee */
.marquee-mask::before,
.marquee-mask::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 60px;
    z-index: 1;
    pointer-events: none;
}

/* Dark mode gradient adjustments */
.dark .marquee-mask::before {
    background: linear-gradient(to right, rgb(67 56 202), transparent);
}

.dark .marquee-mask::after {
    background: linear-gradient(to left, rgb(67 56 202), transparent);
}
</style>
