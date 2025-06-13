<!-- resources/js/Components/Tournament/TeamBuilder.vue -->
<script lang="ts" setup>
import {ref, computed, reactive} from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button,
    Input,
    Label,
    Modal
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {
    UsersIcon,
    PlusIcon,
    EditIcon,
    TrashIcon,
    CrownIcon,
    ShuffleIcon
} from 'lucide-vue-next';
import type {Tournament, TournamentPlayer, Team} from '@/types/api';

interface Props {
    tournament: Tournament | null;
    players: TournamentPlayer[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'teams-updated': [];
}>();

const {t} = useLocale();

// State
const teams = ref<Team[]>([]);
const unassignedPlayers = ref<TournamentPlayer[]>([]);
const showCreateTeamModal = ref(false);
const showEditTeamModal = ref(false);
const editingTeam = ref<Team | null>(null);

// Team creation/edit form
const teamForm = reactive({
    name: '',
    captain_id: null as number | null
});

// Computed
const teamSize = computed(() => props.tournament?.teams?.team_size || 2);
const maxTeams = computed(() => props.tournament?.teams?.max_teams || 16);

const canCreateTeam = computed(() => {
    return teams.value.length < maxTeams.value &&
        unassignedPlayers.value.length >= teamSize.value;
});

const teamsNeeded = computed(() => {
    return Math.ceil(props.players.length / teamSize.value);
});

const playersPerTeam = computed(() => {
    return Math.floor(props.players.length / teamsNeeded.value);
});

// Methods
const initializeTeams = () => {
    // Mock teams data - in real app this would come from API
    teams.value = [
        {
            id: 1,
            tournament_id: props.tournament?.id || 1,
            name: 'Team Alpha',
            players: props.players.slice(0, teamSize.value),
            captain_id: props.players[0]?.user_id,
            seed_position: 1,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        }
    ];

    unassignedPlayers.value = props.players.slice(teamSize.value);
};

const createTeam = async () => {
    if (!teamForm.name.trim() || unassignedPlayers.value.length < teamSize.value) {
        return;
    }

    const selectedPlayers = unassignedPlayers.value.slice(0, teamSize.value);

    const newTeam: Team = {
        id: Date.now(),
        tournament_id: props.tournament?.id || 1,
        name: teamForm.name,
        players: selectedPlayers,
        captain_id: teamForm.captain_id || selectedPlayers[0]?.user_id,
        seed_position: teams.value.length + 1,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    };

    teams.value.push(newTeam);
    unassignedPlayers.value = unassignedPlayers.value.slice(teamSize.value);

    resetTeamForm();
    showCreateTeamModal.value = false;
    emit('teams-updated');
};

const editTeam = (team: Team) => {
    editingTeam.value = team;
    teamForm.name = team.name;
    teamForm.captain_id = team.captain_id || null;
    showEditTeamModal.value = true;
};

const updateTeam = async () => {
    if (!editingTeam.value || !teamForm.name.trim()) {
        return;
    }

    const teamIndex = teams.value.findIndex(t => t.id === editingTeam.value!.id);
    if (teamIndex !== -1) {
        teams.value[teamIndex] = {
            ...teams.value[teamIndex],
            name: teamForm.name,
            captain_id: teamForm.captain_id,
            updated_at: new Date().toISOString()
        };
    }

    resetTeamForm();
    showEditTeamModal.value = false;
    editingTeam.value = null;
    emit('teams-updated');
};

const deleteTeam = async (teamId: number) => {
    if (!confirm(t('Are you sure you want to delete this team? Players will become unassigned.'))) {
        return;
    }

    const team = teams.value.find(t => t.id === teamId);
    if (team) {
        unassignedPlayers.value.push(...team.players);
        teams.value = teams.value.filter(t => t.id !== teamId);
        emit('teams-updated');
    }
};

const autoCreateTeams = async () => {
    if (!confirm(t('Automatically create balanced teams? This will replace all existing teams.'))) {
        return;
    }

    teams.value = [];
    unassignedPlayers.value = [...props.players];

    // Sort players by rating for balanced teams
    const sortedPlayers = [...props.players].sort((a, b) => {
        const ratingA = a.user?.rating || 1500;
        const ratingB = b.user?.rating || 1500;
        return ratingB - ratingA;
    });

    // Distribute players in snake draft pattern
    for (let i = 0; i < teamsNeeded.value; i++) {
        const teamPlayers: TournamentPlayer[] = [];

        // Snake draft: 1,2,3,4 then 4,3,2,1, then 1,2,3,4...
        for (let round = 0; round < teamSize.value; round++) {
            const pickOrder = round % 2 === 0 ? i : (teamsNeeded.value - 1 - i);
            const playerIndex = round * teamsNeeded.value + pickOrder;

            if (playerIndex < sortedPlayers.length) {
                teamPlayers.push(sortedPlayers[playerIndex]);
            }
        }

        if (teamPlayers.length > 0) {
            const newTeam: Team = {
                id: Date.now() + i,
                tournament_id: props.tournament?.id || 1,
                name: `Team ${String.fromCharCode(65 + i)}`, // Team A, B, C...
                players: teamPlayers,
                captain_id: teamPlayers[0]?.user_id,
                seed_position: i + 1,
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString()
            };

            teams.value.push(newTeam);
        }
    }

    // Update unassigned players
    const assignedPlayerIds = new Set(teams.value.flatMap(team => team.players.map(p => p.user_id)));
    unassignedPlayers.value = props.players.filter(p => !assignedPlayerIds.has(p.user_id));

    emit('teams-updated');
};

const movePlayerToTeam = (playerId: number, fromTeamId: number | null, toTeamId: number | null) => {
    // Implementation for drag & drop would go here
    console.log('Move player', playerId, 'from', fromTeamId, 'to', toTeamId);
};

const resetTeamForm = () => {
    teamForm.name = '';
    teamForm.captain_id = null;
};

const getPlayerRating = (player: TournamentPlayer): number => {
    return player.user?.rating || 1500;
};

const getTeamAverageRating = (team: Team): number => {
    const totalRating = team.players.reduce((sum, player) => sum + getPlayerRating(player), 0);
    return Math.round(totalRating / team.players.length);
};

// Initialize on mount
initializeTeams();
</script>

<template>
    <div class="space-y-6">
        <!-- Team Management Header -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle class="flex items-center gap-2">
                        <UsersIcon class="h-5 w-5"/>
                        {{ t('Team Management') }}
                    </CardTitle>

                    <div class="flex items-center space-x-2">
                        <Button
                            :disabled="props.players.length < teamSize * 2"
                            variant="outline"
                            @click="autoCreateTeams"
                        >
                            <ShuffleIcon class="mr-2 h-4 w-4"/>
                            {{ t('Auto Create Teams') }}
                        </Button>

                        <Button
                            :disabled="!canCreateTeam"
                            @click="showCreateTeamModal = true"
                        >
                            <PlusIcon class="mr-2 h-4 w-4"/>
                            {{ t('Create Team') }}
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <!-- Team Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ teams.length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Teams Created') }}</div>
                    </div>

                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ props.players.length - unassignedPlayers.length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Assigned Players') }}</div>
                    </div>

                    <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ unassignedPlayers.length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Unassigned') }}</div>
                    </div>

                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ teamSize }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Players per Team') }}</div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Teams Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Existing Teams -->
            <Card
                v-for="team in teams"
                :key="team.id"
                class="border-2 border-blue-200 dark:border-blue-800"
            >
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ team.name }}</h3>
                            <span
                                class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded dark:bg-blue-900/30 dark:text-blue-300">
                #{{ team.seed_position }}
              </span>
                        </div>

                        <div class="flex items-center space-x-1">
                            <Button size="sm" variant="ghost" @click="editTeam(team)">
                                <EditIcon class="h-4 w-4"/>
                            </Button>
                            <Button
                                class="text-red-600 hover:text-red-700"
                                size="sm"
                                variant="ghost"
                                @click="deleteTeam(team.id)"
                            >
                                <TrashIcon class="h-4 w-4"/>
                            </Button>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ t('Avg Rating') }}: {{ getTeamAverageRating(team) }}
                    </div>
                </CardHeader>
                <CardContent class="pt-0">
                    <div class="space-y-2">
                        <div
                            v-for="player in team.players"
                            :key="player.id"
                            class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded"
                        >
                            <div class="flex items-center space-x-2">
                                <CrownIcon
                                    v-if="player.user_id === team.captain_id"
                                    class="h-3 w-3 text-yellow-500"
                                />
                                <span class="text-sm font-medium">
                  {{ player.user?.firstname }} {{ player.user?.lastname }}
                </span>
                            </div>
                            <span class="text-xs text-gray-500">
                {{ getPlayerRating(player) }}
              </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Unassigned Players -->
            <Card v-if="unassignedPlayers.length > 0"
                  class="border-2 border-dashed border-gray-300 dark:border-gray-600">
                <CardHeader>
                    <CardTitle class="text-gray-600 dark:text-gray-400">
                        {{ t('Unassigned Players') }} ({{ unassignedPlayers.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div
                            v-for="player in unassignedPlayers"
                            :key="player.id"
                            class="flex items-center justify-between p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded"
                        >
              <span class="text-sm font-medium">
                {{ player.user?.firstname }} {{ player.user?.lastname }}
              </span>
                            <span class="text-xs text-gray-500">
                {{ getPlayerRating(player) }}
              </span>
                        </div>
                    </div>

                    <Button
                        v-if="unassignedPlayers.length >= teamSize"
                        class="w-full mt-3"
                        size="sm"
                        @click="showCreateTeamModal = true"
                    >
                        <PlusIcon class="mr-2 h-3 w-3"/>
                        {{ t('Create Team from These') }}
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Create Team Modal -->
        <Modal
            :show="showCreateTeamModal"
            :title="t('Create New Team')"
            @close="showCreateTeamModal = false"
        >
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="team_name">{{ t('Team Name') }} *</Label>
                    <Input
                        id="team_name"
                        v-model="teamForm.name"
                        :placeholder="t('Enter team name')"
                        required
                    />
                </div>

                <div class="space-y-2">
                    <Label for="captain">{{ t('Team Captain') }}</Label>
                    <select
                        id="captain"
                        v-model="teamForm.captain_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2"
                    >
                        <option value="">{{ t('Select captain') }}</option>
                        <option
                            v-for="player in unassignedPlayers.slice(0, teamSize)"
                            :key="player.user_id"
                            :value="player.user_id"
                        >
                            {{ player.user?.firstname }} {{ player.user?.lastname }}
                        </option>
                    </select>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p>{{ t('Players to be assigned') }}:</p>
                    <ul class="mt-2 space-y-1">
                        <li
                            v-for="player in unassignedPlayers.slice(0, teamSize)"
                            :key="player.user_id"
                            class="flex items-center justify-between"
                        >
                            <span>{{ player.user?.firstname }} {{ player.user?.lastname }}</span>
                            <span class="text-xs">{{ getPlayerRating(player) }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <template #footer>
                <Button variant="outline" @click="showCreateTeamModal = false">
                    {{ t('Cancel') }}
                </Button>
                <Button :disabled="!teamForm.name.trim()" @click="createTeam">
                    {{ t('Create Team') }}
                </Button>
            </template>
        </Modal>

        <!-- Edit Team Modal -->
        <Modal
            :show="showEditTeamModal"
            :title="t('Edit Team')"
            @close="showEditTeamModal = false; editingTeam = null"
        >
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="edit_team_name">{{ t('Team Name') }} *</Label>
                    <Input
                        id="edit_team_name"
                        v-model="teamForm.name"
                        :placeholder="t('Enter team name')"
                        required
                    />
                </div>

                <div class="space-y-2">
                    <Label for="edit_captain">{{ t('Team Captain') }}</Label>
                    <select
                        id="edit_captain"
                        v-model="teamForm.captain_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2"
                    >
                        <option value="">{{ t('Select captain') }}</option>
                        <option
                            v-for="player in editingTeam?.players || []"
                            :key="player.user_id"
                            :value="player.user_id"
                        >
                            {{ player.user?.firstname }} {{ player.user?.lastname }}
                        </option>
                    </select>
                </div>
            </div>

            <template #footer>
                <Button variant="outline" @click="showEditTeamModal = false; editingTeam = null">
                    {{ t('Cancel') }}
                </Button>
                <Button :disabled="!teamForm.name.trim()" @click="updateTeam">
                    {{ t('Update Team') }}
                </Button>
            </template>
        </Modal>
    </div>
</template>
