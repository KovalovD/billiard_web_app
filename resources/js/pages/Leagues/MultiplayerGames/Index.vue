<script lang="ts" setup>
import CreateMultiplayerGameModal from '@/Components/CreateMultiplayerGameModal.vue';
import MultiplayerGameCard from '@/Components/MultiplayerGameCard.vue';
import {Button, Card, CardContent, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {League, MultiplayerGame} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {ArrowLeftIcon, PlusIcon} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: string | number;
}>();

const {isAdmin} = useAuth();
const {getMultiplayerGames, error} = useMultiplayerGames();

const league = ref<League | null>(null);
const multiplayerGames = ref<MultiplayerGame[]>([]);
const isLoadingLeague = ref(true);
const isLoadingGames = ref(true);
const showCreateModal = ref(false);

// Load league and games
const fetchLeague = async () => {
    isLoadingLeague.value = true;
    try {
        league.value = await apiClient<League>(`/api/leagues/${props.leagueId}`);
    } catch (err) {
        console.error('Failed to fetch league:', err);
    } finally {
        isLoadingLeague.value = false;
    }
};

const fetchGames = async () => {
    isLoadingGames.value = true;
    try {
        multiplayerGames.value = await getMultiplayerGames(props.leagueId);
    } catch (err) {
        console.error('Failed to fetch multiplayer games:', err);
    } finally {
        isLoadingGames.value = false;
    }
};

const handleGameCreated = () => {
    fetchGames();
};

const handleGameUpdated = () => {
    fetchGames();
};

// Event handler for the Create Game button
const openCreateModal = () => {
    showCreateModal.value = true;
};

onMounted(() => {
    fetchLeague();
    fetchGames();
});
</script>

<template>
    <Head :title="league ? `Multiplayer Games - ${league.name}` : 'Multiplayer Games'"/>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header with back button -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="`/leagues/${leagueId}`">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to League
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold">
                    {{ league ? `Multiplayer Games - ${league.name}` : 'Multiplayer Games' }}
                </h1>

                <Button v-if="isAdmin && league?.game_multiplayer" @click="openCreateModal">
                    <PlusIcon class="mr-2 h-4 w-4"/>
                    Create Game
                </Button>
            </div>

            <!-- Error message -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600">
                {{ error }}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Multiplayer Games</CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Loading state -->
                    <div v-if="isLoadingGames" class="flex justify-center py-8">
                        <Spinner class="text-primary h-8 w-8"/>
                    </div>

                    <!-- No multiplayer support message -->
                    <div v-else-if="league && !league.game_multiplayer" class="py-8 text-center text-gray-500">
                        This league does not support multiplayer games.
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="multiplayerGames.length === 0" class="py-8 text-center text-gray-500">
                        No multiplayer games found for this league.
                        <span v-if="isAdmin"> Create one to get started!</span>
                    </div>

                    <!-- Game list -->
                    <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <MultiplayerGameCard
                            v-for="game in multiplayerGames"
                            :key="game.id"
                            :game="game"
                            :league-id="leagueId"
                            @updated="handleGameUpdated"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Create Game Modal -->
    <CreateMultiplayerGameModal
        :league-id="leagueId"
        :show="showCreateModal"
        @close="showCreateModal = false"
        @created="handleGameCreated"
    />
</template>
