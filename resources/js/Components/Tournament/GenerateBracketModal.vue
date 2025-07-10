<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {apiClient} from '@/lib/apiClient';
import {
    Alert,
    AlertDescription,
    Button,
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner,
    Switch,
} from '@/Components/ui';

const props = defineProps<{
    show: boolean;
    tournamentId: number | string;
}>();

const emit = defineEmits<{
    close: [];
    generate: [data: {
        round_races_to?: Record<string, number>;
        olympic_phase_size?: number;
        olympic_has_third_place?: boolean;
    }];
}>();

const {t} = useLocale();

// State
const isLoading = ref(false);
const error = ref<string | null>(null);
const bracketOptions = ref<any>(null);

// Form data
const roundRacesTo = ref<Record<string, number>>({});
const olympicPhaseSize = ref<number>(8);
const olympicHasThirdPlace = ref(false);

// Load bracket options when modal opens
watch(() => props.show, async (newVal) => {
    if (newVal && props.tournamentId) {
        await loadBracketOptions();
    }
});

const loadBracketOptions = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const response = await apiClient<any>(`/api/admin/tournaments/${props.tournamentId}/bracket-options`);
        bracketOptions.value = response;

        // Initialize round races to with defaults
        if (response.rounds) {
            const defaults: Record<string, number> = {};
            Object.keys(response.rounds).forEach(roundKey => {
                defaults[roundKey] = response.current_round_races_to?.[roundKey] || response.default_races_to || 7;
            });
            roundRacesTo.value = defaults;
        }

        // Set Olympic defaults
        if (response.tournament_type === 'olympic_double_elimination' && response.available_olympic_phases?.length > 0) {
            olympicPhaseSize.value = response.available_olympic_phases[0];
        }
    } catch (err: any) {
        error.value = err.message || t('Failed to load bracket options');
    } finally {
        isLoading.value = false;
    }
};

const isOlympicTournament = computed(() => {
    return bracketOptions.value?.tournament_type === 'olympic_double_elimination';
});

const showThirdPlaceOption = computed(() => {
    return !isOlympicTournament.value || olympicPhaseSize.value > 4;
});

const handleGenerate = () => {
    const data: any = {
        round_races_to: roundRacesTo.value,
    };

    if (isOlympicTournament.value) {
        data.olympic_phase_size = olympicPhaseSize.value;
        data.olympic_has_third_place = olympicHasThirdPlace.value;
    }

    emit('generate', data);
};

const getRoundLabel = (roundKey: string, roundName: string): string => {
    // Add icons or special formatting for certain rounds
    if (roundKey === 'GF') return `🏆 ${roundName}`;
    if (roundKey === '3RD') return `🥉 ${roundName}`;
    if (roundKey.includes('FINALS')) return `🏆 ${roundName}`;
    if (roundKey.includes('SEMIFINALS')) return `🥈 ${roundName}`;
    return roundName;
};
</script>

<template>
    <Dialog :open="show" @update:open="$emit('close')">
        <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ t('Generate Tournament Bracket') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Configure bracket generation options before creating the tournament structure.') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="isLoading" class="flex justify-center py-8">
                <Spinner class="h-8 w-8"/>
            </div>

            <div v-else-if="error" class="py-4">
                <Alert variant="destructive">
                    <AlertDescription>{{ error }}</AlertDescription>
                </Alert>
            </div>

            <div v-else-if="bracketOptions" class="space-y-6 py-4">
                <!-- Tournament Info -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('Tournament Type') }}:</span>
                        <span class="text-sm font-medium">{{
                                bracketOptions.tournament_type_display || bracketOptions.tournament_type
                            }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('Confirmed Players') }}:</span>
                        <span class="text-sm font-medium">{{ bracketOptions.player_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('Bracket Size') }}:</span>
                        <span class="text-sm font-medium">{{ bracketOptions.bracket_size }}</span>
                    </div>
                </div>

                <!-- Olympic Phase Configuration -->
                <div v-if="isOlympicTournament && bracketOptions.available_olympic_phases" class="space-y-4">
                    <div>
                        <Label>{{ t('Olympic Phase Starts With') }}</Label>
                        <Select v-model="olympicPhaseSize">
                            <SelectTrigger>
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="phase in bracketOptions.available_olympic_phases"
                                    :key="phase"
                                    :value="phase"
                                >
                                    {{ phase }} {{ t('players') }}
                                    <span v-if="phase === 2" class="text-sm text-gray-500 ml-2">
                                        ({{ t('Standard Double Elimination') }})
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{
                                t('Tournament will use double elimination until this many players remain, then switch to single elimination.')
                            }}
                        </p>
                    </div>

                    <div v-if="showThirdPlaceOption" class="flex items-center space-x-2">
                        <Switch
                            v-model:checked="olympicHasThirdPlace"
                            id="olympic-third-place"
                        />
                        <Label for="olympic-third-place" class="cursor-pointer">
                            {{ t('Include 3rd Place Match in Olympic Phase') }}
                        </Label>
                    </div>
                </div>

                <!-- Round Race-To Configuration -->
                <div v-if="bracketOptions.rounds" class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium mb-3">{{ t('Races to Win per Round') }}</h3>
                        <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2">
                            <div
                                v-for="(roundName, roundKey) in bracketOptions.rounds"
                                :key="roundKey"
                                class="flex items-center gap-3"
                            >
                                <Label :for="`race-to-${roundKey}`" class="flex-1 text-sm">
                                    {{ getRoundLabel(roundKey, roundName) }}
                                </Label>
                                <Input
                                    :id="`race-to-${roundKey}`"
                                    v-model.number="roundRacesTo[roundKey]"
                                    type="number"
                                    min="1"
                                    max="99"
                                    class="w-20"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="$emit('close')">
                    {{ t('Cancel') }}
                </Button>
                <Button @click="handleGenerate" :disabled="isLoading">
                    {{ t('Generate Bracket') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
