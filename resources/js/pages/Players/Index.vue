// resources/js/Pages/Players/Index.vue
<script lang="ts" setup>
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import UserAvatar from '@/Components/Core/UserAvatar.vue';
import {useAuth} from '@/composables/useAuth';
import {useLocale} from '@/composables/useLocale';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {City, Club, Country} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    ChevronDownIcon,
    ChevronUpIcon,
    FilterIcon,
    MapPinIcon,
    SearchIcon,
    TrophyIcon,
    UsersIcon,
} from 'lucide-vue-next';
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';

interface Player {
    id: number;
    slug: string;
    firstname: string;
    lastname: string;
    full_name: string;
    email: string;
    phone: string;
    sex: string;
    picture?: string;
    tournament_picture?: string;
    avatar?: string;
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
    };
    stats: {
        tournaments_count: number;
        tournaments_won: number;
        league_matches_count: number;
        league_matches_won: number;
        official_rating_points: number | null;
        official_rating_position: number | null;
    };
}

interface AggregatedStats {
    total_players: number;
    tournament_winners: number;
    top_10_rated: number;
    players_with_city: number;
    players_with_club: number;
    unique_cities: number;
    unique_clubs: number;
}

defineOptions({layout: AuthenticatedLayout});

const {isAuthenticated, user} = useAuth();
const {t} = useLocale();
const {setSeoMeta, generateBreadcrumbJsonLd, getAlternateLanguageUrls} = useSeo();

// State
const players = ref<Player[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const totalPages = ref(1);
const total = ref(0);
const currentPage = ref(1);
const aggregatedStats = ref<AggregatedStats | null>(null);

// Filters
const filters = ref({
    name: '',
    country_id: null as number | null,
    city_id: null as number | null,
    club_id: null as number | null,
    per_page: 50
});

// Filter options
const countries = ref<Country[]>([]);
const cities = ref<City[]>([]);
const clubs = ref<Club[]>([]);
const showFilters = ref(false);

// Simple debounce implementation
let searchTimeout: number | null = null;
const debouncedSearch = (value: string | number | undefined) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = window.setTimeout(() => {
        filters.value.name = value?.toString() || '';
        currentPage.value = 1;
        fetchPlayers();
        fetchAggregatedStats();
    }, 300);
};

// Table columns
const columns = computed(() => [
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (player: Player) => ({
            name: player.full_name,
            isCurrentUser: isAuthenticated.value && user.value?.id === player.id,
            player: player
        })
    },
    {
        key: 'location',
        label: t('Location'),
        hideOnMobile: true,
        render: (player: Player) => ({
            city: player.home_city?.name,
            country: player.home_city?.country?.name
        })
    },
    {
        key: 'club',
        label: t('Club'),
        hideOnTablet: true,
        render: (player: Player) => player.home_club?.name || t('No club')
    },
    {
        key: 'tournaments',
        label: t('T'),
        align: 'center' as const,
        render: (player: Player) => ({
            total: player.stats.tournaments_count,
            won: player.stats.tournaments_won
        })
    },
    {
        key: 'matches',
        label: t('M'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: Player) => ({
            total: player.stats.league_matches_count,
            won: player.stats.league_matches_won,
            winRate: player.stats.league_matches_count > 0
                ? Math.round((player.stats.league_matches_won / player.stats.league_matches_count) * 100)
                : 0
        })
    },
    {
        key: 'rating',
        label: t('Rating'),
        align: 'center' as const,
        render: (player: Player) => ({
            points: player.stats.official_rating_points,
            position: player.stats.official_rating_position
        })
    }
]);

// Fetch data
const fetchAggregatedStats = async () => {
    try {
        const params = new URLSearchParams();

        if (filters.value.name) params.append('name', filters.value.name);
        if (filters.value.country_id) params.append('country_id', filters.value.country_id.toString());
        if (filters.value.city_id) params.append('city_id', filters.value.city_id.toString());
        if (filters.value.club_id) params.append('club_id', filters.value.club_id.toString());

        const response = await apiClient<AggregatedStats>(`/api/players/stats?${params.toString()}`);
        aggregatedStats.value = response;
    } catch (err: any) {
        console.error('Failed to load aggregated stats:', err);
    }
};

const fetchPlayers = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        params.append('page', currentPage.value.toString());
        params.append('per_page', filters.value.per_page.toString());

        if (filters.value.name) params.append('name', filters.value.name);
        if (filters.value.country_id) params.append('country_id', filters.value.country_id.toString());
        if (filters.value.city_id) params.append('city_id', filters.value.city_id.toString());
        if (filters.value.club_id) params.append('club_id', filters.value.club_id.toString());

        const response = await apiClient<any>(`/api/players?${params.toString()}`);
        players.value = response.data;
        totalPages.value = response.meta.last_page;
        total.value = response.meta.total;
    } catch (err: any) {
        error.value = err.message || t('Failed to load players');
    } finally {
        isLoading.value = false;
    }
};

const fetchCountries = async () => {
    try {
        const citiesData = await apiClient<City[]>('/api/cities');
        const uniqueCountries = new Map<number, Country>();

        citiesData.forEach(city => {
            if (city.country && !uniqueCountries.has(city.country.id)) {
                uniqueCountries.set(city.country.id, city.country);
            }
        });

        countries.value = Array.from(uniqueCountries.values()).sort((a, b) =>
            a.name.localeCompare(b.name)
        );
    } catch (err) {
        console.error('Failed to load countries:', err);
    }
};

const fetchCities = async () => {
    try {
        cities.value = await apiClient<City[]>('/api/cities');
    } catch (err) {
        console.error('Failed to load cities:', err);
    }
};

const fetchClubs = async () => {
    try {
        const params = filters.value.city_id ? `?city_id=${filters.value.city_id}` : '';
        clubs.value = await apiClient<Club[]>(`/api/clubs${params}`);
    } catch (err) {
        console.error('Failed to load clubs:', err);
    }
};

// Reset filters
const resetFilters = () => {
    filters.value = {
        name: '',
        country_id: null,
        city_id: null,
        club_id: null,
        per_page: 50
    };
    currentPage.value = 1;
    fetchPlayers();
    fetchAggregatedStats();
};

// Get badge class for stats
const getStatsBadgeClass = (value: number, type: 'won' | 'winRate'): string => {
    if (type === 'won') {
        if (value >= 10) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        if (value >= 5) return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
        if (value >= 1) return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    } else if (type === 'winRate') {
        if (value >= 70) return 'text-green-600 dark:text-green-400';
        if (value >= 50) return 'text-blue-600 dark:text-blue-400';
        if (value >= 30) return 'text-orange-600 dark:text-orange-400';
        return 'text-red-600 dark:text-red-400';
    }
    return '';
};

const getRatingBadgeClass = (position: number | null): string => {
    if (!position) return '';
    if (position <= 3) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
    if (position <= 10) return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    if (position <= 20) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const getRowClass = (player: Player): string => {
    const baseClass = 'cursor-pointer transition-colors duration-200';
    if (isAuthenticated.value && user.value?.id === player.id) {
        return `${baseClass} bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30`;
    }
    return baseClass;
};

// Event handlers
const handleTableClick = (event: Event) => {
    const target = event.target as HTMLElement;
    const row = target.closest('tr[data-player-slug]');

    if (row) {
        const playerId = row.getAttribute('data-player-slug');
        if (playerId) {
            router.visit(`/players/${playerId}`);
        }
    }
};

const handleTableKeydown = (event: Event) => {
    const keyboardEvent = event as KeyboardEvent;
    if (keyboardEvent.key === 'Enter' || keyboardEvent.key === ' ') {
        const target = event.target as HTMLElement;
        const row = target.closest('tr[data-player-slug]');

        if (row) {
            event.preventDefault();
            const playerId = row.getAttribute('data-player-slug');
            if (playerId) {
                router.visit(`/players/${playerId}`);
            }
        }
    }
};

// Watchers
watch(() => filters.value.country_id, (newCountryId) => {
    if (newCountryId) {
        const filteredCities = cities.value.filter(city =>
            city.country?.id === newCountryId
        );
        if (filters.value.city_id && !filteredCities.some(city => city.id === filters.value.city_id)) {
            filters.value.city_id = null;
            filters.value.club_id = null;
        }
    }
});

watch(() => filters.value.city_id, (newCityId) => {
    if (newCityId) {
        fetchClubs();
    } else {
        filters.value.club_id = null;
        clubs.value = [];
    }
});

watch([
    () => filters.value.country_id,
    () => filters.value.city_id,
    () => filters.value.club_id
], () => {
    currentPage.value = 1;
    fetchPlayers();
    fetchAggregatedStats();
});

// Lifecycle
onMounted(() => {
    const currentPath = window.location.pathname;

    setSeoMeta({
        title: t('Professional Billiard Players Directory - Find Players & Stats | WinnerBreak'),
        description: t('Browse professional billiard players directory. Search players by location, club, skill level. View detailed statistics, tournament wins, league performance, official ratings. Connect with players from Ukraine and worldwide. Find practice partners and competitors.'),
        keywords: [
            'billiard players directory', 'каталог игроков в бильярд',
            'pool players database', 'база данных игроков в пул',
            'player statistics', 'статистика игроков',
            'tournament winners', 'победители турниров',
            'player rankings', 'рейтинги игроков',
            'billiard professionals', 'профессионалы бильярда',
            'find billiard players', 'найти игроков в бильярд',
            'player profiles', 'профили игроков',
            'skill level search', 'поиск по уровню мастерства',
            'club members', 'члены клуба',
            'Ukraine billiard players', 'украинские игроки в бильярд',
            'Lviv pool players', 'львовские игроки в пул',
            'player achievements', 'достижения игроков',
            'match statistics', 'статистика матчей',
            'win rates', 'процент побед',
            'practice partners', 'партнеры для тренировок',
            'WinnerBreak players', 'игроки ВиннерБрейк'
        ],
        ogType: 'website',
        ogImage: `${window.location.origin}/images/og/Players.png`,
        canonicalUrl: `${window.location.origin}${currentPath}`,
        robots: 'index, follow',
        alternateLanguages: getAlternateLanguageUrls(currentPath),
        additionalMeta: [
            {name: 'total-players', content: aggregatedStats.value?.total_players?.toString() || '0'},
            {property: 'directory:type', content: 'sports_players'},
            {property: 'sport:name', content: 'Billiards'}
        ],
        jsonLd: {
            "@context": "https://schema.org",
            "@graph": [
                generateBreadcrumbJsonLd([
                    {name: t('Home'), url: window.location.origin},
                    {name: t('Players'), url: `${window.location.origin}/players`}
                ]),
                {
                    "@type": "CollectionPage",
                    "name": t('Billiard Players Directory'),
                    "description": t('Comprehensive directory of professional billiard players'),
                    "url": `${window.location.origin}/players`,
                    "numberOfItems": aggregatedStats.value?.total_players || 0
                },
                {
                    "@type": "Dataset",
                    "name": "WinnerBreak Players Database",
                    "description": "Database of professional and amateur billiard players with statistics",
                    "license": "https://creativecommons.org/licenses/by-sa/4.0/",
                    "creator": {
                        "@type": "Organization",
                        "name": "WinnerBreak"
                    },
                    "distribution": {
                        "@type": "DataDownload",
                        "encodingFormat": "application/json",
                        "contentUrl": `${window.location.origin}/api/players`
                    },
                    "includedInDataCatalog": {
                        "@type": "DataCatalog",
                        "name": "WinnerBreak Sports Data"
                    }
                },
                {
                    "@type": "ItemList",
                    "itemListElement": players.value.slice(0, 10).map((player, index) => ({
                        "@type": "ListItem",
                        "position": index + 1,
                        "item": {
                            "@type": "Person",
                            "name": player.full_name,
                            "url": `${window.location.origin}/players/${player.slug}`,
                            "affiliation": player.home_club ? {
                                "@type": "Organization",
                                "name": player.home_club.name
                            } : undefined,
                            "homeLocation": player.home_city ? {
                                "@type": "Place",
                                "name": player.home_city.name
                            } : undefined
                        }
                    }))
                }
            ]
        }
    });

    fetchAggregatedStats();
    fetchPlayers();
    fetchCountries();
    fetchCities();

    // Add event listeners
    const tableContainer = document.querySelector('[data-players-table]');
    if (tableContainer) {
        tableContainer.addEventListener('click', handleTableClick);
        tableContainer.addEventListener('keydown', handleTableKeydown);
    }
});

onUnmounted(() => {
    // Clean up event listeners
    const tableContainer = document.querySelector('[data-players-table]');
    if (tableContainer) {
        tableContainer.removeEventListener('click', handleTableClick);
        tableContainer.removeEventListener('keydown', handleTableKeydown);
    }
});
</script>

<template>
    <Head :title="t('Billiard Players Directory - Find Players & Statistics')"/>

    <div class="py-4 sm:py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header - Compact -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ t('Players Directory') }}
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ t('Find and explore billiard players statistics') }}
                    </p>
                </div>

                <Button
                    :variant="showFilters ? 'secondary' : 'outline'"
                    size="sm"
                    @click="showFilters = !showFilters"
                >
                    <FilterIcon class="mr-1.5 h-3.5 w-3.5"/>
                    <span class="hidden sm:inline">{{ t('Filters') }}</span>
                    <ChevronDownIcon v-if="!showFilters" class="ml-1 h-3.5 w-3.5"/>
                    <ChevronUpIcon v-else class="ml-1 h-3.5 w-3.5"/>
                </Button>
            </div>

            <!-- Stats Cards - Compact -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mb-4">
                <Card>
                    <CardContent class="pt-3 pb-3">
                        <div class="text-center">
                            <div class="text-lg sm:text-xl font-bold text-blue-600 dark:text-blue-400">
                                {{ aggregatedStats?.total_players || total }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">{{
                                    t('Total Players')
                                }}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-3 pb-3">
                        <div class="text-center">
                            <div class="text-lg sm:text-xl font-bold text-green-600 dark:text-green-400">
                                {{ aggregatedStats?.tournament_winners || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">{{
                                    t('Winners')
                                }}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-3 pb-3">
                        <div class="text-center">
                            <div class="text-lg sm:text-xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ aggregatedStats?.top_10_rated || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">{{
                                    t('Top 10')
                                }}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="col-span-2 sm:col-span-1">
                    <CardContent class="pt-3 pb-3">
                        <div class="text-center">
                            <div class="text-lg sm:text-xl font-bold text-purple-600 dark:text-purple-400">
                                {{ aggregatedStats?.unique_clubs || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                {{ t('Clubs') }}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search and Filters - Compact -->
            <Card v-if="showFilters" class="mb-4">
                <CardHeader class="p-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FilterIcon class="h-4 w-4"/>
                        {{ t('Search & Filters') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <Label for="search" class="text-xs">{{ t('Search by name') }}</Label>
                            <div class="relative mt-1">
                                <SearchIcon class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"/>
                                <Input
                                    id="search"
                                    type="text"
                                    :placeholder="t('Enter player name...')"
                                    class="pl-8 h-8 text-sm"
                                    :model-value="filters.name"
                                    @update:model-value="debouncedSearch"
                                />
                            </div>
                        </div>

                        <!-- Country -->
                        <div>
                            <Label for="country" class="text-xs">{{ t('Country') }}</Label>
                            <Select v-model="filters.country_id" class="mt-1">
                                <SelectTrigger id="country" class="h-8 text-sm">
                                    <SelectValue :placeholder="t('All countries')"/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="''">{{ t('All countries') }}</SelectItem>
                                    <SelectItem
                                        v-for="country in countries"
                                        :key="country.id"
                                        :value="country.id"
                                    >
                                        {{ country.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- City -->
                        <div>
                            <Label for="city" class="text-xs">{{ t('City') }}</Label>
                            <Select
                                v-model="filters.city_id"
                                :disabled="!filters.country_id"
                                class="mt-1"
                            >
                                <SelectTrigger id="city" class="h-8 text-sm">
                                    <SelectValue :placeholder="t('All cities')"/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="''">{{ t('All cities') }}</SelectItem>
                                    <SelectItem
                                        v-for="city in cities.filter(c => !filters.country_id || c.country?.id === filters.country_id)"
                                        :key="city.id"
                                        :value="city.id"
                                    >
                                        {{ city.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Club -->
                        <div>
                            <Label for="club" class="text-xs">{{ t('Club') }}</Label>
                            <Select
                                v-model="filters.club_id"
                                :disabled="!filters.city_id"
                                class="mt-1"
                            >
                                <SelectTrigger id="club" class="h-8 text-sm">
                                    <SelectValue :placeholder="t('All clubs')"/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="''">{{ t('All clubs') }}</SelectItem>
                                    <SelectItem
                                        v-for="club in clubs"
                                        :key="club.id"
                                        :value="club.id"
                                    >
                                        {{ club.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Results per page -->
                        <div>
                            <Label for="per_page" class="text-xs">{{ t('Per page') }}</Label>
                            <Select v-model="filters.per_page" class="mt-1">
                                <SelectTrigger id="per_page" class="h-8 text-sm">
                                    <SelectValue/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="25">25</SelectItem>
                                    <SelectItem :value="50">50</SelectItem>
                                    <SelectItem :value="100">100</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end">
                        <Button variant="outline" size="sm" @click="resetFilters">
                            {{ t('Reset filters') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Players Table - Compact -->
            <Card class="shadow-sm">
                <CardHeader class="p-4">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <UsersIcon class="h-4 w-4"/>
                        {{ t('Players') }} ({{ aggregatedStats?.total_players || total }})
                    </CardTitle>
                    <CardDescription class="text-xs">
                        {{ t('Showing :count players', {count: players.length}) }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div data-players-table>
                        <DataTable
                            :columns="columns"
                            :data="players"
                            :empty-message="t('No players found. Try adjusting your filters.')"
                            :loading="isLoading"
                            :mobile-card-mode="true"
                            :row-class="getRowClass"
                            :row-attributes="(player) => ({
                                'data-player-slug': player.slug?.toString(),
                                'role': 'button',
                                'tabindex': '0',
                                'aria-label': `View ${player.full_name} profile`
                            })"
                            :row-height="'compact'"
                        >
                            <!-- Custom cell renderers -->
                            <template #cell-player="{ value }">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <UserAvatar
                                            :user="value.player"
                                            size="sm"
                                            priority="picture"
                                        />
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ value.name }}
                                            <span v-if="value.isCurrentUser"
                                                  class="ml-1 text-xs text-blue-600 dark:text-blue-400">
                                                ({{ t('You') }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template #cell-location="{ value }">
                                <div v-if="value.city || value.country"
                                     class="text-sm text-gray-900 dark:text-gray-100">
                                    <div v-if="value.city" class="truncate">{{ value.city }}</div>
                                    <div v-if="value.country" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ value.country }}
                                    </div>
                                </div>
                                <span v-else class="text-gray-400 text-sm">{{ t('N/A') }}</span>
                            </template>

                            <template #cell-club="{ value }">
                                <span v-if="value" class="text-sm text-gray-900 dark:text-gray-100 truncate">
                                    {{ value }}
                                </span>
                                <span v-else class="text-sm text-gray-400">{{ t('No club') }}</span>
                            </template>

                            <template #cell-tournaments="{ value }">
                                <div class="text-center">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ value.total }}
                                    </div>
                                    <div v-if="value.won > 0" class="mt-0.5">
                                        <span
                                            :class="['inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium', getStatsBadgeClass(value.won, 'won')]">
                                            <TrophyIcon class="h-3 w-3 mr-0.5"/>
                                            {{ value.won }}
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <template #cell-matches="{ value }">
                                <div class="text-center">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ value.total }}
                                    </div>
                                    <div v-if="value.total > 0" class="text-xs mt-0.5">
                                        <span
                                            :class="['font-medium', getStatsBadgeClass(value.winRate, 'winRate')]">
                                            {{ value.winRate }}%
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <template #cell-rating="{ value }">
                                <div v-if="value.position" class="text-center">
                                    <span
                                        :class="['inline-flex px-1.5 py-0.5 text-xs font-medium rounded-full', getRatingBadgeClass(value.position)]">
                                        #{{ value.position }}
                                    </span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ value.points }}
                                    </div>
                                </div>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <!-- Mobile primary info -->
                            <template #mobile-primary="{ item }">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex-shrink-0 h-9 w-9">
                                                <UserAvatar
                                                    :user="item"
                                                    size="sm"
                                                    priority="picture"
                                                />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate">
                                                    {{ item.full_name }}
                                                    <span v-if="isAuthenticated && user?.id === item.id"
                                                          class="ml-1 text-xs text-blue-600 dark:text-blue-400">
                                                        ({{ t('You') }})
                                                    </span>
                                                </p>
                                                <p v-if="item.home_city"
                                                   class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                                    <MapPinIcon class="inline h-3 w-3 mr-0.5"/>
                                                    {{
                                                        item.home_city.name
                                                    }}{{
                                                        item.home_city.country ? `, ${item.home_city.country.name}` : ''
                                                    }}
                                                </p>
                                                <p v-if="item.home_club" class="text-xs text-gray-500 truncate">
                                                    {{ item.home_club.name }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Mobile actions -->
                            <template #mobile-actions="{ item }">
                                <div class="mt-2 pt-2 border-t dark:border-gray-700">
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span class="text-gray-500">{{ t('T') }}:</span>
                                            <span class="font-medium ml-1">{{ item.stats.tournaments_count }}</span>
                                            <span v-if="item.stats.tournaments_won > 0" class="text-green-600 ml-1">
                                                ({{ item.stats.tournaments_won }})
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">{{ t('M') }}:</span>
                                            <span class="font-medium ml-1">{{ item.stats.league_matches_count }}</span>
                                            <span v-if="item.stats.league_matches_count > 0" class="text-blue-600 ml-1">
                                                ({{
                                                    Math.round((item.stats.league_matches_won / item.stats.league_matches_count) * 100)
                                                }}%)
                                            </span>
                                        </div>
                                        <div v-if="item.stats.official_rating_position" class="col-span-2">
                                            <span class="text-gray-500">{{ t('Rating') }}:</span>
                                            <span class="font-medium ml-1">#{{
                                                    item.stats.official_rating_position
                                                }}</span>
                                            <span class="text-gray-500 ml-1">({{
                                                    item.stats.official_rating_points
                                                }})</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </DataTable>
                    </div>

                    <!-- Pagination - Compact -->
                    <div v-if="totalPages > 1"
                         class="flex items-center justify-between px-4 py-2.5 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="currentPage === 1 || isLoading"
                                @click="currentPage--; fetchPlayers()"
                            >
                                {{ t('Previous') }}
                            </Button>
                            <span class="text-xs text-gray-700 dark:text-gray-300">
                                {{ t('Page :current of :total', {current: currentPage, total: totalPages}) }}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="currentPage === totalPages || isLoading"
                                @click="currentPage++; fetchPlayers()"
                            >
                                {{ t('Next') }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
