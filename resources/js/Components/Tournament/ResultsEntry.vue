<!-- resources/js/Components/Tournament/ResultsEntry.vue -->
<script lang="ts" setup>
import {ref, computed, reactive} from 'vue';
import {
    Button,
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Modal,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {
    PlayIcon,
    PlusIcon,
    TrashIcon,
    SaveIcon,
    TrophyIcon
} from 'lucide-vue-next';
import type {Tournament, MatchSchedule} from '@/types/tournament';

interface Props {
    tournament: Tournament | null;
    matches: MatchSchedule[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'result-submitted': [matchId: number, result: any];
}>();

const {t} = useLocale();

// State
const selectedMatch = ref<MatchSchedule | null>(null);
const showResultModal = ref(false);
const isSubmitting = ref(false);

// Result form
const resultForm = reactive({
    scoreA: 0,
    scoreB: 0,
    frames: [] as Array<{ frameNumber: number; winner: 'A' | 'B'; breakPoints?: number; duration?: number }>,
    notes: '',
    duration: ''
});

// Computed
const pendingMatches = computed(() => {
    return props.matches.filter(match =>
        match.status === 'scheduled' || match.status === 'in_progress'
    );
});

const completedMatches = computed(() => {
    return props.matches.filter(match => match.status === 'completed');
});

const bestOf = computed(() => {
    // Would come from tournament settings
    return 5;
});

const framesToWin = computed(() => {
    return Math.ceil(bestOf.value / 2);
});

const currentWinner = computed(() => {
    if (resultForm.scoreA >= framesToWin.value) return 'A';
    if (resultForm.scoreB >= framesToWin.value) return 'B';
    return null;
});

const isValidResult = computed(() => {
    return (resultForm.scoreA >= framesToWin.value || resultForm.scoreB >= framesToWin.value) &&
        (resultForm.scoreA !== resultForm.scoreB) &&
        resultForm.frames.length === (resultForm.scoreA + resultForm.scoreB);
});

// Methods
const openResultEntry = (match: MatchSchedule) => {
    selectedMatch.value = match;
    resetForm();
    showResultModal.value = true;
};

const resetForm = () => {
    resultForm.scoreA = 0;
    resultForm.scoreB = 0;
    resultForm.frames = [];
    resultForm.notes = '';
    resultForm.duration = '';
};

const addFrame = (winner: 'A' | 'B') => {
    const frameNumber = resultForm.frames.length + 1;
    resultForm.frames.push({
        frameNumber,
        winner,
        breakPoints: 0,
        duration: 0
    });

    // Update scores
    if (winner === 'A') {
        resultForm.scoreA++;
    } else {
        resultForm.scoreB++;
    }
};

const removeFrame = (index: number) => {
    const removedFrame = resultForm.frames[index];
    resultForm.frames.splice(index, 1);

    // Update scores
    if (removedFrame.winner === 'A') {
        resultForm.scoreA--;
    } else {
        resultForm.scoreB--;
    }

    // Renumber remaining frames
    resultForm.frames.forEach((frame, i) => {
        frame.frameNumber = i + 1;
    });
};

const quickSetScore = (scoreA: number, scoreB: number) => {
    resultForm.scoreA = scoreA;
    resultForm.scoreB = scoreB;
    resultForm.frames = [];

    // Generate frames based on score
    for (let i = 0; i < scoreA; i++) {
        resultForm.frames.push({
            frameNumber: i + 1,
            winner: 'A'
        });
    }

    for (let i = 0; i < scoreB; i++) {
        resultForm.frames.push({
            frameNumber: scoreA + i + 1,
            winner: 'B'
        });
    }
};

const submitResult = async () => {
    if (!selectedMatch.value || !isValidResult.value) {
        return;
    }

    isSubmitting.value = true;

    try {
        const result = {
            scoreA: resultForm.scoreA,
            scoreB: resultForm.scoreB,
            frames: resultForm.frames,
            notes: resultForm.notes,
            duration: resultForm.duration
        };

        emit('result-submitted', selectedMatch.value.id, result);
        showResultModal.value = false;
        resetForm();
    } catch (error) {
        console.error('Failed to submit result:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const getPlayerName = (player: any): string => {
    if (!player) return t('TBD');
    return player.name || `${player.firstname || ''} ${player.lastname || ''}`.trim() || t('Unknown Player');
};

const formatMatchTime = (dateString: string): string => {
    return new Date(dateString).toLocaleString();
};

// Quick score options for common results
const quickScoreOptions = computed(() => {
    const options = [];
    for (let winner = framesToWin.value; winner <= bestOf.value; winner++) {
        for (let loser = 0; loser < framesToWin.value; loser++) {
            options.push({scoreA: winner, scoreB: loser, label: `${winner}-${loser}`});
            if (winner !== loser) {
                options.push({scoreA: loser, scoreB: winner, label: `${loser}-${winner}`});
            }
        }
    }
    return options.slice(0, 10); // Limit to most common scores
});
</script>

<template>
    <div class="space-y-6">
        <!-- Pending Matches -->
        <Card v-if="pendingMatches.length > 0">
            <CardHeader>
                <CardTitle>{{ t('Matches Awaiting Results') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="match in pendingMatches"
                        :key="match.id"
                        class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                    >
                        <div class="flex-1">
                            <div class="flex items-center space-x-4">
                                <div class="text-sm font-medium">
                                    {{ t('Match') }} #{{ match.id }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ match.scheduledAt ? formatMatchTime(match.scheduledAt) : '' }}
                                </div>
                                <div v-if="match.tableNumber" class="text-sm text-gray-500">
                                    {{ t('Table') }} {{ match.tableNumber }}
                                </div>
                            </div>

                            <div class="mt-2 flex items-center space-x-4">
                                <span class="font-medium">{{ getPlayerName(match.playerA) }}</span>
                                <span class="text-gray-400">vs</span>
                                <span class="font-medium">{{ getPlayerName(match.playerB) }}</span>
                            </div>
                        </div>

                        <Button @click="openResultEntry(match)">
                            <PlayIcon class="mr-2 h-4 w-4"/>
                            {{ t('Enter Result') }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Completed Matches -->
        <Card v-if="completedMatches.length > 0">
            <CardHeader>
                <CardTitle>{{ t('Completed Matches') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="match in completedMatches"
                        :key="match.id"
                        class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"
                    >
                        <div class="flex-1">
                            <div class="flex items-center space-x-4">
                                <div class="text-sm font-medium">
                                    {{ t('Match') }} #{{ match.id }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ match.scheduledAt ? formatMatchTime(match.scheduledAt) : '' }}
                                </div>
                            </div>

                            <div class="mt-2 flex items-center space-x-4">
                <span
                    :class="[
                    'font-medium',
                    match.scoreA > match.scoreB ? 'text-green-600 dark:text-green-400' : ''
                  ]"
                >
                  {{ getPlayerName(match.playerA) }}
                  <span class="ml-2 text-lg font-bold">{{ match.scoreA }}</span>
                  <TrophyIcon
                      v-if="match.scoreA > match.scoreB"
                      class="inline h-4 w-4 ml-1 text-yellow-600"
                  />
                </span>

                                <span class="text-gray-400">-</span>

                                <span
                                    :class="[
                    'font-medium',
                    match.scoreB > match.scoreA ? 'text-green-600 dark:text-green-400' : ''
                  ]"
                                >
                  <span class="mr-2 text-lg font-bold">{{ match.scoreB }}</span>
                  {{ getPlayerName(match.playerB) }}
                  <TrophyIcon
                      v-if="match.scoreB > match.scoreA"
                      class="inline h-4 w-4 ml-1 text-yellow-600"
                  />
                </span>
                            </div>
                        </div>

                        <Button size="sm" variant="outline" @click="openResultEntry(match)">
                            {{ t('Edit') }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Empty State -->
        <Card v-if="pendingMatches.length === 0 && completedMatches.length === 0">
            <CardContent class="py-12 text-center">
                <TrophyIcon class="mx-auto h-12 w-12 text-gray-400 mb-4"/>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                    {{ t('No matches to process') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ t('All matches have been completed or none have been scheduled yet') }}
                </p>
            </CardContent>
        </Card>

        <!-- Result Entry Modal -->
        <Modal
            :show="showResultModal"
            :title="selectedMatch ? `${t('Match')} #${selectedMatch.id} - ${t('Enter Result')}` : ''"
            max-width="2xl"
            @close="showResultModal = false"
        >
            <div v-if="selectedMatch" class="space-y-6">
                <!-- Match Info -->
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center justify-center space-x-4 text-lg font-medium">
                        <span>{{ getPlayerName(selectedMatch.playerA) }}</span>
                        <span class="text-gray-400">vs</span>
                        <span>{{ getPlayerName(selectedMatch.playerB) }}</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ t('Best of :frames', {frames: bestOf}) }} •
                        {{ t('First to :frames wins', {frames: framesToWin}) }}
                    </div>
                </div>

                <!-- Quick Score Entry -->
                <div class="space-y-4">
                    <Label>{{ t('Quick Score Entry') }}</Label>
                    <div class="grid grid-cols-5 gap-2">
                        <Button
                            v-for="option in quickScoreOptions"
                            :key="option.label"
                            size="sm"
                            variant="outline"
                            @click="quickSetScore(option.scoreA, option.scoreB)"
                        >
                            {{ option.label }}
                        </Button>
                    </div>
                </div>

                <!-- Current Score -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="text-center p-4 border rounded-lg">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            {{ getPlayerName(selectedMatch.playerA) }}
                        </div>
                        <div
                            :class="[
                'text-4xl font-bold',
                currentWinner === 'A' ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100'
              ]"
                        >
                            {{ resultForm.scoreA }}
                        </div>
                        <Button
                            :disabled="currentWinner !== null"
                            class="mt-2"
                            size="sm"
                            @click="addFrame('A')"
                        >
                            <PlusIcon class="h-3 w-3 mr-1"/>
                            {{ t('Win Frame') }}
                        </Button>
                    </div>

                    <div class="text-center p-4 border rounded-lg">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            {{ getPlayerName(selectedMatch.playerB) }}
                        </div>
                        <div
                            :class="[
                'text-4xl font-bold',
                currentWinner === 'B' ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100'
              ]"
                        >
                            {{ resultForm.scoreB }}
                        </div>
                        <Button
                            :disabled="currentWinner !== null"
                            class="mt-2"
                            size="sm"
                            @click="addFrame('B')"
                        >
                            <PlusIcon class="h-3 w-3 mr-1"/>
                            {{ t('Win Frame') }}
                        </Button>
                    </div>
                </div>

                <!-- Winner Display -->
                <div v-if="currentWinner"
                     class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <TrophyIcon class="mx-auto h-8 w-8 text-yellow-600 mb-2"/>
                    <div class="text-lg font-bold text-green-700 dark:text-green-300">
                        {{
                            t('Winner: :player', {
                                player: getPlayerName(currentWinner === 'A' ? selectedMatch.playerA : selectedMatch.playerB)
                            })
                        }}
                    </div>
                </div>

                <!-- Frame Details -->
                <div v-if="resultForm.frames.length > 0" class="space-y-4">
                    <Label>{{ t('Frame Details') }}</Label>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        <div
                            v-for="(frame, index) in resultForm.frames"
                            :key="index"
                            class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded"
                        >
                            <div class="flex items-center space-x-4">
                                <span class="text-sm font-medium">{{ t('Frame') }} {{ frame.frameNumber }}</span>
                                <span class="text-sm">
                  {{
                                        t('Winner')
                                    }}: {{
                                        frame.winner === 'A' ? getPlayerName(selectedMatch.playerA) : getPlayerName(selectedMatch.playerB)
                                    }}
                </span>
                            </div>

                            <Button
                                :disabled="currentWinner !== null"
                                size="sm"
                                variant="ghost"
                                @click="removeFrame(index)"
                            >
                                <TrashIcon class="h-3 w-3"/>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="duration">{{ t('Match Duration') }}</Label>
                        <Input
                            id="duration"
                            v-model="resultForm.duration"
                            placeholder="e.g., 45 minutes"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="notes">{{ t('Notes') }}</Label>
                    <textarea
                        id="notes"
                        v-model="resultForm.notes"
                        :placeholder="t('Optional match notes or highlights')"
                        class="w-full h-20 px-3 py-2 border border-gray-300 rounded-md dark:border-gray-600 dark:bg-gray-800"
                    />
                </div>
            </div>

            <template #footer>
                <Button variant="outline" @click="showResultModal = false">
                    {{ t('Cancel') }}
                </Button>

                <Button
                    :disabled="!isValidResult || isSubmitting"
                    @click="submitResult"
                >
                    <SaveIcon v-if="!isSubmitting" class="mr-2 h-4 w-4"/>
                    <div v-else
                         class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                    {{ isSubmitting ? t('Submitting...') : t('Submit Result') }}
                </Button>
            </template>
        </Modal>
    </div>
</template>
