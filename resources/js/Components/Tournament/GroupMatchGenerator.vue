<!-- resources/js/Components/Tournament/GroupMatchGenerator.vue -->
<script lang="ts" setup>
import {computed, ref} from 'vue';
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {CalendarIcon, PlayIcon, RefreshCwIcon} from 'lucide-vue-next';
import type {Group} from '@/types/tournament';

interface Props {
    groups: Group[];
    format: 'round_robin' | 'swiss';
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'matches-generated': [];
}>();

const {t} = useLocale();

// State
const isGenerating = ref(false);

// Computed
const totalMatches = computed(() => {
    return props.groups.reduce((sum, group) => {
        const playerCount = group.players.length;
        if (props.format === 'round_robin') {
            return sum + (playerCount * (playerCount - 1) / 2);
        }
        // Swiss system - roughly same number of rounds as round robin
        return sum + Math.floor(playerCount * Math.log2(playerCount));
    }, 0);
});

// Methods
const generateMatches = async (groupId?: number) => {
    isGenerating.value = true;

    try {
        // Mock match generation
        await new Promise(resolve => setTimeout(resolve, 1000));

        console.log(`Generating ${props.format} matches for group(s):`, groupId || 'all');

        emit('matches-generated');
    } catch (error) {
        console.error('Failed to generate matches:', error);
    } finally {
        isGenerating.value = false;
    }
};

const generateAllMatches = () => {
    generateMatches();
};
</script>

<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CalendarIcon class="h-5 w-5"/>
                    {{ t('Generate Group Matches') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div>
                            <h4 class="font-medium text-blue-800 dark:text-blue-200">{{ t('Match Generation') }}</h4>
                            <p class="text-sm text-blue-600 dark:text-blue-300">
                                {{ t('Format') }}: {{ format === 'round_robin' ? t('Round Robin') : t('Swiss System') }}
                            </p>
                            <p class="text-xs text-blue-500 dark:text-blue-400 mt-1">
                                {{ t('Total matches to generate') }}: {{ totalMatches }}
                            </p>
                        </div>

                        <Button
                            :disabled="isGenerating"
                            @click="generateAllMatches"
                        >
                            <RefreshCwIcon :class="['mr-2 h-4 w-4', { 'animate-spin': isGenerating }]"/>
                            {{ isGenerating ? t('Generating...') : t('Generate All') }}
                        </Button>
                    </div>

                    <!-- Individual Group Generation -->
                    <div class="space-y-3">
                        <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ t('Generate by Group') }}</h5>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="group in groups"
                                :key="group.id"
                                class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                            >
                                <div>
                                    <div class="font-medium">{{ group.name }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ group.players.length }} {{ t('players') }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ Math.floor(group.players.length * (group.players.length - 1) / 2) }}
                                        {{ t('matches') }}
                                    </div>
                                </div>

                                <Button
                                    :disabled="isGenerating"
                                    size="sm"
                                    variant="outline"
                                    @click="generateMatches(group.id)"
                                >
                                    <PlayIcon class="h-3 w-3 mr-1"/>
                                    {{ t('Generate') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
