// resources/js/pages/Admin/OfficialRatings/Players.vue
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
    Modal,
    Spinner
} from '@/Components/ui';
import {useOfficialRatings} from '@/composables/useOfficialRatings';
import {useTournaments} from '@/composables/useTournaments';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {OfficialRating, OfficialRatingPlayer, User} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CrownIcon,
    PlusIcon,
    RefreshCwIcon,
    SearchIcon,
    StarIcon,
    TrashIcon,
    TrophyIcon,
    UserIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    ratingId: number | string;
}>();

const officialRatingsApi = useOfficialRatings();
const tournamentsApi = useTournaments();

// State
const rating = ref<OfficialRating | null>(null);
const players = ref<OfficialRatingPlayer[]>([]);
const searchResults = ref<User[]>([]);
const isLoading = ref(true);
const isLoadingPlayers = ref(false);
const isSearching = ref(false);
const error = ref<string | null>(null);

// Modal states
const showAddModal = ref(false);
const showAddNewModal = ref(false);

// Form data
const addForm = ref({
    user_id: '',
    initial_rating: 1000
});

const addNewForm = ref({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: '',
    initial_rating: 1000
});

const searchQuery = ref('');

// Filter state
const showInactivePlayers = ref(false);

// API composables
const fetchRatingApi = officialRatingsApi.fetchOfficialRating(props.ratingId);
const fetchPlayersApi = officialRatingsApi.fetchRatingPlayers(props.ratingId);
const searchUsersApi = tournamentsApi.searchUsers;
const addPlayerApi = officialRatingsApi.addPlayerToRating(props.ratingId);
const recalculateApi = officialRatingsApi.recalculateRatingPositions(props.ratingId);

// Computed
const filteredPlayers = computed(() => {
    if (showInactivePlayers.value) {
        return players.value;
    }
    return players.value.filter(p => p.is_active);
});

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

// Methods
const fetchData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        await Promise.all([
            fetchRatingApi.execute(),
            fetchPlayersData()
        ]);

        rating.value = fetchRatingApi.data.value;
    } catch (err: any) {
        error.value = err.message || 'Failed to load data';
    } finally {
        isLoading.value = false;
    }
};

const fetchPlayersData = async () => {
    isLoadingPlayers.value = true;
    try {
        await fetchPlayersApi.execute();
        players.value = fetchPlayersApi.data.value || [];
    } catch (err: any) {
        console.error('Failed to load players:', err);
    } finally {
        isLoadingPlayers.value = false;
    }
};

const searchUsers = async () => {
    if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const api = searchUsersApi(searchQuery.value);
        await api.execute();
        searchResults.value = api.data.value || [];
    } catch (err: any) {
        console.error('Failed to search users:', err);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const openAddModal = () => {
    addForm.value = {
        user_id: '',
        initial_rating: rating.value?.initial_rating || 1000
    };
    searchQuery.value = '';
    searchResults.value = [];
    showAddModal.value = true;
};

const openAddNewModal = () => {
    addNewForm.value = {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        password: '',
        initial_rating: rating.value?.initial_rating || 1000
    };
    showAddNewModal.value = true;
};

const selectUser = (user: User) => {
    addForm.value.user_id = user.id.toString();
    searchQuery.value = `${user.firstname} ${user.lastname}`;
    searchResults.value = [];
};

const addExistingPlayer = async () => {
    if (!addForm.value.user_id) return;

    const success = await addPlayerApi.execute({
        user_id: parseInt(addForm.value.user_id),
        initial_rating: addForm.value.initial_rating
    });

    if (success) {
        showAddModal.value = false;
        await fetchPlayersData();
    }
};

const addNewPlayer = async () => {
    if (!addNewForm.value.firstname || !addNewForm.value.lastname || !addNewForm.value.email) return;

    try {
        // First create the user and add to rating
        const response = await apiClient(`/api/admin/official-ratings/${props.ratingId}/add-player`, {
            method: 'post',
            data: addNewForm.value
        });

        if (response) {
            showAddNewModal.value = false;
            await fetchPlayersData();
        }
    } catch (err: any) {
        console.error('Failed to add new player:', err);
    }
};

const removePlayer = async (userId: number) => {
    if (!confirm('Are you sure you want to remove this player from the rating?')) return;

    const removePlayerApi = officialRatingsApi.removePlayerFromRating(props.ratingId, userId);
    const success = await removePlayerApi.execute();

    if (success) {
        await fetchPlayersData();
    }
};

const recalculatePositions = async () => {
    if (!confirm('This will recalculate all player positions based on current ratings. Continue?')) return;

    const success = await recalculateApi.execute();

    if (success) {
        await fetchPlayersData();
        alert('Positions recalculated successfully');
    }
};

const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString();
};

// Watchers
watch(() => props.ratingId, fetchData, {immediate: false});
watch(searchQuery, searchUsers);

onMounted(fetchData);
</script>

<template>
    <Head :title="rating ? `Manage Players - ${rating.name}` : 'Manage Players'"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="`/admin/official-ratings/${ratingId}/manage`">
                        <Button variant="outline">
                            <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                            Back to Rating
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                            Manage Players
                        </h1>
                        <p v-if="rating" class="text-gray-600 dark:text-gray-400">
                            {{ rating.name }}
                        </p>
                    </div>
                </div>

                <div class="flex space-x-2">
                    <Button :disabled="recalculateApi.isActing.value" variant="outline" @click="recalculatePositions">
                        <RefreshCwIcon class="mr-2 h-4 w-4"/>
                        Recalculate Positions
                    </Button>
                    <Button variant="outline" @click="openAddNewModal">
                        <UserIcon class="mr-2 h-4 w-4"/>
                        Add New Player
                    </Button>
                    <Button @click="openAddModal">
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        Add Existing Player
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input
                        v-model="showInactivePlayers"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        type="checkbox"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Show inactive players</span>
                </label>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-10">
                <Spinner class="text-primary h-8 w-8"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">Loading players...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                Error: {{ error }}
            </div>

            <!-- Content -->
            <template v-else>
                <!-- Stats Cards -->
                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <UsersIcon class="h-8 w-8 text-blue-500"/>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Players</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ players.length }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <StarIcon class="h-8 w-8 text-green-500"/>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Players</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ players.filter(p => p.is_active).length }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <TrophyIcon class="h-8 w-8 text-yellow-500"/>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Tournaments</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{
                                            Math.round(players.reduce((sum, p) => sum + p.tournaments_played, 0) / Math.max(players.length, 1))
                                        }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <CrownIcon class="h-8 w-8 text-purple-500"/>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Rating</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{
                                            Math.round(players.reduce((sum, p) => sum + p.rating_points, 0) / Math.max(players.length, 1))
                                        }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Players List -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <UsersIcon class="h-5 w-5"/>
                            Rating Players
                        </CardTitle>
                        <CardDescription>
                            All players in this rating system
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                            <Spinner class="text-primary h-6 w-6"/>
                        </div>
                        <div v-else-if="filteredPlayers.length === 0" class="py-8 text-center text-gray-500">
                            {{
                                showInactivePlayers
                                    ? 'No players in this rating yet.'
                                    : 'No active players in this rating yet.'
                            }}
                        </div>
                        <div v-else class="overflow-auto">
                            <table class="w-full">
                                <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="px-4 py-3 text-left">Rank</th>
                                    <th class="px-4 py-3 text-left">Division</th>
                                    <th class="px-4 py-3 text-left">Player</th>
                                    <th class="px-4 py-3 text-center">Rating</th>
                                    <th class="px-4 py-3 text-center">Tournaments</th>
                                    <th class="px-4 py-3 text-center">Wins</th>
                                    <th class="px-4 py-3 text-center">Win Rate</th>
                                    <th class="px-4 py-3 text-center">Last Tournament</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    v-for="player in filteredPlayers"
                                    :key="player.id"
                                    :class="[
                                        'border-b dark:border-gray-700',
                                        !player.is_active && 'opacity-60'
                                    ]"
                                >
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                                getPositionBadgeClass(player.position)
                                            ]"
                                        >
                                            {{ player.position }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                         ]"
                                        >
                                            {{ player.division }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div>
                                                <p class="font-medium">{{ player.user?.firstname }}
                                                    {{ player.user?.lastname }}</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ player.user?.email }}
                                                </p>
                                                <p v-if="player.position === 1"
                                                   class="text-sm text-yellow-600 dark:text-yellow-400">
                                                    👑 Champion
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-bold text-lg">{{ player.rating_points }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ player.tournaments_played }}</td>
                                    <td class="px-4 py-3 text-center">{{ player.tournaments_won }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm">{{ player.win_rate }}%</span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatDate(player.last_tournament_at) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="[
                                                'px-2 py-1 text-xs rounded-full',
                                                player.is_active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                            ]"
                                        >
                                            {{ player.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="removePlayer(player.user_id)"
                                        >
                                            <TrashIcon class="h-4 w-4"/>
                                        </Button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </div>

    <!-- Add Existing Player Modal -->
    <Modal :show="showAddModal" title="Add Existing Player to Rating" @close="showAddModal = false">
        <div class="space-y-4">
            <div>
                <Label for="search">Search Users</Label>
                <div class="relative">
                    <Input
                        id="search"
                        v-model="searchQuery"
                        class="pl-10"
                        placeholder="Search by name or email..."
                        type="text"
                    />
                    <SearchIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                </div>

                <!-- Search Results -->
                <div v-if="isSearching" class="mt-2 text-sm text-gray-500">
                    Searching...
                </div>
                <div v-else-if="searchResults.length > 0" class="mt-2 max-h-40 overflow-y-auto border rounded-md">
                    <div
                        v-for="user in searchResults"
                        :key="user.id"
                        class="p-2 hover:bg-gray-50 cursor-pointer dark:hover:bg-gray-800"
                        @click="selectUser(user)"
                    >
                        <div class="font-medium">{{ user.firstname }} {{ user.lastname }}</div>
                        <div class="text-sm text-gray-600">{{ user.email }}</div>
                    </div>
                </div>
                <div v-else-if="searchQuery.length >= 2" class="mt-2 text-sm text-gray-500">
                    No users found matching "{{ searchQuery }}"
                </div>
            </div>

            <div>
                <Label for="initial_rating">Initial Rating</Label>
                <Input
                    id="initial_rating"
                    v-model.number="addForm.initial_rating"
                    min="0"
                    placeholder="1000"
                    type="number"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Starting rating for this player
                </p>
            </div>
        </div>

        <template #footer>
            <Button variant="outline" @click="showAddModal = false">Cancel</Button>
            <Button
                :disabled="!addForm.user_id || addPlayerApi.isActing.value"
                @click="addExistingPlayer"
            >
                <span v-if="addPlayerApi.isActing.value">Adding...</span>
                <span v-else>Add Player</span>
            </Button>
        </template>
    </Modal>

    <!-- Add New Player Modal -->
    <Modal :show="showAddNewModal" title="Add New Player to Rating" @close="showAddNewModal = false">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <Label for="firstname">First Name</Label>
                    <Input
                        id="firstname"
                        v-model="addNewForm.firstname"
                        placeholder="First name"
                        required
                        type="text"
                    />
                </div>
                <div>
                    <Label for="lastname">Last Name</Label>
                    <Input
                        id="lastname"
                        v-model="addNewForm.lastname"
                        placeholder="Last name"
                        required
                        type="text"
                    />
                </div>
            </div>

            <div>
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    v-model="addNewForm.email"
                    placeholder="email@example.com"
                    required
                    type="email"
                />
            </div>

            <div>
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    v-model="addNewForm.phone"
                    placeholder="+380123456789"
                    required
                    type="tel"
                />
            </div>

            <div>
                <Label for="password">Password</Label>
                <Input
                    id="password"
                    v-model="addNewForm.password"
                    placeholder="Password"
                    required
                    type="password"
                />
            </div>

            <div>
                <Label for="new_initial_rating">Initial Rating</Label>
                <Input
                    id="new_initial_rating"
                    v-model.number="addNewForm.initial_rating"
                    min="0"
                    placeholder="1000"
                    type="number"
                />
            </div>
        </div>

        <template #footer>
            <Button variant="outline" @click="showAddNewModal = false">Cancel</Button>
            <Button
                :disabled="!addNewForm.firstname || !addNewForm.lastname || !addNewForm.email"
                @click="addNewPlayer"
            >
                Add New Player
            </Button>
        </template>
    </Modal>
</template>
