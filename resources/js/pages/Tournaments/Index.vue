<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import {computed, onMounted, ref} from 'vue';
import {useTournamentStore} from '@/stores/useTournamentStore';
import {useAuth} from '@/composables/useAuth';
import {useLocale} from '@/composables/useLocale';
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {
    CalendarIcon,
    DollarSignIcon,
    FilterIcon,
    MapPinIcon,
    PlusIcon,
    SearchIcon,
    TrophyIcon,
    UsersIcon
} from 'lucide-vue-next';

defineOptions({layout: AuthenticatedLayout});

// Composables
const {t} = useLocale();
const {isAdmin} = useAuth();
const tournamentStore = useTournamentStore();

// Reactive state
const searchQuery = ref('');
const selectedStatus = ref<'all' | 'upcoming' | 'ongoing' | 'completed'>('all');
const selectedDiscipline = ref<string>('all');
const showFilters = ref(false);

// Computed
const filteredTournaments = computed(() => {
    let tournaments = [...tournamentStore.tournaments];

    // Status filter
    if (selectedStatus.value !== 'all') {
        tournaments = tournaments.filter(t => t.status === selectedStatus.value);
    }

    // Discipline filter
    if (selectedDiscipline.value !== 'all') {
        tournaments = tournaments.filter(t => t.discipline === selectedDiscipline.value);
    }

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        tournaments = tournaments.filter(t =>
            t.name.toLowerCase().includes(query) ||
            t.city?.name.toLowerCase().includes(query) ||
            t.club?.name.toLowerCase().includes(query)
        );
    }

    return tournaments;
});

const tournamentStats = computed(() => {
    const total = tournamentStore.tournaments.length;
    const upcoming = tournamentStore.upcomingTournaments.length;
    const ongoing = tournamentStore.ongoingTournaments.length;
    const completed = tournamentStore.completedTournaments.length;

    return {total, upcoming, ongoing, completed};
});

// Methods
function getStatusClass(status: string) {
    const classes: Record<string, string> = {
        upcoming: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        ongoing: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 animate-pulse',
        completed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    };

    return classes[status] || classes.upcoming;
}

function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function formatDateRange(startDate: string, endDate: string) {
    const start = new Date(startDate);
    const end = new Date(endDate);

    if (start.toDateString() === end.toDateString()) {
        return formatDate(startDate);
    }

    return `${formatDate(startDate)} - ${formatDate(endDate)}`;
}

// Lifecycle
onMounted(async () => {
    await tournamentStore.fetchTournaments();
});
</script>

<template>
    <Head :title="t('Tournaments')"/>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ t('Tournaments') }}
                    </h1>
                    <p class="mt-1 text-gray-600 dark:text-gray-400">
                        {{ t('Browse and manage billiard tournaments') }}
                    </p>
                </div>

                <Link v-if="isAdmin" href="/tournaments/create">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        {{ t('Create Tournament') }}
                    </Button>
                </Link>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <TrophyIcon class="h-8 w-8 text-blue-500"/>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ t('Total Tournaments') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ tournamentStats.total }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <CalendarIcon class="h-8 w-8 text-yellow-500"/>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ t('Upcoming') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ tournamentStats.upcoming }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="relative">
                                    <TrophyIcon class="h-8 w-8 text-green-500"/>
                                    <span
                                        class="absolute -top-1 -right-1 h-3 w-3 bg-green-500 rounded-full animate-ping"></span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ t('Ongoing') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ tournamentStats.ongoing }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <TrophyIcon class="h-8 w-8 text-gray-500"/>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ t('Completed') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ tournamentStats.completed }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search and Filters -->
            <Card class="mb-6">
                <CardContent class="p-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Search -->
                        <div class="flex-1 relative">
                            <input
                                v-model="searchQuery"
                                :placeholder="t('Search tournaments...')"
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
                                type="text"
                            />
                            <SearchIcon
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"/>
                        </div>

                        <!-- Status Filter -->
                        <select
                            v-model="selectedStatus"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="all">{{ t('All Status') }}</option>
                            <option value="upcoming">{{ t('Upcoming') }}</option>
                            <option value="ongoing">{{ t('Ongoing') }}</option>
                            <option value="completed">{{ t('Completed') }}</option>
                        </select>

                        <!-- Discipline Filter -->
                        <select
                            v-model="selectedDiscipline"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="all">{{ t('All Disciplines') }}</option>
                            <option value="8-ball">8-ball</option>
                            <option value="9-ball">9-ball</option>
                            <option value="10-ball">10-ball</option>
                            <option value="snooker">Snooker</option>
                            <option value="straight-pool">Straight Pool</option>
                        </select>

                        <!-- Filter Toggle -->
                        <button
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            @click="showFilters = !showFilters"
                        >
                            <FilterIcon class="w-5 h-5"/>
                        </button>
                    </div>
                </CardContent>
            </Card>

            <!-- Tournaments List -->
            <div v-if="tournamentStore.isLoading" class="flex items-center justify-center h-64">
                <div class="text-center">
                    <Spinner class="w-8 h-8 mx-auto mb-4"/>
                    <p class="text-gray-500 dark:text-gray-400">{{ t('Loading tournaments...') }}</p>
                </div>
            </div>

            <div v-else-if="tournamentStore.error"
                 class="bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-700 rounded-lg p-4">
                <p class="text-red-700 dark:text-red-300">{{ tournamentStore.error }}</p>
            </div>

            <div v-else-if="filteredTournaments.length === 0" class="text-center py-12">
                <TrophyIcon class="mx-auto h-12 w-12 text-gray-400"/>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ t('No tournaments found') }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{
                        searchQuery ? t('Try adjusting your search criteria') : t('Create your first tournament to get started')
                    }}
                </p>
                <div v-if="isAdmin && !searchQuery" class="mt-6">
                    <Link href="/tournaments/create">
                        <Button>
                            <PlusIcon class="mr-2 h-4 w-4"/>
                            {{ t('Create Tournament') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Link
                    v-for="tournament in filteredTournaments"
                    :key="tournament.id"
                    :href="`/tournaments/${tournament.id}`"
                    class="block"
                >
                    <Card class="hover:shadow-lg transition-shadow duration-200">
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div>
                                    <CardTitle class="text-lg">{{ tournament.name }}</CardTitle>
                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                      <CalendarIcon class="w-4 h-4"/>
                      {{ formatDateRange(tournament.start_at, tournament.end_at) }}
                    </span>
                                        <span v-if="tournament.city" class="flex items-center gap-1">
                      <MapPinIcon class="w-4 h-4"/>
                      {{ tournament.city.name }}
                    </span>
                                        <span v-if="tournament.club" class="flex items-center gap-1">
                      {{ tournament.club.name }}
                    </span>
                                    </div>
                                </div>
                                <span
                                    :class="getStatusClass(tournament.status)"
                                    class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                                >
                  {{ tournament.status.toUpperCase() }}
                </span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">{{ t('Discipline') }}</span>
                                    <p class="font-medium">{{ tournament.discipline }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">{{ t('Participants') }}</span>
                                    <p class="font-medium flex items-center gap-1">
                                        <UsersIcon class="w-4 h-4"/>
                                        {{ tournament.participants_count || 0 }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">{{ t('Entry Fee') }}</span>
                                    <p class="font-medium flex items-center gap-1">
                                        <DollarSignIcon class="w-4 h-4"/>
                                        {{ tournament.entry_fee > 0 ? `$${tournament.entry_fee}` : t('Free') }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">{{ t('Prize Pool') }}</span>
                                    <p class="font-medium flex items-center gap-1">
                                        <TrophyIcon class="w-4 h-4"/>
                                        {{ tournament.prize_pool > 0 ? `$${tournament.prize_pool}` : '—' }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </div>
</template>
