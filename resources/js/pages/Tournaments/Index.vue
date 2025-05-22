// resources/js/pages/Tournaments/Index.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {CalendarIcon, MapPinIcon, PlusIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
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
                <p class="text-sm">{{
                        selectedStatus === 'all' ? 'No tournaments have been created yet.' : `No ${selectedStatus} tournaments.`
                    }}</p>
            </div>

            <!-- Tournaments Grid -->
            <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="tournament in filteredTournaments"
                    :key="tournament.id"
                    class="hover:shadow-lg transition-shadow cursor-pointer"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-lg">{{ tournament.name }}</CardTitle>
                                <CardDescription class="mt-1">
                                    <div class="flex items-center gap-1">
                                        <TrophyIcon class="h-3 w-3"/>
                                        {{ tournament.game?.name || 'N/A' }}
                                    </div>
                                </CardDescription>
                            </div>
                            <span
                                :class="['rounded-full px-2 py-1 text-xs font-semibold', getStatusBadgeClass(tournament.status)]"
                            >
                                {{ tournament.status_display }}
                            </span>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div class="space-y-3">
                            <!-- Date -->
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <CalendarIcon class="h-4 w-4"/>
                                <span>{{ formatDate(tournament.start_date) }}</span>
                                <span v-if="tournament.end_date !== tournament.start_date">
                                    - {{ formatDate(tournament.end_date) }}
                                </span>
                            </div>

                            <!-- Location -->
                            <div v-if="tournament.city"
                                 class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <MapPinIcon class="h-4 w-4"/>
                                <span>{{ tournament.city.name }}, {{ tournament.city.country?.name }}</span>
                            </div>

                            <!-- Players -->
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <UsersIcon class="h-4 w-4"/>
                                <span>
                                    {{ tournament.players_count }} player{{ tournament.players_count !== 1 ? 's' : '' }}
                                    <span v-if="tournament.max_participants">
                                        / {{ tournament.max_participants }}
                                    </span>
                                </span>
                            </div>

                            <!-- Prize Pool -->
                            <div v-if="tournament.prize_pool > 0" class="text-sm">
                                <span class="font-medium text-green-600 dark:text-green-400">
                                    Prize Pool: {{
                                        tournament.prize_pool.toLocaleString('uk-UA', {
                                            style: 'currency',
                                            currency: 'UAH'
                                        }).replace('UAH', '₴')
                                    }}
                                </span>
                            </div>

                            <!-- Description -->
                            <p v-if="tournament.details" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ tournament.details }}
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center">
                            <Link :href="`/tournaments/${tournament.id}`">
                                <Button size="sm" variant="outline">
                                    View Details
                                </Button>
                            </Link>

                            <Link v-if="isAdmin" :href="`/admin/tournaments/${tournament.id}/edit`">
                                <Button size="sm" variant="ghost">
                                    Edit
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
