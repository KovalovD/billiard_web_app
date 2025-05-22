// resources/js/pages/OfficialRatings/Show.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {OfficialRating, OfficialRatingPlayer, OfficialRatingTournament} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CalendarIcon,
    CrownIcon,
    PencilIcon,
    StarIcon,
    TrophyIcon,
    UserPlusIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    ratingId: number | string;
}>();

const {isAdmin} = useAuth();

const rating = ref<OfficialRating | null>(null);
const players = ref<OfficialRatingPlayer[]>([]);
const tournaments = ref<OfficialRatingTournament[]>([]);
const isLoadingRating = ref(true);
const isLoadingPlayers = ref(true);
const isLoadingTournaments = ref(true);
const error = ref<string | null>(null);
const activeTab = ref<'players' | 'tournaments'>('players');

const topPlayers = computed(() => {
    return players.value.filter(p => p.is_active).slice(0, 10);
});

const allActivePlayers = computed(() => {
    return players.value.filter(p => p.is_active);
});

const activeTournaments = computed(() => {
    return tournaments.value.filter(t => t.is_counting);
});

const getPositionBadgeClass = (position: number): string => {
    switch (position) {
        case 1:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 2:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        case 3:
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

const getCalculationMethodDisplay = (method: string): string => {
    switch (method) {
        case 'tournament_points':
            return 'Tournament Points';
        case 'elo':
            return 'ELO Rating';
        case 'custom':
            return 'Custom';
        default:
            return method;
    }
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

const fetchRating = async () => {
    isLoadingRating.value = true;
    error.value = null;

    try {
        rating.value = await apiClient<OfficialRating>(`/api/official-ratings/${props.ratingId}?include_top_players=true`);
    } catch (err: any) {
        error.value = err.message || 'Failed to load official rating';
    } finally {
        isLoadingRating.value = false;
    }
};

const fetchPlayers = async () => {
    isLoadingPlayers.value = true;

    try {
        players.value = await apiClient<OfficialRatingPlayer[]>(`/api/official-ratings/${props.ratingId}/players`);
    } catch (err: any) {
        console.error('Failed to load players:', err);
    } finally {
        isLoadingPlayers.value = false;
    }
};

const fetchTournaments = async () => {
    isLoadingTournaments.value = true;

    try {
        tournaments.value = await apiClient<OfficialRatingTournament[]>(`/api/official-ratings/${props.ratingId}/tournaments`);
    } catch (err: any) {
        console.error('Failed to load tournaments:', err);
    } finally {
        isLoadingTournaments.value = false;
    }
};

onMounted(() => {
    fetchRating();
    fetchPlayers();
    fetchTournaments();
});
</script>

<template>
    <Head :title="rating ? `Rating: ${rating.name}` : 'Official Rating'"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link href="/official-ratings">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to Ratings
                    </Button>
                </Link>

                <div v-if="isAdmin && rating" class="flex space-x-2">
                    <Link :href="`/admin/official-ratings/${rating.id}/edit`">
                        <Button variant="secondary">
                            <PencilIcon class="mr-2 h-4 w-4"/>
                            Edit Rating
                        </Button>
                    </Link>
                    <Link :href="`/admin/official-ratings/${rating.id}/manage`">
                        <Button variant="secondary">
                            <UserPlusIcon class="mr-2 h-4 w-4"/>
                            Manage
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingRating" class="p-10 text-center">
                <Spinner class="text-primary mx-auto h-8 w-8"/>
                <p class="mt-2 text-gray-500">Loading rating...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                Error loading rating: {{ error }}
            </div>

            <!-- Rating Content -->
            <template v-else-if="rating">
                <!-- Rating Header -->
                <Card class="mb-8">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-3 text-2xl">
                                    <StarIcon class="h-8 w-8 text-yellow-500"/>
                                    {{ rating.name }}
                                    <span v-if="!rating.is_active"
                                          class="text-sm px-3 py-1 bg-gray-100 text-gray-600 rounded-full dark:bg-gray-800 dark:text-gray-400">
                                        Inactive
                                    </span>
                                </CardTitle>
                                <CardDescription class="mt-2 text-lg">
                                    <div class="flex flex-wrap gap-4">
                                        <span class="flex items-center gap-1">
                                            <TrophyIcon class="h-4 w-4"/>
                                            {{ rating.game?.name || 'N/A' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <UsersIcon class="h-4 w-4"/>
                                            {{ rating.players_count }} players
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <CalendarIcon class="h-4 w-4"/>
                                            {{ rating.tournaments_count }} tournaments
                                        </span>
                                    </div>
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Description</h4>
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ rating.description || 'No description provided.' }}
                                </p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Rating Details</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Initial Rating:</span>
                                        <span class="font-medium">{{ rating.initial_rating }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Calculation Method:</span>
                                        <span class="font-medium">{{
                                                getCalculationMethodDisplay(rating.calculation_method)
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tab Navigation -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'players'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'players'"
                        >
                            Players ({{ rating.players_count }})
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'tournaments'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'tournaments'"
                        >
                            Tournaments ({{ rating.tournaments_count }})
                        </button>
                    </nav>
                </div>

                <!-- Players Tab -->
                <div v-if="activeTab === 'players'">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CrownIcon class="h-5 w-5"/>
                                Player Rankings
                            </CardTitle>
                            <CardDescription>
                                Current standings in the {{ rating.name }} rating
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6"/>
                            </div>
                            <div v-else-if="allActivePlayers.length === 0" class="py-8 text-center text-gray-500">
                                No active players in this rating.
                            </div>
                            <div v-else class="overflow-auto">
                                <table class="w-full">
                                    <thead>
                                    <tr class="border-b dark:border-gray-700">
                                        <th class="px-4 py-3 text-left">Rank</th>
                                        <th class="px-4 py-3 text-left">Player</th>
                                        <th class="px-4 py-3 text-center">Rating</th>
                                        <th class="px-4 py-3 text-center">Tournaments</th>
                                        <th class="px-4 py-3 text-center">Wins</th>
                                        <th class="px-4 py-3 text-center">Win Rate</th>
                                        <th class="px-4 py-3 text-center">Last Tournament</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr
                                        v-for="player in allActivePlayers"
                                        :key="player.id"
                                        class="border-b dark:border-gray-700"
                                    >
                                        <td class="px-4 py-3">
                                                <span
                                                    :class="[
                                                        'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                                        getPositionBadgeClass(player.position)
                                                    ]"
                                                >
                                                    {{ player.position }}
                                                </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div>
                                                    <p class="font-medium">{{ player.user?.firstname }}
                                                        {{ player.user?.lastname }}</p>
                                                    <p v-if="player.position === 1"
                                                       class="text-sm text-yellow-600 dark:text-yellow-400">
                                                        👑 Champion
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="font-bold text-lg">{{ player.rating_points }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ player.tournaments_played }}</td>
                                        <td class="px-4 py-3 text-center">{{ player.tournaments_won }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-sm">{{ player.win_rate }}%</span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                            {{
                                                player.last_tournament_at ? formatDate(player.last_tournament_at) : 'Never'
                                            }}
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tournaments Tab -->
                <div v-if="activeTab === 'tournaments'">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <TrophyIcon class="h-5 w-5"/>
                                Associated Tournaments
                            </CardTitle>
                            <CardDescription>
                                Tournaments that count towards this rating
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="isLoadingTournaments" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6"/>
                            </div>
                            <div v-else-if="tournaments.length === 0" class="py-8 text-center text-gray-500">
                                No tournaments associated with this rating.
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="tournament in tournaments"
                                    :key="tournament.id"
                                    :class="[
                                        'p-4 border rounded-lg',
                                        tournament.is_counting
                                            ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20'
                                            : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50'
                                    ]"
                                >
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ tournament.name }}
                                            </h3>
                                            <div
                                                class="mt-1 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="flex items-center gap-1">
                                                    <CalendarIcon class="h-3 w-3"/>
                                                    {{ formatDate(tournament.start_date) }}
                                                    <span v-if="tournament.end_date !== tournament.start_date">
                                                        - {{ formatDate(tournament.end_date) }}
                                                    </span>
                                                </span>
                                                <span v-if="tournament.city" class="flex items-center gap-1">
                                                    {{ tournament.city }}, {{ tournament.country }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <UsersIcon class="h-3 w-3"/>
                                                    {{ tournament.players_count }} players
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm">
                                                <span class="font-medium">Coefficient: {{
                                                        tournament.rating_coefficient
                                                    }}x</span>
                                            </div>
                                            <div class="mt-1">
                                                <span
                                                    :class="[
                                                        'px-2 py-1 text-xs rounded-full',
                                                        tournament.is_counting
                                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                                    ]"
                                                >
                                                    {{ tournament.is_counting ? 'Counting' : 'Not Counting' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>
        </div>
    </div>
</template>
