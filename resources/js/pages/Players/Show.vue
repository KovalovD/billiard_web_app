<script lang="ts" setup>
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    Input,
    Spinner
} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useAuth} from '@/composables/useAuth';
import {useLocale} from '@/composables/useLocale';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import {Head, Link} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    AwardIcon,
    CalendarIcon,
    ChartBarIcon,
    ChevronRightIcon,
    ClockIcon,
    GamepadIcon,
    MapPinIcon,
    SearchIcon,
    StarIcon,
    SwordsIcon,
    TrophyIcon,
    UserIcon,
    UsersIcon,
    XCircleIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import DetailedMatchStats from "@/Components/Players/DetailedMatchStats.vue";

interface PlayerDetail {
    id: number;
    slug: string;
    firstname: string;
    lastname: string;
    full_name: string;
    email: string;
    phone: string;
    sex: string;
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
        };
        player2: {
            id: number;
            full_name: string;
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
    home_city?: { name: string };
    home_club?: { name: string };
}

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    playerId: number | string;
}>();

const {isAuthenticated, user} = useAuth();
const {t} = useLocale();
const {setSeoMeta, generateBreadcrumbJsonLd} = useSeo();

// State
const player = ref<PlayerDetail | null>(null);
const isLoading = ref(true);
const error = ref<string | null>(null);

// Update initial tab function
const getInitialTab = (): 'overview' | 'tournaments' | 'leagues' | 'matches' | 'statistics' => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const validTabs = ['overview', 'tournaments', 'leagues', 'matches', 'statistics'];
    return validTabs.includes(tabParam as string) ? tabParam as any : 'overview';
};

// Update activeTab type
const activeTab = ref<'overview' | 'tournaments' | 'leagues' | 'matches' | 'statistics'>(getInitialTab());

// Update switchTab function
const switchTab = (tab: 'overview' | 'tournaments' | 'leagues' | 'matches' | 'statistics') => {
    activeTab.value = tab;
    const url = new URL(window.location.href);
    if (tab === 'overview') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }
    window.history.replaceState({}, '', url.toString());
};

// Head to Head state
const showH2HDialog = ref(false);
const h2hSearchQuery = ref('');
const h2hSearchResults = ref<PlayerSearchResult[]>([]);
const h2hSearching = ref(false);
const h2hStats = ref<HeadToHeadStats | null>(null);
const h2hLoading = ref(false);
const selectedOpponent = ref<PlayerSearchResult | null>(null);

// Computed
const isCurrentUser = computed(() => {
    return isAuthenticated.value && user.value?.id === player.value?.id;
});

const playerFullName = computed(() => {
    return player.value ? player.value.full_name : t('Player');
});

const memberSince = computed(() => {
    if (!player.value?.created_at) return null;
    return new Date(player.value.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// League table columns
const leagueColumns = computed(() => [
    {
        key: 'league',
        label: t('League'),
        align: 'left' as const,
        render: (stat: any) => ({
            league_id: stat.league_id,
            name: stat.league_name,
            game: stat.game_name,
            isActive: stat.is_active
        })
    },
    {
        key: 'rating',
        label: t('Rating'),
        align: 'center' as const,
        render: (stat: any) => ({
            rating: stat.rating,
            position: stat.position
        })
    },
    {
        key: 'matches',
        label: t('Matches'),
        align: 'center' as const,
        render: (stat: any) => ({
            played: stat.matches_played,
            won: stat.matches_won
        })
    },
    {
        key: 'winRate',
        label: t('Win Rate'),
        align: 'center' as const,
        render: (stat: any) => stat.win_rate
    }
]);

// Tournament columns
const tournamentColumns = computed(() => [
    {
        key: 'tournament',
        label: t('Tournament'),
        align: 'left' as const,
        render: (tournament: any) => ({
            tournament_id: tournament.tournament_id,
            name: tournament.tournament_name,
            location: tournament.city && tournament.country ? `${tournament.city}, ${tournament.country}` : null
        })
    },
    {
        key: 'date',
        label: t('Date'),
        hideOnMobile: true,
        render: (tournament: any) => formatDate(tournament.end_date)
    },
    {
        key: 'position',
        label: t('Result'),
        align: 'center' as const,
        render: (tournament: any) => ({
            position: tournament.position,
            players: tournament.players_count
        })
    },
    {
        key: 'earnings',
        label: t('Earnings'),
        align: 'right' as const,
        render: (tournament: any) => ({
            prize: tournament.prize_amount,
            points: tournament.rating_points
        })
    }
]);

// Methods
const fetchPlayer = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        player.value = await apiClient<PlayerDetail>(`/api/players/${props.playerId}`);
    } catch (err: any) {
        error.value = err.message || t('Failed to load player details');
    } finally {
        isLoading.value = false;
    }
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).replace('UAH', '₴');
};

const getDivisionBadgeClass = (division: string): string => {
    switch (division) {
        case 'Elite':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
        case 'S':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'A':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'B':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'C':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return '';
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

const getWinRateClass = (rate: number): string => {
    if (rate >= 70) return 'text-green-600 dark:text-green-400';
    if (rate >= 50) return 'text-blue-600 dark:text-blue-400';
    if (rate >= 30) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400';
};

const getStageBadgeClass = (stage: string): string => {
    const stageClasses: Record<string, string> = {
        'bracket': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'group': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'third_place': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        'lower_bracket': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
    };
    return stageClasses[stage] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const formatStage = (stage: string): string => {
    const stageNames: Record<string, string> = {
        'bracket': t('Bracket'),
        'group': t('Group Stage'),
        'third_place': t('3rd Place'),
        'lower_bracket': t('Lower Bracket')
    };
    return stageNames[stage] || stage;
};

const formatRound = (round: string): string => {
    const roundNames: Record<string, string> = {
        'finals': t('Finals'),
        'semifinals': t('Semifinals'),
        'quarterfinals': t('Quarterfinals'),
        'round_16': t('Round of 16'),
        'round_32': t('Round of 32'),
        'round_64': t('Round of 64'),
        'round_128': t('Round of 128'),
        'third_place': t('3rd Place Match'),
        'grand_finals': t('Grand Finals')
    };
    return roundNames[round] || round;
};

// Head to Head methods
const searchPlayers = async () => {
    if (h2hSearchQuery.value.length < 2) {
        h2hSearchResults.value = [];
        return;
    }

    h2hSearching.value = true;
    try {
        const results = await apiClient<PlayerSearchResult[]>(`/api/players?name=${encodeURIComponent(h2hSearchQuery.value)}&per_page=10`);
        h2hSearchResults.value = results.data.filter(p => p.id !== player.value?.id);
    } catch (err) {
        console.error('Failed to search players:', err);
        h2hSearchResults.value = [];
    } finally {
        h2hSearching.value = false;
    }
};

const selectOpponent = async (opponent: PlayerSearchResult) => {
    selectedOpponent.value = opponent;
    h2hSearchResults.value = [];
    h2hSearchQuery.value = '';

    // Load head to head stats
    h2hLoading.value = true;
    try {
        h2hStats.value = await apiClient<HeadToHeadStats>(`/api/players/${props.playerId}/vs/${opponent.id}`);
    } catch (err) {
        console.error('Failed to load head to head stats:', err);
        error.value = t('Failed to load head to head statistics');
    } finally {
        h2hLoading.value = false;
    }
};

const openH2HDialog = () => {
    showH2HDialog.value = true;
    h2hSearchQuery.value = '';
    h2hSearchResults.value = [];
    selectedOpponent.value = null;
    h2hStats.value = null;
};

// Lifecycle
onMounted(() => {
    fetchPlayer().then(() => {
        if (player.value) {
            setSeoMeta({
                title: t(':name - Professional Billiard Player Profile', {name: playerFullName.value}),
                description: t('View :name\'s billiard statistics, tournament wins, league performance, and official ratings. Professional pool player profile and achievements.', {name: playerFullName.value}),
                keywords: [`${playerFullName.value}`, 'billiard player', 'pool player profile', 'tournament statistics', 'player achievements'],
                ogType: 'profile',
                jsonLd: {
                    ...generateBreadcrumbJsonLd([
                        {name: t('Home'), url: window.location.origin},
                        {name: t('Players'), url: `${window.location.origin}/players`},
                        {name: playerFullName.value, url: `${window.location.origin}/players/${props.playerId}`}
                    ]),
                    "@context": "https://schema.org",
                    "@type": "Person",
                    "name": playerFullName.value,
                    "sport": "Billiards"
                }
            });
        }
    });
});
</script>

<template>
    <Head :title="playerFullName + ' - ' + t('Player Profile')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link href="/players">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Players') }}
                    </Button>
                </Link>

                <Button v-if="player && !isLoading" @click="openH2HDialog" variant="outline">
                    <SwordsIcon class="mr-2 h-4 w-4"/>
                    {{ t('Head to Head') }}
                </Button>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="p-10 text-center">
                <Spinner class="text-primary mx-auto h-8 w-8"/>
                <p class="mt-2 text-gray-500">{{ t('Loading player profile...') }}</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                {{ error }}
            </div>

            <!-- Player Content -->
            <template v-else-if="player">
                <!-- Player Header Card -->
                <Card class="mb-8">
                    <CardHeader>
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div
                                        class="h-20 w-20 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <UserIcon class="h-10 w-10 text-gray-500 dark:text-gray-400"/>
                                    </div>
                                </div>
                                <div>
                                    <CardTitle class="text-2xl">
                                        {{ player.full_name }}
                                        <span v-if="isCurrentUser"
                                              class="ml-2 text-sm text-blue-600 dark:text-blue-400">
                                            ({{ t('You') }})
                                        </span>
                                    </CardTitle>
                                    <CardDescription class="mt-2">
                                        <div class="flex flex-wrap gap-4">
                                            <span v-if="player.home_city" class="flex items-center gap-1">
                                                <MapPinIcon class="h-4 w-4"/>
                                                {{ player.home_city.name }}, {{ player.home_city.country?.name }}
                                            </span>
                                            <span v-if="player.home_club" class="flex items-center gap-1">
                                                <UsersIcon class="h-4 w-4"/>
                                                {{ player.home_club.name }}
                                            </span>
                                            <span v-if="memberSince" class="flex items-center gap-1">
                                                <CalendarIcon class="h-4 w-4"/>
                                                {{ t('Member since') }} {{ memberSince }}
                                            </span>
                                        </div>
                                    </CardDescription>
                                </div>
                            </div>

                            <!-- Achievement Badges -->
                            <div v-if="player.achievements.length > 0" class="flex flex-wrap gap-2">
                                <div
                                    v-for="achievement in player.achievements"
                                    :key="achievement.name"
                                    class="flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm dark:bg-yellow-900/30 dark:text-yellow-300"
                                    :title="achievement.description"
                                >
                                    <component
                                        :is="achievement.icon === 'trophy' ? TrophyIcon : achievement.icon === 'star' ? StarIcon : AwardIcon"
                                        class="h-4 w-4"/>
                                    {{ achievement.name }}
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Tournament Stats -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ t('Tournaments') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ player.tournament_stats.total_tournaments }}</div>
                            <div class="mt-2 flex items-center gap-4 text-sm">
                                <span class="flex items-center gap-1 text-yellow-600 dark:text-yellow-400">
                                    <TrophyIcon class="h-4 w-4"/>
                                    {{ player.tournament_stats.tournaments_won }} {{ t('won') }}
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ player.tournament_stats.win_rate }}% {{ t('win rate') }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Total Prize -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ t('Total Prize Won') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ formatCurrency(player.tournament_stats.total_prize_won) }}
                            </div>
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ t('From tournaments') }}
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Rating Points -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ t('Rating Points') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ player.tournament_stats.total_rating_points }}
                            </div>
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ t('Total earned') }}
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Active Leagues -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ t('Active Leagues') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{ player.league_stats.filter(l => l.is_active).length }}
                            </div>
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ t('Currently playing') }}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Official Ratings -->
                <Card v-if="player.official_ratings.length > 0" class="mb-8">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <StarIcon class="h-5 w-5"/>
                            {{ t('Official Ratings') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div
                                v-for="rating in player.official_ratings"
                                :key="rating.official_rating_id"
                                class="p-4 border rounded-lg"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-medium">{{ rating.rating_name }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ rating.game_name }}</p>
                                    </div>
                                    <span
                                        :class="['px-2 py-1 text-xs font-medium rounded-full', getDivisionBadgeClass(rating.division)]">
                                        {{ rating.division }}
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">{{ t('Position') }}</span>
                                        <p class="font-bold text-lg">#{{ rating.position }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">{{ t('Points') }}</span>
                                        <p class="font-bold text-lg">{{ rating.rating_points }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">{{ t('Tournaments') }}</span>
                                        <p class="font-medium">{{ rating.tournaments_played }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">{{ t('Total Earned') }}</span>
                                        <p class="font-medium text-green-600 dark:text-green-400">
                                            {{ formatCurrency(rating.total_money_earned) }}
                                        </p>
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
                                activeTab === 'overview'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="switchTab('overview')"
                        >
                            {{ t('Overview') }}
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'tournaments'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="switchTab('tournaments')"
                        >
                            {{ t('Tournaments') }} ({{ player.tournament_stats.total_tournaments }})
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'leagues'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="switchTab('leagues')"
                        >
                            {{ t('Leagues') }} ({{ player.league_stats.length }})
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'matches'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="switchTab('matches')"
                        >
                            {{ t('Tournament Matches') }}
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'statistics'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="switchTab('statistics')"
                        >
                            {{ t('Detailed Statistics') }}
                        </button>
                    </nav>
                </div>

                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="space-y-6">
                    <!-- Win Rate by Tournament Type -->
                    <Card v-if="Object.keys(player.tournament_stats.win_rate_by_type).length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ChartBarIcon class="h-5 w-5"/>
                                {{ t('Performance by Tournament Type') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div
                                    v-for="(stats, type) in player.tournament_stats.win_rate_by_type"
                                    :key="type"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-800"
                                >
                                    <div>
                                        <h4 class="font-medium capitalize">{{ type.replace('_', ' ') }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ stats.total }} {{ t('tournaments') }}, {{ stats.wins }} {{ t('won') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p :class="['text-2xl font-bold', getWinRateClass(stats.win_rate)]">
                                            {{ stats.win_rate }}%
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('win rate') }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Activity Summary -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('Recent Activity') }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Accordion>
                                <!-- Recent Tournaments -->
                                <AccordionItem value="tournaments">
                                    <AccordionTrigger value="tournaments">
                                        <div class="flex items-center gap-2">
                                            <TrophyIcon class="h-4 w-4"/>
                                            {{ t('Recent Tournaments') }}
                                            ({{ Math.min(5, player.recent_tournaments.length) }})
                                        </div>
                                    </AccordionTrigger>
                                    <AccordionContent value="tournaments">
                                        <div class="space-y-3">
                                            <div
                                                v-for="tournament in player.recent_tournaments.slice(0, 5)"
                                                :key="tournament.tournament_id"
                                                class="flex items-center justify-between"
                                            >
                                                <div>
                                                    <Link :href="`/tournaments/${tournament.tournament_id}`"
                                                          class="font-medium hover:underline">
                                                        {{ tournament.tournament_name }}
                                                    </Link>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ formatDate(tournament.end_date) }}
                                                        <span v-if="tournament.city"> • {{
                                                                tournament.city
                                                            }}, {{ tournament.country }}</span>
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <span v-if="tournament.position"
                                                          :class="['px-2 py-1 text-xs font-medium rounded-full', getPositionBadgeClass(tournament.position)]">
                                                        #{{ tournament.position }}
                                                    </span>
                                                    <p v-if="tournament.prize_amount > 0"
                                                       class="text-sm text-green-600 dark:text-green-400 mt-1">
                                                        {{ formatCurrency(tournament.prize_amount) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </AccordionContent>
                                </AccordionItem>

                                <!-- Recent Tournament Matches -->
                                <AccordionItem value="matches">
                                    <AccordionTrigger value="matches">
                                        <div class="flex items-center gap-2">
                                            <GamepadIcon class="h-4 w-4"/>
                                            {{ t('Recent Tournament Matches') }}
                                            ({{ Math.min(10, player.recent_matches.length) }})
                                        </div>
                                    </AccordionTrigger>
                                    <AccordionContent value="matches">
                                        <div class="space-y-3">
                                            <div
                                                v-for="match in player.recent_matches.slice(0, 10)"
                                                :key="match.match_id"
                                                class="flex items-center justify-between p-2 rounded"
                                                :class="match.won ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'"
                                            >
                                                <div>
                                                    <p class="font-medium">
                                                        {{ match.won ? t('Won') : t('Lost') }} vs
                                                        <Link v-if="match.opponent_id"
                                                              :href="`/players/${match.opponent_id}`"
                                                              class="hover:underline">
                                                            {{ match.opponent }}
                                                        </Link>
                                                        <span v-else>{{ match.opponent }}</span>
                                                    </p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ match.tournament_name }} • {{
                                                            formatStage(match.match_stage)
                                                        }}
                                                        <span v-if="match.match_round"> • {{
                                                                formatRound(match.match_round)
                                                            }}</span>
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="font-bold">{{ match.score }}</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ t('Race to') }} {{ match.races_to }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </AccordionContent>
                                </AccordionItem>
                            </Accordion>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tournaments Tab -->
                <div v-if="activeTab === 'tournaments'">
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('Tournament History') }}</CardTitle>
                            <CardDescription>
                                {{ t('All tournaments participated by this player') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-0">
                            <DataTable
                                :columns="tournamentColumns"
                                :data="player.recent_tournaments"
                                :empty-message="t('No tournaments found')"
                            >
                                <template #cell-tournament="{ value }">
                                    <div>
                                        <Link :href="`/tournaments/${value.tournament_id}`"
                                              class="font-medium hover:underline">
                                            {{ value.name }}
                                        </Link>
                                        <p v-if="value.location" class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ value.location }}
                                        </p>
                                    </div>
                                </template>

                                <template #cell-position="{ value }">
                                    <div v-if="value.position" class="text-center">
                                        <span
                                            :class="['inline-flex px-2 py-1 text-xs font-medium rounded-full', getPositionBadgeClass(value.position)]">
                                            #{{ value.position }}
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ t('of') }} {{ value.players }}
                                        </p>
                                    </div>
                                    <span v-else class="text-gray-400">—</span>
                                </template>

                                <template #cell-earnings="{ value }">
                                    <div class="text-right">
                                        <p v-if="value.prize > 0"
                                           class="font-medium text-green-600 dark:text-green-400">
                                            {{ formatCurrency(value.prize) }}
                                        </p>
                                        <p v-if="value.points > 0" class="text-sm text-gray-600 dark:text-gray-400">
                                            +{{ value.points }} {{ t('pts') }}
                                        </p>
                                    </div>
                                </template>
                            </DataTable>
                        </CardContent>
                    </Card>
                </div>

                <!-- Leagues Tab -->
                <div v-if="activeTab === 'leagues'">
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('League Participation') }}</CardTitle>
                            <CardDescription>
                                {{ t('All leagues where this player participates') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-0">
                            <DataTable
                                :columns="leagueColumns"
                                :data="player.league_stats"
                                :empty-message="t('No leagues found')"
                            >
                                <template #cell-league="{ value }">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <Link :href="`/leagues/${value.league_id}`"
                                                  class="font-medium hover:underline">
                                                {{ value.name }}
                                            </Link>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ value.game }}
                                            </p>
                                        </div>
                                        <span v-if="value.isActive"
                                              class="inline-flex px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full dark:bg-green-900/30 dark:text-green-300">
                                            {{ t('Active') }}
                                        </span>
                                    </div>
                                </template>

                                <template #cell-rating="{ value }">
                                    <div class="text-center">
                                        <p class="font-bold">{{ value.rating }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            #{{ value.position }}
                                        </p>
                                    </div>
                                </template>

                                <template #cell-matches="{ value }">
                                    <div class="text-center">
                                        <p>{{ value.played }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ value.won }} {{ t('won') }}
                                        </p>
                                    </div>
                                </template>

                                <template #cell-winRate="{ value }">
                                    <span :class="['font-medium', getWinRateClass(value)]">
                                        {{ value }}%
                                    </span>
                                </template>
                            </DataTable>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tournament Matches Tab -->
                <div v-if="activeTab === 'matches'">
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('Tournament Match History') }}</CardTitle>
                            <CardDescription>
                                {{ t('Recent matches from tournaments') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="player.recent_matches.length === 0" class="py-8 text-center text-gray-500">
                                {{ t('No tournament matches found') }}
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="match in player.recent_matches"
                                    :key="match.match_id"
                                    class="flex items-center justify-between p-4 border rounded-lg"
                                    :class="match.won ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'"
                                >
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="['font-medium', match.won ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300']">
                                                {{ match.won ? t('Victory') : t('Defeat') }}
                                            </span>
                                            <span class="text-gray-600 dark:text-gray-400">vs</span>
                                            <Link v-if="match.opponent_id" :href="`/players/${match.opponent_id}`"
                                                  class="font-medium hover:underline">
                                                {{ match.opponent }}
                                            </Link>
                                            <span v-else class="font-medium">{{ match.opponent }}</span>
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            <span>{{ match.tournament_name }}</span>
                                            <span
                                                :class="['ml-2 px-2 py-0.5 text-xs rounded-full', getStageBadgeClass(match.match_stage)]">
                                                {{ formatStage(match.match_stage) }}
                                            </span>
                                            <span v-if="match.match_round"
                                                  class="ml-2">{{ formatRound(match.match_round) }}</span>
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            <span v-if="match.club">{{ match.club }} • </span>
                                            <span>{{ formatDate(match.completed_at) }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-lg">{{ match.score }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ t('Race to') }} {{ match.races_to }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="activeTab === 'statistics'">
                    <DetailedMatchStats :player-id="player.id"/>
                </div>
            </template>

            <!-- Head to Head Dialog -->
            <Dialog :open="showH2HDialog" @update:open="showH2HDialog = $event">
                <DialogContent class="max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
                    <DialogHeader class="pb-4 border-b border-gray-200 dark:border-gray-700">
                        <DialogTitle class="text-xl font-semibold flex items-center gap-2">
                            <SwordsIcon class="h-5 w-5"/>
                            {{ t('Head to Head Comparison') }}
                        </DialogTitle>
                    </DialogHeader>

                    <div class="flex-1 overflow-y-auto">
                        <!-- Opponent Search -->
                        <div v-if="!selectedOpponent" class="p-6 space-y-6">
                            <div class="text-center space-y-3">
                                <div
                                    class="mx-auto h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                    <UserIcon class="h-8 w-8 text-gray-400"/>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium">{{ t('Select an Opponent') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ t('Search for a player to compare head-to-head statistics') }}
                                    </p>
                                </div>
                            </div>

                            <div class="max-w-md mx-auto">
                                <div class="relative">
                                    <Input
                                        v-model="h2hSearchQuery"
                                        @input="searchPlayers"
                                        :placeholder="t('Search player name...')"
                                        class="pl-10"
                                    />
                                    <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"/>
                                </div>
                            </div>

                            <!-- Search Results -->
                            <div v-if="h2hSearching" class="text-center py-8">
                                <Spinner class="mx-auto h-6 w-6 text-primary"/>
                                <p class="mt-2 text-sm text-gray-500">{{ t('Searching players...') }}</p>
                            </div>

                            <div v-else-if="h2hSearchResults.length > 0" class="max-w-2xl mx-auto space-y-2">
                                <button
                                    v-for="result in h2hSearchResults"
                                    :key="result.id"
                                    @click="selectOpponent(result)"
                                    class="w-full p-3 text-left bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all group"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                                    {{
                                                        result.full_name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
                                                    }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                    {{ result.full_name }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    <span v-if="result.home_city">{{ result.home_city.name }}</span>
                                                    <span v-if="result.home_club"> • {{ result.home_club.name }}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <ChevronRightIcon
                                            class="h-5 w-5 text-gray-400 group-hover:text-blue-500 transition-colors"/>
                                    </div>
                                </button>
                            </div>

                            <div v-else-if="h2hSearchQuery.length >= 2" class="text-center py-8 text-gray-500">
                                {{ t('No players found') }}
                            </div>
                        </div>

                        <!-- Head to Head Stats -->
                        <div v-if="selectedOpponent && h2hStats" class="p-6 space-y-6">
                            <!-- Players Header -->
                            <Card>
                                <CardContent class="p-6">
                                    <div class="grid grid-cols-3 gap-4 items-center">
                                        <!-- Player 1 -->
                                        <div class="text-center">
                                            <div
                                                class="mx-auto h-20 w-20 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                                                <UserIcon class="h-10 w-10 text-blue-600 dark:text-blue-400"/>
                                            </div>
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ player.full_name }}</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ player.home_city?.name }}
                                            </p>
                                        </div>

                                        <!-- VS Badge -->
                                        <div class="flex items-center justify-center">
                                            <div
                                                class="bg-gray-100 dark:bg-gray-800 rounded-full h-16 w-16 flex items-center justify-center">
                                                <span
                                                    class="text-xl font-bold text-gray-600 dark:text-gray-300">VS</span>
                                            </div>
                                        </div>

                                        <!-- Player 2 -->
                                        <div class="text-center">
                                            <div
                                                class="mx-auto h-20 w-20 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-3">
                                                <UserIcon class="h-10 w-10 text-purple-600 dark:text-purple-400"/>
                                            </div>
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ selectedOpponent.full_name }}</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ selectedOpponent.home_city?.name }}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Statistics Overview -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Total Matches -->
                                <Card>
                                    <CardContent class="p-4 text-center">
                                        <GamepadIcon class="h-6 w-6 mx-auto mb-2 text-gray-400"/>
                                        <p class="text-2xl font-bold">{{ h2hStats.summary.total_matches }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Matches') }}</p>
                                    </CardContent>
                                </Card>

                                <!-- Win Distribution -->
                                <Card class="md:col-span-2">
                                    <CardContent class="p-4">
                                        <div class="space-y-3">
                                            <!-- Wins Counter -->
                                            <div class="flex items-center justify-between">
                                                <div class="text-center">
                                                    <p class="text-3xl font-bold"
                                                       :class="h2hStats.summary.player1_wins > h2hStats.summary.player2_wins ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                                                        {{ h2hStats.summary.player1_wins }}
                                                    </p>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{
                                                            t('Wins')
                                                        }}</p>
                                                </div>
                                                <div class="flex-1 mx-4">
                                                    <div
                                                        class="relative h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                        <div
                                                            class="absolute left-0 top-0 h-full bg-blue-600 dark:bg-blue-500 transition-all duration-500"
                                                            :style="`width: ${h2hStats.summary.player1_win_rate}%`"
                                                        />
                                                    </div>
                                                    <div
                                                        class="flex justify-between mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                        <span>{{ h2hStats.summary.player1_win_rate }}%</span>
                                                        <span>{{ h2hStats.summary.player2_win_rate }}%</span>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <p class="text-3xl font-bold"
                                                       :class="h2hStats.summary.player2_wins > h2hStats.summary.player1_wins ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                                                        {{ h2hStats.summary.player2_wins }}
                                                    </p>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{
                                                            t('Wins')
                                                        }}</p>
                                                </div>
                                            </div>

                                            <!-- Games Score -->
                                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                                <div class="flex justify-between items-center text-sm">
                                                    <span class="text-gray-600 dark:text-gray-400">{{
                                                            t('Games Won')
                                                        }}</span>
                                                    <span class="font-medium">
                                                        {{
                                                            h2hStats.summary.player1_games_won
                                                        }} - {{ h2hStats.summary.player2_games_won }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Match History -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2 text-base">
                                        <ClockIcon class="h-4 w-4"/>
                                        {{ t('Match History') }}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-0">
                                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <div
                                            v-for="match in h2hStats.match_history"
                                            :key="match.match_id"
                                            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ match.tournament_name }}
                                                    </p>
                                                    <div
                                                        class="flex items-center gap-2 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                        <span
                                                            :class="['px-2 py-0.5 text-xs rounded-full', getStageBadgeClass(match.match_stage)]">
                                                            {{ formatStage(match.match_stage) }}
                                                        </span>
                                                        <span v-if="match.match_round">{{
                                                                formatRound(match.match_round)
                                                            }}</span>
                                                        <span>• {{ formatDate(match.completed_at) }}</span>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-4">
                                                    <div class="text-right">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xl font-bold"
                                                                  :class="match.winner_id === player.id ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                                                                {{ match.player1_score }}
                                                            </span>
                                                            <span class="text-gray-400">:</span>
                                                            <span class="text-xl font-bold"
                                                                  :class="match.winner_id === selectedOpponent.id ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                                                                {{ match.player2_score }}
                                                            </span>
                                                        </div>
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            {{ t('Race to') }} {{ match.races_to }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <TrophyIcon
                                                            v-if="match.winner_id === player.id"
                                                            class="h-5 w-5 text-green-500"
                                                        />
                                                        <XCircleIcon
                                                            v-else
                                                            class="h-5 w-5 text-red-400"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Loading State -->
                        <div v-if="h2hLoading" class="p-16 text-center">
                            <Spinner class="mx-auto h-8 w-8 text-primary"/>
                            <p class="mt-3 text-gray-500">{{ t('Loading head to head statistics...') }}</p>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
