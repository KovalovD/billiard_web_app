<!-- resources/js/Pages/Tournaments/Players.vue -->
<script lang="ts" setup>
import {ref, computed, onMounted, watch} from 'vue';
import {Head, router} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Button,
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Modal
} from '@/Components/ui';
import {useTournamentStore} from '@/stores/tournament';
import {useLocale} from '@/composables/useLocale';
import tournamentService from '@/Services/TournamentService';
import UserSearchCard from '@/Components/Tournament/UserSearchCard.vue';
import UserRegistrationForm from '@/Components/Tournament/UserRegistrationForm.vue';
import TeamBuilder from '@/Components/Tournament/TeamBuilder.vue';
import PlayerList from '@/Components/Tournament/PlayerList.vue';
import {
    UserPlusIcon,
    UsersIcon,
    SearchIcon,
    ArrowLeftIcon,
    ShuffleIcon
} from 'lucide-vue-next';

defineOptions({layout: AuthenticatedLayout});

interface Props {
    tournamentId: number;
}

const props = defineProps<Props>();

const {t} = useLocale();
const tournamentStore = useTournamentStore();

// State
const activeTab = ref<'search' | 'register' | 'teams'>('search');
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const isLoading = ref(false);

// Modals
const showRegisterModal = ref(false);
const showTeamModal = ref(false);

// Computed
const tournament = computed(() => tournamentStore.currentTournament);
const players = computed(() => tournamentStore.confirmedPlayers);
const isTeamTournament = computed(() => tournament.value?.teams?.enabled || false);

const canAddMorePlayers = computed(() => {
    if (!tournament.value) return false;
    return players.value.length < tournament.value.max_participants;
});

const playersNeeded = computed(() => {
    if (!tournament.value) return 0;
    return tournament.value.max_participants - players.value.length;
});

// Methods
const fetchTournament = async () => {
    isLoading.value = true;
    try {
        await tournamentStore.fetchTournament(props.tournamentId);
        await tournamentStore.fetchPlayers(props.tournamentId);
    } catch (error) {
        console.error('Failed to fetch tournament:', error);
    } finally {
        isLoading.value = false;
    }
};

const searchUsers = async (query: string) => {
    if (query.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        searchResults.value = await tournamentService.searchUsers(query);
    } catch (error) {
        console.error('Search failed:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const addPlayerToTournament = async (userId: number) => {
    try {
        await tournamentStore.addPlayer(props.tournamentId, userId);
        // Remove from search results to prevent duplicate adding
        searchResults.value = searchResults.value.filter(user => user.id !== userId);
    } catch (error) {
        console.error('Failed to add player:', error);
    }
};

const removePlayerFromTournament = async (userId: number) => {
    if (!confirm(t('Are you sure you want to remove this player?'))) {
        return;
    }

    try {
        await tournamentStore.removePlayer(props.tournamentId, userId);
    } catch (error) {
        console.error('Failed to remove player:', error);
    }
};

const handleUserRegistered = (user: any) => {
    // Add the newly registered user to the tournament
    addPlayerToTournament(user.id);
    showRegisterModal.value = false;
};

const handleRandomShuffle = () => {
    if (!confirm(t('Randomly shuffle all registered players? This will affect seeding positions.'))) {
        return;
    }

    // Implement random shuffle logic
    console.log('Random shuffle players');
};

const goBack = () => {
    router.visit(`/tournaments/${props.tournamentId}`);
};

// Watchers
watch(searchQuery, (newQuery) => {
    searchUsers(newQuery);
}, {debounce: 300});

onMounted(() => {
    fetchTournament();
});
</script>

<template>
    <Head :title="t('Manage Players')"/>

    <div class="py-12">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-4 mb-2">
                        <Button variant="outline" @click="goBack">
                            <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                            {{ t('Back to Tournament') }}
                        </Button>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ t('Manage Players') }}
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ tournament?.name }}
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ players.length }} / {{ tournament?.max_participants || 0 }}
                        </div>
                        <div class="text-sm text-gray-500">{{ t('Registered') }}</div>
                    </div>

                    <Button
                        v-if="players.length > 0"
                        variant="outline"
                        @click="handleRandomShuffle"
                    >
                        <ShuffleIcon class="mr-2 h-4 w-4"/>
                        {{ t('Shuffle') }}
                    </Button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <template v-else>
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <Card>
                        <CardContent class="p-6 text-center">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ players.length }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('Confirmed Players') }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6 text-center">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                                {{ playersNeeded }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('Still Needed') }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6 text-center">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                {{ tournament?.max_participants || 0 }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('Maximum') }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="isTeamTournament">
                        <CardContent class="p-6 text-center">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {{ Math.ceil(players.length / (tournament?.teams?.team_size || 2)) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('Teams Formed') }}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tab Navigation -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            :class="[
                'py-4 px-1 text-sm font-medium border-b-2',
                activeTab === 'search'
                  ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
                            @click="activeTab = 'search'"
                        >
                            <SearchIcon class="mr-2 h-4 w-4 inline"/>
                            {{ t('Search & Add Players') }}
                        </button>

                        <button
                            :class="[
                'py-4 px-1 text-sm font-medium border-b-2',
                activeTab === 'register'
                  ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
                            @click="activeTab = 'register'"
                        >
                            <UserPlusIcon class="mr-2 h-4 w-4 inline"/>
                            {{ t('Register New Players') }}
                        </button>

                        <button
                            v-if="isTeamTournament"
                            :class="[
                'py-4 px-1 text-sm font-medium border-b-2',
                activeTab === 'teams'
                  ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
                            @click="activeTab = 'teams'"
                        >
                            <UsersIcon class="mr-2 h-4 w-4 inline"/>
                            {{ t('Manage Teams') }}
                        </button>
                    </nav>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content Area -->
                    <div class="lg:col-span-2">
                        <!-- Search Tab -->
                        <div v-if="activeTab === 'search'" class="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{{ t('Search Existing Players') }}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div class="relative">
                                            <SearchIcon
                                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                                            <Input
                                                v-model="searchQuery"
                                                :placeholder="t('Search by name, email, or username...')"
                                                class="pl-10"
                                            />
                                        </div>

                                        <div v-if="isSearching" class="flex justify-center py-4">
                                            <div
                                                class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                        </div>

                                        <div v-else-if="searchResults.length > 0" class="space-y-3">
                                            <UserSearchCard
                                                v-for="user in searchResults"
                                                :key="user.id"
                                                :disabled="!canAddMorePlayers"
                                                :user="user"
                                                @add="addPlayerToTournament"
                                            />
                                        </div>

                                        <div v-else-if="searchQuery.length >= 2" class="text-center py-8 text-gray-500">
                                            <UsersIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                                            <p>{{ t('No players found matching your search') }}</p>
                                        </div>

                                        <div v-else class="text-center py-8 text-gray-500">
                                            <SearchIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                                            <p>{{ t('Type at least 2 characters to search') }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Register Tab -->
                        <div v-if="activeTab === 'register'" class="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{{ t('Register New Player') }}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <UserRegistrationForm
                                        :disabled="!canAddMorePlayers"
                                        @registered="handleUserRegistered"
                                    />
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Teams Tab -->
                        <div v-if="activeTab === 'teams' && isTeamTournament" class="space-y-6">
                            <TeamBuilder
                                :players="players"
                                :tournament="tournament"
                                @teams-updated="fetchTournament"
                            />
                        </div>
                    </div>

                    <!-- Player List Sidebar -->
                    <div class="lg:col-span-1">
                        <PlayerList
                            :players="players"
                            :tournament="tournament"
                            @remove="removePlayerFromTournament"
                        />
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
