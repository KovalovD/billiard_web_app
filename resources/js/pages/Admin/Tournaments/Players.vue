// resources/js/pages/Admin/Tournaments/Players.vue
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
import {useTournaments} from '@/composables/useTournaments';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {Tournament, TournamentPlayer, User} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, PlusIcon, SearchIcon, TrashIcon, UserPlusIcon, UsersIcon} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {
    fetchTournament,
    fetchTournamentPlayers,
    addPlayerToTournament,
    addNewPlayerToTournament,
    removePlayerFromTournament,
    searchUsers
} = useTournaments();

// Data
const tournament = ref<Tournament | null>(null);
const players = ref<TournamentPlayer[]>([]);
const searchResults = ref<User[]>([]);
const searchQuery = ref('');
const newPlayerForm = ref({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: 'defaultPassword123'
});

// Loading states
const isLoadingTournament = ref(true);
const isLoadingPlayers = ref(true);
const isSearching = ref(false);
const isAddingPlayer = ref(false);

// Modal states
const showAddExistingModal = ref(false);
const showAddNewModal = ref(false);

// API calls
const tournamentApi = fetchTournament(props.tournamentId);
const playersApi = fetchTournamentPlayers(props.tournamentId);

const addExistingApi = addPlayerToTournament(props.tournamentId);
const addNewApi = addNewPlayerToTournament(props.tournamentId);

// Computed
const sortedPlayers = computed(() => {
    return [...players.value].sort((a, b) => {
        // Sort by position first (null positions at the end)
        if (a.position === null && b.position === null) {
            return (a.user?.lastname || '').localeCompare(b.user?.lastname || '');
        }
        if (a.position === null) return 1;
        if (b.position === null) return -1;
        return a.position - b.position;
    });
});

const canAddPlayers = computed(() => {
    return tournament.value?.is_registration_open || tournament.value?.status !== 'completed';
});

// Watch search query
watch(searchQuery, async (newQuery) => {
    if (newQuery.length >= 2) {
        const {
            data: success,
            execute: searchUsersExecute,
        } = searchUsers(newQuery);
        isSearching.value = true;
        await searchUsersExecute();

        if (success && success.value) {
            searchResults.value = success.value;
        }
        isSearching.value = false;
    } else {
        searchResults.value = [];
    }
});

const loadTournament = async () => {
    isLoadingTournament.value = true;
    const success = await tournamentApi.execute();
    if (success && tournamentApi.data.value) {
        tournament.value = tournamentApi.data.value;
    }
    isLoadingTournament.value = false;
};

const loadPlayers = async () => {
    isLoadingPlayers.value = true;
    const success = await playersApi.execute();
    if (success && playersApi.data.value) {
        players.value = playersApi.data.value;
    }
    isLoadingPlayers.value = false;
};

const handleAddExistingPlayer = async (userId: number) => {
    isAddingPlayer.value = true;
    const success = await addExistingApi.execute({user_id: userId});
    if (success) {
        showAddExistingModal.value = false;
        searchQuery.value = '';
        await loadPlayers();
    }
    isAddingPlayer.value = false;
};

const handleAddNewPlayer = async () => {
    if (!isNewPlayerFormValid.value) return;

    isAddingPlayer.value = true;
    const success = await addNewApi.execute(newPlayerForm.value);
    if (success) {
        showAddNewModal.value = false;
        resetNewPlayerForm();
        await loadPlayers();
    }
    isAddingPlayer.value = false;
};

const handleRemovePlayer = async (playerId: number) => {
    if (!confirm('Are you sure you want to remove this player from the tournament?')) {
        return;
    }

    const removeApi = removePlayerFromTournament(props.tournamentId, playerId);
    const success = await removeApi.execute();
    if (success) {
        await loadPlayers();
    }
};

const resetNewPlayerForm = () => {
    newPlayerForm.value = {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        password: 'defaultPassword123'
    };
};

const isNewPlayerFormValid = computed(() => {
    return newPlayerForm.value.firstname.trim() !== '' &&
        newPlayerForm.value.lastname.trim() !== '' &&
        newPlayerForm.value.email.trim() !== '' &&
        newPlayerForm.value.phone.trim() !== '';
});

const isPlayerAlreadyAdded = (userId: number): boolean => {
    return players.value.some(player => player.user?.id === userId);
};

const getStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'registered':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'confirmed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'eliminated':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        case 'dnf':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const handleBackToTournament = () => {
    router.visit(`/tournaments/${props.tournamentId}`);
};

onMounted(() => {
    loadTournament();
    loadPlayers();
});
</script>

<template>
    <Head :title="tournament ? `Manage Players: ${tournament.name}` : 'Manage Tournament Players'"/>

    <div class="py-12">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Manage Players</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : 'Loading...' }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Button v-if="canAddPlayers" variant="outline" @click="showAddExistingModal = true">
                        <UserPlusIcon class="mr-2 h-4 w-4"/>
                        Add Existing Player
                    </Button>
                    <Button v-if="canAddPlayers" @click="showAddNewModal = true">
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        Add New Player
                    </Button>
                    <Button variant="outline" @click="handleBackToTournament">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to Tournament
                    </Button>
                </div>
            </div>

            <!-- Tournament Info -->
            <Card v-if="tournament" class="mb-6">
                <CardContent class="pt-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ tournament.players_count }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Players</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ tournament.max_participants || '∞' }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Max Players</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ tournament.status_display }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Status</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ tournament.is_registration_open ? 'Open' : 'Closed' }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Registration</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Players List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UsersIcon class="h-5 w-5"/>
                        Tournament Players
                    </CardTitle>
                    <CardDescription>
                        Manage player registration and tournament participation
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                        <Spinner class="text-primary h-6 w-6"/>
                    </div>
                    <div v-else-if="players.length === 0" class="py-8 text-center text-gray-500">
                        No players registered yet.
                    </div>
                    <div v-else class="overflow-auto">
                        <table class="w-full">
                            <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-4 py-3 text-left">Position</th>
                                <th class="px-4 py-3 text-left">Player</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Rating Points</th>
                                <th class="px-4 py-3 text-center">Prize</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="player in sortedPlayers"
                                :key="player.id"
                                class="border-b dark:border-gray-700"
                            >
                                <td class="px-4 py-3">
                                    <span v-if="player.position" class="font-bold">#{{ player.position }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium">{{ player.user?.firstname }} {{
                                                player.user?.lastname
                                            }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ player.user?.email }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', getStatusBadgeClass(player.status)]"
                                    >
                                        {{ player.status_display }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="player.rating_points > 0" class="font-medium">+{{
                                            player.rating_points
                                        }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="player.prize_amount > 0"
                                          class="font-medium text-green-600 dark:text-green-400">
                                        ₴{{ player.prize_amount.toLocaleString() }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Button
                                        v-if="canAddPlayers"
                                        size="sm"
                                        variant="ghost"
                                        @click="handleRemovePlayer(player.id)"
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

            <!-- Add Existing Player Modal -->
            <Modal :show="showAddExistingModal" title="Add Existing Player" @close="showAddExistingModal = false">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="search">Search Players</Label>
                        <div class="relative">
                            <SearchIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                            <Input
                                id="search"
                                v-model="searchQuery"
                                class="pl-10"
                                placeholder="Search by name or email..."
                            />
                        </div>
                    </div>

                    <div v-if="isSearching" class="flex justify-center py-4">
                        <Spinner class="h-6 w-6"/>
                    </div>

                    <div v-else-if="searchResults.length > 0" class="max-h-60 space-y-2 overflow-y-auto">
                        <div
                            v-for="user in searchResults"
                            :key="user.id"
                            class="flex items-center justify-between rounded-lg border p-3"
                        >
                            <div>
                                <p class="font-medium">{{ user.firstname }} {{ user.lastname }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ user.email }}</p>
                            </div>
                            <Button
                                v-if="!isPlayerAlreadyAdded(user.id)"
                                :disabled="isAddingPlayer"
                                size="sm"
                                @click="handleAddExistingPlayer(user.id)"
                            >
                                Add
                            </Button>
                            <span v-else class="text-sm text-gray-500">Already added</span>
                        </div>
                    </div>

                    <div v-else-if="searchQuery.length >= 2" class="py-4 text-center text-gray-500">
                        No players found
                    </div>

                    <div v-else class="py-4 text-center text-gray-500">
                        Type at least 2 characters to search
                    </div>
                </div>

                <template #footer>
                    <Button variant="outline" @click="showAddExistingModal = false">
                        Cancel
                    </Button>
                </template>
            </Modal>

            <!-- Add New Player Modal -->
            <Modal :show="showAddNewModal" title="Add New Player" @close="showAddNewModal = false">
                <form class="space-y-4" @submit.prevent="handleAddNewPlayer">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="firstname">First Name *</Label>
                            <Input
                                id="firstname"
                                v-model="newPlayerForm.firstname"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="lastname">Last Name *</Label>
                            <Input
                                id="lastname"
                                v-model="newPlayerForm.lastname"
                                required
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email *</Label>
                        <Input
                            id="email"
                            v-model="newPlayerForm.email"
                            required
                            type="email"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">Phone *</Label>
                        <Input
                            id="phone"
                            v-model="newPlayerForm.phone"
                            required
                            type="tel"
                        />
                    </div>
                </form>

                <template #footer>
                    <Button variant="outline" @click="showAddNewModal = false">
                        Cancel
                    </Button>
                    <Button
                        :disabled="!isNewPlayerFormValid || isAddingPlayer"
                        @click="handleAddNewPlayer"
                    >
                        <Spinner v-if="isAddingPlayer" class="mr-2 h-4 w-4"/>
                        {{ isAddingPlayer ? 'Adding...' : 'Add Player' }}
                    </Button>
                </template>
            </Modal>

            <!-- Error Display -->
            <div v-if="addExistingApi.error.value || addNewApi.error.value"
                 class="mt-4 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                Error: {{ addExistingApi.error.value?.message || addNewApi.error.value?.message }}
            </div>
        </div>
    </div>
</template>
