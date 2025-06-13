<!-- resources/js/Components/Tournament/PlayoffAdvancement.vue -->
<script lang="ts" setup>
import {computed, ref} from 'vue';
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
import {useLocale} from '@/composables/useLocale';
import {ArrowRightIcon, CheckCircleIcon, TrophyIcon} from 'lucide-vue-next';
import type {Group, TournamentPlayer} from '@/types/tournament';

interface Props {
    groups: Group[];
    qualifiedPlayers: TournamentPlayer[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'start-playoffs': [];
}>();

const {t} = useLocale();

// State
const playoffFormat = ref<'single_elimination' | 'double_elimination'>('single_elimination');
const advancePerGroup = ref(2);
const wildcardCount = ref(0);

// Computed
const totalQualified = computed(() => {
    return props.groups.length * advancePerGroup.value + wildcardCount.value;
});

const playoffBracketSize = computed(() => {
    // Round up to next power of 2
    return Math.pow(2, Math.ceil(Math.log2(totalQualified.value)));
});

const qualificationSummary = computed(() => {
    return props.groups.map(group => {
        const topPlayers = group.standings
            .sort((a, b) => a.position - b.position)
            .slice(0, advancePerGroup.value);

        return {
            group: group.name,
            qualified: topPlayers.map(s => s.player),
            remaining: group.standings.length - advancePerGroup.value
        };
    });
});

const wildcardCandidates = computed(() => {
    // Get best non-qualified players across all groups
    const nonQualified = props.groups.flatMap(group =>
        group.standings
            .sort((a, b) => a.position - b.position)
            .slice(advancePerGroup.value)
    );

    // Sort by performance (could be points, then frame difference)
    return nonQualified
        .sort((a, b) => {
            if (a.points !== b.points) return b.points - a.points;
            if (a.frame_difference !== b.frame_difference) return b.frame_difference - a.frame_difference;
            return b.frames_won - a.frames_won;
        })
        .slice(0, wildcardCount.value);
});

// Methods
const handleStartPlayoffs = () => {
    emit('start-playoffs');
};

const updateAdvancement = () => {
    // Recalculate when settings change
    console.log('Advancement settings updated');
};
</script>

<template>
    <div class="space-y-6">
        <!-- Playoff Configuration -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <TrophyIcon class="h-5 w-5 text-yellow-600"/>
                    {{ t('Playoff Configuration') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">{{ t('Advance per Group') }}</label>
                        <Select v-model="advancePerGroup" @update:model-value="updateAdvancement">
                            <SelectTrigger>
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="1">{{ t('Top 1 from each group') }}</SelectItem>
                                <SelectItem :value="2">{{ t('Top 2 from each group') }}</SelectItem>
                                <SelectItem :value="3">{{ t('Top 3 from each group') }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">{{ t('Wildcard Spots') }}</label>
                        <Select v-model="wildcardCount" @update:model-value="updateAdvancement">
                            <SelectTrigger>
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="0">{{ t('No wildcards') }}</SelectItem>
                                <SelectItem :value="2">{{ t('2 wildcards') }}</SelectItem>
                                <SelectItem :value="4">{{ t('4 wildcards') }}</SelectItem>
                                <SelectItem :value="6">{{ t('6 wildcards') }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">{{ t('Playoff Format') }}</label>
                        <Select v-model="playoffFormat">
                            <SelectTrigger>
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="single_elimination">{{ t('Single Elimination') }}</SelectItem>
                                <SelectItem value="double_elimination">{{ t('Double Elimination') }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Playoff Summary -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-2">{{ t('Playoff Summary') }}</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-blue-600 dark:text-blue-400 font-medium">{{ totalQualified }}</span>
                            <span class="text-blue-700 dark:text-blue-300 ml-1">{{ t('qualified players') }}</span>
                        </div>
                        <div>
                            <span class="text-blue-600 dark:text-blue-400 font-medium">{{ playoffBracketSize }}</span>
                            <span class="text-blue-700 dark:text-blue-300 ml-1">{{ t('bracket size') }}</span>
                        </div>
                        <div>
                            <span class="text-blue-600 dark:text-blue-400 font-medium">{{
                                    Math.log2(playoffBracketSize)
                                }}</span>
                            <span class="text-blue-700 dark:text-blue-300 ml-1">{{ t('rounds') }}</span>
                        </div>
                        <div>
                            <span class="text-blue-600 dark:text-blue-400 font-medium">{{
                                    playoffBracketSize - totalQualified
                                }}</span>
                            <span class="text-blue-700 dark:text-blue-300 ml-1">{{ t('byes') }}</span>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Group Qualification -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('Group Qualification') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="summary in qualificationSummary"
                        :key="summary.group"
                        class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium">{{ summary.group }}</h4>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ summary.qualified.length }}/{{ advancePerGroup }} {{ t('qualified') }}
              </span>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(player, index) in summary.qualified"
                                :key="player.id"
                                class="flex items-center justify-between p-2 bg-green-50 dark:bg-green-900/20 rounded"
                            >
                                <div class="flex items-center space-x-2">
                                    <CheckCircleIcon class="h-4 w-4 text-green-600"/>
                                    <span class="text-sm font-medium">{{ index + 1 }}. {{
                                            player.user?.firstname
                                        }} {{ player.user?.lastname }}</span>
                                </div>
                                <ArrowRightIcon class="h-4 w-4 text-green-600"/>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Wildcard Selection -->
        <Card v-if="wildcardCount > 0">
            <CardHeader>
                <CardTitle>{{ t('Wildcard Selection') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div
                        v-for="(candidate, index) in wildcardCandidates"
                        :key="candidate.id"
                        class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800"
                    >
                        <div class="flex items-center space-x-3">
              <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                {{ t('Wildcard') }} {{ index + 1 }}
              </span>
                            <span class="font-medium">{{
                                    candidate.player.user?.firstname
                                }} {{ candidate.player.user?.lastname }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                ({{ candidate.points }} {{ t('pts') }}, {{
                                    candidate.frame_difference > 0 ? '+' : ''
                                }}{{ candidate.frame_difference }})
              </span>
                        </div>
                        <ArrowRightIcon class="h-4 w-4 text-yellow-600"/>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Start Playoffs -->
        <div class="text-center">
            <Button
                :disabled="totalQualified === 0"
                class="bg-green-600 hover:bg-green-700"
                size="lg"
                @click="handleStartPlayoffs"
            >
                <TrophyIcon class="mr-2 h-5 w-5"/>
                {{ t('Start Playoff Bracket') }}
            </Button>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{
                    t('This will create a :format bracket with :count players', {
                        format: playoffFormat === 'single_elimination' ? t('single elimination') : t('double elimination'),
                        count: totalQualified
                    })
                }}
            </p>
        </div>
    </div>
</template>
