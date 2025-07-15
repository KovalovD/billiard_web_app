<script lang="ts" setup>
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useLeagueStatus} from '@/composables/useLeagueStatus';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {ApiError, League} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {CalendarIcon, GamepadIcon, PlusIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {computed, onMounted, onUnmounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

const {t} = useLocale();
const {
    setSeoMeta, generateBreadcrumbJsonLd, getAlternateLanguageUrls,
    generateGameJsonLd
} = useSeo();

defineOptions({layout: AuthenticatedLayout});

const {isAdmin, isAuthenticated} = useAuth();
const leagues = useLeagues();
const {getLeagueStatus, getPlayersText} = useLeagueStatus();

const leaguesData = ref<League[]>([]);
const isLoading = ref(false);
const loadingError = ref<ApiError | null>(null);
// Sort leagues alphabetically
const sortedLeagues = computed(() => {
    if (!leaguesData.value) return [];
    return [...leaguesData.value].sort((a, b) => a.name.localeCompare(b.name));
});

// Define table columns (removed actions column)
const columns = computed(() => [
    {
        key: 'name',
        label: t('League'),
        align: 'left' as const,
        render: (league: League) => ({
            name: league.name || t('Unnamed League'),
            details: league.details,
        })
    },
    {
        key: 'game',
        label: t('Game'),
        hideOnMobile: true,
        render: (league: League) => league.game || t('N/A')
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (league: League) => getLeagueStatus(league)
    },
    {
        key: 'players',
        label: t('Players'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (league: League) => getPlayersText(league)
    },
    {
        key: 'matches',
        label: t('Matches'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (league: League) => league.matches_count || 0
    },
    {
        key: 'startDate',
        label: t('Start Date'),
        hideOnTablet: true,
        render: (league: League) => league.started_at
    }
]);

const fetchLeagues = async () => {
    isLoading.value = true;
    loadingError.value = null;

    try {
        const {data, execute} = leagues.fetchLeagues();
        await execute();
        leaguesData.value = data.value || [];
    } catch (err) {
        loadingError.value = err as ApiError;
    } finally {
        isLoading.value = false;
    }
};

const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) return t('N/A');
    return new Date(dateString).toLocaleDateString();
};

const getRowClass = (): string => {
    return 'cursor-pointer transition-colors duration-200';
};

// Event delegation handler
const handleTableClick = (event: Event) => {
    const target = event.target as HTMLElement;
    const row = target.closest('tr[data-league-slug]');

    if (row) {
        const leagueId = row.getAttribute('data-league-slug');
        if (leagueId) {
            router.visit(`/leagues/${leagueId}`);
        }
    }
};

const handleTableKeydown = (event: Event) => {
    const keyboardEvent = event as KeyboardEvent;
    if (keyboardEvent.key === 'Enter' || keyboardEvent.key === ' ') {
        const target = event.target as HTMLElement;
        const row = target.closest('tr[data-league-slug]');

        if (row) {
            event.preventDefault();
            const leagueId = row.getAttribute('data-league-slug');
            if (leagueId) {
                router.visit(`/leagues/${leagueId}`);
            }
        }
    }
};

onMounted(() => {
    const currentPath = window.location.pathname;

    setSeoMeta({
        title: t('Billiard Leagues Directory - Join Professional Pool Competitions | WinnerBreak'),
        description: t('Discover and join competitive billiard leagues across Ukraine and worldwide. Find 8-ball, 9-ball, and snooker leagues matching your skill level. Track standings, challenge players, improve your ELO rating, and compete for prizes in professional pool leagues.'),
        keywords: [
            'billiard leagues', 'бильярдные лиги', 'pool leagues directory', 'каталог лиг пула',
            'competitive billiards', 'соревновательный бильярд', 'league standings', 'турнирная таблица',
            'ELO rating system', 'система рейтинга ELO', '8-ball leagues', 'лиги восьмерки',
            '9-ball competitions', 'соревнования девятки', 'snooker leagues', 'снукерные лиги',
            'Ukraine billiards', 'украинский бильярд', 'Lviv pool leagues', 'львовские лиги пула',
            'online league registration', 'онлайн регистрация в лигу', 'amateur pool leagues', 'любительские лиги',
            'professional billiards', 'профессиональный бильярд', 'WinnerBreak leagues', 'лиги ВиннерБрейк'
        ],
        ogType: 'website',
        ogImage: '/images/leagues-preview.jpg',
        canonicalUrl: `${window.location.origin}${currentPath}`,
        robots: 'index, follow',
        alternateLanguages: getAlternateLanguageUrls(currentPath),
        additionalMeta: [
            {property: 'article:section', content: 'Sports'},
            {property: 'article:tag', content: 'Billiards'},
            {property: 'article:tag', content: 'Pool'},
            {property: 'article:tag', content: 'Leagues'}
        ],
        jsonLd: {
            "@context": "https://schema.org",
            "@graph": [
                generateBreadcrumbJsonLd([
                    {name: t('Home'), url: window.location.origin},
                    {name: t('Leagues'), url: `${window.location.origin}/leagues`}
                ]),
                {
                    "@type": "CollectionPage",
                    "name": t('Billiard Leagues Directory'),
                    "description": t('Comprehensive directory of competitive billiard leagues'),
                    "url": `${window.location.origin}/leagues`,
                    "isPartOf": {
                        "@type": "WebSite",
                        "name": "WinnerBreak"
                    }
                },
                {
                    "@type": "SportsActivityLocation",
                    "name": t('WinnerBreak Billiard Leagues'),
                    "description": t('Professional and amateur billiard leagues for all skill levels'),
                    "sport": "Billiards",
                    "address": {
                        "@type": "PostalAddress",
                        "addressCountry": "Multiple Countries"
                    },
                    "geo": {
                        "@type": "GeoCoordinates",
                        "latitude": "49.839683",
                        "longitude": "24.029717"
                    }
                },
                generateGameJsonLd({
                    name: "Billiard Leagues",
                    description: "Competitive pool and billiard leagues",
                    minPlayers: 2,
                    maxPlayers: 100
                })
            ]
        }
    });

    fetchLeagues();

    // Add event delegation to the table container
    const tableContainer = document.querySelector('[data-league-table]');
    if (tableContainer) {
        tableContainer.addEventListener('click', handleTableClick);
        tableContainer.addEventListener('keydown', handleTableKeydown);
    }
});

onUnmounted(() => {
    // Clean up event listeners
    const tableContainer = document.querySelector('[data-league-table]');
    if (tableContainer) {
        tableContainer.removeEventListener('click', handleTableClick);
        tableContainer.removeEventListener('keydown', handleTableKeydown);
    }
});
</script>

<template>
    <Head :title="t('Billiard Leagues - Join Competitive Pool Leagues')"/>
    <div class="py-6 sm:py-8 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <header class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                        {{ t('Leagues') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        {{ t('Discover and join competitive billiard leagues') }}
                    </p>
                </div>

                <!-- Only show create button to authenticated admins -->
                <Link v-if="isAuthenticated && isAdmin"
                      href="/admin/leagues/create"
                      aria-label="Create new billiard league"
                      class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                    <PlusIcon class="mr-2 h-4 w-4" aria-hidden="true"/>
                    {{ t('Create League') }}
                </Link>
            </header>


            <main>
                <Card class="shadow-lg">
                    <CardHeader class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700">
                        <CardTitle class="flex items-center gap-2">
                            <TrophyIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" aria-hidden="true"/>
                            {{ t('Leagues Directory') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <!-- Loading State -->
                        <div v-if="isLoading" class="flex justify-center py-12">
                            <div class="text-center">
                                <div
                                    class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
                                <p class="mt-2 text-gray-500">{{ t('Loading leagues...') }}</p>
                            </div>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="loadingError" class="p-6 text-center text-red-600">
                            {{ loadingError.message }}
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="sortedLeagues.length === 0" class="p-6 text-center text-gray-500">
                            {{ t('No leagues have been created yet.') }}
                        </div>

                        <!-- Mobile Cards View -->
                        <div v-else class="block lg:hidden space-y-4 p-4">
                            <div
                                v-for="league in sortedLeagues"
                                :key="league.id"
                                class="relative rounded-lg border p-4 cursor-pointer transition-colors bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                                @click="router.visit(`/leagues/${league.slug}`)"
                            >
                                <!-- League Header -->
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {{ league.name }}
                                        </h3>
                                        <p v-if="league.details"
                                           class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                            {{ league.details }}
                                        </p>
                                    </div>
                                    <span
                                        v-if="getLeagueStatus(league)"
                                        :class="[
                                            'inline-flex px-2 py-1 text-xs font-medium rounded-full flex-shrink-0',
                                            getLeagueStatus(league)?.class
                                        ]"
                                    >
                                        {{ getLeagueStatus(league)?.text }}
                                    </span>
                                </div>

                                <!-- League Info Grid -->
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <!-- Game & Players -->
                                    <div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-400 mb-1">
                                            <GamepadIcon class="h-4 w-4 mr-1 flex-shrink-0"/>
                                            <span class="truncate">{{ league.game || t('N/A') }}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                                            <UsersIcon class="h-4 w-4 mr-1 flex-shrink-0"/>
                                            <span class="truncate">{{ getPlayersText(league) }}</span>
                                        </div>
                                    </div>

                                    <!-- Matches & Date -->
                                    <div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-400 mb-1">
                                            <TrophyIcon class="h-4 w-4 mr-1 flex-shrink-0"/>
                                            <span class="truncate">{{ league.matches_count || 0 }} {{
                                                    t('Matches')
                                                }}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                                            <CalendarIcon class="h-4 w-4 mr-1 flex-shrink-0"/>
                                            <span class="truncate">{{ formatDate(league.started_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table View -->
                        <div class="hidden lg:block" data-league-table>
                            <DataTable
                                :columns="columns"
                                :compact-mode="true"
                                :data="sortedLeagues"
                                :empty-message="t('No leagues have been created yet.')"
                                :loading="isLoading"
                                :row-class="getRowClass"
                                :row-attributes="(league) => ({
                                    'data-league-slug': league.slug?.toString(),
                                    'role': 'button',
                                    'tabindex': '0',
                                    'aria-label': `View ${league.name} league details`
                                })"
                            >
                                <!-- Custom cell renderers -->
                                <template #cell-name="{ value }">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center shadow-md">
                                                <TrophyIcon class="h-4 w-4 text-white"
                                                            aria-hidden="true"/>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ value.name }}
                                            </div>
                                            <div v-if="value.details"
                                                 class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                {{ value.details }}
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #cell-game="{ value }">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <GamepadIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true"/>
                                        {{ value }}
                                    </div>
                                </template>

                                <template #cell-status="{ value }">
                                    <span
                                        v-if="value"
                                        :class="[
                                            'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                            value.class
                                        ]"
                                    >
                                        {{ value.text }}
                                    </span>
                                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">{{
                                            t('Unknown')
                                        }}</span>
                                </template>

                                <template #cell-players="{ value }">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <UsersIcon class="h-4 w-4 mr-2 text-gray-400" aria-hidden="true"/>
                                        {{ value }}
                                    </div>
                                </template>

                                <template #cell-matches="{ value }">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ value }} {{ t('Matches') }}
                                    </div>
                                </template>

                                <template #cell-startDate="{ value }">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <CalendarIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                                        <time :datetime="value">{{ formatDate(value) }}</time>
                                    </div>
                                </template>
                            </DataTable>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </div>
    </div>
</template>
