<!-- resources/js/Pages/Tournaments/Bracket.vue -->
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
    SelectValue
} from '@/Components/ui';
import {useTournamentStore} from '@/stores/tournament';
import {useLocale} from '@/composables/useLocale';
import BracketNode from '@/Components/Tournament/BracketNode.vue';
import BracketSettings from '@/Components/Tournament/BracketSettings.vue';
import {
    ArrowLeftIcon,
    RefreshCwIcon,
    PlayIcon,
    SettingsIcon
} from 'lucide-vue-next';
import type {BracketNode as BracketNodeType} from '@/types/tournament';

defineOptions({layout: AuthenticatedLayout});

interface Props {
    tournamentId: number;
}

const props = defineProps<Props>();

const {t} = useLocale();
const tournamentStore = useTournamentStore();

// State
const selectedFormat = ref<'single_elimination' | 'double_elimination'>('single_elimination');
const selectedSeeding = ref<'manual' | 'random' | 'rating_based'>('random');
const bestOf = ref(3);
const showSettings = ref(false);
const isGenerating = ref(false);
const isDragging = ref(false);
const draggedPlayer = ref<any>(null);

// Computed
const tournament = computed(() => tournamentStore.currentTournament);
const players = computed(() => tournamentStore.confirmedPlayers);
const bracketRounds = computed(() => tournamentStore.bracketRounds);
const isGenerated = computed(() => bracketRounds.value.length > 0);

const roundLabels = computed(() => {
    const totalRounds = bracketRounds.value.length;
    if (totalRounds === 0) return [];

    const labels = [];
    for (let i = 0; i < totalRounds; i++) {
        if (i === totalRounds - 1) {
            labels.push(t('Final'));
        } else if (i === totalRounds - 2) {
            labels.push(t('Semifinal'));
        } else if (i === totalRounds - 3) {
            labels.push(t('Quarterfinal'));
        } else {
            labels.push(t('Round :round', {round: i + 1}));
        }
    }
    return labels;
});

const canStartTournament = computed(() => {
    return isGenerated.value &&
        bracketRounds.value[0]?.nodes.every(node =>
            node.player_a && node.player_b
        );
});

// Methods
const fetchTournament = async () => {
    try {
        await tournamentStore.fetchTournament(props.tournamentId);
        await tournamentStore.fetchPlayers(props.tournamentId);
    } catch (error) {
        console.error('Failed to fetch tournament:', error);
    }
};

const generateBracket = async () => {
    if (players.value.length < 2) {
        alert(t('At least 2 players are required to generate a bracket'));
        return;
    }

    isGenerating.value = true;

    try {
        const format = {
            type: selectedFormat.value,
            settings: {
                bestOf: bestOf.value,
                lowerBracket: selectedFormat.value === 'double_elimination'
            }
        };

        const seeding = {
            type: selectedSeeding.value
        };

        await tournamentStore.generateBracket(props.tournamentId, format, seeding);
    } catch (error) {
        console.error('Failed to generate bracket:', error);
    } finally {
        isGenerating.value = false;
    }
};

const regenerateBracket = async () => {
    if (!confirm(t('Are you sure? This will reset the entire bracket and any existing match results.'))) {
        return;
    }

    await generateBracket();
};

const handleNodeUpdate = async (nodeId: number, update: Partial<BracketNodeType>) => {
    try {
        await tournamentStore.updateBracketNode(props.tournamentId, nodeId, update);
    } catch (error) {
        console.error('Failed to update bracket node:', error);
    }
};

const handlePlayerDrop = (nodeId: number, slot: 'A' | 'B', player: any) => {
    const update = slot === 'A'
        ? {
            player_a_id: player.user_id,
            player_a: {id: player.user_id, name: `${player.user.firstname} ${player.user.lastname}`}
        }
        : {
            player_b_id: player.user_id,
            player_b: {id: player.user_id, name: `${player.user.firstname} ${player.user.lastname}`}
        };

    handleNodeUpdate(nodeId, update);
};

const startTournament = async () => {
    if (!canStartTournament.value) {
        alert(t('Please ensure all bracket positions are filled before starting the tournament'));
        return;
    }

    if (!confirm(t('Start the tournament? This will lock the bracket and begin match play.'))) {
        return;
    }

    try {
        // Update tournament status to active
        await tournamentStore.updateTournament(props.tournamentId, {status: 'active'});
        router.visit(`/tournaments/${props.tournamentId}/schedule`);
    } catch (error) {
        console.error('Failed to start tournament:', error);
    }
};

const goBack = () => {
    router.visit(`/tournaments/${props.tournamentId}`);
};

// Drag and Drop handlers
const handleDragStart = (player: any) => {
    isDragging.value = true;
    draggedPlayer.value = player;
};

const handleDragEnd = () => {
    isDragging.value = false;
    draggedPlayer.value = null;
};

onMounted(() => {
    fetchTournament();
});
</script>

<template>
    <Head :title="t('Tournament Bracket')"/>

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
                        {{ t('Tournament Bracket') }}
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ tournament?.name }}
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <Button
                        variant="outline"
                        @click="showSettings = true"
                    >
                        <SettingsIcon class="mr-2 h-4 w-4"/>
                        {{ t('Settings') }}
                    </Button>

                    <Button
                        v-if="!isGenerated"
                        :disabled="isGenerating || players.length < 2"
                        @click="generateBracket"
                    >
                        <RefreshCwIcon :class="['mr-2 h-4 w-4', { 'animate-spin': isGenerating }]"/>
                        {{ isGenerating ? t('Generating...') : t('Generate Bracket') }}
                    </Button>

                    <Button
                        v-else
                        :disabled="isGenerating"
                        variant="outline"
                        @click="regenerateBracket"
                    >
                        <RefreshCwIcon class="mr-2 h-4 w-4"/>
                        {{ t('Regenerate') }}
                    </Button>

                    <Button
                        v-if="isGenerated"
                        :disabled="!canStartTournament"
                        class="bg-green-600 hover:bg-green-700"
                        @click="startTournament"
                    >
                        <PlayIcon class="mr-2 h-4 w-4"/>
                        {{ t('Start Tournament') }}
                    </Button>
                </div>
            </div>

            <!-- Tournament Info -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ players.length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ t('Registered Players') }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ bracketRounds.length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ t('Tournament Rounds') }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                            {{ selectedFormat === 'single_elimination' ? t('Single') : t('Double') }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ t('Elimination Format') }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6 text-center">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                            {{ bestOf }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ t('Best of :number', {number: bestOf}) }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pre-generation Setup -->
            <div v-if="!isGenerated" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Bracket Configuration') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('Format') }}</label>
                                <Select v-model="selectedFormat">
                                    <SelectTrigger>
                                        <SelectValue/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="single_elimination">{{
                                                t('Single Elimination')
                                            }}
                                        </SelectItem>
                                        <SelectItem value="double_elimination">{{
                                                t('Double Elimination')
                                            }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('Seeding') }}</label>
                                <Select v-model="selectedSeeding">
                                    <SelectTrigger>
                                        <SelectValue/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="random">{{ t('Random') }}</SelectItem>
                                        <SelectItem value="rating_based">{{ t('Rating Based') }}</SelectItem>
                                        <SelectItem value="manual">{{ t('Manual') }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('Best of') }}</label>
                                <Select v-model="bestOf">
                                    <SelectTrigger>
                                        <SelectValue/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="1">{{ t('Best of 1') }}</SelectItem>
                                        <SelectItem :value="3">{{ t('Best of 3') }}</SelectItem>
                                        <SelectItem :value="5">{{ t('Best of 5') }}</SelectItem>
                                        <SelectItem :value="7">{{ t('Best of 7') }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="text-center">
                            <Button
                                :disabled="isGenerating || players.length < 2"
                                size="lg"
                                @click="generateBracket"
                            >
                                <RefreshCwIcon :class="['mr-2 h-5 w-5', { 'animate-spin': isGenerating }]"/>
                                {{ isGenerating ? t('Generating Bracket...') : t('Generate Tournament Bracket') }}
                            </Button>

                            <p v-if="players.length < 2" class="mt-4 text-sm text-red-600 dark:text-red-400">
                                {{ t('At least 2 players are required to generate a bracket') }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Player List for Pre-generation -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Registered Players') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="players.length === 0" class="text-center py-8 text-gray-500">
                            <p>{{ t('No players registered yet') }}</p>
                            <Button
                                class="mt-4"
                                variant="outline"
                                @click="router.visit(`/tournaments/${props.tournamentId}/players`)"
                            >
                                {{ t('Manage Players') }}
                            </Button>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="player in players"
                                :key="player.id"
                                class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                draggable="true"
                                @dragend="handleDragEnd"
                                @dragstart="handleDragStart(player)"
                            >
                                <div class="text-sm font-medium">
                                    {{ player.user?.firstname }} {{ player.user?.lastname }}
                                </div>
                                <div v-if="player.user?.rating" class="text-xs text-blue-600 dark:text-blue-400">
                                    {{ player.user.rating }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Generated Bracket -->
            <div v-else class="space-y-8">
                <!-- Bracket Visualization -->
                <div class="overflow-x-auto">
                    <div class="min-w-max space-y-8">
                        <div
                            v-for="(round, roundIndex) in bracketRounds"
                            :key="round.round"
                            class="space-y-4"
                        >
                            <!-- Round Header -->
                            <div class="text-center">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ roundLabels[roundIndex] }}
                                </h3>
                                <p class="text-sm text-gray-500">{{ t('Round :number', {number: round.round}) }}</p>
                            </div>

                            <!-- Round Matches -->
                            <div class="flex justify-center">
                                <div :style="{ gridTemplateColumns: `repeat(${Math.ceil(round.nodes.length / 2)}, 1fr)` }"
                                     class="grid gap-6">
                                    <BracketNode
                                        v-for="node in round.nodes"
                                        :key="node.id"
                                        :best-of="bestOf"
                                        :dragging="isDragging"
                                        :node="node"
                                        @update="handleNodeUpdate"
                                        @player-drop="handlePlayerDrop"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unassigned Players (for manual seeding) -->
                <Card v-if="selectedSeeding === 'manual'" class="border-dashed">
                    <CardHeader>
                        <CardTitle>{{ t('Available Players') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            <div
                                v-for="player in players"
                                :key="player.id"
                                class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-dashed border-blue-300 dark:border-blue-700 cursor-move"
                                draggable="true"
                                @dragend="handleDragEnd"
                                @dragstart="handleDragStart(player)"
                            >
                                <div class="text-sm font-medium text-center">
                                    {{ player.user?.firstname }} {{ player.user?.lastname }}
                                </div>
                                <div v-if="player.user?.rating"
                                     class="text-xs text-blue-600 dark:text-blue-400 text-center mt-1">
                                    {{ player.user.rating }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            <p>{{ t('Drag and drop players into bracket positions') }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Bracket Settings Modal -->
            <BracketSettings
                :best-of="bestOf"
                :format="selectedFormat"
                :seeding="selectedSeeding"
                :show="showSettings"
                @close="showSettings = false"
                @update:format="selectedFormat = $event"
                @update:seeding="selectedSeeding = $event"
                @update:best-of="bestOf = $event"
            />
        </div>
    </div>
</template>

<style scoped>
/* Custom drag styles */
.dragging {
    opacity: 0.5;
}

/* Bracket connector lines - basic implementation */
.bracket-connector {
    position: relative;
}

.bracket-connector::after {
    content: '';
    position: absolute;
    top: 50%;
    right: -20px;
    width: 40px;
    height: 2px;
    background-color: #d1d5db;
}

.dark .bracket-connector::after {
    background-color: #374151;
}
</style>
