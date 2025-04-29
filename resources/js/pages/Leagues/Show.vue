<script setup lang="ts">
// ... импорты ...
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { useApi, useApiAction } from '@/composables/useApi';
import { apiClient } from '@/lib/apiClient';
import type { League, Rating, Player, MatchGame, ApiError } from '@/Types/api';
import { Button, Card, CardContent, CardHeader, CardTitle, CardDescription, Spinner, Modal } from '@/Components/ui';
import PlayerList from '@/Components/PlayerList.vue';
import ChallengeModal from '@/Components/ChallengeModal.vue';
import ResultModal from '@/Components/ResultModal.vue';
import { ArrowLeftIcon, UserPlusIcon, LogOutIcon, PencilIcon, TrashIcon } from 'lucide-vue-next'; // Убрал лишние иконки матчей
import { ref, onMounted, computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps<{ leagueId: number | string; header?: string; }>();
const { user, isAuthenticated, isAdmin } = useAuth(); // Берем isAuthenticated

// --- Состояния, загрузка данных, действия - без изменений ---
const showChallengeModal = ref(false);
const targetPlayerForChallenge = ref<Player | null>(null);
// ... (остальные ref и useApi/useApiAction как были) ...
const fetchLeagueFn = () => apiClient<League>(`/api/leagues/${props.leagueId}`);
const { data: league, isLoading: isLoadingLeague, error: leagueError, execute: fetchLeague } = useApi<League>(fetchLeagueFn);
const fetchPlayersFn = () => apiClient<Rating[]>(`/api/leagues/${props.leagueId}/players`);
const { data: players, isLoading: isLoadingPlayers, error: playersError, execute: fetchPlayers } = useApi<Rating[]>(fetchPlayersFn);
const matches = ref<MatchGame[]>([]); // Матчи TODO
const isLoadingMatches = ref(false);
const matchesError = ref<ApiError | null>(null);
const { execute: joinLeagueAction, isActing: isJoining, error: joinError } = useApiAction(/* ... */);
const { execute: leaveLeagueAction, isActing: isLeaving, error: leaveError } = useApiAction(/* ... */);
const { execute: deleteLeagueAction, isActing: isDeleting, error: deleteError } = useApiAction(/* ... */);
const isCurrentUserInLeague = computed(() => { /* ... */ });
const pageTitle = computed(() => league.value ? `League: ${league.value.name}` : 'League Details');
const displayError = (message: string) => { /* ... */ };
const handleJoinLeague = async () => { /* ... */ };
const handleLeaveLeague = async () => { /* ... */ };
const openChallengeModal = (player: Player) => { targetPlayerForChallenge.value = player; showChallengeModal.value = true; };
const handleChallengeSuccess = (message: string) => { /* ... */ };
// ... (другие обработчики матчей) ...
const handleDeleteLeague = async () => { /* ... */ };
onMounted(() => { fetchLeague(); fetchPlayers(); /* fetchMatches(); */ });
</script>

<template>
  <Head :title="pageTitle" />
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex justify-between items-center">
          <Link :href="route('leagues.index')"> <Button variant="outline"> <ArrowLeftIcon class="w-4 h-4 mr-2" /> Back </Button> </Link>
          <div v-if="isAdmin && league" class="flex space-x-2">
            <Link :href="route('leagues.edit', { league: league.id })"> <Button variant="secondary"> <PencilIcon class="w-4 h-4 mr-2" /> Edit </Button> </Link>
            <Button variant="destructive" @click="handleDeleteLeague" :disabled="isDeleting"> <Spinner v-if="isDeleting" /> <TrashIcon v-else /> Delete </Button>
          </div>
        </div>

        <div v-if="isLoadingLeague" class="text-center p-10"><Spinner /> Loading...</div>
        <div v-else-if="leagueError" class="text-red-500 bg-red-100 p-4 rounded mb-6">Error: {{ leagueError.message }}</div>
        <template v-else-if="league">
          <Card class="mb-8">
            <CardHeader> <CardTitle>{{ league.name }}</CardTitle> <CardDescription>Game: {{ league.game ?? 'N/A' }} | Rating: {{ league.has_rating ? `Enabled (${league.start_rating})` : 'Disabled' }}</CardDescription> </CardHeader>
            <CardContent>
              <p v-if="league.details">{{ league.details }}</p> <p v-else class="italic text-gray-500">No details.</p>
              <div class="mt-4 text-sm text-gray-500 space-x-4"> <span v-if="league.started_at">Starts: {{}}</span> <span v-if="league.finished_at">Ends: {{}}</span> </div>
              <div v-if="isAuthenticated" class="mt-6">
                <Button v-if="!isCurrentUserInLeague" @click="handleJoinLeague" :disabled="isJoining || !league.has_rating"> <Spinner v-if="isJoining"/> <UserPlusIcon v-else/> Join </Button>
                <Button v-else variant="secondary" @click="handleLeaveLeague" :disabled="isLeaving || !league.has_rating"> <Spinner v-if="isLeaving"/> <LogOutIcon v-else/> Leave </Button>
              </div>
            </CardContent>
          </Card>

          <Card class="mb-8">
            <CardHeader><CardTitle>Players & Ratings</CardTitle></CardHeader>
            <CardContent>
              <div v-if="isLoadingPlayers"><Spinner /> Loading...</div>
              <div v-else-if="playersError" class="text-red-500">Error: {{ playersError.message }}</div>
              <PlayerList v-else :players="players || []" :leagueId="league.id" :currentUserId="user?.id ?? null" :isAuthenticated="isAuthenticated" @challenge="openChallengeModal"/>
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle>Matches</CardTitle><CardDescription>Recent challenges.</CardDescription></CardHeader>
            <CardContent>
              <div v-if="isLoadingMatches"><Spinner /> Loading...</div>
              <div v-else-if="matchesError" class="text-red-500">Error: {{ matchesError.message }}</div>
              <div v-else-if="matches.length === 0" class="text-gray-500">No matches yet.</div>
              <ul v-else class="space-y-3">
              </ul>
            </CardContent>
          </Card>
        </template>
        <div v-else-if="!isLoadingLeague" class="text-gray-500 p-10"> League not found. </div>
      </div>

      <ChallengeModal :show="showChallengeModal" :league="league" :targetPlayer="targetPlayerForChallenge" @close="showChallengeModal = false" @success="handleChallengeSuccess" @error="(err: ApiError) => displayError(`Challenge error: ${err.message || '?'}`)"/>
      <ResultModal :show="showResultModal" :matchGame="matchForResults" :currentUser="user" @close="showResultModal = false" @success="handleResultSuccess" @error="(err: ApiError) => displayError(`Result error: ${err.message || '?'}`)"/>
      <Modal :show="showGenericErrorModal" title="Error" @close="showGenericErrorModal = false"> <p>{{ genericErrorMessage }}</p> <template #footer> <Button @click="showGenericErrorModal = false">Close</Button> </template> </Modal>
    </div>
</template>
