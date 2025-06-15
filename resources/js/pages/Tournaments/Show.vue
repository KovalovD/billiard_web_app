<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import {computed, onMounted, ref} from 'vue';
import {useTournament} from '@/composables/useTournament';
import {useAuth} from '@/composables/useAuth';
import {useLocale} from '@/composables/useLocale';
import {
    CalendarDaysIcon,
    CalendarIcon,
    Columns3Icon,
    MapPinIcon,
    PlusIcon,
    SettingsIcon,
    ShuffleIcon,
    TableIcon,
    TrophyIcon,
    UserPlusIcon,
    UsersIcon
} from 'lucide-vue-next';

// Import tournament components
import BracketCanvas from '@/Components/tournaments/BracketCanvas.vue';
import ScheduleGrid from '@/Components/tournaments/ScheduleGrid.vue';
import MatchCard from '@/Components/tournaments/MatchCard.vue';
import SeedingDrawer from '@/Components/tournaments/SeedingDrawer.vue';
import StandingsTable from '@/Components/tournaments/StandingsTable.vue';
import ParticipantsList from '@/Components/tournaments/ParticipantsList.vue';
//import TournamentWebSocket from '@/Components/tournaments/TournamentWebSocket.vue';
import {Modal, Spinner} from '@/Components/ui';

defineOptions({layout: AuthenticatedLayout});

// Route params
const props = defineProps<{
    tournamentId: number | string;
}>();

// Composables
const {t} = useLocale();
const {isAdmin} = useAuth();
const isReferee = computed(() => false); // TODO: Implement referee check

// Tournament composable
const {
    currentTournament,
    currentStage,
    stages,
    currentStageMatches,
    currentStageParticipants,
    participants,
    isLoading,
    error,
    canEditBracket,
    loadStage,
    updateMatchScore,
    scheduleMatch,
    applySeeding,
    setWalkover
} = useTournament({
    tournamentId: props.tournamentId,
    autoLoadTournament: true,
    autoLoadMatches: true,
    autoSubscribe: true
});

// Local state
const currentView = ref<'bracket' | 'schedule' | 'standings' | 'participants'>('bracket');
const selectedMatch = ref<any>(null);
const showSeedingDrawer = ref(false);
const showSettings = ref(false);
const showAddStage = ref(false);
const showAddParticipant = ref(false);

// View modes configuration
const viewModes = [
    {value: 'bracket', label: t('Bracket'), icon: Columns3Icon},
    {value: 'schedule', label: t('Schedule'), icon: CalendarDaysIcon},
    {value: 'standings', label: t('Standings'), icon: TableIcon},
    {value: 'participants', label: t('Participants'), icon: UsersIcon}
];

// Computed
const showStandingsTab = computed(() => {
    return currentStage.value?.type === 'group' || currentStage.value?.type === 'round_robin';
});
// Computed
const showBracketTab = computed(() => {
    return currentStage.value?.type !== 'group' && currentStage.value?.type !== 'round_robin';
});

// Methods
function getStatusClass(status?: string) {
    const classes: Record<string, string> = {
        upcoming: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        ongoing: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        completed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    };

    return classes[status || 'upcoming'];
}

function getStageTitle(stage: any) {
    const titles: Record<string, string> = {
        single_elim: t('Single Elimination'),
        double_elim: t('Double Elimination'),
        group: t('Group Stage'),
        round_robin: t('Round Robin'),
        swiss: t('Swiss'),
        custom: t('Custom')
    };

    return titles[stage.type] || `${t('Stage')} ${stage.number}`;
}

function formatDate(dateStr?: string) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

async function selectStage(stage: any) {
    await loadStage(stage.id);

    // Switch to appropriate view based on stage type
    if (stage.type === 'group' || stage.type === 'round_robin') {
        currentView.value = 'standings';
    } else {
        currentView.value = 'bracket';
    }
}

function handleMatchClick(payload: { matchId: number }) {
    selectedMatch.value = currentStageMatches.value.find(m => m.id === payload.matchId);
}

async function handleScoreSubmit(payload: any) {
    await updateMatchScore(payload.matchId, payload.status, payload.sets);
    selectedMatch.value = null;
}

async function handleWalkover(payload: any) {
    await setWalkover(props.tournamentId, payload.matchId, payload.winnerId);
    selectedMatch.value = null;
}

async function handleReschedule(payload: any) {
    await scheduleMatch(payload.matchId, payload.tableId, payload.newStart);
}

function handleAutoSchedule(date: string) {
    const startTime = new Date(date);
    startTime.setHours(9, 0, 0, 0);

    // TODO: Open modal for auto-schedule options
    console.log('Auto schedule from:', startTime.toISOString());
}

function handleSeedChange(payload: any) {
    console.log('Seed change:', payload);
    // TODO: Handle individual seed change from bracket
}

async function handleBulkSeedChange(changes: Array<{ participantId: number; seed: number }>) {
    // Apply manual seeding
    const seeds = changes.map(c => c.participantId);
    await applySeeding('manual', {seeds});
    showSeedingDrawer.value = false;
}

function handleRemoveParticipant(participantId: number) {
    console.log('Remove participant:', participantId);
    // TODO: Implement remove participant
}

// Lifecycle
onMounted(() => {
    // Select first stage when loaded
    if (stages.value.length > 0 && !currentStage.value) {
        selectStage(stages.value[0]);
    }
});
</script>

<template>
    <Head :title="currentTournament?.name || t('Tournament')"/>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ currentTournament?.name || t('Loading...') }}
                        </h1>
                        <div v-if="currentTournament"
                             class="mt-1 flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
              <span class="flex items-center gap-1">
                <CalendarIcon class="w-4 h-4"/>
                {{ formatDate(currentTournament.start_at) }} - {{ formatDate(currentTournament.end_at) }}
              </span>
                            <span v-if="currentTournament.city" class="flex items-center gap-1">
                <MapPinIcon class="w-4 h-4"/>
                {{ currentTournament.city.name }}
              </span>
                            <span v-if="currentTournament.club" class="flex items-center gap-1">
                {{ currentTournament.club.name }}
              </span>
                            <span class="flex items-center gap-1">
                <TrophyIcon class="w-4 h-4"/>
                {{ currentTournament.discipline }}
              </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
            <span
                v-if="currentTournament"
                :class="getStatusClass(currentTournament.status)"
                class="px-3 py-1 rounded-full text-sm font-medium"
            >
              {{ currentTournament.status?.toUpperCase() }}
            </span>

                        <button
                            v-if="isAdmin"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            @click="showSettings = true"
                        >
                            <SettingsIcon class="w-5 h-5"/>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stage Tabs -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="-mb-px flex space-x-8">
                    <button
                        v-for="stage in stages"
                        :key="stage.id"
                        :class="currentStage?.id === stage.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'"
                        class="py-4 px-1 text-sm font-medium border-b-2 transition-colors"
                        @click="selectStage(stage)"
                    >
                        {{ getStageTitle(stage) }}
                        <span
                            v-if="stage.is_complete"
                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"
                        >
              {{ t('Complete') }}
            </span>
                    </button>

                    <button
                        v-if="isAdmin && stages.length < 5"
                        class="py-4 px-1 text-sm font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        @click="showAddStage = true"
                    >
                        <PlusIcon class="w-4 h-4"/>
                    </button>
                </nav>
            </div>
        </div>

        <!-- View Mode Tabs -->
        <div class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-4 py-2">
                    <button
                        v-for="view in viewModes"
                        v-show="(view.value !== 'standings' || showStandingsTab) && (view.value !== 'bracket' || showBracketTab)"
                        :key="view.value"
                        :class="currentView === view.value
              ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm'
              : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'"
                        class="px-3 py-1 text-sm font-medium rounded-md transition-colors"
                        @click="currentView = view.value"
                    >
                        <component :is="view.icon" class="w-4 h-4 inline-block mr-1"/>
                        {{ view.label }}
                    </button>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center h-64">
                <div class="text-center">
                    <Spinner class="w-8 h-8 mx-auto mb-4"/>
                    <p class="text-gray-500 dark:text-gray-400">{{ t('Loading tournament data...') }}</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error"
                 class="bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-700 rounded-lg p-4">
                <p class="text-red-700 dark:text-red-300">{{ error }}</p>
            </div>

            <!-- Content Views -->
            <template v-else-if="currentStage">
                <!-- Bracket View -->
                <div v-if="showBracketTab" v-show="currentView === 'bracket'"
                     class="bg-white dark:bg-gray-800 rounded-lg shadow h-[600px]">
                    <BracketCanvas
                        :bracket-type="currentStage.type === 'double_elim' ? 'double' : 'single'"
                        :matches="currentStageMatches"
                        :participants="currentStageParticipants"
                        :show-seeding="canEditBracket && isAdmin"
                        @update:match="handleMatchClick"
                        @seed:change="handleSeedChange"
                    />
                </div>

                <!-- Schedule View -->
                <div v-show="currentView === 'schedule'" class="bg-white dark:bg-gray-800 rounded-lg shadow h-[600px]">
                    <ScheduleGrid
                        :matches="currentStageMatches"
                        :participants="participants"
                        :tables="currentTournament?.pool_tables || []"
                        @reschedule="handleReschedule"
                        @auto-schedule="handleAutoSchedule"
                    />
                </div>

                <!-- Standings View -->
                <div v-if="showStandingsTab" v-show="currentView === 'standings'"
                     class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <StandingsTable
                        :show-form-column="true"
                        :show-qualification-info="true"
                        :stage-id="currentStage.id"
                        :tournament-id="tournamentId"
                    />
                </div>

                <!-- Participants View -->
                <div v-show="currentView === 'participants'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold">{{ t('Participants') }} ({{
                                currentStageParticipants.length
                            }})</h2>
                        <div class="flex gap-2">
                            <button
                                v-if="isAdmin && canEditBracket"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                                @click="showSeedingDrawer = true"
                            >
                                <ShuffleIcon class="w-4 h-4 inline-block mr-1"/>
                                {{ t('Manage Seeding') }}
                            </button>
                            <button
                                v-if="isAdmin"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                                @click="showAddParticipant = true"
                            >
                                <UserPlusIcon class="w-4 h-4 inline-block mr-1"/>
                                {{ t('Add Participant') }}
                            </button>
                        </div>
                    </div>

                    <ParticipantsList
                        :editable="isAdmin && canEditBracket"
                        :participants="currentStageParticipants"
                        @remove="handleRemoveParticipant"
                    />
                </div>
            </template>
        </div>

        <!-- Match Details Modal -->
        <Modal
            :show="!!selectedMatch"
            :title="`${t('Match')} #${selectedMatch?.metadata?.match_number || selectedMatch?.id}`"
            @close="selectedMatch = null"
        >
            <MatchCard
                v-if="selectedMatch"
                :editable="isAdmin || isReferee"
                :match="selectedMatch"
                :participants="participants"
                :show-sets="true"
                @walkover="handleWalkover(selectedMatch)"
                @submit-score="handleScoreSubmit"
            />
        </Modal>

        <!-- Seeding Drawer -->
        <SeedingDrawer
            :is-open="showSeedingDrawer"
            :participants="currentStageParticipants"
            @close="showSeedingDrawer = false"
            @seed-change="handleBulkSeedChange"
        />

        <!-- WebSocket Integration -->
        <!--        <TournamentWebSocket
                    :tournament-id="tournamentId"
                    :stage-id="currentStage?.id"
                    :auto-connect="true"
                />-->
    </div>
</template>
