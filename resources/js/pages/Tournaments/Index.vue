// resources/js/Pages/Tournaments/Index.vue
<script lang="ts" setup>
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
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
const {setSeoMeta, generateBreadcrumbJsonLd, getAlternateLanguageUrls} = useSeo();

const tournaments = ref<Tournament[]>([]);
const userParticipations = ref<TournamentPlayer[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const selectedStatus = ref<string>('all');

const statusOptions = [
    {value: 'all', label: t('All')},
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

// Define table columns for desktop
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
        label: t('You'),
        align: 'center' as const,
        hideOnTablet: true,
        width: '80px',
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
        label: t('Prize'),
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
                return `🏆`;
            } else if (participation.position && participation.position <= 3) {
                return `#${participation.position}`;
            } else if (participation.position) {
                return `#${participation.position}`;
            }
            return t('✓');
        case 'applied':
            return t('...');
        case 'rejected':
            return t('✗');
        default:
            return t('?');
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
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
};

const formatDateRange = (startDate: string, endDate: string): string => {
    const start = formatDate(startDate);
    const end = formatDate(endDate);
    return start === end ? start : `${start}-${end}`;
};

const formatPrizePool = (amount: number): string => {
    if (amount <= 0) return t('N/A');
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }) + '₴';
};

const getRowClass = (tournament: Tournament): string => {
    const baseClass = 'cursor-pointer transition-colors duration-200';
    if (isUserParticipant(tournament.id)) {
        return `${baseClass} bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 border-l-4 border-blue-300`;
    }
    return baseClass;
};

// Navigate to tournament details
const navigateToTournament = (tournament: Tournament) => {
    router.visit(`/tournaments/${tournament.slug}`);
};

// Event delegation handler
const handleTableClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const row = target.closest('tr[data-tournament-slug]');

    if (row) {
        const tournamentSlug = row.getAttribute('data-tournament-slug');
        if (tournamentSlug) {
            router.visit(`/tournaments/${tournamentSlug}`);
        }
    }
};

const handleTableKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' || event.key === ' ') {
        const target = event.target as HTMLElement;
        const row = target.closest('tr[data-tournament-slug]');

        if (row) {
            event.preventDefault();
            const tournamentSlug = row.getAttribute('data-tournament-slug');
            if (tournamentSlug) {
                router.visit(`/tournaments/${tournamentSlug}`);
            }
        }
    }
};

onMounted(() => {
    const currentPath = window.location.pathname;

    setSeoMeta({
        title: t('Billiard Tournaments 2025 - Professional Pool Championships | WinnerBreak'),
        description: t('Find and join professional billiard tournaments across Ukraine and worldwide. Compete for cash prizes, earn ranking points, qualify for championships. Register for 8-ball, 9-ball, and snooker tournaments. Entry fees from 100₴. All skill levels welcome.'),
        keywords: [
            'billiard tournaments 2025', 'бильярдные турниры 2025',
            'pool tournaments Ukraine', 'турниры по пулу Украина',
            'professional billiards championships', 'профессиональные чемпионаты по бильярду',
            'tournament prizes', 'призы турнира',
            'billiard competition registration', 'регистрация на соревнования по бильярду',
            'pool championship', 'чемпионат по пулу',
            '8-ball tournaments', 'турниры по восьмерке',
            '9-ball competitions', 'соревнования по девятке',
            'snooker championship', 'чемпионат по снукеру',
            'tournament entry fees', 'вступительные взносы',
            'cash prizes billiards', 'денежные призы бильярд',
            'ranking points', 'рейтинговые очки',
            'qualifier tournaments', 'квалификационные турниры',
            'WinnerBreak tournaments', 'турниры ВиннерБрейк',
            'Lviv billiard tournaments', 'львовские бильярдные турниры'
        ],
        ogType: 'website',
        ogImage: '/images/tournaments-preview.jpg',
        canonicalUrl: `${window.location.origin}${currentPath}`,
        robots: 'index, follow',
        alternateLanguages: getAlternateLanguageUrls(currentPath),
        additionalMeta: [
            {name: 'geo.region', content: 'UA'},
            {name: 'geo.placename', content: 'Ukraine'},
            {name: 'geo.position', content: '49.839683;24.029717'},
            {name: 'ICBM', content: '49.839683, 24.029717'}
        ],
        jsonLd: {
            "@context": "https://schema.org",
            "@graph": [
                generateBreadcrumbJsonLd([
                    {name: t('Home'), url: window.location.origin},
                    {name: t('Tournaments'), url: `${window.location.origin}/tournaments`}
                ]),
                {
                    "@type": "CollectionPage",
                    "name": t('Billiard Tournaments Directory'),
                    "description": t('Professional billiard tournaments with prize pools'),
                    "url": `${window.location.origin}/tournaments`
                },
                {
                    "@type": "ItemList",
                    "itemListElement": tournaments.value.slice(0, 10).map((tournament, index) => ({
                        "@type": "ListItem",
                        "position": index + 1,
                        "item": {
                            "@type": "SportsEvent",
                            "name": tournament.name,
                            "startDate": tournament.start_date,
                            "endDate": tournament.end_date,
                            "location": tournament.city ? {
                                "@type": "Place",
                                "name": tournament.city.name,
                                "address": {
                                    "@type": "PostalAddress",
                                    "addressLocality": tournament.city.name,
                                    "addressCountry": tournament.city.country?.name
                                }
                            } : undefined,
                            "offers": tournament.entry_fee ? {
                                "@type": "Offer",
                                "price": tournament.entry_fee,
                                "priceCurrency": "UAH"
                            } : undefined
                        }
                    }))
                }
            ]
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

    <div class="py-4 sm:py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header - Compact -->
            <header class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        {{ t('Tournaments') }}
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ t('Discover and follow billiard tournaments') }}
                        <span v-if="isAuthenticated && userParticipations.length > 0"
                              class="inline-flex items-center ml-2 px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                            <TrophyIcon class="w-3 h-3 mr-0.5"/>
                            {{ userParticipations.length }}
                        </span>
                    </p>
                </div>

                <!-- Only show create button to authenticated admins -->
                <Link v-if="isAuthenticated && isAdmin"
                      href="/admin/tournaments/create"
                      aria-label="Create new rating system"
                      class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                    <PlusIcon class="mr-1.5 h-3.5 w-3.5" aria-hidden="true"/>
                    {{ t('Create Tournament') }}
                </Link>
            </header>

            <!-- Filters - Compact -->
            <nav class="mb-4 flex flex-wrap gap-2">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    :class="[
                        'px-2.5 py-1.5 rounded-md text-sm font-medium transition-colors',
                        selectedStatus === option.value
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                    ]"
                    @click="selectedStatus = option.value"
                >
                    {{ option.label }}
                </button>
            </nav>

            <main>
                <Card class="shadow-sm">
                    <CardHeader class="bg-gray-50 dark:bg-gray-700/50 p-4">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <TrophyIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400"/>
                            {{ t('Tournament Directory') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <!-- Loading State -->
                        <div v-if="isLoading" class="flex justify-center py-8">
                            <div class="text-center">
                                <div
                                    class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
                                <p class="mt-2 text-sm text-gray-500">{{ t('Loading tournaments...') }}</p>
                            </div>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="error" class="p-4 text-center text-sm text-red-600">
                            {{ error }}
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="filteredTournaments.length === 0" class="p-8 text-center text-sm text-gray-500">
                            {{
                                selectedStatus === 'all' ? t('No tournaments have been created yet.') : t('No :status tournaments.', {status: selectedStatus})
                            }}
                        </div>

                        <!-- Mobile Cards View - Compact -->
                        <div v-else class="block lg:hidden space-y-3 p-3">
                            <div
                                v-for="tournament in filteredTournaments"
                                :key="tournament.id"
                                :class="[
                                    'relative rounded-md border p-3 cursor-pointer transition-colors',
                                    isUserParticipant(tournament.id)
                                        ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800'
                                        : 'bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700'
                                ]"
                                @click="navigateToTournament(tournament)"
                            >
                                <!-- Tournament Header -->
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 break-words line-clamp-2">
                                            {{ tournament.name }}
                                            <CrownIcon v-if="isUserParticipant(tournament.id)"
                                                       class="inline h-3 w-3 text-yellow-500 ml-0.5"/>
                                        </h3>
                                        <p v-if="tournament.organizer"
                                           class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                            {{ tournament.organizer }}
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex px-1.5 py-0.5 text-xs font-medium rounded-full flex-shrink-0',
                                            getStatusBadgeClass(tournament.status)
                                        ]"
                                    >
                                        {{ tournament.status_display }}
                                    </span>
                                </div>

                                <!-- Tournament Info Grid - Compact -->
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <!-- Game & Date -->
                                    <div>
                                        <div v-if="tournament.game"
                                             class="flex items-center text-gray-600 dark:text-gray-400">
                                            <TrophyIcon class="h-3 w-3 mr-0.5 flex-shrink-0"/>
                                            <span class="truncate">{{ tournament.game.name }}</span>
                                        </div>
                                        <div v-if="tournament.start_date"
                                             class="flex items-center text-gray-600 dark:text-gray-400 mt-0.5">
                                            <CalendarIcon class="h-3 w-3 mr-0.5 flex-shrink-0"/>
                                            <span class="truncate">{{
                                                    formatDateRange(tournament.start_date, tournament.end_date)
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Location & Players -->
                                    <div>
                                        <div v-if="tournament.city"
                                             class="flex items-center text-gray-600 dark:text-gray-400">
                                            <MapPinIcon class="h-3 w-3 mr-0.5 flex-shrink-0"/>
                                            <span class="truncate">{{ tournament.city.name }}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-400 mt-0.5">
                                            <UsersIcon class="h-3 w-3 mr-0.5 flex-shrink-0"/>
                                            <span>
                                                {{ tournament.players_count || 0 }}
                                                <span v-if="tournament.max_participants">/{{
                                                        tournament.max_participants
                                                    }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Prize Pool -->
                                <div v-if="tournament.prize_pool > 0"
                                     class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{
                                            t('Prize')
                                        }}</span>
                                    <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                        {{ formatPrizePool(tournament.prize_pool) }}
                                    </div>
                                </div>

                                <!-- User Participation Badge -->
                                <div v-if="isUserParticipant(tournament.id)"
                                     class="absolute top-2 right-12">
                                    <span
                                        :class="[
                                            'inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full',
                                            getParticipationBadgeClass(getUserParticipation(tournament.id)!)
                                        ]"
                                    >
                                        {{ getParticipationBadgeText(getUserParticipation(tournament.id)!) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table View - Compact -->
                        <div class="hidden lg:block" data-tournament-table>
                            <DataTable
                                :columns="columns"
                                :compact-mode="true"
                                :data="filteredTournaments"
                                :empty-message="selectedStatus === 'all' ? t('No tournaments have been created yet.') : t('No :status tournaments.', {status: selectedStatus})"
                                :loading="isLoading"
                                :row-class="getRowClass"
                                :row-attributes="(tournament) => ({
                                    'data-tournament-slug': tournament.slug?.toString(),
                                    'role': 'button',
                                    'tabindex': '0',
                                    'aria-label': `View ${tournament.name} tournament details`
                                })"
                                :row-height="'compact'"
                            >
                                <!-- Custom cell renderers -->
                                <template #cell-name="{ value }">
                                    <div class="flex items-center gap-2">
                                        <div class="min-w-0 flex-1 max-w-100">
                                            <p class="text-sm font-medium break-words line-clamp-2">{{ value.name }}</p>
                                            <p v-if="value.organizer"
                                               class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                                {{ value.organizer }}
                                            </p>
                                        </div>
                                        <div v-if="value.isParticipant" class="flex-shrink-0">
                                            <CrownIcon class="h-3.5 w-3.5 text-yellow-500"/>
                                        </div>
                                    </div>
                                </template>

                                <template #cell-participation="{ value }">
                                    <span v-if="value"
                                          :class="['inline-flex rounded-full px-1.5 py-0.5 text-xs font-semibold', value.badgeClass]">
                                        {{ value.badgeText }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </template>

                                <template #cell-game="{ value }">
                                    <div v-if="value"
                                         class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <TrophyIcon class="h-3.5 w-3.5 mr-1.5 text-gray-400 flex-shrink-0"/>
                                        <span class="truncate">{{ value }}</span>
                                    </div>
                                    <div v-else class="text-xs text-gray-400">{{ t('N/A') }}</div>
                                </template>

                                <template #cell-status="{ value }">
                                    <span
                                        :class="[
                                            'inline-flex px-1.5 py-0.5 text-xs font-medium rounded-full',
                                            getStatusBadgeClass(value.status)
                                        ]"
                                    >
                                        {{ value.status_display }}
                                    </span>
                                </template>

                                <template #cell-date="{ value }">
                                    <div v-if="value"
                                         class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <CalendarIcon class="h-3.5 w-3.5 mr-1.5 flex-shrink-0"/>
                                        <span class="truncate">{{ value }}</span>
                                    </div>
                                    <div v-else class="text-xs text-gray-400">{{ t('N/A') }}</div>
                                </template>

                                <template #cell-location="{ value }">
                                    <div v-if="value && value.hasLocation"
                                         class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <MapPinIcon class="h-3.5 w-3.5 mr-1.5 flex-shrink-0"/>
                                        <div class="min-w-0">
                                            <div class="truncate">{{ value.city.name }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400">{{ t('N/A') }}</div>
                                </template>

                                <template #cell-players="{ value }">
                                    <div v-if="value"
                                         class="text-sm text-gray-900 dark:text-gray-100 text-center">
                                        {{ value.count }}<span v-if="value.hasMax" class="text-gray-500">/{{ value.max }}</span>
                                    </div>
                                    <div v-else class="text-xs text-gray-400">{{ t('N/A') }}</div>
                                </template>

                                <template #cell-prize="{ value }">
                                    <span v-if="value" class="text-sm text-green-600 dark:text-green-400 font-medium truncate">
                                        {{ value }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">{{ t('N/A') }}</span>
                                </template>
                            </DataTable>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </div>
    </div>
</template>
