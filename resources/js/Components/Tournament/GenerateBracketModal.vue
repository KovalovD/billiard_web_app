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
    Separator,
    Spinner,
    Switch,
} from '@/Components/ui';
import {InfoIcon} from 'lucide-vue-next';

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
const upperBracketRacesTo = ref<Record<string, number>>({});
const lowerBracketRacesTo = ref<number>(7);
const olympicStageRacesTo = ref<Record<string, number>>({});
const specialMatchesRacesTo = ref<Record<string, number>>({});
const olympicPhaseSize = ref<number>(8);
const olympicHasThirdPlace = ref(false);
const olympicThirdPlaceRacesTo = ref<number>(7);

// Load bracket options when modal opens
watch(() => props.show, async (newVal) => {
    if (newVal && props.tournamentId) {
        await loadBracketOptions();
    }
});

// Watch Olympic phase size changes to update rounds
watch(() => olympicPhaseSize.value, (newSize) => {
    if (isOlympicTournament.value && bracketOptions.value) {
        updateOlympicRounds(newSize);
    }
});

const updateOlympicRounds = (phaseSize: number) => {
    const defaultRacesTo = bracketOptions.value.default_races_to || 7;
    const bracketSize = bracketOptions.value.bracket_size;

    // Calculate how many upper bracket rounds based on Olympic phase
    const totalUpperRounds = Math.log2(bracketSize);
    const olympicStartRound = totalUpperRounds - Math.log2(phaseSize / 2) + 1;
    const actualUpperRounds = olympicStartRound - 1;

    // Update upper bracket rounds
    const newUpperRounds: Record<string, number> = {};
    for (let i = 1; i <= actualUpperRounds; i++) {
        const key = `UB_R${i}`;
        newUpperRounds[key] = upperBracketRacesTo.value[key] || defaultRacesTo;
    }
    upperBracketRacesTo.value = newUpperRounds;

    // Update Olympic stage rounds
    const olympicRounds = Math.log2(phaseSize);
    const newOlympicRounds: Record<string, number> = {};
    for (let i = 1; i <= olympicRounds; i++) {
        const key = `O_R${i}`;
        newOlympicRounds[key] = olympicStageRacesTo.value[key] || defaultRacesTo;
    }
    olympicStageRacesTo.value = newOlympicRounds;
};

const loadBracketOptions = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const response = await apiClient<any>(`/api/admin/tournaments/${props.tournamentId}/bracket-options`);
        bracketOptions.value = response;

        const defaultRacesTo = response.default_races_to || 7;

        // Initialize with empty objects
        upperBracketRacesTo.value = {};
        olympicStageRacesTo.value = {};
        specialMatchesRacesTo.value = {};
        lowerBracketRacesTo.value = defaultRacesTo;
        olympicThirdPlaceRacesTo.value = defaultRacesTo;

        // Parse rounds into categories
        if (response.rounds) {
            Object.entries(response.rounds).forEach(([roundKey]) => {
                const currentValue = response.current_round_races_to?.[roundKey] || defaultRacesTo;

                if (roundKey.startsWith('UB_')) {
                    upperBracketRacesTo.value[roundKey] = currentValue;
                } else if (roundKey.startsWith('LB_')) {
                    // For lower bracket, use the first value found or default
                    if (lowerBracketRacesTo.value === defaultRacesTo) {
                        lowerBracketRacesTo.value = currentValue;
                    }
                } else if (roundKey.startsWith('O_')) {
                    if (roundKey === 'O_3RD') {
                        olympicThirdPlaceRacesTo.value = currentValue;
                    } else {
                        olympicStageRacesTo.value[roundKey] = currentValue;
                    }
                } else {
                    // Special matches (GF, GF_RESET, 3RD)
                    specialMatchesRacesTo.value[roundKey] = currentValue;
                }
            });
        }

        // Set Olympic defaults
        if (response.tournament_type === 'olympic_double_elimination' && response.available_olympic_phases?.length > 0) {
            olympicPhaseSize.value = response.olympic_phase_size || response.available_olympic_phases[0];
            // Update rounds based on initial Olympic phase size
            updateOlympicRounds(olympicPhaseSize.value);
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

const isDoubleElimination = computed(() => {
    return ['double_elimination', 'double_elimination_full', 'olympic_double_elimination'].includes(
        bracketOptions.value?.tournament_type
    );
});

const hasGrandFinalsReset = computed(() => {
    return bracketOptions.value?.tournament_type === 'double_elimination_full';
});

const showThirdPlaceOption = computed(() => {
    return isOlympicTournament.value && olympicPhaseSize.value !== 2;
});

// Get sorted upper bracket rounds
const upperBracketRounds = computed(() => {
    return Object.entries(upperBracketRacesTo.value)
        .sort(([a], [b]) => {
            const aNum = parseInt(a.replace('UB_R', ''));
            const bNum = parseInt(b.replace('UB_R', ''));
            return aNum - bNum;
        });
});

// Get sorted olympic stage rounds
const olympicStageRounds = computed(() => {
    return Object.entries(olympicStageRacesTo.value)
        .filter(([key]) => !key.includes('3RD'))
        .sort(([a], [b]) => {
            const aNum = parseInt(a.replace('O_R', ''));
            const bNum = parseInt(b.replace('O_R', ''));
            return aNum - bNum;
        });
});

// Count lower bracket rounds
const lowerBracketRoundCount = computed(() => {
    if (!bracketOptions.value?.rounds) return 0;

    if (isOlympicTournament.value) {
        // For Olympic tournaments, calculate based on upper bracket rounds
        const upperRounds = upperBracketRounds.value.length;
        return upperRounds > 0 ? (upperRounds * 2 - 1) : 0;
    }

    return Object.keys(bracketOptions.value.rounds).filter(key => key.startsWith('LB_')).length;
});

const handleGenerate = () => {
    // Compile all round races_to values
    const allRoundsRacesTo: Record<string, number> = {};

    // Add upper bracket rounds
    Object.entries(upperBracketRacesTo.value).forEach(([key, value]) => {
        allRoundsRacesTo[key] = value;
    });

    // Add all lower bracket rounds with the same value
    if (lowerBracketRoundCount.value > 0) {
        for (let i = 1; i <= lowerBracketRoundCount.value; i++) {
            allRoundsRacesTo[`LB_R${i}`] = lowerBracketRacesTo.value;
        }
    }

    // Add olympic stage rounds
    Object.entries(olympicStageRacesTo.value).forEach(([key, value]) => {
        allRoundsRacesTo[key] = value;
    });

    // Add Olympic third place if enabled
    if (isOlympicTournament.value && olympicHasThirdPlace.value) {
        allRoundsRacesTo['O_3RD'] = olympicThirdPlaceRacesTo.value;
    }

    // Add special matches
    Object.entries(specialMatchesRacesTo.value).forEach(([key, value]) => {
        allRoundsRacesTo[key] = value;
    });

    const data: any = {
        round_races_to: allRoundsRacesTo,
    };

    if (isOlympicTournament.value) {
        data.olympic_phase_size = olympicPhaseSize.value;
        data.olympic_has_third_place = olympicHasThirdPlace.value;
    }

    emit('generate', data);
};

// Format round name
const formatRoundName = (roundKey: string): string => {
    const roundNum = parseInt(roundKey.replace(/\D/g, ''));
    return t('Round :num', { num: roundNum });
};
</script>

<template>
    <Dialog :open="show" @update:open="$emit('close')">
        <DialogContent class="sm:max-w-[650px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ t('Generate Tournament Bracket') }}</DialogTitle>
                <DialogDescription v-if="bracketOptions" class="mt-2">
                    <div class="flex items-center gap-4 text-sm">
                        <span class="font-medium">{{ bracketOptions.tournament_type_display }}</span>
                        <span class="text-muted-foreground">•</span>
                        <span>{{ bracketOptions.player_count }}/{{ bracketOptions.bracket_size }} {{ t('players') }}</span>
                    </div>
                </DialogDescription>
            </DialogHeader>

            <div v-if="isLoading" class="flex justify-center py-12">
                <Spinner class="h-8 w-8"/>
            </div>

            <div v-else-if="error" class="py-4">
                <Alert variant="destructive">
                    <AlertDescription>{{ error }}</AlertDescription>
                </Alert>
            </div>

            <div v-else-if="bracketOptions" class="space-y-6 py-4">
                <!-- Olympic Configuration -->
                <div v-if="isOlympicTournament && bracketOptions.available_olympic_phases" class="space-y-4">
                    <div>
                        <Label class="text-base font-medium mb-3 block">{{ t('Olympic Stage Configuration') }}</Label>
                        <div class="space-y-4">
                            <div>
                                <Label class="text-sm text-muted-foreground mb-2">{{ t('Switch to Single Elimination at') }}</Label>
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
                                            {{ t('Top :count players', { count: phase }) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div v-if="showThirdPlaceOption" class="space-y-3">
                                <div class="flex items-center">
                                    <Switch
                                        v-model="olympicHasThirdPlace"
                                        id="olympic-3rd"
                                    />
                                    <Label for="olympic-3rd" class="ml-3 cursor-pointer">
                                        {{ t('Include 3rd Place Match') }}
                                    </Label>
                                </div>

                                <div v-if="olympicHasThirdPlace" class="pl-7">
                                    <Label for="olympic-3rd-races" class="text-sm mb-1.5 block">
                                        {{ t('3rd Place Match - Races to Win') }}
                                    </Label>
                                    <Input
                                        id="olympic-3rd-races"
                                        v-model.number="olympicThirdPlaceRacesTo"
                                        type="number"
                                        min="1"
                                        max="99"
                                        class="w-32"
                                        :placeholder="t('Races to win')"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <Separator/>
                </div>

                <!-- Upper Bracket -->
                <div v-if="upperBracketRounds.length > 0">
                    <div class="mb-3">
                        <h4 class="text-sm font-medium uppercase text-muted-foreground">
                            {{ t('Upper Bracket') }}
                            <span v-if="isOlympicTournament" class="normal-case text-xs">
                                ({{ upperBracketRounds.length }} {{ t('rounds') }})
                            </span>
                        </h4>
                        <p class="text-xs text-muted-foreground mt-1">{{ t('Set races to win for each round') }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div
                            v-for="[roundKey] in upperBracketRounds"
                            :key="roundKey"
                            class="space-y-1.5"
                        >
                            <Label :for="`u-${roundKey}`" class="text-sm">
                                {{ formatRoundName(roundKey) }}
                            </Label>
                            <Input
                                :id="`u-${roundKey}`"
                                v-model.number="upperBracketRacesTo[roundKey]"
                                type="number"
                                min="1"
                                max="99"
                                class="w-full"
                                :placeholder="t('Races to win')"
                            />
                        </div>
                    </div>
                </div>

                <!-- Lower Bracket -->
                <div v-if="isDoubleElimination && lowerBracketRoundCount > 0">
                    <Separator/>
                    <div>
                        <div class="mb-3">
                            <h4 class="text-sm font-medium uppercase text-muted-foreground">{{ t('Lower Bracket') }}</h4>
                            <p class="text-xs text-muted-foreground mt-1">
                                {{ t('Set races to win for all :count lower bracket rounds', { count: lowerBracketRoundCount }) }}
                            </p>
                        </div>
                        <div class="max-w-xs">
                            <Label for="lower-bracket-races" class="text-sm mb-1.5 block">
                                {{ t('All Lower Bracket Rounds') }}
                            </Label>
                            <Input
                                id="lower-bracket-races"
                                v-model.number="lowerBracketRacesTo"
                                type="number"
                                min="1"
                                max="99"
                                class="w-full"
                                :placeholder="t('Races to win')"
                            />
                        </div>
                    </div>
                </div>

                <!-- Olympic Stage -->
                <div v-if="isOlympicTournament && olympicStageRounds.length > 0">
                    <Separator/>
                    <div>
                        <div class="mb-3">
                            <h4 class="text-sm font-medium uppercase text-muted-foreground">
                                {{ t('Olympic Stage') }}
                                <span class="normal-case text-xs">
                                    ({{ olympicStageRounds.length }} {{ t('rounds') }})
                                </span>
                            </h4>
                            <p class="text-xs text-muted-foreground mt-1">{{ t('Single elimination rounds - Set races to win') }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div
                                v-for="[roundKey] in olympicStageRounds"
                                :key="roundKey"
                                class="space-y-1.5"
                            >
                                <Label :for="`o-${roundKey}`" class="text-sm">
                                    {{ formatRoundName(roundKey) }}
                                </Label>
                                <Input
                                    :id="`o-${roundKey}`"
                                    v-model.number="olympicStageRacesTo[roundKey]"
                                    type="number"
                                    min="1"
                                    max="99"
                                    class="w-full"
                                    :placeholder="t('Races to win')"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Special Matches -->
                <div v-if="Object.keys(specialMatchesRacesTo).length > 0">
                    <Separator/>
                    <div>
                        <div class="mb-3">
                            <h4 class="text-sm font-medium uppercase text-muted-foreground">{{ t('Finals & Special Matches') }}</h4>
                            <p class="text-xs text-muted-foreground mt-1">{{ t('Set races to win for finals') }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Grand Finals -->
                            <div v-if="specialMatchesRacesTo.GF !== undefined" class="space-y-1.5">
                                <Label for="gf" class="text-sm flex items-center gap-1.5">
                                    <span>🏆</span>
                                    {{ t('Grand Finals') }}
                                </Label>
                                <Input
                                    id="gf"
                                    v-model.number="specialMatchesRacesTo.GF"
                                    type="number"
                                    min="1"
                                    max="99"
                                    class="w-full"
                                    :placeholder="t('Races to win')"
                                />
                            </div>

                            <!-- Grand Finals Reset -->
                            <div v-if="hasGrandFinalsReset && specialMatchesRacesTo.GF_RESET !== undefined"
                                 class="space-y-1.5">
                                <Label for="gfr" class="text-sm flex items-center gap-1.5">
                                    <span>🔄</span>
                                    {{ t('Grand Finals Reset') }}
                                </Label>
                                <Input
                                    id="gfr"
                                    v-model.number="specialMatchesRacesTo.GF_RESET"
                                    type="number"
                                    min="1"
                                    max="99"
                                    class="w-full"
                                    :placeholder="t('Races to win')"
                                />
                            </div>

                            <!-- Third Place -->
                            <div v-if="specialMatchesRacesTo['3RD'] !== undefined"
                                 class="space-y-1.5">
                                <Label for="3rd" class="text-sm flex items-center gap-1.5">
                                    <span>🥉</span>
                                    {{ t('3rd Place Match') }}
                                </Label>
                                <Input
                                    id="3rd"
                                    v-model.number="specialMatchesRacesTo['3RD']"
                                    type="number"
                                    min="1"
                                    max="99"
                                    class="w-full"
                                    :placeholder="t('Races to win')"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <Alert>
                    <InfoIcon class="h-4 w-4"/>
                    <AlertDescription>
                        {{ t('default_races_to', { count: bracketOptions.default_races_to || 7 }) }}
                    </AlertDescription>
                </Alert>
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
