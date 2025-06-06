<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {OfficialRating, OfficialRatingPlayer, OfficialRatingTournament, RatingDelta} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {useLocale} from "@/composables/useLocale";
import {
    ArrowLeftIcon,
    CalendarIcon,
    ChevronDownIcon,
    CrownIcon,
    PencilIcon,
    StarIcon,
    TrophyIcon,
    UserIcon,
    UserPlusIcon,
    UsersIcon,
} from 'lucide-vue-next';
import {computed, nextTick, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    ratingId: number | string;
}>();

const {t} = useLocale();

const {isAdmin, isAuthenticated, user} = useAuth();

const rating = ref<OfficialRating | null>(null);
const players = ref<OfficialRatingPlayer[]>([]);
const tournaments = ref<OfficialRatingTournament[]>([]);
const isLoadingRating = ref(true);
const isLoadingPlayers = ref(true);
const isLoadingTournaments = ref(true);
const error = ref<string | null>(null);
const activeTab = ref<'players' | 'tournaments'>('players');
const showScrollToUser = ref(false);
const deltaDate = ref('');
const ratingDelta = ref<RatingDelta | null>(null);
const isLoadingDelta = ref(false);

const allActivePlayers = computed(() => {
    return players.value.filter(p => p.is_active);
});

const currentUserPlayer = computed(() => {
    if (!isAuthenticated.value || !user.value) return null;
    return allActivePlayers.value.find(p => p.user?.id === user.value?.id);
});

const isCurrentUser = (player: OfficialRatingPlayer): boolean | null => {
    return isAuthenticated.value && user.value && player.user?.id === user.value.id;
};

const scrollToUser = async () => {
    if (currentUserPlayer.value) {
        await nextTick();
        // Find the user's row element by data attribute
        const userRowElement = document.querySelector(`[data-user-row="${currentUserPlayer.value.user?.id}"]`) as HTMLElement;

        if (userRowElement) {
            userRowElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            // Flash highlight effect
            userRowElement.classList.add('animate-pulse');
            setTimeout(() => {
                userRowElement.classList.remove('animate-pulse');
            }, 2000);
        }
    }
};

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
            return t('Tournament Points');
        case 'elo':
            return t('ELO Rating');
        case 'custom':
            return t('Custom');
        default:
            return method;
    }
};

const formatDate = (dateString: string | undefined): string => {
    if (!dateString) {
        return ''
    }
    return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + '₴';
};

const fetchRating = async () => {
    isLoadingRating.value = true;
    error.value = null;

    try {
        rating.value = await apiClient<OfficialRating>(`/api/official-ratings/${props.ratingId}?include_top_players=true`);
    } catch (err: any) {
        error.value = err.message || t('Failed to load official rating');
    } finally {
        isLoadingRating.value = false;
    }
};

const fetchPlayers = async () => {
    isLoadingPlayers.value = true;

    try {
        players.value = await apiClient<OfficialRatingPlayer[]>(`/api/official-ratings/${props.ratingId}/players`);

        // Check if we should show scroll to user button
        if (currentUserPlayer.value && allActivePlayers.value.length > 10) {
            showScrollToUser.value = true;
        }
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

const fetchDelta = async () => {
    if (!deltaDate.value) return;
    isLoadingDelta.value = true;
    ratingDelta.value = null;

    try {
        ratingDelta.value = await apiClient<RatingDelta>(`/api/official-ratings/${props.ratingId}/player-delta?date=${deltaDate.value}`);
    } catch (err: any) {
        console.error('Failed to load delta:', err);
    } finally {
        isLoadingDelta.value = false;
    }
};

onMounted(() => {
    fetchRating();
    fetchPlayers();
    fetchTournaments();
});
</script>

<template>
    <Head :title="rating ? t('Rating: :name', {name: rating.name}) : t('Official Rating')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link href="/official-ratings">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Ratings') }}
                    </Button>
                </Link>

                <div v-if="isAdmin && rating" class="flex space-x-2">
                    <Link :href="`/admin/official-ratings/${rating.id}/edit`">
                        <Button variant="secondary">
                            <PencilIcon class="mr-2 h-4 w-4"/>
                            {{ t('Edit Rating') }}
                        </Button>
                    </Link>
                    <Link :href="`/admin/official-ratings/${rating.id}/manage`">
                        <Button variant="secondary">
                            <UserPlusIcon class="mr-2 h-4 w-4"/>
                            {{ t('Manage') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingRating" class="p-10 text-center">
                <Spinner class="text-primary mx-auto h-8 w-8"/>
                <p class="mt-2 text-gray-500">{{ t('Loading rating...') }}</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                {{ t('Error loading rating: :error', {error}) }}
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
                                        {{ t('Inactive') }}
                                    </span>
                                </CardTitle>
                                <CardDescription class="mt-2 text-lg">
                                    <div class="flex flex-wrap gap-4">
                                        <span class="flex items-center gap-1">
                                            <TrophyIcon class="h-4 w-4"/>
                                            {{ rating.game_type_name || 'N/A' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <UsersIcon class="h-4 w-4"/>
                                            {{ rating.players_count }} {{ t('players') }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <CalendarIcon class="h-4 w-4"/>
                                            {{ rating.tournaments_count }} {{ t('tournaments') }}
                                        </span>
                                        <!-- User's position indicator -->
                                        <span v-if="currentUserPlayer"
                                              class="flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                                            <UserIcon class="h-3 w-3"/>
                                            {{ t('Your position') }}: #{{ currentUserPlayer.position }}
                                        </span>
                                    </div>
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ t('Description') }}</h4>
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ rating.description || t('No description provided.') }}
                                </p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ t('Rating Details') }}</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">{{ t('Initial Rating') }}:</span>
                                        <span class="font-medium">{{ rating.initial_rating }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">{{ t('Calculation Method') }}:</span>
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
                            {{ t('Players') }} ({{ rating.players_count }})
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
                            {{ t('Tournaments') }} ({{ rating.tournaments_count }})
                        </button>
                    </nav>
                </div>

                <!-- Players Tab -->
                <div v-if="activeTab === 'players'">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <CrownIcon class="h-5 w-5"/>
                                        {{ t('Player Rankings') }}
                                    </CardTitle>
                                    <CardDescription>
                                        {{ t('Current standings in the :rating rating', {rating: rating.name}) }}
                                    </CardDescription>
                                </div>

                                <!-- Find Me Button -->
                                <Button
                                    v-if="showScrollToUser && currentUserPlayer"
                                    class="flex items-center gap-2"
                                    size="sm"
                                    variant="outline"
                                    @click="scrollToUser"
                                >
                                    <UserIcon class="h-4 w-4"/>
                                    {{ t('Find Me (#:position)', {position: currentUserPlayer.position}) }}
                                    <ChevronDownIcon class="h-4 w-4"/>
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="isAuthenticated" class="mb-4 flex flex-wrap items-center gap-2">
                                <input type="date" v-model="deltaDate" class="rounded border px-2 py-1 text-sm dark:bg-gray-800" />
                                <Button size="sm" @click="fetchDelta" :disabled="!deltaDate || isLoadingDelta">{{ t('Load Delta') }}</Button>
                                <Spinner v-if="isLoadingDelta" class="text-primary h-4 w-4" />
                                <div v-if="ratingDelta" class="text-sm ml-2">
                                    {{ t('Change since :date:', {date: deltaDate}) }}
                                    <span :class="ratingDelta.points_delta >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ ratingDelta.points_delta >= 0 ? `+${ratingDelta.points_delta}` : ratingDelta.points_delta }} pts
                                    </span>,
                                    {{ t('position :before → :after', {before: ratingDelta.position_before, after: ratingDelta.current_position}) }}
                                    <span v-if="ratingDelta.prize_amount_delta !== 0" class="ml-2">
                                        {{ t('Prize') }}:
                                        <span
                                            :class="ratingDelta.prize_amount_delta >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{
                                                ratingDelta.prize_amount_delta >= 0 ? `+${ratingDelta.prize_amount_delta.toFixed(2)}` : 0.00
                                            }}₴
                                        </span>
                                    </span><span v-if="ratingDelta.bonus_amount_delta !== 0" class="ml-2">
                                        {{ t('Bonus') }}:
                                        <span
                                            :class="ratingDelta.bonus_amount_delta >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{
                                                ratingDelta.bonus_amount_delta >= 0 ? `+${ratingDelta.bonus_amount_delta.toFixed(2)}` : 0.00
                                            }}₴
                                        </span>
                                    </span>
                                    <span v-if="ratingDelta.achievement_amount_delta !== 0" class="ml-2">
                                        {{ t('Achievement') }}:
                                        <span
                                            :class="ratingDelta.achievement_amount_delta >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{
                                                ratingDelta.achievement_amount_delta >= 0 ? `+${ratingDelta.achievement_amount_delta.toFixed(2)}` : 0.00
                                            }}₴
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6"/>
                            </div>
                            <div v-else-if="allActivePlayers.length === 0" class="py-8 text-center text-gray-500">
                                {{ t('No active players in this rating.') }}
                            </div>
                            <div v-else class="overflow-auto">
                                <table class="w-full">
                                    <thead>
                                    <tr class="border-b dark:border-gray-700">
                                        <th class="px-4 py-3 text-left">{{ t('Rank') }}</th>
                                        <th class="px-4 py-3 text-left">{{ t('Division') }}</th>
                                        <th class="px-4 py-3 text-left">{{ t('Player') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Rating') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Tournaments') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Wins') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Win Rate') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Prize') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Bonus') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Achievement') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Total Money') }}</th>
                                        <th class="px-4 py-3 text-center">{{ t('Last Tournament') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr
                                        v-for="player in allActivePlayers"
                                        :key="player.id"
                                        :class="[
                                            'border-b dark:border-gray-700 transition-all duration-200',
                                            isCurrentUser(player)
                                                ? 'bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 border-l-4 border-blue-500 shadow-sm'
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'
                                        ]"
                                        :data-user-row="player.user?.id"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    :class="[
                                                        'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                                        getPositionBadgeClass(player.position)
                                                    ]"
                                                >
                                                    {{ player.position }}
                                                </span>
                                                <!-- Current user indicator -->
                                                <UserIcon
                                                    v-if="isCurrentUser(player)"
                                                    class="h-4 w-4 text-blue-600 dark:text-blue-400"
                                                    title="This is you!"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                                <span
                                                    :class="[
                                                        'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                                        isCurrentUser(player)
                                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                                    ]"
                                                >
                                                    {{ player.division }}
                                                </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div>
                                                    <p :class="[
                                                        'font-medium',
                                                        isCurrentUser(player)
                                                            ? 'text-blue-900 dark:text-blue-100'
                                                            : 'text-gray-900 dark:text-gray-100'
                                                    ]">
                                                        {{ player.user?.firstname }} {{ player.user?.lastname }}
                                                        <span v-if="isCurrentUser(player)"
                                                              class="text-xs text-blue-600 dark:text-blue-400 ml-1">(You)</span>
                                                    </p>
                                                    <p v-if="player.position === 1"
                                                       class="text-sm text-yellow-600 dark:text-yellow-400">
                                                        👑 Champion
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span :class="[
                                                'font-bold text-lg',
                                                isCurrentUser(player)
                                                    ? 'text-blue-900 dark:text-blue-100'
                                                    : 'text-gray-900 dark:text-gray-100'
                                            ]">
                                                {{ player.rating_points }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                :class="isCurrentUser(player) ? 'font-semibold text-blue-900 dark:text-blue-100' : ''">
                                                {{ player.tournaments_played }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                :class="isCurrentUser(player) ? 'font-semibold text-blue-900 dark:text-blue-100' : ''">
                                                {{ player.tournaments_won }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span :class="[
                                                'text-sm',
                                                isCurrentUser(player) ? 'font-semibold text-blue-900 dark:text-blue-100' : ''
                                            ]">
                                                {{ player.win_rate }}%
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="player.total_prize_amount > 0" :class="[
                                                'font-medium text-green-600 dark:text-green-400',
                                                isCurrentUser(player) ? 'font-bold' : ''
                                            ]">
                                                {{ formatCurrency(player.total_prize_amount) }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="player.total_bonus_amount > 0" :class="[
                                                'font-medium text-orange-600 dark:text-orange-400',
                                                isCurrentUser(player) ? 'font-bold' : ''
                                            ]">
                                                {{ formatCurrency(player.total_bonus_amount) }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="player.total_achievement_amount > 0" :class="[
                                                'font-medium text-purple-600 dark:text-purple-400',
                                                isCurrentUser(player) ? 'font-bold' : ''
                                            ]">
                                                {{ formatCurrency(player.total_achievement_amount) }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="player.total_money_earned > 0" :class="[
                                                'font-bold text-indigo-600 dark:text-indigo-400',
                                                isCurrentUser(player) ? 'text-indigo-800 dark:text-indigo-200' : ''
                                            ]">
                                                {{ formatCurrency(player.total_money_earned) }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                            {{
                                                player.last_tournament_at ? formatDate(player.last_tournament_at) : t('Never')
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
                                {{ t('Associated Tournaments') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('Tournaments that count towards this rating') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="isLoadingTournaments" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6"/>
                            </div>
                            <div v-else-if="tournaments.length === 0" class="py-8 text-center text-gray-500">
                                {{ t('No tournaments associated with this rating.') }}
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
                                                    {{ tournament.players_count }} {{ t('players') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm">
                                                <span class="font-medium">{{ t('Coefficient') }}: {{
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
                                                    {{ tournament.is_counting ? t('Counting') : t('Not Counting') }}
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
