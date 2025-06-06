<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    CalendarIcon,
    CrownIcon,
    EyeIcon,
    MapPinIcon,
    PencilIcon,
    PlusIcon,
    StarIcon,
    TrophyIcon,
    UsersIcon,
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {isAdmin, isAuthenticated} = useAuth();
const {t} = useLocale();

const tournaments = ref<Tournament[]>([]);
const userParticipations = ref<TournamentPlayer[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const selectedStatus = ref<string>('all');

const statusOptions = [
    {value: 'all', label: t('All Tournaments')},
    {value: 'upcoming', label: t('Upcoming')},
    {value: 'active', label: t('Active')},
    {value: 'completed', label: t('Completed')}
];

const filteredTournaments = computed(() => {
    if (selectedStatus.value === 'all') {
        return tournaments.value;
    }
    return tournaments.value.filter(t => t.status === selectedStatus.value);
});

// Create a map of tournament IDs where user participated
const userTournamentMap = computed(() => {
    const map = new Map<number, TournamentPlayer>();
    userParticipations.value.forEach(participation => {
        if (participation.tournament) {
            map.set(participation.tournament.id, participation);
        }
    });
    return map;
});

const fetchTournaments = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const [tournamentsData, userTournamentsData] = await Promise.all([
            apiClient<Tournament[]>('/api/tournaments'),
            // Only fetch user tournaments if authenticated
            isAuthenticated.value
                ? apiClient<{
                    tournaments: {
                        upcoming: any[],
                        active: any[],
                        completed: any[],
                        pending_applications: any[],
                        rejected_applications: any[]
                    }
                }>('/api/user/tournaments/my-tournaments-and-applications')
                : Promise.resolve(null)
        ]);

        tournaments.value = tournamentsData;

        if (userTournamentsData && isAuthenticated.value) {
            // Flatten all user participations into a single array
            userParticipations.value = [
                ...userTournamentsData.tournaments.upcoming.map(t => t.participation),
                ...userTournamentsData.tournaments.active.map(t => t.participation),
                ...userTournamentsData.tournaments.completed.map(t => t.participation),
                ...userTournamentsData.tournaments.pending_applications.map(t => t.participation),
                ...userTournamentsData.tournaments.rejected_applications.map(t => t.participation),
            ].filter(p => p && p.tournament);
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to load tournaments';
    } finally {
        isLoading.value = false;
    }
};

const getUserParticipation = (tournamentId: number): TournamentPlayer | undefined => {
    return userTournamentMap.value.get(tournamentId);
};

const isUserParticipant = (tournamentId: number): boolean => {
    return userTournamentMap.value.has(tournamentId);
};

const getParticipationBadgeClass = (participation: TournamentPlayer): string => {
    switch (participation.status) {
        case 'confirmed':
            if (participation.position === 1) {
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
            } else if (participation.position && participation.position <= 3) {
                return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
            }
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'applied':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getParticipationBadgeText = (participation: TournamentPlayer): string => {
    switch (participation.status) {
        case 'confirmed':
            if (participation.position === 1) {
                return `🏆 ${t('Winner')}`;
            } else if (participation.position && participation.position <= 3) {
                return `🥉 ${participation.position}${getOrdinalSuffix(participation.position)} ${t('Place')}`;
            } else if (participation.position) {
                return `${participation.position}${getOrdinalSuffix(participation.position)} ${t('Place')}`;
            }
            return t('Participated');
        case 'applied':
            return t('Applied');
        case 'rejected':
            return t('Rejected');
        default:
            return t('Registered');
    }
};

const getOrdinalSuffix = (num: number): string => {
    if (num > 3 && num < 21) return 'th';
    switch (num % 10) {
        case 1:
            return 'st';
        case 2:
            return 'nd';
        case 3:
            return 'rd';
        default:
            return 'th';
    }
};

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

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

const formatDateRange = (startDate: string, endDate: string): string => {
    const start = formatDate(startDate);
    const end = formatDate(endDate);
    return start === end ? start : `${start} - ${end}`;
};

const formatPrizePool = (amount: number): string => {
    if (amount <= 0) return t('N/A');
    return amount.toLocaleString('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).replace('UAH', '₴');
};

onMounted(() => {
    fetchTournaments();
});
</script>

<template>
    <Head :title="t('Tournaments')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ t('Tournaments') }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ t('Discover and follow billiard tournaments') }}
                        <span v-if="isAuthenticated && userParticipations.length > 0"
                              class="inline-flex items-center ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                            <StarIcon class="w-3 h-3 mr-1"/>
                            {{ userParticipations.length }} {{ t('participations') }}
                        </span>
                    </p>
                </div>

                <!-- Only show create button to authenticated admins -->
                <Link v-if="isAuthenticated && isAdmin" href="/admin/tournaments/create">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        {{ t('Create Tournament') }}
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-2">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    :class="[
                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                        selectedStatus === option.value
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                    ]"
                    @click="selectedStatus = option.value"
                >
                    {{ option.label }}
                </button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5"/>
                        {{ t('Tournament Directory') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Loading State -->
                    <div v-if="isLoading" class="flex items-center justify-center py-10">
                        <Spinner class="text-primary h-8 w-8"/>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">{{ t('Loading tournaments...') }}</span>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error"
                         class="rounded bg-red-100 p-4 text-center text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        {{ t('Error loading tournaments: :error', { error }) }}
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="filteredTournaments.length === 0"
                         class="py-10 text-center text-gray-500 dark:text-gray-400">
                        <TrophyIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                        <p class="text-lg">{{ t('No tournaments found') }}</p>
                        <p class="text-sm">
                            {{
                                selectedStatus === 'all' ? t('No tournaments have been created yet.') : t('No :status tournaments.', {status: selectedStatus})
                            }}
                        </p>
                    </div>

                    <!-- Tournaments Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    {{ t('Tournament') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    {{ t('Game') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    {{ t('Status') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    {{ t('Date') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    {{ t('Location') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    {{ t('Players') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    {{ t('Prize Pool') }}
                                </th>
                                <th
                                    class="sticky right-0 z-10 px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-800"
                                >
                                    {{ t('Actions') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                            <tr
                                v-for="tournament in filteredTournaments"
                                :key="tournament.id"
                                :class="[
                                    'transition-colors',
                                    isUserParticipant(tournament.id)
                                        ? 'bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 border-l-4 border-blue-300'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'
                                ]"
                            >
                                <!-- Tournament Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                :class="[
                                                    'h-8 w-8 rounded-full flex items-center justify-center',
                                                    isUserParticipant(tournament.id)
                                                        ? 'bg-blue-100 dark:bg-blue-900/30'
                                                        : 'bg-yellow-100 dark:bg-yellow-900/30'
                                                ]"
                                            >
                                                <StarIcon
                                                    v-if="isUserParticipant(tournament.id)"
                                                    class="h-4 w-4 text-blue-600 dark:text-blue-400"
                                                />
                                                <TrophyIcon
                                                    v-else
                                                    class="h-4 w-4 text-yellow-600 dark:text-yellow-400"
                                                />
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center gap-2">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ tournament.name }}
                                                </div>
                                                <!-- Participation Badge -->
                                                <span
                                                    v-if="isUserParticipant(tournament.id)"
                                                    :class="[
                                                        'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
                                                        getParticipationBadgeClass(getUserParticipation(tournament.id)!)
                                                    ]"
                                                >
                                                    <CrownIcon
                                                        v-if="getUserParticipation(tournament.id)?.position === 1"
                                                        class="w-3 h-3 mr-1"
                                                    />
                                                    {{
                                                        getParticipationBadgeText(getUserParticipation(tournament.id)!)
                                                    }}
                                                </span>
                                            </div>
                                            <div v-if="tournament.organizer"
                                                 class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ t('by') }} {{ tournament.organizer }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Game -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <TrophyIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        {{ tournament.game?.name || t('N/A') }}
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                                getStatusBadgeClass(tournament.status)
                                            ]"
                                        >
                                            {{ tournament.status_display }}
                                        </span>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <CalendarIcon class="h-4 w-4 mr-2"/>
                                        {{ formatDateRange(tournament.start_date, tournament.end_date) }}
                                    </div>
                                </td>

                                <!-- Location -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="tournament.city"
                                         class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <MapPinIcon class="h-4 w-4 mr-2"/>
                                        <div>
                                            <div>{{ tournament.city.name }}</div>
                                            <div class="text-xs">{{ tournament.city.country?.name }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-400">{{ t('N/A') }}</div>
                                </td>

                                <!-- Players -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        <div>
                                            {{ tournament.players_count }}
                                            <span v-if="tournament.max_participants">
                                                    / {{ tournament.max_participants }}
                                                </span>
                                            <div class="text-xs text-gray-500">
                                                {{ tournament.players_count !== 1 ? t('players') : t('player') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Prize Pool -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm">
                                        <span
                                            :class="tournament.prize_pool > 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400'">
                                                {{ formatPrizePool(tournament.prize_pool) }}
                                            </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="sticky right-0 z-10 px-6 py-4 whitespace-nowrap text-right text-sm font-medium bg-white dark:bg-gray-900">
                                    <div class="flex justify-end space-x-2">
                                        <!-- Everyone can view -->
                                        <Link :href="`/tournaments/${tournament.id}`">
                                            <Button size="sm" variant="outline">
                                                <EyeIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>

                                        <!-- Only authenticated admins can edit -->
                                        <Link v-if="isAuthenticated && isAdmin"
                                              :href="`/admin/tournaments/${tournament.id}/edit`">
                                            <Button size="sm" variant="outline">
                                                <PencilIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
