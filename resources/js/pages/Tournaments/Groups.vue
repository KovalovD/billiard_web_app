<!-- resources/js/Pages/Tournaments/Groups.vue -->
<script lang="ts" setup>
import {ref, computed, onMounted} from 'vue';
import {Head, router} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Button,
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Modal
} from '@/Components/ui';
import {useTournamentStore} from '@/stores/tournament';
import {useLocale} from '@/composables/useLocale';
import GroupStandingsTable from '@/Components/Tournament/GroupStandingsTable.vue';
import GroupMatchGenerator from '@/Components/Tournament/GroupMatchGenerator.vue';
import PlayoffAdvancement from '@/Components/Tournament/PlayoffAdvancement.vue';
import {
    ArrowLeftIcon,
    UsersIcon,
    ShuffleIcon,
    PlayIcon,
    TrophyIcon,
    SettingsIcon
} from 'lucide-vue-next';
import type {Group, GroupStanding} from '@/types/tournament';

defineOptions({layout: AuthenticatedLayout});

interface Props {
    tournamentId: number;
}

const props = defineProps<Props>();

const {t} = useLocale();
const tournamentStore = useTournamentStore();

// State
const activeTab = ref<'groups' | 'matches' | 'standings' | 'playoffs'>('groups');
const groupCount = ref(4);
const selectedFormat = ref<'round_robin' | 'swiss'>('round_robin');
const showCreateGroupsModal = ref(false);
const showAdvancementModal = ref(false);
const isGenerating = ref(false);

// Computed
const tournament = computed(() => tournamentStore.currentTournament);
const players = computed(() => tournamentStore.confirmedPlayers);
const groups = computed(() => tournamentStore.groupStandings);
const matches = computed(() => tournamentStore.scheduledMatches);

const hasGroups = computed(() => groups.value.length > 0);
const canCreateGroups = computed(() => players.value.length >= groupCount.value * 2);
const playersPerGroup = computed(() => Math.floor(players.value.length / groupCount.value));

const groupProgress = computed(() => {
    if (!hasGroups.value) return 0;

    const totalMatches = groups.value.reduce((sum, group) => {
        const groupSize = group.standings.length;
        return sum + (groupSize * (groupSize - 1) / 2); // Round robin matches per group
    }, 0);

    const completedMatches = matches.value.filter(m => m.status === 'completed').length;
    return totalMatches > 0 ? Math.round((completedMatches / totalMatches) * 100) : 0;
});

const qualifiedPlayers = computed(() => {
    return groups.value.flatMap(group =>
        group.standings.filter(s => s.qualified).map(s => s.player)
    );
});

// Methods
const fetchTournament = async () => {
    try {
        await tournamentStore.fetchTournament(props.tournamentId);
        await tournamentStore.fetchPlayers(props.tournamentId);

        // Load existing groups if any
        if (tournament.value?.status === 'active') {
            // Mock loading existing groups
            await loadExistingGroups();
        }
    } catch (error) {
        console.error('Failed to fetch tournament:', error);
    }
};

const loadExistingGroups = async () => {
    // Mock existing groups data
    const mockGroups = [
        {
            id: 1,
            name: 'Group A',
            tournament_id: props.tournamentId,
            players: players.value.slice(0, playersPerGroup.value),
            advance_count: 2,
            standings: players.value.slice(0, playersPerGroup.value).map((player, index) => ({
                id: index + 1,
                group_id: 1,
                player_id: player.user_id,
                player,
                matches_played: Math.floor(Math.random() * 3),
                wins: Math.floor(Math.random() * 3),
                losses: Math.floor(Math.random() * 2),
                draws: 0,
                frames_won: Math.floor(Math.random() * 10),
                frames_lost: Math.floor(Math.random() * 8),
                frame_difference: Math.floor(Math.random() * 4) - 2,
                points: Math.floor(Math.random() * 9),
                position: index + 1,
                qualified: index < 2
            }))
        }
    ];

    // This would normally come from the store/API
    console.log('Loaded existing groups:', mockGroups);
};

const createGroups = async () => {
    if (!canCreateGroups.value) {
        alert(t('Not enough players to create :count groups', {count: groupCount.value}));
        return;
    }

    isGenerating.value = true;

    try {
        await tournamentStore.createGroups(props.tournamentId, groupCount.value);
        showCreateGroupsModal.value = false;
        activeTab.value = 'standings';
    } catch (error) {
        console.error('Failed to create groups:', error);
    } finally {
        isGenerating.value = false;
    }
};

const redistributePlayers = async () => {
    if (!confirm(t('Redistribute all players into new balanced groups? This will reset all group progress.'))) {
        return;
    }

    isGenerating.value = true;

    try {
        await tournamentStore.createGroups(props.tournamentId, groupCount.value);
    } catch (error) {
        console.error('Failed to redistribute players:', error);
    } finally {
        isGenerating.value = false;
    }
};

const generateGroupMatches = async (groupId: number) => {
    try {
        await tournamentStore.generateGroupMatches(props.tournamentId, groupId, selectedFormat.value);
    } catch (error) {
        console.error('Failed to generate group matches:', error);
    }
};

const startPlayoffs = async () => {
    if (qualifiedPlayers.value.length === 0) {
        alert(t('No players have qualified for playoffs yet'));
        return;
    }

    if (!confirm(t('Start playoff bracket with qualified players? This will lock group stage results.'))) {
        return;
    }

    try {
        // Generate playoff bracket with qualified players
        const playoffFormat = {
            type: 'single_elimination' as const,
            settings: {
                bestOf: 5,
                lowerBracket: false
            }
        };

        const seeding = {
            type: 'rating_based' as const
        };

        await tournamentStore.generateBracket(props.tournamentId, playoffFormat, seeding);
        router.visit(`/tournaments/${props.tournamentId}/bracket`);
    } catch (error) {
        console.error('Failed to start playoffs:', error);
    }
};

const goBack = () => {
    router.visit(`/tournaments/${props.tournamentId}`);
};

onMounted(() => {
    fetchTournament();
});
</script>

<template>
    <Head :title="t('Group Stage')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
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
                        {{ t('Group Stage') }}
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ tournament?.name }}
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <Button
                        v-if="!hasGroups"
                        :disabled="!canCreateGroups"
                        @click="showCreateGroupsModal = true"
                    >
                        <UsersIcon class="mr-2 h-4 w-4"/>
                        {{ t('Create Groups') }}
                    </Button>

                    <Button
                        v-else
                        :disabled="isGenerating"
                        variant="outline"
                        @click="redistributePlayers"
                    >
                        <ShuffleIcon class="mr-2 h-4 w-4"/>
                        {{ t('Redistribute') }}
                    </Button>

                    <Button
                        v-if="hasGroups && qualifiedPlayers.length > 0"
                        class="bg-green-600 hover:bg-green-700"
                        @click="startPlayoffs"
                    >
                        <TrophyIcon class="mr-2 h-4 w-4"/>
                        {{ t('Start Playoffs') }}
                    </Button>
                </div>
            </div>

            <!-- Progress Overview -->
            <div v-if="hasGroups" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ groups.length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Groups') }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ groupProgress }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Progress') }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                            {{ matches.filter(m => m.status === 'completed').length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Completed Matches') }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                            {{ qualifiedPlayers.length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Qualified') }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tab Navigation -->
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8">
                    <button
                        :class="[
              'py-4 px-1 text-sm font-medium border-b-2',
              activeTab === 'groups'
                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
                        @click="activeTab = 'groups'"
                    >
                        <UsersIcon class="mr-2 h-4 w-4 inline"/>
                        {{ t('Groups Setup') }}
                    </button>

                    <button
                        v-if="hasGroups"
                        :class="[
              'py-4 px-1 text-sm font-medium border-b-2',
              activeTab === 'matches'
                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
                        @click="activeTab = 'matches'"
                    >
                        <PlayIcon class="mr-2 h-4 w-4 inline"/>
                        {{ t('Matches') }}
                    </button>

                    <button
                        v-if="hasGroups"
                        :class="[
              'py-4 px-1 text-sm font-medium border-b-2',
              activeTab === 'standings'
                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
                        @click="activeTab = 'standings'"
                    >
                        <TrophyIcon class="mr-2 h-4 w-4 inline"/>
                        {{ t('Standings') }}
                    </button>

                    <button
                        v-if="hasGroups && qualifiedPlayers.length > 0"
                        :class="[
              'py-4 px-1 text-sm font-medium border-b-2',
              activeTab === 'playoffs'
                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
                        @click="activeTab = 'playoffs'"
                    >
                        <SettingsIcon class="mr-2 h-4 w-4 inline"/>
                        {{ t('Playoff Setup') }}
                    </button>
                </nav>
            </div>

            <!-- Content -->
            <div>
                <!-- Groups Setup Tab -->
                <div v-if="activeTab === 'groups'">
                    <div v-if="!hasGroups" class="space-y-6">
                        <!-- Setup Instructions -->
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ t('Group Stage Setup') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <p class="text-gray-600 dark:text-gray-400">
                                        {{
                                            t('Create groups for round-robin play. Players will compete within their groups before advancing to playoffs.')
                                        }}
                                    </p>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h4 class="font-medium mb-2">{{ t('Current Setup') }}</h4>
                                            <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                                <li>{{ t('Total Players') }}: {{ players.length }}</li>
                                                <li>{{ t('Planned Groups') }}: {{ groupCount }}</li>
                                                <li>{{ t('Players per Group') }}: {{ playersPerGroup }}</li>
                                                <li>{{ t('Format') }}: {{
                                                        selectedFormat === 'round_robin' ? t('Round Robin') : t('Swiss System')
                                                    }}
                                                </li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="font-medium mb-2">{{ t('Matches per Group') }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{
                                                    t('Each group will have :count matches', {count: playersPerGroup * (playersPerGroup - 1) / 2})
                                                }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                {{ t('Total tournament matches') }}:
                                                {{ groupCount * playersPerGroup * (playersPerGroup - 1) / 2 }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <Button
                                            :disabled="!canCreateGroups"
                                            size="lg"
                                            @click="showCreateGroupsModal = true"
                                        >
                                            <UsersIcon class="mr-2 h-5 w-5"/>
                                            {{ t('Create Groups') }}
                                        </Button>

                                        <p v-if="!canCreateGroups" class="mt-4 text-sm text-red-600 dark:text-red-400">
                                            {{
                                                t('Need at least :count players to create :groups groups', {
                                                    count: groupCount * 2,
                                                    groups: groupCount
                                                })
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div v-else>
                        <!-- Groups Overview -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Card
                                v-for="group in groups"
                                :key="group.id"
                                class="border-2 border-blue-200 dark:border-blue-800"
                            >
                                <CardHeader>
                                    <CardTitle>{{ group.name }}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-3">
                                        <div
                                            v-for="standing in group.standings.slice(0, 3)"
                                            :key="standing.id"
                                            class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs font-medium text-gray-600">{{
                                                        standing.position
                                                    }}</span>
                                                <span class="text-sm">{{
                                                        standing.player.user?.firstname
                                                    }} {{ standing.player.user?.lastname }}</span>
                                            </div>
                                            <span class="text-xs text-gray-500">{{ standing.points }}pts</span>
                                        </div>

                                        <Button
                                            class="w-full"
                                            size="sm"
                                            variant="outline"
                                            @click="generateGroupMatches(group.id)"
                                        >
                                            {{ t('Generate Matches') }}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>

                <!-- Matches Tab -->
                <div v-if="activeTab === 'matches' && hasGroups">
                    <GroupMatchGenerator
                        :format="selectedFormat"
                        :groups="groups"
                        @matches-generated="fetchTournament"
                    />
                </div>

                <!-- Standings Tab -->
                <div v-if="activeTab === 'standings' && hasGroups">
                    <div class="space-y-6">
                        <GroupStandingsTable
                            v-for="group in groups"
                            :key="group.id"
                            :group="group"
                            @update-standings="fetchTournament"
                        />
                    </div>
                </div>

                <!-- Playoffs Tab -->
                <div v-if="activeTab === 'playoffs' && hasGroups">
                    <PlayoffAdvancement
                        :groups="groups"
                        :qualified-players="qualifiedPlayers"
                        @start-playoffs="startPlayoffs"
                    />
                </div>
            </div>

            <!-- Create Groups Modal -->
            <Modal
                :show="showCreateGroupsModal"
                :title="t('Create Tournament Groups')"
                @close="showCreateGroupsModal = false"
            >
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">{{ t('Number of Groups') }}</label>
                        <Select v-model="groupCount">
                            <SelectTrigger>
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="2">2 {{ t('groups') }}</SelectItem>
                                <SelectItem :value="4">4 {{ t('groups') }}</SelectItem>
                                <SelectItem :value="6">6 {{ t('groups') }}</SelectItem>
                                <SelectItem :value="8">8 {{ t('groups') }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">{{ t('Group Format') }}</label>
                        <Select v-model="selectedFormat">
                            <SelectTrigger>
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="round_robin">{{ t('Round Robin') }}</SelectItem>
                                <SelectItem value="swiss">{{ t('Swiss System') }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>{{ t('Players per group') }}: {{ playersPerGroup }}</p>
                        <p>{{ t('Matches per group') }}: {{ playersPerGroup * (playersPerGroup - 1) / 2 }}</p>
                        <p>{{ t('Total matches') }}: {{ groupCount * playersPerGroup * (playersPerGroup - 1) / 2 }}</p>
                    </div>
                </div>

                <template #footer>
                    <Button variant="outline" @click="showCreateGroupsModal = false">
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        :disabled="!canCreateGroups || isGenerating"
                        @click="createGroups"
                    >
                        <UsersIcon v-if="!isGenerating" class="mr-2 h-4 w-4"/>
                        <div v-else
                             class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                        {{ isGenerating ? t('Creating...') : t('Create Groups') }}
                    </Button>
                </template>
            </Modal>
        </div>
    </div>
</template>
