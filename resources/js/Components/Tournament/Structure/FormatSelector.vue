<!-- resources/js/Components/Tournament/Structure/FormatSelector.vue -->
<script lang="ts" setup>
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Label,
    RadioGroup,
    RadioGroupItem
} from '@/Components/ui';
import {Layers, Shuffle, Trophy, Users} from 'lucide-vue-next';
import {computed, watch} from 'vue';
import type {TournamentFormat} from '@/types/tournament';

const props = defineProps<{
    modelValue: {
        tournament_format: TournamentFormat;
        seeding_method: string;
        number_of_groups?: number;
        players_per_group?: number;
        advance_per_group?: number;
        best_of_rule?: string;
        has_lower_bracket?: boolean;
        is_team_tournament?: boolean;
        team_size?: number;
    };
}>();

const emit = defineEmits<{
    'update:modelValue': [value: typeof props.modelValue];
}>();

const formats = [
    {
        value: 'single_elimination',
        label: 'Single Elimination',
        description: 'Traditional knockout format. Lose once and you\'re out.',
        icon: Trophy,
        hasGroups: false,
        hasBrackets: true
    },
    {
        value: 'double_elimination',
        label: 'Double Elimination',
        description: 'Players get a second chance in the lower bracket.',
        icon: Shuffle,
        hasGroups: false,
        hasBrackets: true
    },
    {
        value: 'group_stage',
        label: 'Group Stage (Round Robin)',
        description: 'Players compete in groups, everyone plays everyone.',
        icon: Layers,
        hasGroups: true,
        hasBrackets: false
    },
    {
        value: 'group_playoff',
        label: 'Group Stage + Playoffs',
        description: 'Group stage followed by elimination playoffs.',
        icon: Users,
        hasGroups: true,
        hasBrackets: true
    },
    {
        value: 'round_robin',
        label: 'Round Robin',
        description: 'Every player plays against every other player.',
        icon: Shuffle,
        hasGroups: false,
        hasBrackets: false
    }
];

const seedingMethods = [
    {value: 'manual', label: 'Manual Seeding', description: 'Manually set player positions'},
    {value: 'random', label: 'Random Shuffle', description: 'Randomly assign positions'},
    {value: 'rating_based', label: 'Rating-Based', description: 'Seed by player ratings'}
];

const bestOfRules = [
    {value: 'best_of_1', label: 'Best of 1', games: 1},
    {value: 'best_of_3', label: 'Best of 3', games: 3},
    {value: 'best_of_5', label: 'Best of 5', games: 5},
    {value: 'best_of_7', label: 'Best of 7', games: 7}
];

const selectedFormat = computed(() =>
    formats.find(f => f.value === props.modelValue.tournament_format)
);

const updateValue = (key: string, value: any) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value
    });
};

// Watch format changes to reset related fields
watch(() => props.modelValue.tournament_format, (newFormat) => {
    const format = formats.find(f => f.value === newFormat);
    if (format) {
        if (!format.hasGroups) {
            updateValue('number_of_groups', undefined);
            updateValue('players_per_group', undefined);
            updateValue('advance_per_group', undefined);
        }
        if (!format.hasBrackets) {
            updateValue('has_lower_bracket', false);
        }
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Tournament Format -->
        <Card>
            <CardHeader>
                <CardTitle>Tournament Format</CardTitle>
                <CardDescription>Choose how players will compete in this tournament</CardDescription>
            </CardHeader>
            <CardContent>
                <RadioGroup
                    :model-value="modelValue.tournament_format"
                    @update:model-value="updateValue('tournament_format', $event)"
                >
                    <div class="grid gap-4">
                        <div
                            v-for="format in formats"
                            :key="format.value"
                            :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': modelValue.tournament_format === format.value }"
                            class="flex items-start space-x-3 rounded-lg border p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <RadioGroupItem :value="format.value" class="mt-1"/>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <component :is="format.icon" class="h-5 w-5 text-gray-500"/>
                                    <Label class="font-semibold cursor-pointer">{{ format.label }}</Label>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ format.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </RadioGroup>
            </CardContent>
        </Card>

        <!-- Group Configuration -->
        <Card v-if="selectedFormat?.hasGroups">
            <CardHeader>
                <CardTitle>Group Configuration</CardTitle>
                <CardDescription>Configure group stage settings</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <Label for="num-groups">Number of Groups</Label>
                        <input
                            id="num-groups"
                            :value="modelValue.number_of_groups || 4"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            max="16"
                            min="2"
                            type="number"
                            @input="updateValue('number_of_groups', Number($event.target.value))"
                        />
                    </div>
                    <div>
                        <Label for="players-per-group">Players per Group</Label>
                        <input
                            id="players-per-group"
                            :value="modelValue.players_per_group || 4"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            max="16"
                            min="2"
                            type="number"
                            @input="updateValue('players_per_group', Number($event.target.value))"
                        />
                    </div>
                    <div v-if="modelValue.tournament_format === 'group_playoff'">
                        <Label for="advance-per-group">Advance per Group</Label>
                        <input
                            id="advance-per-group"
                            :value="modelValue.advance_per_group || 2"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            max="8"
                            min="1"
                            type="number"
                            @input="updateValue('advance_per_group', Number($event.target.value))"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Bracket Configuration -->
        <Card v-if="selectedFormat?.hasBrackets">
            <CardHeader>
                <CardTitle>Bracket Configuration</CardTitle>
                <CardDescription>Configure elimination bracket settings</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-4">
                    <div>
                        <Label for="best-of">Match Format</Label>
                        <select
                            id="best-of"
                            :value="modelValue.best_of_rule || 'best_of_3'"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            @change="updateValue('best_of_rule', $event.target.value)"
                        >
                            <option v-for="rule in bestOfRules" :key="rule.value" :value="rule.value">
                                {{ rule.label }} (First to {{ Math.ceil(rule.games / 2) }})
                            </option>
                        </select>
                    </div>

                    <div v-if="modelValue.tournament_format === 'double_elimination'"
                         class="flex items-center space-x-3">
                        <input
                            id="lower-bracket"
                            :checked="modelValue.has_lower_bracket !== false"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            type="checkbox"
                            @change="updateValue('has_lower_bracket', $event.target.checked)"
                        />
                        <Label class="cursor-pointer" for="lower-bracket">Enable Lower Bracket</Label>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Seeding Method -->
        <Card>
            <CardHeader>
                <CardTitle>Seeding Method</CardTitle>
                <CardDescription>How should players be positioned in the tournament?</CardDescription>
            </CardHeader>
            <CardContent>
                <RadioGroup
                    :model-value="modelValue.seeding_method || 'manual'"
                    @update:model-value="updateValue('seeding_method', $event)"
                >
                    <div class="space-y-3">
                        <div
                            v-for="method in seedingMethods"
                            :key="method.value"
                            class="flex items-start space-x-3"
                        >
                            <RadioGroupItem :value="method.value" class="mt-1"/>
                            <div>
                                <Label class="cursor-pointer">{{ method.label }}</Label>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ method.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </RadioGroup>
            </CardContent>
        </Card>

        <!-- Team Configuration -->
        <Card>
            <CardHeader>
                <CardTitle>Team Configuration</CardTitle>
                <CardDescription>Is this a team-based tournament?</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-center space-x-3">
                    <input
                        id="team-tournament"
                        :checked="modelValue.is_team_tournament"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        type="checkbox"
                        @change="updateValue('is_team_tournament', $event.target.checked)"
                    />
                    <Label class="cursor-pointer" for="team-tournament">Team Tournament</Label>
                </div>

                <div v-if="modelValue.is_team_tournament" class="mt-4">
                    <Label for="team-size">Team Size</Label>
                    <input
                        id="team-size"
                        :value="modelValue.team_size || 2"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        max="10"
                        min="2"
                        type="number"
                        @input="updateValue('team_size', Number($event.target.value))"
                    />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
