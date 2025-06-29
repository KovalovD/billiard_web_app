<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useAuth} from '@/composables/useAuth';
import {useLocale} from '@/composables/useLocale';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {CalendarIcon, CrownIcon, MapPinIcon, PlusIcon, TrophyIcon, UsersIcon,} from 'lucide-vue-next';
import {computed, onMounted, onUnmounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {isAdmin, isAuthenticated} = useAuth();
const {t} = useLocale();
const {setSeoMeta, generateBreadcrumbJsonLd} = useSeo();

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

// Define table columns (removed actions column)
const columns = computed(() => [
    {
        key: 'name',
        label: t('Tournament'),
        align: 'left' as const,
        render: (tournament: Tournament) => ({
            name: tournament.name || t('Unnamed Tournament'),
            organizer: tournament.organizer || null,
            participation: getUserParticipation(tournament.id),
            isParticipant: isUserParticipant(tournament.id)
        })
    },
    {
        key: 'participation',
        label: t('Your Status'),
        align: 'center' as const,
        render: (tournament: Tournament) => {
            const participation = getUserParticipation(tournament.id);
            if (!participation) return null;
            return {
                status: participation.status,
                position: participation.position,
                badgeClass: getParticipationBadgeClass(participation),
                badgeText: getParticipationBadgeText(participation)
            };
        }
    },
    {
        key: 'game',
        label: t('Game'),
        hideOnMobile: true,
        render: (tournament: Tournament) => {
            if (!tournament.game) {
                return null;
            }
            return tournament.game.name;
        }
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (tournament: Tournament) => ({
            status: tournament.status,
            status_display: tournament.status_display
        })
    },
    {
        key: 'date',
        label: t('Date'),
        hideOnMobile: true,
        render: (tournament: Tournament) => {
            if (!tournament.start_date || !tournament.end_date) {
                return null;
            }
            return formatDateRange(tournament.start_date, tournament.end_date);
        }
    },
    {
        key: 'location',
        label: t('Location'),
        hideOnTablet: true,
        render: (tournament: Tournament) => {
            if (!tournament.city) {
                return null;
            }
            return {
                city: tournament.city,
                hasLocation: true
            };
        }
    },
    {
        key: 'players',
        label: t('Players'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (tournament: Tournament) => {
            const count = tournament.players_count ?? 0;
            const max = tournament.max_participants ?? null;
            return {
                count,
                max,
                hasMax: max !== null
            };
        }
    },
    {
        key: 'prize',
        label: t('Prize Pool'),
        align: 'right' as const,
        hideOnTablet: true,
        render: (tournament: Tournament) => {
            if (!tournament.prize_pool) {
                return null;
            }
            return formatPrizePool(tournament.prize_pool);
        }
    }
]);

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

const getRowClass = (tournament: Tournament): string => {
    const baseClass = 'cursor-pointer transition-colors duration-200';
    if (isUserParticipant(tournament.id)) {
        return `${baseClass} bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 border-l-4 border-blue-300`;
    }
    return baseClass;
};

// Event delegation handler
const handleTableClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const row = target.closest('tr[data-tournament-id]');

    if (row) {
        const tournamentId = row.getAttribute('data-tournament-id');
        if (tournamentId) {
            router.visit(`/tournaments/${tournamentId}`);
        }
    }
};

const handleTableKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' || event.key === ' ') {
        const target = event.target as HTMLElement;
        const row = target.closest('tr[data-tournament-id]');

        if (row) {
            event.preventDefault();
            const tournamentId = row.getAttribute('data-tournament-id');
            if (tournamentId) {
                router.visit(`/tournaments/${tournamentId}`);
            }
        }
    }
};

onMounted(() => {
    setSeoMeta({
        title: t('Billiard Tournaments - Professional Pool Competitions'),
        description: t('Find and join professional billiard tournaments. Compete for prizes, earn ranking points, and advance your professional billiards career.'),
        keywords: ['billiard tournaments', 'pool tournaments', 'professional billiards', 'tournament prizes', 'billiard competition', 'pool championship'],
        ogType: 'website',
        jsonLd: {
            ...generateBreadcrumbJsonLd([
                {name: t('Home'), url: window.location.origin},
                {name: t('Tournaments'), url: `${window.location.origin}/tournaments`}
            ]),
            "@context": "https://schema.org",
            "@type": "SportsActivityLocation",
            "name": t('WinnerBreak Billiard Tournaments'),
            "description": t('Professional billiard tournaments with prize pools'),
            "sport": "Billiards"
        }
    });

    fetchTournaments();

    // Add event delegation to the table container
    const tableContainer = document.querySelector('[data-tournament-table]');
    if (tableContainer) {
        tableContainer.addEventListener('click', handleTableClick);
        tableContainer.addEventListener('keydown', handleTableKeydown);
    }
});

onUnmounted(() => {
    // Clean up event listeners
    const tableContainer = document.querySelector('[data-tournament-table]');
    if (tableContainer) {
        tableContainer.removeEventListener('click', handleTableClick);
        tableContainer.removeEventListener('keydown', handleTableKeydown);
    }
});
</script>

<template>
    <Head :title="t('Billiard Tournaments - Professional Pool Competitions')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <header class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ t('Tournaments') }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ t('Discover and follow billiard tournaments') }}
                        <span v-if="isAuthenticated && userParticipations.length > 0"
                              class="inline-flex items-center ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                            <TrophyIcon class="w-3 h-3 mr-1" aria-hidden="true"/>
                            {{ userParticipations.length }} {{ t('participations') }}
                        </span>
                    </p>
                </div>

                <!-- Only show create button to authenticated admins -->
                <Link v-if="isAuthenticated && isAdmin" href="/admin/tournaments/create"
                      aria-label="Create new billiard tournament">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4" aria-hidden="true"/>
                        {{ t('Create Tournament') }}
                    </Button>
                </Link>
            </header>

            <!-- Filters -->
            <nav class="mb-6 flex flex-wrap gap-2" role="navigation" aria-label="Tournament status filter">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    :class="[
                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                        selectedStatus === option.value
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                    ]"
                    :aria-pressed="selectedStatus === option.value"
                    @click="selectedStatus = option.value"
                >
                    {{ option.label }}
                </button>
            </nav>

            <main>
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrophyIcon class="h-5 w-5" aria-hidden="true"/>
                            {{ t('Tournament Directory') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div data-tournament-table>
                            <DataTable
                                :columns="columns"
                                :compact-mode="true"
                                :data="filteredTournaments"
                                :empty-message="selectedStatus === 'all' ? t('No tournaments have been created yet.') : t('No :status tournaments.', {status: selectedStatus})"
                                :loading="isLoading"
                                :row-class="getRowClass"
                                :row-attributes="(tournament) => ({
                                    'data-tournament-id': tournament.id?.toString(),
                                    'role': 'button',
                                    'tabindex': '0',
                                    'aria-label': `View ${tournament.name} tournament details`
                                })"
                            >
                                <!-- Custom cell renderers -->
                                <template #cell-name="{ value }">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="font-medium">{{ value.name }}</p>
                                            <p v-if="value.organizer" class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ value.organizer }}
                                            </p>
                                        </div>
                                        <div v-if="value.isParticipant" class="ml-2">
                                            <CrownIcon class="h-4 w-4 text-yellow-500" aria-hidden="true"
                                                       :aria-label="t('You participated in this tournament')"/>
                                        </div>
                                    </div>
                                </template>

                                <template #cell-participation="{ value }">
                                    <span v-if="value"
                                          :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', value.badgeClass]">
                                        {{ value.badgeText }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </template>

                                <template #cell-game="{ value }">
                                    <div v-if="value"
                                         class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <TrophyIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true"/>
                                        {{ value }}
                                    </div>
                                    <div v-else class="text-sm text-gray-400">{{ t('N/A') }}</div>
                                </template>

                                <template #cell-status="{ value }">
                                    <span
                                        :class="[
                                            'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                            getStatusBadgeClass(value.status)
                                        ]"
                                    >
                                        {{ value.status_display }}
                                    </span>
                                </template>

                                <template #cell-date="{ value }">
                                    <div v-if="value"
                                         class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <CalendarIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                                        {{ value }}
                                    </div>
                                    <div v-else class="text-sm text-gray-400">{{ t('N/A') }}</div>
                                </template>

                                <template #cell-location="{ value }">
                                    <div v-if="value && value.hasLocation"
                                         class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <MapPinIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                                        <div>
                                            <div>{{ value.city.name }}</div>
                                            <div class="text-xs">{{ value.city.country.name }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-400">{{ t('N/A') }}</div>
                                </template>

                                <template #cell-players="{ value }">
                                    <div v-if="value"
                                         class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true"/>
                                        <div>
                                            {{ value.count }}
                                            <span v-if="value.hasMax">
                                                / {{ value.max }}
                                            </span>
                                            <div class="text-xs text-gray-500">
                                                {{ value.count !== 1 ? t('players') : t('player') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-400">{{ t('N/A') }}</div>
                                </template>

                                <template #cell-prize="{ value }">
                                    <span v-if="value" class="text-green-600 dark:text-green-400 font-medium">
                                        {{ value }}
                                    </span>
                                    <span v-else class="text-gray-400">{{ t('N/A') }}</span>
                                </template>
                            </DataTable>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </div>
    </div>
</template>
