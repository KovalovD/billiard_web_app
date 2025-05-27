<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {CalendarIcon, EyeIcon, MapPinIcon, PencilIcon, PlusIcon, TrophyIcon, UsersIcon,} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {isAdmin} = useAuth();

const tournaments = ref<Tournament[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const selectedStatus = ref<string>('all');

const statusOptions = [
    {value: 'all', label: 'All Tournaments'},
    {value: 'upcoming', label: 'Upcoming'},
    {value: 'active', label: 'Active'},
    {value: 'completed', label: 'Completed'}
];

const filteredTournaments = computed(() => {
    if (selectedStatus.value === 'all') {
        return tournaments.value;
    }
    return tournaments.value.filter(t => t.status === selectedStatus.value);
});

const fetchTournaments = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        tournaments.value = await apiClient<Tournament[]>('/api/tournaments');
    } catch (err: any) {
        error.value = err.message || 'Failed to load tournaments';
    } finally {
        isLoading.value = false;
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
    if (amount <= 0) return 'N/A';
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
    <Head title="Tournaments"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Tournaments</h1>
                    <p class="text-gray-600 dark:text-gray-400">Discover and follow billiard tournaments</p>
                </div>

                <Link v-if="isAdmin" href="/admin/tournaments/create">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        Create Tournament
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
                        Tournament Directory
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Loading State -->
                    <div v-if="isLoading" class="flex items-center justify-center py-10">
                        <Spinner class="text-primary h-8 w-8"/>
                        <span class="ml-2 text-gray-500 dark:text-gray-400">Loading tournaments...</span>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error"
                         class="rounded bg-red-100 p-4 text-center text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        Error loading tournaments: {{ error }}
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="filteredTournaments.length === 0"
                         class="py-10 text-center text-gray-500 dark:text-gray-400">
                        <TrophyIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                        <p class="text-lg">No tournaments found</p>
                        <p class="text-sm">
                            {{
                                selectedStatus === 'all' ? 'No tournaments have been created yet.' : `No ${selectedStatus} tournaments.`
                            }}
                        </p>
                    </div>

                    <!-- Tournaments Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Tournament
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Game
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Location
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Players
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Prize Pool
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                            <tr
                                v-for="tournament in filteredTournaments"
                                :key="tournament.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <!-- Tournament Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <div
                                                class="h-8 w-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                                <TrophyIcon class="h-4 w-4 text-yellow-600 dark:text-yellow-400"/>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ tournament.name }}
                                            </div>
                                            <div v-if="tournament.organizer"
                                                 class="text-sm text-gray-500 dark:text-gray-400">
                                                by {{ tournament.organizer }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Game -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <TrophyIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                        {{ tournament.game?.name || 'N/A' }}
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
                                    <div v-else class="text-sm text-gray-400">N/A</div>
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
                                                {{ tournament.players_count !== 1 ? 'players' : 'player' }}
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
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <Link :href="`/tournaments/${tournament.id}`">
                                            <Button size="sm" variant="outline">
                                                <EyeIcon class="h-4 w-4"/>
                                            </Button>
                                        </Link>

                                        <Link v-if="isAdmin" :href="`/admin/tournaments/${tournament.id}/edit`">
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
