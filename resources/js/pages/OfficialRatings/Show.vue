<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner, Textarea} from '@/Components/ui';
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
    Loader2,
    PencilIcon,
    StarIcon,
    TrophyIcon,
    UserIcon,
    UserPlusIcon,
    UsersIcon,
} from 'lucide-vue-next';
import {computed, nextTick, onMounted, ref} from 'vue';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useGameRules} from '@/composables/useGameRules';
import {useToastStore} from '@/stores/toast';
import {useSeo} from "@/composables/useSeo";
import {scrollToUserElement} from '@/utils/scrollToUser';
import UserAvatar from "@/Components/ui/UserAvatar.vue";

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    ratingId: number | string;
}>();

const {t} = useLocale();

const {isAdmin, isAuthenticated, user} = useAuth();
const {setSeoMeta, generateBreadcrumbJsonLd} = useSeo();

const rating = ref<OfficialRating | null>(null);
const players = ref<OfficialRatingPlayer[]>([]);
const tournaments = ref<OfficialRatingTournament[]>([]);
const isLoadingRating = ref(true);
const isLoadingPlayers = ref(true);
const isLoadingTournaments = ref(true);
const error = ref<string | null>(null);
const showScrollToUser = ref(false);
const deltaDate = ref('');
const ratingDelta = ref<RatingDelta | null>(null);
const isLoadingDelta = ref(false);
const tableRef = ref<HTMLElement | null>(null);

// Get initial tab from URL query parameter
const getInitialTab = (): 'players' | 'tournaments' | 'rules' => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const validTabs = ['players', 'tournaments', 'rules'];
    return validTabs.includes(tabParam as string) ? tabParam as any : 'players';
};

const activeTab = ref<'players' | 'tournaments' | 'rules'>(getInitialTab());

// Handle tab change and update URL
const switchTab = (tab: 'players' | 'tournaments' | 'rules') => {
    activeTab.value = tab;

    // Update URL without page reload
    const url = new URL(window.location.href);
    if (tab === 'players') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }

    window.history.replaceState({}, '', url.toString());
};

const {
    currentRule,
    isLoading: isLoadingRules,
    error: rulesError,
    fetchRuleByRating,
    updateRule,
    deleteRule,
    createRule
} = useGameRules();

const toastStore = useToastStore();

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
    if (!currentUserPlayer.value) return;

    await nextTick();

    const userId = currentUserPlayer.value.user?.id;
    const success = await scrollToUserElement(userId);

    if (!success) {
        toastStore.error(t('Could not find your position in the list'));
    }
};

const getPositionBadgeClass = (position: number): string => {
    switch (position) {
        case 1:
            return 'bg-gradient-to-r from-amber-500 to-yellow-500 text-white shadow-md';
        case 2:
            return 'bg-gradient-to-r from-gray-400 to-gray-500 text-white shadow-md';
        case 3:
            return 'bg-gradient-to-r from-orange-600 to-orange-700 text-white shadow-md';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
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

const isEditingRules = ref(false);

const startEditingRules = async () => {
    if (!currentRule.value) {
        currentRule.value = {
            id: 0,
            official_rating_id: Number(props.ratingId),
            rules: '',
            created_at: '',
            updated_at: ''
        };
    }
    isEditingRules.value = true;
};

const cancelEditRules = () => {
    isEditingRules.value = false;
    if (currentRule.value?.id === 0) {
        currentRule.value = null;
    }
};

const saveRules = async () => {
    if (!currentRule.value) return;

    try {
        if (currentRule.value.id === 0 || currentRule.value.id === undefined) {
            await createRule({
                official_rating_id: Number(props.ratingId),
                rules: currentRule.value.rules
            });
        } else {
            await updateRule(currentRule.value.id, {
                rules: currentRule.value.rules
            });
        }
        isEditingRules.value = false;
        toastStore.success(t('Rules updated successfully'));
    } catch (error: any) {
        console.error('Failed to save rules:', error);
        toastStore.error(error.message || t('Failed to save rules'));
    }
};

const deleteRules = async () => {
    if (!currentRule.value) return;

    if (!confirm(t('Are you sure you want to delete these rules?'))) {
        return;
    }

    try {
        await deleteRule(currentRule.value.id);
        toastStore.success(t('Rules deleted successfully'));
    } catch (e) {
        console.error('Failed to delete rules:', e);
        toastStore.error(t('Failed to delete rules'));
    }
};

// Add columns definition before the template
const columns = computed(() => [
    {
        key: 'position',
        label: t('Rank'),
        align: 'left' as const,
        render: (player: OfficialRatingPlayer) => ({
            position: player.position,
            isCurrentUser: isCurrentUser(player)
        })
    },
    {
        key: 'division',
        label: t('Division'),
        align: 'left' as const,
        hideOnMobile: true,
        render: (player: OfficialRatingPlayer) => ({
            division: player.division,
            isCurrentUser: isCurrentUser(player)
        })
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (player: OfficialRatingPlayer) => ({
            name: `${player.user?.firstname} ${player.user?.lastname}`,
            isCurrentUser: isCurrentUser(player),
            isChampion: player.position === 1
        })
    },
    {
        key: 'rating',
        label: t('Rating'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: OfficialRatingPlayer) => ({
            points: player.rating_points,
            isCurrentUser: isCurrentUser(player)
        })
    },
    {
        key: 'tournaments',
        label: t('Tournaments'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (player: OfficialRatingPlayer) => ({
            played: player.tournaments_played,
            isCurrentUser: isCurrentUser(player)
        })
    },
    {
        key: 'earned_money',
        label: t('Earned'),
        align: 'center' as const,
        mobileLabel: t('₴'),
        render: (player: OfficialRatingPlayer) => ({
            amount: player.total_prize_amount,
            isCurrentUser: isCurrentUser(player)
        })
    },
    {
        key: 'killerPoolPrize',
        label: t('Killer Pool'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (player: OfficialRatingPlayer) => ({
            amount: player.total_killer_pool_prize_amount,
            isCurrentUser: isCurrentUser(player)
        })
    },
    {
        key: 'achievement',
        label: t('Achievement'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (player: OfficialRatingPlayer) => ({
            amount: player.total_achievement_amount,
            isCurrentUser: isCurrentUser(player)
        })
    },
    {
        key: 'bonus',
        label: t('Bonus'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: OfficialRatingPlayer) => ({
            amount: player.total_bonus_amount,
            isCurrentUser: isCurrentUser(player)
        })
    },
    {
        key: 'lastTournament',
        label: t('Last Tournament'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (player: OfficialRatingPlayer) => ({
            date: player.last_tournament_at,
            isCurrentUser: isCurrentUser(player)
        })
    }
]);

const getRowClass = (player: OfficialRatingPlayer): string => {
    const baseClass = 'transition-colors duration-200';
    if (isCurrentUser(player)) {
        return `${baseClass} bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/30 border-l-4 border-indigo-500`;
    }
    return baseClass;
};

onMounted(() => {
    fetchRating().then(() => {
        if (rating.value) {
            setSeoMeta({
                title: t(':name - Official Billiard Rating System', {name: rating.value.name}),
                description: t('rating_desc', {
                    description: rating.value.description ? rating.value.description + '. ' : '',
                    game: rating.value.game_type_name || 'billiard'
                }),
                keywords: [rating.value.name, 'billiard rating', rating.value.game_type_name || 'pool', 'player rankings', 'ELO rating', 'tournament standings'],
                ogType: 'website',
                jsonLd: {
                    ...generateBreadcrumbJsonLd([
                        {name: t('Home'), url: window.location.origin},
                        {name: t('Official Ratings'), url: `${window.location.origin}/official-ratings`},
                        {
                            name: rating.value.name,
                            url: `${window.location.origin}/official-ratings/${rating.value.slug}`
                        }
                    ]),
                    "@context": "https://schema.org",
                    "@type": "SportsActivityLocation",
                    "name": rating.value.name,
                    "description": rating.value.description || t('Official billiard rating system'),
                    "sport": "Billiards"
                }
            });
        }
    });
    fetchPlayers();
    fetchTournaments();
    fetchRuleByRating(Number(props.ratingId));
});

</script>

<template>
    <Head :title="rating ? t('Rating: :name', {name: rating.name}) : t('Official Rating')"/>

    <div class="py-6 sm:py-8 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <header class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <Link href="/official-ratings">
                    <Button variant="outline" size="sm" class="inline-flex items-center">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Back to Ratings') }}</span>
                        <span class="sm:hidden">{{ t('Back') }}</span>
                    </Button>
                </Link>

                <div v-if="isAdmin && rating" class="flex flex-wrap gap-2">
                    <Link :href="`/admin/official-ratings/${rating.slug}/edit`">
                        <Button variant="secondary" size="sm">
                            <PencilIcon class="mr-2 h-4 w-4"/>
                            <span class="hidden sm:inline">{{ t('Edit Rating') }}</span>
                            <span class="sm:hidden">{{ t('Edit') }}</span>
                        </Button>
                    </Link>
                    <Link :href="`/admin/official-ratings/${rating.slug}/manage`">
                        <Button variant="secondary" size="sm">
                            <UserPlusIcon class="mr-2 h-4 w-4"/>
                            {{ t('Manage') }}
                        </Button>
                    </Link>
                </div>
            </header>

            <!-- Loading State -->
            <div v-if="isLoadingRating" class="flex justify-center py-12">
                <div class="text-center">
                    <Spinner class="text-primary mx-auto h-8 w-8 text-indigo-600"/>
                    <p class="mt-2 text-gray-500">{{ t('Loading rating...') }}</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error"
                 class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <p class="text-red-600 dark:text-red-400">{{ t('Error loading rating: :error', {error}) }}</p>
            </div>

            <!-- Rating Content -->
            <template v-else-if="rating">
                <!-- Rating Header Card -->
                <Card class="mb-8 shadow-lg overflow-hidden">
                    <div
                        class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <div
                                        class="h-12 w-12 rounded-full bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center shadow-md">
                                        <StarIcon class="h-6 w-6 text-white"/>
                                    </div>
                                    <div>
                                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                            {{ rating.name }}
                                            <span v-if="!rating.is_active"
                                                  class="text-xs sm:text-sm px-2 sm:px-3 py-1 bg-gray-100 text-gray-600 rounded-full dark:bg-gray-800 dark:text-gray-400">
                                                {{ t('Inactive') }}
                                            </span>
                                        </h1>
                                        <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mt-1">
                                            {{ rating.game_type_name || 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Stats Grid -->
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6 mt-4">
                                    <div class="text-center sm:text-left">
                                        <div class="flex items-center gap-2 justify-center sm:justify-start">
                                            <UsersIcon class="h-4 w-4 text-gray-400"/>
                                            <span class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{
                                                    rating.players_count
                                                }}</span>
                                        </div>
                                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{
                                                t('players')
                                            }}</p>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <div class="flex items-center gap-2 justify-center sm:justify-start">
                                            <TrophyIcon class="h-4 w-4 text-gray-400"/>
                                            <span class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{
                                                    rating.tournaments_count
                                                }}</span>
                                        </div>
                                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                            {{ t('tournaments') }}</p>
                                    </div>
                                    <div v-if="currentUserPlayer" class="col-span-2 sm:col-span-1">
                                        <div
                                            class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 bg-indigo-100 text-indigo-800 rounded-full dark:bg-indigo-900/30 dark:text-indigo-300">
                                            <UserIcon class="h-4 w-4"/>
                                            <span class="text-sm sm:text-base font-medium">{{
                                                    t('Your position')
                                                }}: #{{ currentUserPlayer.position }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <CardContent class="p-6 sm:p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{
                                        t('Description')
                                    }}</h4>
                                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                                    {{ rating.description || t('No description provided.') }}
                                </p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{
                                        t('Rating Details')
                                    }}</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm sm:text-base">
                                        <span class="text-gray-600 dark:text-gray-400">{{ t('Initial Rating') }}:</span>
                                        <span class="font-medium">{{ rating.initial_rating }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm sm:text-base">
                                        <span class="text-gray-600 dark:text-gray-400">{{
                                                t('Calculation Method')
                                            }}:</span>
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
                <nav class="mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto" role="navigation"
                     aria-label="Rating tabs">
                    <div class="-mb-px flex space-x-6 sm:space-x-8 min-w-max">
                        <button
                            :class="[
                                'py-4 px-1 text-sm sm:text-base font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'players'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'players'"
                            role="tab"
                            @click="switchTab('players')"
                        >
                            {{ t('Players') }} ({{ rating.players_count }})
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm sm:text-base font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'tournaments'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'tournaments'"
                            role="tab"
                            @click="switchTab('tournaments')"
                        >
                            {{ t('Tournaments') }} ({{ rating.tournaments_count }})
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm sm:text-base font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'rules'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'rules'"
                            role="tab"
                            @click="switchTab('rules')"
                        >
                            {{ t('Rules') }}
                        </button>
                    </div>
                </nav>

                <!-- Players Tab -->
                <main v-if="activeTab === 'players'" role="tabpanel">
                    <Card class="shadow-lg">
                        <CardHeader
                            class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <CrownIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                                        {{ t('Player Rankings') }}
                                    </CardTitle>
                                    <CardDescription class="mt-1">
                                        {{ t('Current standings in the :rating rating', {rating: rating.name}) }}
                                    </CardDescription>
                                </div>

                                <!-- Find Me Button -->
                                <Button
                                    v-if="showScrollToUser && currentUserPlayer"
                                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white"
                                    size="sm"
                                    @click="scrollToUser"
                                >
                                    <UserIcon class="h-4 w-4"/>
                                    {{ t('Find Me') }} (#{{ currentUserPlayer.position }})
                                    <ChevronDownIcon class="h-4 w-4"/>
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <!-- Delta Controls -->
                            <div v-if="isAuthenticated"
                                 class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                                    <div class="flex gap-2">
                                        <input v-model="deltaDate"
                                               class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600"
                                               type="date"/>
                                        <Button :disabled="!deltaDate || isLoadingDelta" size="sm" @click="fetchDelta">
                                            {{ t('Load Delta') }}
                                        </Button>
                                    </div>
                                    <Spinner v-if="isLoadingDelta" class="text-primary h-4 w-4 text-indigo-600"/>
                                    <div v-if="ratingDelta" class="text-sm">
                                        <span class="font-medium">{{
                                                t('Change since :date:', {date: deltaDate})
                                            }}</span>
                                        <span :class="[
                                            'ml-2 font-bold',
                                            ratingDelta.points_delta >= 0 ? 'text-emerald-600' : 'text-red-600'
                                        ]">
                                            {{
                                                ratingDelta.points_delta >= 0 ? `+${ratingDelta.points_delta}` : ratingDelta.points_delta
                                            }} pts
                                        </span>
                                        <span class="ml-2 text-gray-600 dark:text-gray-400">
                                            {{
                                                t('position :before → :after', {
                                            before: ratingDelta.position_before,
                                            after: ratingDelta.current_position
                                                })
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6 text-indigo-600"/>
                            </div>
                            <div v-else-if="allActivePlayers.length === 0" class="py-8 text-center text-gray-500">
                                {{ t('No active players in this rating.') }}
                            </div>
                            <div v-else>
                                <DataTable
                                    ref="tableRef"
                                    :columns="columns"
                                    :compact-mode="true"
                                    :data="allActivePlayers"
                                    :empty-message="t('No active players in this rating.')"
                                    :loading="isLoadingPlayers"
                                    :row-attributes="(player) => ({
                                        'data-user-id': player.user?.id?.toString()
                                    })"
                                    :row-class="getRowClass"
                                    :mobile-card-mode="true"
                                >
                                    <template #cell-position="{ value }">
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="[
                                                    'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold',
                                                    getPositionBadgeClass(value.position)
                                                ]"
                                            >
                                                {{ value.position }}
                                            </span>
                                            <UserIcon
                                                v-if="value.isCurrentUser"
                                                class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                                                :title="t('This is you!')"
                                            />
                                        </div>
                                    </template>

                                    <template #cell-division="{ value }">
                                        <span
                                            :class="[
                                                'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                                value.isCurrentUser
                                                    ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300'
                                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                            ]"
                                        >
                                            {{ value.division }}
                                        </span>
                                    </template>

                                    <template #cell-player="{ value, item }">
                                        <div class="flex items-center gap-2">
                                            <UserAvatar
                                                :user="item.user"
                                                size="sm"
                                                priority="tournament_picture"
                                                :exclusive-priority="true"
                                            />
                                            <div>
                                                <p :class="[
                                                    'font-medium',
                                                    value.isCurrentUser
                                                        ? 'text-indigo-900 dark:text-indigo-100'
                                                        : 'text-gray-900 dark:text-gray-100'
                                                ]">
                                                    {{ value.name }}
                                                    <span v-if="value.isCurrentUser"
                                                          class="text-xs text-indigo-600 dark:text-indigo-400 ml-1">({{
                                                            t('You')
                                                        }})</span>
                                                </p>
                                                <p v-if="value.isChampion"
                                                   class="text-sm text-amber-600 dark:text-amber-400">
                                                    👑 {{ t('Leader') }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>

                                    <template #cell-rating="{ value }">
                                        <span :class="[
                                            'font-bold text-lg',
                                            value.isCurrentUser
                                                ? 'text-indigo-900 dark:text-indigo-100'
                                                : 'text-gray-900 dark:text-gray-100'
                                        ]">
                                            {{ value.points }}
                                        </span>
                                    </template>

                                    <template #cell-tournaments="{ value }">
                                        <span
                                            :class="value.isCurrentUser ? 'font-semibold text-indigo-900 dark:text-indigo-100' : ''">
                                            {{ value.played }}
                                        </span>
                                    </template>

                                    <template #cell-earned_money="{ value }">
                                        <span v-if="value.amount > 0" :class="[
                                            'font-medium text-emerald-600 dark:text-emerald-400',
                                            value.isCurrentUser ? 'font-bold' : ''
                                        ]">
                                            {{ formatCurrency(value.amount) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-killerPoolPrize="{ value }">
                                        <span v-if="value.amount > 0" :class="[
                                            'font-medium text-purple-600 dark:text-purple-400',
                                            value.isCurrentUser ? 'font-bold' : ''
                                        ]">
                                            {{ formatCurrency(value.amount) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-achievement="{ value }">
                                        <span v-if="value.amount > 0" :class="[
                                            'font-medium text-blue-600 dark:text-blue-400',
                                            value.isCurrentUser ? 'font-bold' : ''
                                        ]">
                                            {{ formatCurrency(value.amount) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-bonus="{ value }">
                                        <span v-if="value.amount > 0" :class="[
                                            'font-medium text-orange-600 dark:text-orange-400',
                                            value.isCurrentUser ? 'font-bold' : ''
                                        ]">
                                            {{ value.amount }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-lastTournament="{ value }">
                                        <span v-if="value.date"
                                              :class="value.isCurrentUser ? 'font-semibold text-indigo-900 dark:text-indigo-100' : ''">
                                            {{ formatDate(value.date) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <!-- Mobile card primary info -->
                                    <template #mobile-primary="{ item }">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-3">
                                                <span :class="[
                                                    'inline-flex h-10 w-10 items-center justify-center rounded-full text-base font-bold',
                                                    getPositionBadgeClass(item.position)
                                                ]">
                                                    {{ item.position }}
                                                </span>
                                                <UserAvatar
                                                    :user="item.user"
                                                    size="md"
                                                    priority="tournament_picture"
                                                    :exclusive-priority="true"
                                                />
                                                <div>
                                                    <h3 :class="[
                                                        'text-base font-semibold',
                                                        isCurrentUser(item)
                                                            ? 'text-indigo-900 dark:text-indigo-100'
                                                            : 'text-gray-900 dark:text-white'
                                                    ]">
                                                        {{ item.user?.firstname }} {{ item.user?.lastname }}
                                                        <span v-if="isCurrentUser(item)"
                                                              class="text-xs text-indigo-600 dark:text-indigo-400 ml-1">({{
                                                                t('You')
                                                            }})</span>
                                                    </h3>
                                                    <p v-if="item.position === 1"
                                                       class="text-sm text-amber-600 dark:text-amber-400">
                                                        👑 {{ t('Leader') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                                    {{ formatCurrency(item.total_prize_amount || 0) }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ item.rating_points }} {{ t('pts') }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                </DataTable>
                            </div>
                        </CardContent>
                    </Card>
                </main>

                <!-- Tournaments Tab -->
                <main v-if="activeTab === 'tournaments'" role="tabpanel">
                    <Card class="shadow-lg">
                        <CardHeader
                            class="bg-gradient-to-r from-gray-50 to-emerald-50 dark:from-gray-800 dark:to-gray-700">
                            <CardTitle class="flex items-center gap-2">
                                <TrophyIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400"/>
                                {{ t('Associated Tournaments') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('Tournaments that count towards this rating') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-4 sm:p-6">
                            <div v-if="isLoadingTournaments" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6 text-indigo-600"/>
                            </div>
                            <div v-else-if="tournaments.length === 0" class="py-8 text-center text-gray-500">
                                {{ t('No tournaments associated with this rating.') }}
                            </div>
                            <div v-else class="space-y-4">
                                <article
                                    v-for="tournament in tournaments"
                                    :key="tournament.id"
                                    :class="[
                                        'p-4 sm:p-6 border-2 rounded-lg transition-all hover:shadow-md',
                                        tournament.is_counting
                                            ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20'
                                            : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50'
                                    ]"
                                >
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                                {{ tournament.name }}
                                            </h3>
                                            <div class="flex flex-wrap gap-3 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="flex items-center gap-1">
                                                    <CalendarIcon class="h-4 w-4"/>
                                                    {{ formatDate(tournament.start_date) }}
                                                    <span v-if="tournament.end_date !== tournament.start_date">
                                                        - {{ formatDate(tournament.end_date) }}
                                                    </span>
                                                </span>
                                                <span v-if="tournament.city" class="flex items-center gap-1">
                                                    📍 {{ tournament.city }}, {{ tournament.country }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <UsersIcon class="h-4 w-4"/>
                                                    {{ tournament.players_count }} {{ t('players') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex sm:flex-col items-center sm:items-end gap-3">
                                            <div class="text-sm">
                                                <span class="font-medium">{{ t('Coefficient') }}: {{
                                                        tournament.rating_coefficient
                                                    }}x</span>
                                            </div>
                                            <span
                                                :class="[
                                                    'px-3 py-1 text-xs font-medium rounded-full',
                                                    tournament.is_counting
                                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                                ]"
                                            >
                                                {{ tournament.is_counting ? t('Counting') : t('Not Counting') }}
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </CardContent>
                    </Card>
                </main>

                <!-- Rules Tab -->
                <main v-if="activeTab === 'rules'" role="tabpanel">
                    <Card class="shadow-lg">
                        <CardHeader
                            class="bg-gradient-to-r from-gray-50 to-purple-50 dark:from-gray-800 dark:to-gray-700">
                            <CardTitle class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <span class="flex items-center gap-2">
                                    <PencilIcon class="h-5 w-5 text-purple-600 dark:text-purple-400"/>
                                    {{ t('Game Rules') }}
                                </span>
                                <div v-if="isAdmin" class="flex items-center gap-2">
                                    <Button
                                        v-if="!isEditingRules"
                                        :disabled="isLoadingRules"
                                        size="sm"
                                        variant="outline"
                                        @click="startEditingRules"
                                    >
                                        <PencilIcon class="h-4 w-4 mr-2"/>
                                        {{ currentRule ? t('Edit Rules') : t('Add Rules') }}
                                    </Button>
                                    <template v-else>
                                        <Button
                                            :disabled="isLoadingRules"
                                            size="sm"
                                            variant="outline"
                                            @click="cancelEditRules"
                                        >
                                            {{ t('Cancel') }}
                                        </Button>
                                        <Button
                                            :disabled="isLoadingRules"
                                            size="sm"
                                            variant="default"
                                            @click="saveRules"
                                        >
                                            <Spinner v-if="isLoadingRules" class="h-4 w-4 mr-2"/>
                                            {{ t('Save') }}
                                        </Button>
                                    </template>
                                    <Button
                                        v-if="currentRule && currentRule.id > 0 && !isEditingRules"
                                        :disabled="isLoadingRules"
                                        size="sm"
                                        variant="destructive"
                                        @click="deleteRules"
                                    >
                                        {{ t('Delete') }}
                                    </Button>
                                </div>
                            </CardTitle>
                            <CardDescription>{{ t('Official rating rules and regulations') }}</CardDescription>
                        </CardHeader>
                        <CardContent class="p-4 sm:p-6">
                            <div v-if="isLoadingRules" class="flex justify-center py-4">
                                <Loader2 class="h-6 w-6 animate-spin text-indigo-600"/>
                            </div>
                            <div v-else-if="rulesError" class="text-red-500">
                                {{ rulesError }}
                            </div>
                            <div v-else>
                                <div v-if="!isEditingRules">
                                    <div v-if="currentRule?.rules"
                                         class="prose prose-sm sm:prose max-w-none whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                                        {{ currentRule.rules }}
                                    </div>
                                    <div v-else class="text-gray-500 text-center py-8">
                                        <p class="text-lg">{{ t('No rules defined yet') }}</p>
                                        <p v-if="isAdmin" class="text-sm mt-2">
                                            {{ t('Click "Add Rules" to create game rules for this rating.') }}
                                        </p>
                                    </div>
                                </div>
                                <div v-else class="space-y-4">
                                    <Textarea
                                        :disabled="isLoadingRules"
                                        :model-value="currentRule?.rules || ''"
                                        :placeholder="t('Enter game rules here...')"
                                        class="min-h-64"
                                        rows="10"
                                        @update:model-value="(value) => {
                                            if (currentRule) {
                                                currentRule.rules = value;
                                            }
                                        }"
                                    />
                                    <p class="text-sm text-gray-500">
                                        {{ t('Define the official rules and regulations for this rating system.') }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </main>
            </template>
        </div>
    </div>
</template>
