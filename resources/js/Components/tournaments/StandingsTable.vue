<template>
    <div class="w-full">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-8">
            <Spinner class="w-6 h-6 mr-2"/>
            <span class="text-gray-500 dark:text-gray-400">Loading standings...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error"
             class="bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-700 rounded-lg p-4">
            <p class="text-red-700 dark:text-red-300">{{ error }}</p>
        </div>

        <!-- Standings Content -->
        <div v-else-if="standings.length > 0" class="space-y-6">
            <!-- Group Standings -->
            <div v-for="group in standings" :key="group.group"
                 class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ group.group }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                Pos
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                Player
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                P
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                W
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                L
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                Sets
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                +/-
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                Pts
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                Win%
                            </th>
                            <th v-if="showFormColumn" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                scope="col">
                                Form
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="(entry, index) in group.standings"
                            :key="entry.participant.id"
                            :class="getRowClass(entry, index)"
                        >
                            <!-- Position -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                    <span
                        :class="getPositionClass(entry.position)"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold"
                    >
                      {{ entry.position }}
                    </span>
                                    <component
                                        :is="getPositionChangeIcon(entry)"
                                        v-if="getPositionChange(entry)"
                                        :class="getPositionChangeClass(entry)"
                                        class="ml-2 w-4 h-4"
                                    />
                                </div>
                            </td>

                            <!-- Player -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ entry.participant.display_name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span v-if="entry.participant.user?.home_club">
                          {{ entry.participant.user.home_club.name }}
                        </span>
                                            <span v-else-if="entry.participant.team?.club">
                          {{ entry.participant.team.club.name }}
                        </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Played -->
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">
                                {{ entry.matches_played }}
                            </td>

                            <!-- Won -->
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                  <span class="font-medium text-green-600 dark:text-green-400">
                    {{ entry.matches_won }}
                  </span>
                            </td>

                            <!-- Lost -->
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                  <span class="font-medium text-red-600 dark:text-red-400">
                    {{ entry.matches_lost }}
                  </span>
                            </td>

                            <!-- Sets -->
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">
                                {{ entry.sets_won }}-{{ entry.sets_lost }}
                            </td>

                            <!-- Set Difference -->
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                  <span
                      :class="{
                      'text-green-600 dark:text-green-400': entry.sets_difference > 0,
                      'text-red-600 dark:text-red-400': entry.sets_difference < 0,
                      'text-gray-600 dark:text-gray-400': entry.sets_difference === 0
                    }"
                      class="font-medium"
                  >
                    {{ entry.sets_difference > 0 ? '+' : '' }}{{ entry.sets_difference }}
                  </span>
                            </td>

                            <!-- Points -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ entry.points }}
                  </span>
                            </td>

                            <!-- Win Percentage -->
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">
                                {{ entry.win_percentage.toFixed(1) }}%
                            </td>

                            <!-- Form (last 5 matches) -->
                            <td v-if="showFormColumn" class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                    <span
                        v-for="(result, i) in getLastResults(entry)"
                        :key="i"
                        :class="{
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': result === 'W',
                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': result === 'L',
                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': result === 'D'
                      }"
                        class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold"
                    >
                      {{ result }}
                    </span>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Group Legend -->
                <div v-if="showQualificationInfo && stage?.settings?.advance_per_group"
                     class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center gap-4 text-sm">
            <span class="flex items-center gap-2">
              <span class="w-4 h-4 bg-green-500 rounded"></span>
              <span class="text-gray-600 dark:text-gray-400">
                Top {{ stage.settings.advance_per_group }} advance
              </span>
            </span>
                        <span v-if="getEliminationCount() > 0" class="flex items-center gap-2">
              <span class="w-4 h-4 bg-red-500 rounded"></span>
              <span class="text-gray-600 dark:text-gray-400">
                Bottom {{ getEliminationCount() }} eliminated
              </span>
            </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8">
            <TrophyIcon class="mx-auto h-12 w-12 text-gray-400"/>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No standings yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Standings will appear once matches have been played.
            </p>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {computed, onUnmounted, ref, watch} from 'vue';
import type {StandingsEntry} from '@/stores/useTournamentStore';
import {useTournamentStore} from '@/stores/useTournamentStore';
import {ChevronDownIcon, ChevronUpIcon, MinusIcon, TrophyIcon} from 'lucide-vue-next';
import {Spinner} from '@/Components/ui';

// Props
const props = defineProps<{
    tournamentId: number | string;
    stageId: number | string;
    showFormColumn?: boolean;
    showQualificationInfo?: boolean;
}>();

// Store
const store = useTournamentStore();

// State
const standings = ref<Array<{ group: string; standings: StandingsEntry[] }>>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);
const previousStandings = ref<Array<{ group: string; standings: StandingsEntry[] }>>([]);

// Computed
const stage = computed(() => store.getStageById(Number(props.stageId)));

// Methods
async function fetchStandings() {
    isLoading.value = true;
    error.value = null;

    try {
        // Save previous standings for position change calculation
        if (standings.value.length > 0) {
            previousStandings.value = JSON.parse(JSON.stringify(standings.value));
        }

        standings.value = await store.fetchStandings(props.tournamentId, props.stageId);
    } catch (err: any) {
        error.value = err.message || 'Failed to load standings';
    } finally {
        isLoading.value = false;
    }
}

function getRowClass(entry: StandingsEntry, index: number): string {
    const classes: string[] = [];

    // Qualification positions
    if (stage.value?.settings?.advance_per_group && index < stage.value.settings.advance_per_group) {
        classes.push('bg-green-50 dark:bg-green-900/10');
    }

    // Elimination positions
    const eliminationCount = getEliminationCount();
    const totalTeams = standings.value[0]?.standings.length || 0;
    if (eliminationCount > 0 && index >= totalTeams - eliminationCount) {
        classes.push('bg-red-50 dark:bg-red-900/10');
    }

    return classes.join(' ');
}

function getPositionClass(position: number): string {
    switch (position) {
        case 1:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 2:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 3:
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
}

function getPositionChange(entry: StandingsEntry): number {
    if (!previousStandings.value.length) return 0;

    // Find previous position
    for (const group of previousStandings.value) {
        const prevEntry = group.standings.find(s => s.participant.id === entry.participant.id);
        if (prevEntry) {
            return prevEntry.position - entry.position;
        }
    }

    return 0;
}

function getPositionChangeIcon(entry: StandingsEntry) {
    const change = getPositionChange(entry);
    if (change > 0) return ChevronUpIcon;
    if (change < 0) return ChevronDownIcon;
    return MinusIcon;
}

function getPositionChangeClass(entry: StandingsEntry): string {
    const change = getPositionChange(entry);
    if (change > 0) return 'text-green-600 dark:text-green-400';
    if (change < 0) return 'text-red-600 dark:text-red-400';
    return 'text-gray-400';
}

function getLastResults(entry: StandingsEntry): string[] {
    // This would need to be implemented based on match history
    // For now, return empty array
    return [];
}

function getEliminationCount(): number {
    if (!stage.value?.settings) return 0;

    const totalPerGroup = stage.value.settings.players_per_group || 0;
    const advanceCount = stage.value.settings.advance_per_group || 0;

    // Some formats might eliminate bottom players
    if (totalPerGroup > advanceCount + 2) {
        return 1; // Eliminate last place
    }

    return 0;
}

// Watch for changes
watch(() => props.stageId, () => {
    fetchStandings();
});

// Listen for WebSocket updates
window.addEventListener('tournament:standings-updated', fetchStandings);
window.addEventListener('tournament:match-updated', fetchStandings);

// Cleanup
onUnmounted(() => {
    window.removeEventListener('tournament:standings-updated', fetchStandings);
    window.removeEventListener('tournament:match-updated', fetchStandings);
});
</script>

<style scoped>
/* Custom table styles */
@media (max-width: 768px) {
    /* Make table scrollable on mobile */
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }
}
</style>
