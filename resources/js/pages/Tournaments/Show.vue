<!-- resources/js/pages/Tournaments/Show.vue -->
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import TournamentApplicationCard from '@/Components/Tournament/TournamentApplicationCard.vue';
import SingleEliminationBracket from '@/Components/Tournament/SingleEliminationBracket.vue';
import DoubleEliminationBracket from '@/Components/Tournament/DoubleEliminationBracket.vue';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentGroup, TournamentMatch, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {
    ArrowLeftIcon,
    CalendarIcon,
    CheckCircleIcon,
    ClipboardListIcon,
    ClockIcon,
    GitBranchIcon,
    LayersIcon,
    LogInIcon,
    MapPinIcon,
    PencilIcon,
    PlayIcon,
    StarIcon,
    TrophyIcon,
    UserCheckIcon,
    UserPlusIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import StageTransition from "@/Components/Tournament/StageTransition.vue";

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {isAdmin, isAuthenticated, user} = useAuth();
const {t} = useLocale();

const tournament = ref<Tournament | null>(null);
const players = ref<TournamentPlayer[]>([]);
const matches = ref<TournamentMatch[]>([]);
const groups = ref<TournamentGroup[]>([]);
const isLoadingTournament = ref(true);
const isLoadingPlayers = ref(true);
const isLoadingMatches = ref(false);
const isLoadingGroups = ref(false);
const error = ref<string | null>(null);
const activeTab = ref<'info' | 'players' | 'bracket' | 'matches' | 'groups' | 'results' | 'applications'>('info');

// Get current user ID
const currentUserId = user.value?.id;

const sortedPlayers = computed(() => {
    return [...players.value].sort((a, b) => {
        const statusOrder = {confirmed: 1, applied: 2, rejected: 3};
        const aStatus = statusOrder[a.status as keyof typeof statusOrder] || 4;
        const bStatus = statusOrder[b.status as keyof typeof statusOrder] || 4;

        if (aStatus !== bStatus) {
            return aStatus - bStatus;
        }

        if (a.position !== null && b.position !== null && a.position != undefined && b.position !== undefined) {
            return a.position - b.position;
        }
        if (a.position !== null) return -1;
        if (b.position !== null) return 1;

        return new Date(a.applied_at || a.registered_at).getTime() -
            new Date(b.applied_at || b.registered_at).getTime();
    });
});

const confirmedPlayers = computed(() =>
    sortedPlayers.value.filter(p => p.is_confirmed)
);

const pendingApplications = computed(() =>
    sortedPlayers.value.filter(p => p.is_pending)
);

const rejectedApplications = computed(() =>
    sortedPlayers.value.filter(p => p.is_rejected)
);

const completedPlayers = computed(() => {
    return sortedPlayers.value.filter(p => p.position !== null);
});

// Computed properties for stage-based navigation
const showSeedingButton = computed(() => {
    return isAuthenticated.value &&
        isAdmin.value &&
        tournament.value?.stage === 'seeding' &&
        tournament.value?.status !== 'completed';
});

const showBracketButton = computed(() => {
    return isAuthenticated.value &&
        isAdmin.value &&
        tournament.value &&
        ['single_elimination', 'double_elimination', 'double_elimination_full', 'groups_playoff', 'team_groups_playoff'].includes(tournament.value.tournament_type) &&
        (tournament.value.stage === 'bracket' || tournament.value.brackets_generated) &&
        tournament.value.status !== 'completed';
});

const showGroupsButton = computed(() => {
    return isAuthenticated.value &&
        isAdmin.value &&
        tournament.value &&
        ['groups', 'groups_playoff', 'team_groups_playoff'].includes(tournament.value.tournament_type) &&
        tournament.value.stage === 'group' &&
        tournament.value.status !== 'completed';
});

const showMatchesButton = computed(() => {
    return isAuthenticated.value &&
        isAdmin.value &&
        tournament.value &&
        tournament.value.status === 'active';
});

// Check if bracket tab should be shown for non-admins
const canViewBracket = computed(() => {
    return tournament.value &&
        ['single_elimination', 'double_elimination', 'double_elimination_full', 'groups_playoff', 'team_groups_playoff'].includes(tournament.value.tournament_type) &&
        (tournament.value.stage === 'bracket' || tournament.value.brackets_generated || tournament.value.status === 'completed');
});

// Check if groups tab should be shown for non-admins
const canViewGroups = computed(() => {
    return tournament.value &&
        ['groups', 'groups_playoff', 'team_groups_playoff'].includes(tournament.value.tournament_type) &&
        (tournament.value.stage === 'group' || tournament.value.stage === 'bracket' || tournament.value.status === 'completed') &&
        groups.value.length > 0;
});

// Check if matches tab should be shown for non-admins
const canViewMatches = computed(() => {
    return tournament.value &&
        (tournament.value.status === 'active' || tournament.value.status === 'completed') &&
        matches.value.length > 0;
});

// Check if tournament is double elimination
const isDoubleElimination = computed(() => {
    return tournament.value?.tournament_type === 'double_elimination' ||
        tournament.value?.tournament_type === 'double_elimination_full';
});

// Filter and sort matches for display
const displayMatches = computed(() => {
    return [...matches.value].sort((a, b) => {
        // Sort by status priority
        const statusOrder = ['in_progress', 'verification', 'ready', 'pending', 'completed'];
        const statusDiff = statusOrder.indexOf(a.status) - statusOrder.indexOf(b.status);
        if (statusDiff !== 0) return statusDiff;

        // Then by scheduled time
        if (a.scheduled_at && b.scheduled_at) {
            return new Date(a.scheduled_at).getTime() - new Date(b.scheduled_at).getTime();
        }

        // Then by match code
        return (a.match_code || '').localeCompare(b.match_code || '');
    });
});

// Group matches by group code
const matchesByGroup = computed(() => {
    const grouped: Record<string, TournamentMatch[]> = {};

    matches.value.filter(m => m.stage === 'group').forEach(match => {
        // Find group for this match
        const group = groups.value.find(g => {
            const groupPlayers = players.value.filter(p => p.group_code === g.group_code);
            return groupPlayers.some(p => p.id === match.player1_id || p.id === match.player2_id);
        });

        if (group) {
            if (!grouped[group.group_code]) {
                grouped[group.group_code] = [];
            }
            grouped[group.group_code].push(match);
        }
    });

    return grouped;
});

const getStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'upcoming':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'completed':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getPlayerStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'applied':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'confirmed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        case 'eliminated':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
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

const getMatchStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'verification':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
        case 'ready':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
    }
};

const formatDateTime = (dateString: string | undefined): string => {
    if (!dateString) {
        return ''
    }

    return new Date(dateString).toLocaleString('uk-UK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDate = (dateString: string | undefined): string => {
    if (!dateString) {
        return ''
    }
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).replace('UAH', '₴').replace('грн', '₴');
};

const fetchTournament = async () => {
    isLoadingTournament.value = true;
    error.value = null;

    try {
        tournament.value = await apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`);
    } catch (err: any) {
        error.value = err.message || 'Failed to load tournament';
    } finally {
        isLoadingTournament.value = false;
    }
};

const fetchPlayers = async () => {
    isLoadingPlayers.value = true;

    try {
        players.value = await apiClient<TournamentPlayer[]>(`/api/tournaments/${props.tournamentId}/players`);
    } catch (err: any) {
        console.error('Failed to load players:', err);
    } finally {
        isLoadingPlayers.value = false;
    }
};

const fetchMatches = async () => {

    isLoadingMatches.value = true;
    try {
        // Use the public endpoint for non-admins
        const endpoint = isAdmin.value
            ? `/api/admin/tournaments/${props.tournamentId}/matches`
            : `/api/tournaments/${props.tournamentId}/matches`;
        matches.value = await apiClient<TournamentMatch[]>(endpoint);
    } catch (err: any) {
        console.error('Failed to load matches:', err);
        matches.value = [];
    } finally {
        isLoadingMatches.value = false;
    }
};

const fetchGroups = async () => {
    if (!canViewGroups.value) return;

    isLoadingGroups.value = true;
    try {
        groups.value = await apiClient<TournamentGroup[]>(`/api/tournaments/${props.tournamentId}/groups`);
    } catch (err: any) {
        console.error('Failed to load groups:', err);
        groups.value = [];
    } finally {
        isLoadingGroups.value = false;
    }
};

const handleApplicationUpdated = () => {
    fetchTournament();
    fetchPlayers();
};

// Get players in a specific group
const getPlayersInGroup = (groupCode: string) => {
    return players.value.filter(p => p.group_code === groupCode)
        .sort((a, b) => {
            // Sort by group position if available
            if (a.group_position && b.group_position) {
                return a.group_position - b.group_position;
            }
            // Otherwise sort by wins/losses
            const aDiff = a.group_wins - a.group_losses;
            const bDiff = b.group_wins - b.group_losses;
            if (aDiff !== bDiff) return bDiff - aDiff;

            // Then by games difference
            return b.group_games_diff - a.group_games_diff;
        });
};

onMounted(() => {
    fetchTournament();
    fetchPlayers();
    fetchMatches();
    fetchGroups();
});

// Add columns definition before the template
const columns = computed(() => [
    {
        key: 'position',
        label: t('Position'),
        align: 'left' as const,
        render: (player: TournamentPlayer) => ({
            position: player.position,
            isWinner: player.is_winner
        })
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (player: TournamentPlayer) => ({
            name: `${player.user?.firstname} ${player.user?.lastname}`,
            isWinner: player.is_winner
        })
    },
    {
        key: 'rating',
        label: t('Rating Points'),
        align: 'center' as const,
        render: (player: TournamentPlayer) => ({
            points: player.rating_points
        })
    },
    {
        key: 'bonus',
        label: t('Bonus'),
        align: 'right' as const,
        render: (player: TournamentPlayer) => ({
            amount: player.bonus_amount
        })
    },
    {
        key: 'prize',
        label: t('Prize'),
        align: 'right' as const,
        render: (player: TournamentPlayer) => ({
            amount: player.prize_amount
        })
    },
    {
        key: 'achievement',
        label: t('Achievement'),
        align: 'right' as const,
        render: (player: TournamentPlayer) => ({
            amount: player.achievement_amount
        })
    },
    {
        key: 'total',
        label: t('Total'),
        align: 'right' as const,
        render: (player: TournamentPlayer) => ({
            amount: player.total_amount
        })
    }
]);
</script>

<template>
    <Head :title="tournament ? t('Tournament: :name', {name: tournament.name}) : t('Tournament')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link href="/tournaments">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Tournaments') }}
                    </Button>
                </Link>

                <!-- Admin controls - only for authenticated admins -->
                <div v-if="isAuthenticated && isAdmin && tournament" class="flex flex-wrap gap-2">
                    <Link :href="`/admin/tournaments/${tournament.id}/edit`">
                        <Button size="sm" variant="secondary">
                            <PencilIcon class="mr-2 h-4 w-4"/>
                            {{ t('Edit') }}
                        </Button>
                    </Link>

                    <Link :href="`/admin/tournaments/${tournament.id}/players`">
                        <Button size="sm" variant="secondary">
                            <UserPlusIcon class="mr-2 h-4 w-4"/>
                            {{ t('Players') }}
                        </Button>
                    </Link>

                    <!-- Stage-based buttons -->
                    <Link v-if="showSeedingButton" :href="`/admin/tournaments/${tournament.id}/seeding`">
                        <Button size="sm" variant="secondary">
                            <StarIcon class="mr-2 h-4 w-4"/>
                            {{ t('Seeding') }}
                        </Button>
                    </Link>

                    <Link v-if="showGroupsButton" :href="`/admin/tournaments/${tournament.id}/groups`">
                        <Button size="sm" variant="secondary">
                            <LayersIcon class="mr-2 h-4 w-4"/>
                            {{ t('Groups') }}
                        </Button>
                    </Link>

                    <Link v-if="showBracketButton" :href="`/admin/tournaments/${tournament.id}/bracket`">
                        <Button size="sm" variant="secondary">
                            <GitBranchIcon class="mr-2 h-4 w-4"/>
                            {{ t('Bracket') }}
                        </Button>
                    </Link>

                    <Link v-if="showMatchesButton" :href="`/admin/tournaments/${tournament.id}/matches`">
                        <Button size="sm" variant="secondary">
                            <PlayIcon class="mr-2 h-4 w-4"/>
                            {{ t('Matches') }}
                        </Button>
                    </Link>

                    <Link v-if="tournament.pending_applications_count > 0"
                          :href="`/admin/tournaments/${tournament.id}/applications`">
                        <Button class="relative" size="sm" variant="secondary">
                            <ClipboardListIcon class="mr-2 h-4 w-4"/>
                            {{ t('Applications') }}
                            <span
                                class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ tournament.pending_applications_count }}
                            </span>
                        </Button>
                    </Link>
                    <Link v-else-if="tournament.requires_application"
                          :href="`/admin/tournaments/${tournament.id}/applications`">
                        <Button size="sm" variant="secondary">
                            <ClipboardListIcon class="mr-2 h-4 w-4"/>
                            {{ t('Applications') }}
                        </Button>
                    </Link>

                    <Link :href="`/admin/tournaments/${tournament.id}/results`">
                        <Button size="sm" variant="secondary">
                            <TrophyIcon class="mr-2 h-4 w-4"/>
                            {{ t('Results') }}
                        </Button>
                    </Link>
                </div>

                <!-- Login prompt for guests -->
                <div v-else-if="!isAuthenticated && tournament" class="text-center">
                    <Link :href="route('login')" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        <LogInIcon class="mr-1 inline h-4 w-4"/>
                        {{ t('Login to participate') }}
                    </Link>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingTournament" class="p-10 text-center">
                <Spinner class="text-primary mx-auto h-8 w-8"/>
                <p class="mt-2 text-gray-500">{{ t('Loading tournament...') }}</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                {{ t('Error loading tournament: :error', { error }) }}
            </div>

            <!-- Tournament Content -->
            <template v-else-if="tournament">
                <!-- Tournament Header -->
                <Card class="mb-8">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-3 text-2xl">
                                    {{ tournament.name }}
                                    <span
                                        :class="['rounded-full px-3 py-1 text-sm font-semibold', getStatusBadgeClass(tournament.status)]"
                                    >
                                        {{ tournament.status_display }}
                                    </span>
                                </CardTitle>
                                <CardDescription class="mt-2 text-lg">
                                    <div class="flex flex-wrap gap-4">
                                        <span class="flex items-center gap-1">
                                            <TrophyIcon class="h-4 w-4"/>
                                            {{ tournament.game?.name || 'N/A' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <CalendarIcon class="h-4 w-4"/>
                                            {{ formatDateTime(tournament.start_date) }}
                                            <span v-if="tournament.end_date !== tournament.start_date">
                                                - {{ formatDateTime(tournament.end_date) }}
                                            </span>
                                        </span>
                                        <span v-if="tournament.city" class="flex items-center gap-1">
                                            <MapPinIcon class="h-4 w-4"/>
                                            {{ tournament.city.name }}, {{ tournament.city.country?.name }}
                                        </span>
                                    </div>
                                    <div v-if="tournament.stage" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        {{ t('Stage') }}: <span class="font-medium">{{
                                            tournament.stage_display
                                        }}</span>
                                    </div>
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <StageTransition
                    v-if="isAuthenticated && isAdmin && tournament && tournament.status !== 'completed'"
                    :tournament="tournament"
                    @updated="fetchTournament"
                />

                <!-- Tournament Application Card - Only show to authenticated users -->
                <div v-if="isAuthenticated && tournament.requires_application && tournament.status === 'upcoming'"
                     class="mb-8">
                    <TournamentApplicationCard
                        :tournament="tournament"
                        @application-updated="handleApplicationUpdated"
                    />
                </div>

                <!-- Guest application prompt -->
                <div v-else-if="!isAuthenticated && tournament.requires_application && tournament.status === 'upcoming'"
                     class="mb-8">
                    <Card class="border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20">
                        <CardContent class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-blue-800 dark:text-blue-300">{{ t('Tournament Registration') }}</h3>
                                    <p class="text-blue-600 dark:text-blue-400">{{ t('This tournament requires application to participate.') }}</p>
                                </div>
                                <Link :href="route('login')">
                                    <Button>
                                        <LogInIcon class="mr-2 h-4 w-4"/>
                                        {{ t('Login to Apply') }}
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tab Navigation -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8 overflow-x-auto">
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                                activeTab === 'info'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'info'"
                        >
                            {{ t('Information') }}
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                                activeTab === 'players'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'players'"
                        >
                            {{ t('Players') }} ({{ tournament.confirmed_players_count }})
                        </button>
                        <button
                            v-if="canViewGroups"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                                activeTab === 'groups'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'groups'"
                        >
                            {{ t('Groups') }}
                        </button>
                        <button
                            v-if="canViewBracket"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                                activeTab === 'bracket'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'bracket'"
                        >
                            {{ t('Bracket') }}
                        </button>
                        <button
                            v-if="canViewMatches"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                                activeTab === 'matches'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'matches'"
                        >
                            {{ t('Matches') }}
                        </button>
                        <button
                            v-if="tournament.requires_application && (isAuthenticated && isAdmin || tournament.pending_applications_count > 0)"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                                activeTab === 'applications'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'applications'"
                        >
                            {{ t('Applications') }} ({{ tournament.pending_applications_count }})
                        </button>
                        <button
                            v-if="tournament.status === 'completed'"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                                activeTab === 'results'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'results'"
                        >
                            {{ t('Results') }}
                        </button>
                    </nav>
                </div>

                <!-- Tournament Information Tab -->
                <div v-if="activeTab === 'info'" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Details Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ t('Tournament Details') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{
                                                t('Tournament Type')
                                            }}</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">
                                            {{ tournament.tournament_type_display }}
                                            <span v-if="tournament.races_to" class="text-sm">
                                                ({{ t('Race to') }} {{ tournament.races_to }})
                                            </span>
                                        </p>
                                    </div>

                                    <div v-if="tournament.details">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ t('Description') }}</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                            {{ tournament.details }}</p>
                                    </div>

                                    <div v-if="tournament.regulation">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ t('Regulation') }}</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                            {{ tournament.regulation }}</p>
                                    </div>

                                    <div v-if="tournament.format">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ t('Format') }}</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">{{ tournament.format }}</p>
                                    </div>

                                    <div v-if="tournament.organizer">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ t('Organizer') }}</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">{{ tournament.organizer }}</p>
                                    </div>

                                    <div v-if="tournament.application_deadline">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ t('Application Deadline') }}</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">
                                            {{ formatDateTime(tournament.application_deadline) }}</p>
                                    </div>

                                    <div v-if="tournament.official_ratings && tournament.official_ratings.length > 0"
                                         class="flex justify-between">
                                        <dt class="text-gray-600 dark:text-gray-400">
                                            <StarIcon class="h-4 w-4 inline mr-1"/>
                                            {{ t('Official Rating:') }}
                                        </dt>
                                        <dd class="font-medium">
                                            {{ tournament.official_ratings[0].name }}
                                            <span class="text-xs text-gray-500">(×{{
                                                    tournament.official_ratings[0].rating_coefficient
                                                }})</span>
                                        </dd>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Stats Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ t('Tournament Stats') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                            {{ tournament.confirmed_players_count }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ t('Confirmed Players') }}
                                        </div>
                                    </div>

                                    <div v-if="tournament.pending_applications_count > 0"
                                         class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                            {{ tournament.pending_applications_count }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ t('Pending Applications') }}
                                        </div>
                                    </div>

                                    <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                            {{ tournament.max_participants || '∞' }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ t('Max Participants') }}
                                        </div>
                                    </div>

                                    <div v-if="tournament.entry_fee > 0"
                                         class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                            {{ formatCurrency(tournament.entry_fee) }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Entry Fee') }}</div>
                                    </div>

                                    <div v-if="tournament.prize_pool > 0"
                                         class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800 col-span-2">
                                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                            {{ formatCurrency(tournament.prize_pool) }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Prize Pool') }}</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Players Tab -->
                <div v-if="activeTab === 'players'">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <UsersIcon class="h-5 w-5"/>
                                {{ t('Confirmed Players') }}
                            </CardTitle>
                            <CardDescription>
                                {{ tournament.confirmed_players_count }} {{ t('confirmed players') }}
                                <span v-if="tournament.max_participants">
                                   {{ t('out of') }} {{ tournament.max_participants }} {{ t('maximum') }}
                               </span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6"/>
                            </div>
                            <div v-else-if="confirmedPlayers.length === 0" class="py-8 text-center text-gray-500">
                                {{ t('No confirmed players yet.') }}
                            </div>
                            <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="player in confirmedPlayers"
                                    :key="player.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-800"
                                >
                                    <div>
                                        <p class="font-medium">
                                            <span v-if="player.seed_number"
                                                  class="text-gray-500 mr-2">#{{ player.seed_number }}</span>
                                            {{ player.user?.firstname }} {{ player.user?.lastname }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ player.status_display }}
                                        </p>
                                        <p v-if="player.confirmed_at" class="text-xs text-gray-500">
                                            {{ t('Confirmed:') }} {{ formatDate(player.confirmed_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Groups Tab -->
                <div v-if="activeTab === 'groups' && canViewGroups">
                    <div class="space-y-6">
                        <Card v-if="isLoadingGroups">
                            <CardContent class="py-10">
                                <div class="flex justify-center">
                                    <Spinner class="text-primary h-8 w-8"/>
                                </div>
                            </CardContent>
                        </Card>

                        <div v-else-if="groups.length > 0" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <Card v-for="group in groups" :key="group.id">
                                <CardHeader>
                                    <CardTitle>{{ t('Group :code', {code: group.group_code}) }}</CardTitle>
                                    <CardDescription>
                                        {{ t(':count players', {count: group.group_size}) }}
                                        <span v-if="group.advance_count"> • {{
                                                t(':count advance', {count: group.advance_count})
                                            }}</span>
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(player, index) in getPlayersInGroup(group.group_code)"
                                            :key="player.id"
                                            :class="[
                                                'flex items-center justify-between p-2 rounded',
                                                index < group.advance_count ? 'bg-green-50 dark:bg-green-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                                            ]"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-500">
                                                    {{ index + 1 }}
                                                </span>
                                                <span>{{ player.user?.firstname }} {{ player.user?.lastname }}</span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                <span v-if="player.group_wins > 0 || player.group_losses > 0">
                                                    {{ player.group_wins }}W - {{ player.group_losses }}L
                                                </span>
                                                <span v-else>{{ t('Not played') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Group Matches -->
                                    <div v-if="matchesByGroup[group.group_code]?.length > 0" class="mt-4 pt-4 border-t">
                                        <h4 class="text-sm font-medium mb-2">{{ t('Matches') }}</h4>
                                        <div class="space-y-2">
                                            <div
                                                v-for="match in matchesByGroup[group.group_code]"
                                                :key="match.id"
                                                class="text-sm"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <div class="flex-1">
                                                        <span
                                                            :class="{'font-bold': match.winner_id === match.player1_id}">
                                                            {{
                                                                match.player1?.firstname?.substring(0, 1)
                                                            }}. {{ match.player1?.lastname }}
                                                        </span>
                                                        <span class="mx-2">vs</span>
                                                        <span
                                                            :class="{'font-bold': match.winner_id === match.player2_id}">
                                                            {{
                                                                match.player2?.firstname?.substring(0, 1)
                                                            }}. {{ match.player2?.lastname }}
                                                        </span>
                                                    </div>
                                                    <div class="ml-2">
                                                        <span v-if="match.status === 'completed'" class="font-medium">
                                                            {{ match.player1_score }} - {{ match.player2_score }}
                                                        </span>
                                                        <span v-else
                                                              :class="['px-2 py-1 text-xs rounded-full', getMatchStatusBadgeClass(match.status)]">
                                                            {{ match.status_display }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>

                <!-- Bracket Tab -->
                <div v-if="activeTab === 'bracket' && canViewBracket">
                    <div v-if="isLoadingMatches" class="flex justify-center py-12">
                        <Spinner class="h-8 w-8 text-primary"/>
                    </div>
                    <div v-else-if="matches.length === 0" class="text-center py-12">
                        <p class="text-gray-500">{{ t('Bracket has not been generated yet.') }}</p>
                    </div>
                    <template v-else>
                        <SingleEliminationBracket
                            v-if="!isDoubleElimination"
                            :can-edit="false"
                            :current-user-id="currentUserId"
                            :matches="matches"
                            :tournament="tournament!"
                        />

                        <DoubleEliminationBracket
                            v-else
                            :can-edit="false"
                            :current-user-id="currentUserId"
                            :matches="matches"
                            :tournament="tournament!"
                        />
                    </template>
                </div>

                <!-- Matches Tab -->
                <div v-if="activeTab === 'matches' && canViewMatches">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <PlayIcon class="h-5 w-5"/>
                                {{ t('Tournament Matches') }}
                            </CardTitle>
                            <CardDescription>
                                {{ t('All tournament matches and results') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="isLoadingMatches" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6"/>
                            </div>
                            <div v-else-if="displayMatches.length === 0" class="py-8 text-center text-gray-500">
                                {{ t('No matches scheduled yet.') }}
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="match in displayMatches"
                                    :key="match.id"
                                    class="flex items-center justify-between rounded-lg border p-4"
                                >
                                    <!-- Match Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4">
                                            <!-- Match Code & Stage -->
                                            <div class="min-w-[120px]">
                                                <p class="font-medium">
                                                    {{ match.match_code || `#${match.id}` }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ match.stage_display }}
                                                    <span v-if="match.round_display"> - {{ match.round_display }}</span>
                                                </p>
                                            </div>

                                            <!-- Players -->
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex-1">
                                                        <p :class="{'text-green-600 font-bold': match.winner_id === match.player1_id}">
                                                            {{ match.player1?.firstname }} {{ match.player1?.lastname }}
                                                            <span v-if="!match.player1"
                                                                  class="text-gray-400">{{ t('TBD') }}</span>
                                                        </p>
                                                        <p :class="{'text-green-600 font-bold': match.winner_id === match.player2_id}">
                                                            {{ match.player2?.firstname }} {{ match.player2?.lastname }}
                                                            <span v-if="!match.player2"
                                                                  class="text-gray-400">{{ t('TBD') }}</span>
                                                        </p>
                                                    </div>

                                                    <!-- Scores -->
                                                    <div class="mx-4 text-center">
                                                        <p class="text-xl font-bold">
                                                            {{ match.player1_score ?? '-' }}
                                                        </p>
                                                        <p class="text-xl font-bold">
                                                            {{ match.player2_score ?? '-' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status & Table -->
                                            <div class="text-right">
                                                <span :class="[
                                                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                                    getMatchStatusBadgeClass(match.status)
                                                ]">
                                                    {{ match.status_display }}
                                                </span>
                                                <p v-if="match.club_table" class="mt-1 text-sm text-gray-500">
                                                    {{ match.club_table.name }}
                                                </p>
                                                <p v-if="match.scheduled_at" class="mt-1 text-sm text-gray-500">
                                                    <ClockIcon class="inline h-3 w-3"/>
                                                    {{ formatDateTime(match.scheduled_at) }}
                                                </p>
                                                <p v-if="match.completed_at && match.status === 'completed'"
                                                   class="mt-1 text-sm text-gray-500">
                                                    <CheckCircleIcon class="inline h-3 w-3"/>
                                                    {{ formatDateTime(match.completed_at) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Applications Tab -->
                <div v-if="activeTab === 'applications'">
                    <div class="space-y-6">
                        <!-- Pending Applications -->
                        <Card v-if="pendingApplications.length > 0">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <ClipboardListIcon class="h-5 w-5 text-yellow-600"/>
                                    {{ t('Pending Applications') }} ({{ pendingApplications.length }})
                                </CardTitle>
                                <CardDescription>{{ t('Applications waiting for approval') }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="application in pendingApplications"
                                        :key="application.id"
                                        class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg dark:bg-yellow-900/20"
                                    >
                                        <div>
                                            <p class="font-medium">{{ application.user?.firstname }}
                                                {{ application.user?.lastname }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ t('Applied:') }} {{ formatDate(application.applied_at) }}
                                            </p>
                                        </div>
                                        <span
                                            :class="['px-2 py-1 text-xs font-semibold rounded-full', getPlayerStatusBadgeClass(application.status)]">
                                           {{ application.status_display }}
                                       </span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Rejected Applications -->
                        <Card v-if="rejectedApplications.length > 0">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <UserCheckIcon class="h-5 w-5 text-red-600"/>
                                    {{ t('Rejected Applications') }} ({{ rejectedApplications.length }})
                                </CardTitle>
                                <CardDescription>{{ t('Applications that were not accepted') }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="application in rejectedApplications"
                                        :key="application.id"
                                        class="flex items-center justify-between p-3 bg-red-50 rounded-lg dark:bg-red-900/20"
                                    >
                                        <div>
                                            <p class="font-medium">{{ application.user?.firstname }}
                                                {{ application.user?.lastname }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ t('Rejected:') }} {{ formatDate(application.rejected_at) }}
                                            </p>
                                        </div>
                                        <span
                                            :class="['px-2 py-1 text-xs font-semibold rounded-full', getPlayerStatusBadgeClass(application.status)]">
                                           {{ application.status_display }}
                                       </span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Empty State -->
                        <Card v-if="pendingApplications.length === 0 && rejectedApplications.length === 0">
                            <CardContent class="py-10 text-center">
                                <ClipboardListIcon class="mx-auto h-12 w-12 text-gray-400"/>
                                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">{{ t('No Applications') }}</p>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">
                                    {{ t('There are no applications to display.') }}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Results Tab -->
                <div v-if="activeTab === 'results' && tournament.status === 'completed'">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <TrophyIcon class="h-5 w-5"/>
                                {{ t('Tournament Results') }}
                            </CardTitle>
                            <CardDescription>{{ t('Final standings and prizes') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="completedPlayers.length === 0" class="py-8 text-center text-gray-500">
                                {{ t('No results available yet.') }}
                            </div>
                            <div v-else class="overflow-auto">
                                <DataTable
                                    :columns="columns"
                                    :compact-mode="true"
                                    :data="completedPlayers"
                                    :empty-message="t('No results available yet.')"
                                >
                                    <template #cell-position="{ value }">
                                        <span
                                            :class="[
                                                'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                                getPositionBadgeClass(value.position)
                                            ]"
                                        >
                                            {{ value.position }}
                                        </span>
                                    </template>

                                    <template #cell-player="{ value }">
                                        <div>
                                            <p class="font-medium">{{ value.name }}</p>
                                            <p v-if="value.isWinner"
                                               class="text-sm text-yellow-600 dark:text-yellow-400">🏆 {{
                                                    t('Winner')
                                                }}</p>
                                        </div>
                                    </template>

                                    <template #cell-rating="{ value }">
                                        <span
                                            v-if="value.points > 0"
                                            class="rounded-full bg-blue-100 px-2 py-1 text-sm text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                                        >
                                            +{{ value.points }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-bonus="{ value }">
                                        <span v-if="value.amount > 0"
                                              class="font-medium text-orange-600 dark:text-orange-400">
                                            {{ formatCurrency(value.amount) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-prize="{ value }">
                                        <span v-if="value.amount > 0"
                                              class="font-medium text-green-600 dark:text-green-400">
                                            {{ formatCurrency(value.amount) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-achievement="{ value }">
                                        <span v-if="value.amount > 0"
                                              class="font-medium text-purple-600 dark:text-purple-400">
                                            {{ formatCurrency(value.amount) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>

                                    <template #cell-total="{ value }">
                                        <span v-if="value.amount > 0"
                                              class="font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatCurrency(value.amount) }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>
                                </DataTable>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>
        </div>
    </div>
</template>
