<!-- resources/js/Components/Tournament/TeamConfigSection.vue -->
<script lang="ts" setup>
import {computed} from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';

interface TeamConfig {
    enabled: boolean;
    team_size: number;
    max_teams: number;
    allow_mixed: boolean;
}

interface Props {
    config: TeamConfig;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:config': [config: TeamConfig];
}>();

const {t} = useLocale();

const teamSizeOptions = [
    {value: 2, label: t('Doubles (2 players)'), description: t('Classic pairs format')},
    {value: 3, label: t('Triples (3 players)'), description: t('Three-player teams')},
    {value: 4, label: t('Squads (4 players)'), description: t('Four-player teams')},
    {value: 5, label: t('Large Teams (5 players)'), description: t('Five-player teams')}
];

const maxTeamsOptions = [
    {value: 4, label: '4 teams'},
    {value: 8, label: '8 teams'},
    {value: 16, label: '16 teams'},
    {value: 32, label: '32 teams'}
];

const totalParticipants = computed(() =>
    props.config.max_teams * props.config.team_size
);

const updateConfig = (updates: Partial<TeamConfig>) => {
    emit('update:config', {...props.config, ...updates});
};

const toggleTeamMode = () => {
    updateConfig({enabled: !props.config.enabled});
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                {{ t('Team Configuration') }}
                <div class="flex items-center space-x-2">
                    <input
                        id="enable_teams"
                        :checked="config.enabled"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        type="checkbox"
                        @change="toggleTeamMode"
                    />
                    <Label class="text-sm" for="enable_teams">
                        {{ t('Enable team play') }}
                    </Label>
                </div>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <div v-if="!config.enabled" class="text-center py-8 text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-4">👤</div>
                <p>{{ t('Individual tournament - each player competes alone') }}</p>
                <p class="text-sm mt-2">{{ t('Enable team play above to configure team-based competition') }}</p>
            </div>

            <div v-else class="space-y-6">
                <!-- Team Size Selection -->
                <div class="space-y-3">
                    <Label>{{ t('Team Size') }} *</Label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div
                            v-for="option in teamSizeOptions"
                            :key="option.value"
                            class="relative"
                        >
                            <input
                                :id="`team_size_${option.value}`"
                                :checked="config.team_size === option.value"
                                :value="option.value"
                                class="peer sr-only"
                                name="team_size"
                                type="radio"
                                @change="updateConfig({ team_size: option.value })"
                            />
                            <label
                                :for="`team_size_${option.value}`"
                                class="flex items-start space-x-3 p-3 border-2 border-gray-200 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 dark:peer-checked:bg-blue-900/20"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ option.label }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ option.description }}
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Team Limits -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <Label for="max_teams">{{ t('Maximum Teams') }} *</Label>
                        <Select
                            :model-value="config.max_teams.toString()"
                            @update:model-value="updateConfig({ max_teams: parseInt($event) })"
                        >
                            <SelectTrigger>
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in maxTeamsOptions"
                                    :key="option.value"
                                    :value="option.value.toString()"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label>{{ t('Total Participants') }}</Label>
                        <div
                            class="flex items-center h-10 px-3 py-2 border border-gray-300 bg-gray-50 rounded-md dark:border-gray-600 dark:bg-gray-700">
              <span class="text-gray-900 dark:text-gray-100">
                {{ totalParticipants }} {{ t('players') }}
              </span>
                        </div>
                    </div>
                </div>

                <!-- Team Rules -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                        {{ t('Team Rules') }}
                    </h4>

                    <div class="flex items-center space-x-2">
                        <input
                            id="allow_mixed"
                            :checked="config.allow_mixed"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            type="checkbox"
                            @change="updateConfig({ allow_mixed: $event.target.checked })"
                        />
                        <Label class="text-sm" for="allow_mixed">
                            {{ t('Allow mixed-skill teams') }}
                        </Label>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-6">
                        {{
                            config.allow_mixed
                                ? t('Teams can include players of different skill levels')
                                : t('Teams should have players of similar skill levels')
                        }}
                    </p>
                </div>

                <!-- Team Play Information -->
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                        {{ t('How Team Play Works') }}
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <p>
                            <strong>{{ t('Team Formation') }}:</strong>
                            {{ t('Players will register individually and then form teams during registration') }}
                        </p>
                        <p>
                            <strong>{{ t('Match Format') }}:</strong>
                            {{ t('Each team member plays individual matches, team score is combined') }}
                        </p>
                        <p>
                            <strong>{{ t('Rotation') }}:</strong>
                            {{ t('Team members alternate playing matches according to tournament rules') }}
                        </p>
                    </div>
                </div>

                <!-- Team Configuration Summary -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                        {{ t('Team Configuration Summary') }}
                    </h4>
                    <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                        <p>
                            <strong>{{ t('Format') }}:</strong>
                            {{ teamSizeOptions.find(opt => opt.value === config.team_size)?.label }}
                        </p>
                        <p>
                            <strong>{{ t('Teams') }}:</strong>
                            {{ config.max_teams }} {{ t('teams maximum') }}
                        </p>
                        <p>
                            <strong>{{ t('Total Players') }}:</strong>
                            {{ totalParticipants }} {{ t('participants') }}
                        </p>
                        <p>
                            <strong>{{ t('Mixed Skills') }}:</strong>
                            {{ config.allow_mixed ? t('Allowed') : t('Not allowed') }}
                        </p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
