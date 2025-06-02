<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import TournamentApplicationCard from '@/Components/Tournament/TournamentApplicationCard.vue';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CalendarIcon,
    ClipboardListIcon,
    MapPinIcon,
    PencilIcon,
    StarIcon,
    TrophyIcon,
    UserCheckIcon,
    UserPlusIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {isAdmin} = useAuth();

const tournament = ref<Tournament | null>(null);
const players = ref<TournamentPlayer[]>([]);
const isLoadingTournament = ref(true);
const isLoadingPlayers = ref(true);
const error = ref<string | null>(null);
const activeTab = ref<'info' | 'players' | 'results' | 'applications'>('info');

const sortedPlayers = computed(() => {
    return [...players.value].sort((a, b) => {
        // Sort by status first: confirmed > applied > rejected
        const statusOrder = {confirmed: 1, applied: 2, rejected: 3};
        const aStatus = statusOrder[a.status as keyof typeof statusOrder] || 4;
        const bStatus = statusOrder[b.status as keyof typeof statusOrder] || 4;

        if (aStatus !== bStatus) {
            return aStatus - bStatus;
        }

        // Then by position (for confirmed players)
        if (a.position !== null && b.position !== null) {
            return a.position - b.position;
        }
        if (a.position !== null) return -1;
        if (b.position !== null) return 1;

        // Finally by application date
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
    }).replace('UAH', '₴');
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

const handleApplicationUpdated = () => {
    // Refresh tournament data to get updated counts
    fetchTournament();
    fetchPlayers();
};

onMounted(() => {
    fetchTournament();
    fetchPlayers();
});
</script>

<template>
    <Head :title="tournament ? `Tournament: ${tournament.name}` : 'Tournament'"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link href="/tournaments">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to Tournaments
                    </Button>
                </Link>

                <div v-if="isAdmin && tournament" class="flex space-x-2">
                    <Link :href="`/admin/tournaments/${tournament.id}/edit`">
                        <Button variant="secondary">
                            <PencilIcon class="mr-2 h-4 w-4"/>
                            Edit Tournament
                        </Button>
                    </Link>
                    <Link :href="`/admin/tournaments/${tournament.id}/players`">
                        <Button variant="secondary">
                            <UserPlusIcon class="mr-2 h-4 w-4"/>
                            Manage Players
                        </Button>
                    </Link>
                    <Link v-if="tournament.pending_applications_count > 0"
                          :href="`/admin/tournaments/${tournament.id}/applications`">
                        <Button class="relative" variant="secondary">
                            <ClipboardListIcon class="mr-2 h-4 w-4"/>
                            Applications
                            <span
                                class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ tournament.pending_applications_count }}
                            </span>
                        </Button>
                    </Link>
                    <Link v-else-if="tournament.requires_application"
                          :href="`/admin/tournaments/${tournament.id}/applications`">
                        <Button variant="secondary">
                            <ClipboardListIcon class="mr-2 h-4 w-4"/>
                            Applications
                        </Button>
                    </Link>
                    <Link :href="`/admin/tournaments/${tournament.id}/results`">
                        <Button variant="secondary">
                            <TrophyIcon class="mr-2 h-4 w-4"/>
                            Manage Results
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingTournament" class="p-10 text-center">
                <Spinner class="text-primary mx-auto h-8 w-8"/>
                <p class="mt-2 text-gray-500">Loading tournament...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                Error loading tournament: {{ error }}
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
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <!-- Tournament Application Card -->
                <div v-if="tournament.requires_application && tournament.status === 'upcoming'" class="mb-8">
                    <TournamentApplicationCard
                        :tournament="tournament"
                        @application-updated="handleApplicationUpdated"
                    />
                </div>

                <!-- Tab Navigation -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'info'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'info'"
                        >
                            Information
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'players'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'players'"
                        >
                            Players ({{ tournament.confirmed_players_count }})
                        </button>
                        <button
                            v-if="tournament.requires_application && (isAdmin || tournament.pending_applications_count > 0)"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'applications'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'applications'"
                        >
                            Applications ({{ tournament.pending_applications_count }})
                        </button>
                        <button
                            v-if="tournament.is_completed"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'results'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'results'"
                        >
                            Results
                        </button>
                    </nav>
                </div>

                <!-- Tournament Information Tab -->
                <div v-if="activeTab === 'info'" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Details Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Tournament Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div v-if="tournament.details">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Description</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                            {{ tournament.details }}</p>
                                    </div>

                                    <div v-if="tournament.regulation">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Regulation</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                            {{ tournament.regulation }}</p>
                                    </div>

                                    <div v-if="tournament.format">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Format</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">{{ tournament.format }}</p>
                                    </div>

                                    <div v-if="tournament.organizer">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Organizer</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">{{ tournament.organizer }}</p>
                                    </div>

                                    <div v-if="tournament.application_deadline">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Application
                                            Deadline</h4>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">
                                            {{ formatDateTime(tournament.application_deadline) }}</p>
                                    </div>

                                    <div v-if="tournament.official_ratings && tournament.official_ratings.length > 0"
                                         class="flex justify-between">
                                        <dt class="text-gray-600 dark:text-gray-400">
                                            <StarIcon class="h-4 w-4 inline mr-1"/>
                                            Official Rating:
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
                                <CardTitle>Tournament Stats</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                            {{ tournament.confirmed_players_count }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Confirmed Players
                                        </div>
                                    </div>

                                    <div v-if="tournament.pending_applications_count > 0"
                                         class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                            {{ tournament.pending_applications_count }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Pending Applications
                                        </div>
                                    </div>

                                    <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                            {{ tournament.max_participants || '∞' }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Max Participants
                                        </div>
                                    </div>

                                    <div v-if="tournament.entry_fee > 0"
                                         class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                            {{ formatCurrency(tournament.entry_fee) }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Entry Fee</div>
                                    </div>

                                    <div v-if="tournament.prize_pool > 0"
                                         class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800 col-span-2">
                                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                            {{ formatCurrency(tournament.prize_pool) }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Prize Pool</div>
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
                                Confirmed Players
                            </CardTitle>
                            <CardDescription>
                                {{ tournament.confirmed_players_count }} confirmed players
                                <span v-if="tournament.max_participants">
                                    out of {{ tournament.max_participants }} maximum
                                </span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                                <Spinner class="text-primary h-6 w-6"/>
                            </div>
                            <div v-else-if="confirmedPlayers.length === 0" class="py-8 text-center text-gray-500">
                                No confirmed players yet.
                            </div>
                            <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="player in confirmedPlayers"
                                    :key="player.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-800"
                                >
                                    <div>
                                        <p class="font-medium">{{ player.user?.firstname }}
                                            {{ player.user?.lastname }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ player.status_display }}
                                        </p>
                                        <p v-if="player.confirmed_at" class="text-xs text-gray-500">
                                            Confirmed: {{ formatDate(player.confirmed_at) }}
                                        </p>
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
                                    Pending Applications ({{ pendingApplications.length }})
                                </CardTitle>
                                <CardDescription>Applications waiting for approval</CardDescription>
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
                                                Applied: {{ formatDate(application.applied_at) }}
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
                                    Rejected Applications ({{ rejectedApplications.length }})
                                </CardTitle>
                                <CardDescription>Applications that were not accepted</CardDescription>
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
                                                Rejected: {{ formatDate(application.rejected_at) }}
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
                                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No Applications</p>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">
                                    There are no applications to display.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Results Tab -->
                <div v-if="activeTab === 'results' && tournament.is_completed">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <TrophyIcon class="h-5 w-5"/>
                                Tournament Results
                            </CardTitle>
                            <CardDescription>Final standings and prizes</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="completedPlayers.length === 0" class="py-8 text-center text-gray-500">
                                No results available yet.
                            </div>
                            <div v-else class="overflow-auto">
                                <table class="w-full">
                                    <thead>
                                    <tr class="border-b dark:border-gray-700">
                                        <th class="px-4 py-3 text-left">Position</th>
                                        <th class="px-4 py-3 text-left">Player</th>
                                        <th class="px-4 py-3 text-center">Rating Points</th>
                                        <th class="px-4 py-3 text-right">Prize</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr
                                        v-for="player in completedPlayers"
                                        :key="player.id"
                                        class="border-b dark:border-gray-700"
                                    >
                                        <td class="px-4 py-3">
                                            <span
                                                :class="[
                                                    'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                                    getPositionBadgeClass(player.position || 0)
                                                ]"
                                            >
                                                {{ player.position }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <p class="font-medium">{{ player.user?.firstname }}
                                                    {{ player.user?.lastname }}</p>
                                                <p v-if="player.is_winner"
                                                   class="text-sm text-yellow-600 dark:text-yellow-400">🏆 Winner</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                v-if="player.rating_points > 0"
                                                class="rounded-full bg-blue-100 px-2 py-1 text-sm text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                                            >
                                                +{{ player.rating_points }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span v-if="player.prize_amount > 0"
                                                  class="font-medium text-green-600 dark:text-green-400">
                                                {{ formatCurrency(player.prize_amount) }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>
        </div>
    </div>
</template>
