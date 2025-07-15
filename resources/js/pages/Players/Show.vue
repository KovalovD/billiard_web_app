<!-- resources/js/Pages/Players/Show.vue -->
<script lang="ts" setup>
import {
    Button,
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    Input,
    Label,
    Spinner,
} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import UserAvatar from '@/Components/Core/UserAvatar.vue';
import {useAuth} from '@/composables/useAuth';
import {useLocale} from '@/composables/useLocale';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import {Head, router} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    AwardIcon,
    CakeIcon,
    CalendarIcon,
    ChartBarIcon,
    ChevronRightIcon,
    ClockIcon,
    GamepadIcon,
    InfoIcon,
    MapPinIcon,
    MedalIcon,
    PackageIcon,
    SearchIcon,
    StarIcon,
    SwordsIcon,
    TrendingUpIcon,
    TrophyIcon,
    UserIcon,
    XCircleIcon,
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import DetailedMatchStats from "@/Components/Players/DetailedMatchStats.vue";

interface Equipment {
    id?: string;
    type: 'cue' | 'case' | 'chalk' | 'glove' | 'other';
    brand: string;
    model?: string;
    description?: string;
}

interface PlayerDetail {
    id: number;
    slug: string;
    firstname: string;
    lastname: string;
    full_name: string;
    email: string;
    phone: string;
    sex: string;
    sex_value?: string;
    birthdate?: string;
    description?: string;
    equipment?: Equipment[];
    picture?: string;
    tournament_picture?: string;
    avatar?: string;
    created_at: string;
    home_city?: {
        id: number;
        name: string;
        country?: {
            id: number;
            name: string;
        };
    };
    home_club?: {
        id: number;
        name: string;
        city?: {
            id: number;
            name: string;
        };
    };
    tournament_stats: {
        total_tournaments: number;
        tournaments_won: number;
        tournaments_top3: number;
        total_prize_won: number;
        total_rating_points: number;
        win_rate: number;
        top3_rate: number;
        win_rate_by_type: Record<string, {
            total: number;
            wins: number;
            win_rate: number;
        }>;
    };
    league_stats: Array<{
        league_id: number;
        league_name: string;
        game_name: string;
        game_type: string;
        rating: number;
        position: number;
        matches_played: number;
        matches_won: number;
        win_rate: number;
        is_active: boolean;
    }>;
    official_ratings: Array<{
        official_rating_id: number;
        rating_name: string;
        game_name: string;
        rating_points: number;
        position: number;
        division: string;
        tournaments_played: number;
        tournaments_won: number;
        win_rate: number;
        last_tournament_at: string | null;
        total_prize_amount: number;
        total_money_earned: number;
    }>;
    recent_tournaments: Array<{
        tournament_id: number;
        tournament_name: string;
        game_name: string;
        city: string | null;
        country: string | null;
        club: string | null;
        start_date: string;
        end_date: string;
        position: number | null;
        prize_amount: number;
        rating_points: number;
        players_count: number;
    }>;
    recent_matches: Array<{
        match_id: number;
        tournament_name: string;
        game_name: string;
        match_stage: string;
        match_round: string;
        opponent: string;
        opponent_id: number | null;
        score: string;
        won: boolean;
        races_to: number;
        club: string | null;
        completed_at: string;
    }>;
    achievements: Array<{
        type: string;
        name: string;
        description: string;
        icon: string;
    }>;
}

interface HeadToHeadStats {
    summary: {
        total_matches: number;
        player1_wins: number;
        player2_wins: number;
        player1_win_rate: number;
        player2_win_rate: number;
        player1_games_won: number;
        player2_games_won: number;
        games_difference: number;
    };
    players: {
        player1: {
            id: number;
            full_name: string;
            picture?: string;
            tournament_picture?: string;
            avatar?: string;
        };
        player2: {
            id: number;
            full_name: string;
            picture?: string;
            tournament_picture?: string;
            avatar?: string;
        };
    };
    match_history: Array<{
        match_id: number;
        tournament_name: string;
        game_name: string;
        match_stage: string;
        match_round: string;
        player1_score: number;
        player2_score: number;
        winner_id: number;
        races_to: number;
        club: string | null;
        completed_at: string;
    }>;
    by_tournament_type: Record<string, {
        tournament_type: string;
        total_matches: number;
        player1_wins: number;
        player2_wins: number;
        player1_win_rate: number;
    }>;
}

interface PlayerSearchResult {
    id: number;
    full_name: string;
    firstname?: string;
    lastname?: string;
    picture?: string;
    tournament_picture?: string;
    avatar?: string;
    home_city?: { name: string };
    home_club?: { name: string };
}

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    playerSlug: string;
    playerId: number;
}>();

const {isAuthenticated, user} = useAuth();
const {t} = useLocale();
const {setSeoMeta, generateBreadcrumbJsonLd} = useSeo();

// State
const player = ref<PlayerDetail | null>(null);
const isLoading = ref(true);
const error = ref<string | null>(null);

// Head-to-head state
const showHeadToHeadModal = ref(false);
const headToHeadStats = ref<HeadToHeadStats | null>(null);
const isHeadToHeadLoading = ref(false);
const headToHeadError = ref<string | null>(null);

// Player search state
const searchQuery = ref('');
const searchResults = ref<PlayerSearchResult[]>([]);
const isSearching = ref(false);
const selectedOpponent = ref<PlayerSearchResult | null>(null);

// Get initial tab from URL query parameter
const getInitialTab = (): 'overview' | 'tournaments' | 'matches' | 'ratings' | 'achievements' | 'detailed-stats' => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const validTabs = ['overview', 'tournaments', 'matches', 'ratings', 'achievements', 'detailed-stats'];
    return validTabs.includes(tabParam as string) ? tabParam as any : 'overview';
};

// Active tab state
const activeTab = ref<'overview' | 'tournaments' | 'matches' | 'ratings' | 'achievements' | 'detailed-stats'>(getInitialTab());

// Computed
const isCurrentUser = computed(() => {
    return isAuthenticated.value && user.value?.id === player.value?.id;
});

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + '₴';
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('uk-UK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatDateTime = (dateString: string): string => {
    return new Date(dateString).toLocaleString('uk-UK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const calculateAge = (birthdate: string): number => {
    const today = new Date();
    const birth = new Date(birthdate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }

    return age;
};

const getPositionBadgeClass = (position: number | null): string => {
    if (!position) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    if (position === 1) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
    if (position === 2) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    if (position === 3) return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
};

const getWinRateClass = (rate: number): string => {
    if (rate >= 70) return 'text-green-600 dark:text-green-400';
    if (rate >= 50) return 'text-blue-600 dark:text-blue-400';
    if (rate >= 30) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400';
};

const equipmentTypes = [
    {value: 'cue', label: t('Cue'), icon: '🎱'},
    {value: 'case', label: t('Case'), icon: '💼'},
    {value: 'chalk', label: t('Chalk'), icon: '🟦'},
    {value: 'glove', label: t('Glove'), icon: '🧤'},
    {value: 'other', label: t('Other'), icon: '📦'}
];

const getEquipmentIcon = (type: string) => {
    return equipmentTypes.find(t => t.value === type)?.icon || '📦';
};

const getEquipmentLabel = (type: string) => {
    return equipmentTypes.find(t => t.value === type)?.label || type;
};

// Methods
const fetchPlayer = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        player.value = await apiClient<PlayerDetail>(`/api/players/${props.playerId}`);

        setSeoMeta({
            title: `${player.value.full_name} - Player Profile`,
            description: `View ${player.value.full_name}'s tournament statistics, achievements, and match history.`,
            keywords: ['billiard player', 'pool player', 'tournament statistics', 'player profile'],
            ogType: 'profile',
            jsonLd: {
                ...generateBreadcrumbJsonLd([
                    {name: t('Home'), url: window.location.origin},
                    {name: t('Players'), url: `${window.location.origin}/players`},
                    {name: player.value.full_name, url: window.location.href}
                ]),
                "@context": "https://schema.org",
                "@type": "Person",
                "name": player.value.full_name,
                "description": `Billiard player profile for ${player.value.full_name}`,
                "url": window.location.href
            }
        });
    } catch (err: any) {
        error.value = err.message || t('Failed to load player data');
    } finally {
        isLoading.value = false;
    }
};

const searchPlayers = async (query: string) => {
    if (query.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const results = await apiClient<any>(`/api/players?name=${encodeURIComponent(query)}&per_page=10`);
        searchResults.value = results.data.filter((p: PlayerSearchResult) => p.id !== player.value?.id);
    } catch (err) {
        console.error('Failed to search players:', err);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const fetchHeadToHeadStats = async (opponentId: number) => {
    isHeadToHeadLoading.value = true;
    headToHeadError.value = null;

    try {
        headToHeadStats.value = await apiClient<HeadToHeadStats>(`/api/players/${props.playerId}/vs/${opponentId}`);
        showHeadToHeadModal.value = true;
    } catch (err: any) {
        headToHeadError.value = err.message || t('Failed to load head-to-head statistics');
    } finally {
        isHeadToHeadLoading.value = false;
    }
};

const handleHeadToHeadSearch = async (query: string | number | undefined) => {
    const searchValue = query?.toString() || '';
    searchQuery.value = searchValue;
    await searchPlayers(searchValue);
};

const selectOpponent = (opponent: PlayerSearchResult) => {
    selectedOpponent.value = opponent;
    fetchHeadToHeadStats(opponent.id);
};

const resetHeadToHeadState = () => {
    selectedOpponent.value = null;
    headToHeadStats.value = null;
    headToHeadError.value = null;
    searchQuery.value = '';
    searchResults.value = [];
};

// Handle tab change and update URL
const switchTab = (tab: 'overview' | 'tournaments' | 'matches' | 'ratings' | 'achievements' | 'detailed-stats') => {
    activeTab.value = tab;

    // Update URL without page reload
    const url = new URL(window.location.href);
    if (tab === 'overview') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }

    window.history.replaceState({}, '', url.toString());
};

// Table columns for recent tournaments
const tournamentColumns = computed(() => [
    {
        key: 'tournament',
        label: t('Tournament'),
        align: 'left' as const,
        render: (tournament: any) => ({
            name: tournament.tournament_name,
            game: tournament.game_name,
            location: tournament.city || tournament.country
        })
    },
    {
        key: 'position',
        label: t('Position'),
        align: 'center' as const,
        render: (tournament: any) => tournament.position
    },
    {
        key: 'prize',
        label: t('Prize'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (tournament: any) => tournament.prize_amount
    },
    {
        key: 'rating_points',
        label: t('Rating'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (tournament: any) => tournament.rating_points
    },
    {
        key: 'date',
        label: t('Date'),
        align: 'center' as const,
        render: (tournament: any) => tournament.start_date
    }
]);

// Table columns for recent matches
const matchColumns = computed(() => [
    {
        key: 'match',
        label: t('Match'),
        align: 'left' as const,
        render: (match: any) => ({
            tournament: match.tournament_name,
            game: match.game_name,
            stage: match.match_stage,
            round: match.match_round
        })
    },
    {
        key: 'opponent',
        label: t('Opponent'),
        align: 'left' as const,
        render: (match: any) => match.opponent
    },
    {
        key: 'result',
        label: t('Result'),
        align: 'center' as const,
        render: (match: any) => ({
            score: match.score,
            won: match.won,
            racesTo: match.races_to
        })
    },
    {
        key: 'date',
        label: t('Date'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (match: any) => match.completed_at
    }
]);

// Lifecycle
onMounted(() => {
    fetchPlayer();
});
</script>

<template>
    <Head :title="player ? `${player.full_name} - Player Profile` : 'Player Profile'"/>

    <div class="py-4 sm:py-6 lg:py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Mobile-optimized Header -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="router.visit('/players')"
                        class="flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                    >
                        <ArrowLeftIcon class="h-3.5 w-3.5 mr-1.5"/>
                        <span class="hidden sm:inline">{{ t('Back to Players') }}</span>
                        <span class="sm:hidden">{{ t('Back') }}</span>
                    </Button>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    @click="showHeadToHeadModal = true"
                >
                    <SwordsIcon class="mr-1.5 h-3.5 w-3.5"/>
                    <span class="hidden sm:inline">{{ t('Head to Head') }}</span>
                    <span class="sm:hidden">{{ t('H2H') }}</span>
                </Button>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex justify-center items-center py-8">
                <Spinner class="h-6 w-6"/>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center py-8">
                <XCircleIcon class="h-10 w-10 text-red-500 mx-auto mb-3"/>
                <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-1">
                    {{ t('Error Loading Player') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ error }}</p>
            </div>

            <!-- Player Content -->
            <div v-else-if="player" class="space-y-4">
                <!-- Player Info Card -->
                <Card class="mb-6 shadow-lg overflow-hidden">
                    <div
                        class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-gray-700 p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2.5 mb-2">
                                    <UserAvatar
                                        :user="player"
                                        size="lg"
                                        priority="tournament_picture"
                                    />
                                    <div>
                                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                            {{ player.full_name }}
                                            <span v-if="isCurrentUser"
                                                  class="text-xs px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                                                {{ t('You') }}
                                            </span>
                                        </h1>
                                        <div
                                            class="flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                            <div v-if="player.home_city" class="flex items-center">
                                                <MapPinIcon class="h-3.5 w-3.5 mr-1"/>
                                                {{
                                                    player.home_city.name
                                                }}{{
                                                    player.home_city.country ? `, ${player.home_city.country.name}` : ''
                                                }}
                                            </div>
                                            <div v-if="player.home_club" class="flex items-center">
                                                <TrophyIcon class="h-3.5 w-3.5 mr-1"/>
                                                {{ player.home_club.name }}
                                            </div>
                                            <div v-if="player.created_at" class="flex items-center">
                                                <CalendarIcon class="h-3.5 w-3.5 mr-1"/>
                                                {{ t('Member since') }} {{ formatDate(player.created_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stats Grid -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                                    <div class="text-center sm:text-left">
                                        <div class="flex items-center gap-1.5 justify-center sm:justify-start">
                                            <TrophyIcon class="h-3.5 w-3.5 text-blue-500"/>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                                {{ player.tournament_stats.total_tournaments }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ t('Tournaments') }}</p>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <div class="flex items-center gap-1.5 justify-center sm:justify-start">
                                            <AwardIcon class="h-3.5 w-3.5 text-green-500"/>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                                {{ player.tournament_stats.tournaments_won }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{
                                                t('Wins')
                                            }}</p>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <div class="flex items-center gap-1.5 justify-center sm:justify-start">
                                            <TrendingUpIcon class="h-3.5 w-3.5 text-yellow-500"/>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                                {{ Math.round(player.tournament_stats.win_rate) }}%
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{
                                                t('Win Rate')
                                            }}</p>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <div class="flex items-center gap-1.5 justify-center sm:justify-start">
                                            <StarIcon class="h-3.5 w-3.5 text-purple-500"/>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                                {{ formatCurrency(player.tournament_stats.total_prize_won) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ t('Total Prize') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <CardContent class="p-4 sm:p-6">
                        <!-- Player Info Section -->
                        <div class="space-y-4">
                            <!-- Personal Information -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div v-if="player.birthdate" class="flex items-center gap-2.5">
                                    <CakeIcon class="h-4 w-4 text-gray-400"/>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Age') }}</p>
                                        <p class="text-sm font-medium">
                                            {{ calculateAge(player.birthdate) }} {{ t('years') }}
                                            <span class="text-xs text-gray-500 ml-1">({{
                                                    formatDate(player.birthdate)
                                                }})</span>
                                        </p>
                                    </div>
                                </div>

                                <div v-if="player.sex_value" class="flex items-center gap-2.5">
                                    <UserIcon class="h-4 w-4 text-gray-400"/>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Gender') }}</p>
                                        <p class="text-sm font-medium">{{ player.sex_value }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div v-if="player.description" class="border-t pt-4">
                                <div class="flex items-center gap-1.5 mb-2">
                                    <InfoIcon class="h-4 w-4 text-gray-400"/>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{
                                            t('About')
                                        }}</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{
                                        player.description
                                    }}</p>
                            </div>

                            <!-- Equipment -->
                            <div v-if="player.equipment && player.equipment.length > 0" class="border-t pt-4">
                                <div class="flex items-center gap-1.5 mb-3">
                                    <PackageIcon class="h-4 w-4 text-gray-400"/>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{
                                            t('Equipment')
                                        }}</h4>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div
                                        v-for="(item, index) in player.equipment"
                                        :key="item.id || index"
                                        class="flex items-center gap-2.5 p-2 bg-gray-50 dark:bg-gray-800/50 rounded-lg"
                                    >
                                        <span class="text-xl">{{ getEquipmentIcon(item.type) }}</span>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ item.brand }}
                                                <span v-if="item.model" class="text-gray-600 dark:text-gray-400">
                                                    - {{ item.model }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ getEquipmentLabel(item.type) }}
                                                <span v-if="item.description" class="ml-1">
                                                    • {{ item.description }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Statistics Summary -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 border-t pt-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1.5">
                                        {{ t('Player Statistics') }}</h4>
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{
                                                    t('Top 3 Finishes')
                                                }}:</span>
                                            <span class="font-medium">{{
                                                    player.tournament_stats.tournaments_top3
                                                }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{ t('Top 3 Rate') }}:</span>
                                            <span class="font-medium">{{
                                                    Math.round(player.tournament_stats.top3_rate)
                                                }}%</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{
                                                    t('Rating Points')
                                                }}:</span>
                                            <span class="font-medium">{{
                                                    player.tournament_stats.total_rating_points
                                                }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1.5">{{
                                            t('Active Leagues')
                                        }}</h4>
                                    <div v-if="player.league_stats.filter(l => l.is_active).length > 0"
                                         class="space-y-1">
                                        <div v-for="league in player.league_stats.filter(l => l.is_active).slice(0, 3)"
                                             :key="league.league_id" class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{
                                                    league.league_name
                                                }}:</span>
                                            <span class="font-medium">#{{ league.position }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ t('No active leagues') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tab Navigation -->
                <nav class="mb-4 border-b border-gray-200 dark:border-gray-700 overflow-x-auto" role="navigation"
                     aria-label="Player tabs">
                    <div class="-mb-px flex space-x-4 sm:space-x-6 min-w-max">
                        <button
                            id="tab-overview"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'overview'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'overview'"
                            role="tab"
                            @click="switchTab('overview')"
                        >
                            <span class="flex items-center gap-1.5">
                                <ChartBarIcon class="h-3.5 w-3.5"/>
                                {{ t('Overview') }}
                            </span>
                        </button>
                        <button
                            id="tab-tournaments"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'tournaments'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'tournaments'"
                            role="tab"
                            @click="switchTab('tournaments')"
                        >
                            <span class="flex items-center gap-1.5">
                                <TrophyIcon class="h-3.5 w-3.5"/>
                                {{ t('Tournaments') }}
                                <span v-if="player"
                                      class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                    {{ player.recent_tournaments.length }}
                                </span>
                            </span>
                        </button>
                        <button
                            id="tab-matches"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'matches'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'matches'"
                            role="tab"
                            @click="switchTab('matches')"
                        >
                            <span class="flex items-center gap-1.5">
                                <GamepadIcon class="h-3.5 w-3.5"/>
                                {{ t('Matches') }}
                                <span v-if="player"
                                      class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                    {{ player.recent_matches.length }}
                                </span>
                            </span>
                        </button>
                        <button
                            id="tab-ratings"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'ratings'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'ratings'"
                            role="tab"
                            @click="switchTab('ratings')"
                        >
                            <span class="flex items-center gap-1.5">
                                <StarIcon class="h-3.5 w-3.5"/>
                                {{ t('Ratings') }}
                                <span v-if="player"
                                      class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                    {{ player.official_ratings.length }}
                                </span>
                            </span>
                        </button>
                        <button
                            id="tab-achievements"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'achievements'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'achievements'"
                            role="tab"
                            @click="switchTab('achievements')"
                        >
                            <span class="flex items-center gap-1.5">
                                <AwardIcon class="h-3.5 w-3.5"/>
                                {{ t('Achievements') }}
                                <span v-if="player"
                                      class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                    {{ player.achievements.length }}
                                </span>
                            </span>
                        </button>
                        <button
                            id="tab-detailed-stats"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'detailed-stats'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'detailed-stats'"
                            role="tab"
                            @click="switchTab('detailed-stats')"
                        >
                            <span class="flex items-center gap-1.5">
                                <ChartBarIcon class="h-3.5 w-3.5"/>
                                {{ t('Detailed Stats') }}
                            </span>
                        </button>
                    </div>
                </nav>

                <!-- Tab Content -->
                <main role="tabpanel">
                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- League Stats -->
                            <Card>
                                <CardHeader class="py-3">
                                    <CardTitle class="flex items-center gap-1.5 text-base">
                                        <TrendingUpIcon class="h-4 w-4"/>
                                        {{ t('League Performance') }}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-4">
                                    <div v-if="player.league_stats.length > 0" class="space-y-3">
                                        <div v-for="league in player.league_stats" :key="league.league_id"
                                             class="border rounded-lg p-3">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <h4 class="text-sm font-medium">{{ league.league_name }}</h4>
                                                <span
                                                    :class="['px-1.5 py-0.5 text-xs font-medium rounded-full', getPositionBadgeClass(league.position)]">
                                                    #{{ league.position }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-1.5 text-xs">
                                                <div>
                                                    <span class="text-gray-500">{{ t('Rating') }}:</span>
                                                    <span class="font-medium ml-1">{{ league.rating }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">{{ t('Matches') }}:</span>
                                                    <span class="font-medium ml-1">{{ league.matches_played }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">{{ t('Wins') }}:</span>
                                                    <span class="font-medium ml-1">{{ league.matches_won }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">{{ t('Win Rate') }}:</span>
                                                    <span
                                                        :class="['font-medium ml-1', getWinRateClass(league.win_rate)]">
                                                        {{ Math.round(league.win_rate) }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-sm text-gray-500 py-3">
                                        {{ t('No league statistics available') }}
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Recent Activity -->
                            <Card>
                                <CardHeader class="py-3">
                                    <CardTitle class="flex items-center gap-1.5 text-base">
                                        <ClockIcon class="h-4 w-4"/>
                                        {{ t('Recent Activity') }}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-4">
                                    <div v-if="player.recent_tournaments.length > 0" class="space-y-2">
                                        <div v-for="tournament in player.recent_tournaments.slice(0, 5)"
                                             :key="tournament.tournament_id"
                                             class="flex items-center justify-between p-2 border rounded-lg">
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-sm font-medium truncate">{{
                                                        tournament.tournament_name
                                                    }}</h4>
                                                <p class="text-xs text-gray-500">{{ tournament.game_name }}</p>
                                            </div>
                                            <div class="text-right">
                                                <span
                                                    :class="['px-1.5 py-0.5 text-xs font-medium rounded-full', getPositionBadgeClass(tournament.position)]">
                                                    {{ tournament.position ? `#${tournament.position}` : t('N/A') }}
                                                </span>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    {{ formatDate(tournament.start_date) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-sm text-gray-500 py-3">
                                        {{ t('No recent tournaments') }}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Tournaments Tab -->
                    <div v-if="activeTab === 'tournaments'" class="space-y-4">
                        <Card>
                            <CardHeader class="py-3">
                                <CardTitle class="flex items-center gap-1.5 text-base">
                                    <TrophyIcon class="h-4 w-4"/>
                                    {{ t('Recent Tournaments') }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-0">
                                <DataTable
                                    :columns="tournamentColumns"
                                    :data="player.recent_tournaments"
                                    :empty-message="t('No tournaments found')"
                                    :mobile-card-mode="true"
                                    :compact-mode="true"
                                >
                                    <template #cell-tournament="{ value }">
                                        <div>
                                            <div class="text-sm font-medium">{{ value.name }}</div>
                                            <div class="text-xs text-gray-500">{{ value.game }}</div>
                                            <div v-if="value.location" class="text-xs text-gray-400">{{
                                                    value.location
                                                }}
                                            </div>
                                        </div>
                                    </template>

                                    <template #cell-position="{ value }">
                                        <span v-if="value"
                                              :class="['px-1.5 py-0.5 text-xs font-medium rounded-full', getPositionBadgeClass(value)]">
                                            #{{ value }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-prize="{ value }">
                                        <span class="text-sm font-medium">{{ formatCurrency(value) }}</span>
                                    </template>

                                    <template #cell-rating_points="{ value }">
                                        <span class="text-sm font-medium">{{ value }}</span>
                                    </template>

                                    <template #cell-date="{ value }">
                                        <span class="text-xs">{{ formatDate(value) }}</span>
                                    </template>
                                </DataTable>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Matches Tab -->
                    <div v-if="activeTab === 'matches'" class="space-y-4">
                        <Card>
                            <CardHeader class="py-3">
                                <CardTitle class="flex items-center gap-1.5 text-base">
                                    <GamepadIcon class="h-4 w-4"/>
                                    {{ t('Recent Matches') }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-0">
                                <DataTable
                                    :columns="matchColumns"
                                    :data="player.recent_matches"
                                    :empty-message="t('No matches found')"
                                    :mobile-card-mode="true"
                                    :compact-mode="true"
                                >
                                    <template #cell-match="{ value }">
                                        <div>
                                            <div class="text-sm font-medium">{{ value.tournament }}</div>
                                            <div class="text-xs text-gray-500">{{ value.game }}</div>
                                            <div class="text-xs text-gray-400">{{ value.stage }} - {{
                                                    value.round
                                                }}
                                            </div>
                                        </div>
                                    </template>

                                    <template #cell-opponent="{ value }">
                                        <span class="text-sm font-medium">{{ value }}</span>
                                    </template>

                                    <template #cell-result="{ value }">
                                        <div class="text-center">
                                            <div
                                                :class="['text-sm font-medium', value.won ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400']">
                                                {{ value.score }}
                                            </div>
                                            <div class="text-xs text-gray-500">{{ t('Best of') }} {{
                                                    value.racesTo
                                                }}
                                            </div>
                                        </div>
                                    </template>

                                    <template #cell-date="{ value }">
                                        <span class="text-xs">{{ formatDateTime(value) }}</span>
                                    </template>
                                </DataTable>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Ratings Tab -->
                    <div v-if="activeTab === 'ratings'" class="space-y-4">
                        <Card>
                            <CardHeader class="py-3">
                                <CardTitle class="flex items-center gap-1.5 text-base">
                                    <StarIcon class="h-4 w-4"/>
                                    {{ t('Official Ratings') }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-4">
                                <div v-if="player.official_ratings.length > 0"
                                     class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div v-for="rating in player.official_ratings" :key="rating.official_rating_id"
                                         class="border rounded-lg p-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="text-sm font-medium">{{ rating.rating_name }}</h4>
                                            <span
                                                :class="['px-1.5 py-0.5 text-xs font-medium rounded-full', getPositionBadgeClass(rating.position)]">
                                                #{{ rating.position }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div>
                                                <span class="text-gray-500">{{ t('Points') }}:</span>
                                                <span class="font-medium ml-1">{{ rating.rating_points }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">{{ t('Division') }}:</span>
                                                <span class="font-medium ml-1">{{ rating.division }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">{{ t('Tournaments') }}:</span>
                                                <span class="font-medium ml-1">{{ rating.tournaments_played }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">{{ t('Win Rate') }}:</span>
                                                <span :class="['font-medium ml-1', getWinRateClass(rating.win_rate)]">
                                                    {{ Math.round(rating.win_rate) }}%
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mt-2 pt-2 border-t">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-500">{{ t('Total Prize') }}:</span>
                                                <span class="font-medium">{{
                                                        formatCurrency(rating.total_prize_amount)
                                                    }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-sm text-gray-500 py-6">
                                    {{ t('No official ratings available') }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Achievements Tab -->
                    <div v-if="activeTab === 'achievements'" class="space-y-4">
                        <Card>
                            <CardHeader class="py-3">
                                <CardTitle class="flex items-center gap-1.5 text-base">
                                    <AwardIcon class="h-4 w-4"/>
                                    {{ t('Achievements') }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-4">
                                <div v-if="player.achievements.length > 0"
                                     class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div v-for="achievement in player.achievements" :key="achievement.type"
                                         class="border rounded-lg p-3 text-center">
                                        <div class="flex justify-center mb-2">
                                            <div
                                                class="h-10 w-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                                <MedalIcon class="h-5 w-5 text-yellow-600 dark:text-yellow-400"/>
                                            </div>
                                        </div>
                                        <h4 class="text-sm font-medium mb-0.5">{{ achievement.name }}</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">{{
                                                achievement.description
                                            }}</p>
                                    </div>
                                </div>
                                <div v-else class="text-center text-sm text-gray-500 py-6">
                                    {{ t('No achievements unlocked yet') }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Detailed Stats Tab -->
                    <div v-if="activeTab === 'detailed-stats'" class="space-y-4">
                        <Card>
                            <CardHeader class="py-3">
                                <CardTitle class="flex items-center gap-1.5 text-base">
                                    <ChartBarIcon class="h-4 w-4"/>
                                    {{ t('Detailed Match Statistics') }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-4">
                                <DetailedMatchStats :player-id="player.id"/>
                            </CardContent>
                        </Card>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Head to Head Modal -->
    <Dialog v-model:open="showHeadToHeadModal" @update:open="(open) => !open && resetHeadToHeadState()">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <div class="flex items-center justify-between mb-3">
                    <DialogTitle class="flex items-center gap-1.5 text-base">
                        <SwordsIcon class="h-4 w-4"/>
                        {{ t('Head to Head Statistics') }}
                    </DialogTitle>
                    <Button
                        v-if="selectedOpponent"
                        variant="outline"
                        size="sm"
                        @click="resetHeadToHeadState"
                        class="flex items-center gap-1.5"
                    >
                        <ArrowLeftIcon class="h-3.5 w-3.5"/>
                        {{ t('Back to Search') }}
                    </Button>
                </div>
            </DialogHeader>

            <div v-if="!selectedOpponent" class="space-y-3">
                <div>
                    <Label for="opponent-search" class="text-sm">{{ t('Search for opponent') }}</Label>
                    <div class="relative mt-1">
                        <SearchIcon class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"/>
                        <Input
                            id="opponent-search"
                            type="text"
                            :placeholder="t('Enter opponent name...')"
                            class="pl-9"
                            :model-value="searchQuery"
                            @update:model-value="handleHeadToHeadSearch"
                        />
                    </div>
                </div>

                <div v-if="isSearching" class="flex justify-center py-3">
                    <Spinner class="h-5 w-5"/>
                </div>

                <div v-else-if="searchResults.length > 0" class="space-y-1.5 max-h-60 overflow-y-auto">
                    <div
                        v-for="result in searchResults"
                        :key="result.id"
                        class="flex items-center justify-between p-2.5 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                        @click="selectOpponent(result)"
                    >
                        <div class="flex items-center gap-2.5">
                            <UserAvatar
                                :user="result"
                                size="sm"
                                priority="picture"
                            />
                            <div>
                                <div class="text-sm font-medium">{{ result.full_name }}</div>
                                <div v-if="result.home_city || result.home_club" class="text-xs text-gray-500">
                                    {{ [result.home_city?.name, result.home_club?.name].filter(Boolean).join(', ') }}
                                </div>
                            </div>
                        </div>
                        <ChevronRightIcon class="h-3.5 w-3.5 text-gray-400"/>
                    </div>
                </div>

                <div v-else-if="searchQuery.length >= 2" class="text-center text-sm text-gray-500 py-3">
                    {{ t('No players found') }}
                </div>
            </div>

            <div v-else-if="isHeadToHeadLoading" class="flex justify-center py-6">
                <Spinner class="h-6 w-6"/>
            </div>

            <div v-else-if="headToHeadError" class="text-center py-6">
                <XCircleIcon class="h-10 w-10 text-red-500 mx-auto mb-3"/>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ headToHeadError }}</p>
            </div>

            <div v-else-if="headToHeadStats" class="space-y-4">
                <!-- Summary Stats -->
                <Card>
                    <CardHeader class="py-3">
                        <CardTitle class="text-base">{{ t('Summary') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="p-4">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="text-center">
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ headToHeadStats.summary.total_matches }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ t('Total Matches') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="mb-1.5">
                                    <UserAvatar
                                        :user="headToHeadStats.players.player1"
                                        size="sm"
                                        priority="tournament_picture"
                                        className="mx-auto"
                                    />
                                </div>
                                <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                    {{ headToHeadStats.summary.player1_wins }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ headToHeadStats.players.player1.full_name }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="mb-1.5">
                                    <UserAvatar
                                        :user="headToHeadStats.players.player2"
                                        size="sm"
                                        priority="tournament_picture"
                                        className="mx-auto"
                                    />
                                </div>
                                <div class="text-xl font-bold text-red-600 dark:text-red-400">
                                    {{ headToHeadStats.summary.player2_wins }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ headToHeadStats.players.player2.full_name }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ Math.round(headToHeadStats.summary.player1_win_rate) }}%
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ t('Win Rate') }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Match History -->
                <Card>
                    <CardHeader class="py-3">
                        <CardTitle class="text-base">{{ t('Match History') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="p-4">
                        <div v-if="headToHeadStats.match_history.length > 0" class="space-y-2">
                            <div v-for="match in headToHeadStats.match_history" :key="match.match_id"
                                 class="border rounded-lg p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <div>
                                        <h4 class="text-sm font-medium">{{ match.tournament_name }}</h4>
                                        <p class="text-xs text-gray-500">{{ match.game_name }} - {{
                                                match.match_stage
                                            }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium">
                                            {{ match.player1_score }} - {{ match.player2_score }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{
                                                formatDateTime(match.completed_at)
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-sm text-gray-500 py-3">
                            {{ t('No match history available') }}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </DialogContent>
    </Dialog>
</template>
