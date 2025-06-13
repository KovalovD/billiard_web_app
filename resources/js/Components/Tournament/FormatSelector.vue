<!-- resources/js/Components/Tournament/FormatSelector.vue -->
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

interface FormatType {
    type: 'single_elimination' | 'double_elimination' | 'group_stage' | 'group_playoff';
    best_of: number;
    rounds?: number;
    group_count?: number;
    playoff_size?: number;
    third_place_match: boolean;
}

interface Props {
    format: FormatType;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:format': [format: FormatType];
}>();

const {t} = useLocale();

const formatOptions = [
    {
        value: 'single_elimination',
        label: t('Single Elimination'),
        description: t('Players are eliminated after one loss'),
        icon: '🏆'
    },
    {
        value: 'double_elimination',
        label: t('Double Elimination'),
        description: t('Players get a second chance in lower bracket'),
        icon: '⚡'
    },
    {
        value: 'group_stage',
        label: t('Group Stage Only'),
        description: t('Round-robin groups, no playoffs'),
        icon: '👥'
    },
    {
        value: 'group_playoff',
        label: t('Group + Playoff'),
        description: t('Group stage followed by elimination playoffs'),
        icon: '🎯'
    }
];

const bestOfOptions = [
    {value: 1, label: t('Best of 1')},
    {value: 3, label: t('Best of 3')},
    {value: 5, label: t('Best of 5')},
    {value: 7, label: t('Best of 7')},
    {value: 9, label: t('Best of 9')}
];

const showGroupSettings = computed(() =>
    props.format.type === 'group_stage' || props.format.type === 'group_playoff'
);

const showPlayoffSettings = computed(() =>
    props.format.type === 'group_playoff'
);

const showThirdPlaceMatch = computed(() =>
    props.format.type === 'single_elimination' || props.format.type === 'double_elimination'
);

const updateFormat = (updates: Partial<FormatType>) => {
    emit('update:format', {...props.format, ...updates});
};

const handleTypeChange = (newType: FormatType['type']) => {
    const newFormat: FormatType = {
        ...props.format,
        type: newType
    };

    // Set sensible defaults based on format type
    switch (newType) {
        case 'single_elimination':
        case 'double_elimination':
            newFormat.group_count = undefined;
            newFormat.playoff_size = undefined;
            break;
        case 'group_stage':
            newFormat.group_count = newFormat.group_count || 4;
            newFormat.playoff_size = undefined;
            break;
        case 'group_playoff':
            newFormat.group_count = newFormat.group_count || 4;
            newFormat.playoff_size = newFormat.playoff_size || 8;
            break;
    }

    emit('update:format', newFormat);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ t('Tournament Format') }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <!-- Format Type Selection -->
            <div class="space-y-3">
                <Label>{{ t('Format Type') }} *</Label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="option in formatOptions"
                        :key="option.value"
                        class="relative"
                    >
                        <input
                            :id="option.value"
                            :checked="format.type === option.value"
                            :value="option.value"
                            class="peer sr-only"
                            name="format_type"
                            type="radio"
                            @change="handleTypeChange(option.value as FormatType['type'])"
                        />
                        <label
                            :for="option.value"
                            class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 dark:peer-checked:bg-blue-900/20"
                        >
                            <span class="text-2xl">{{ option.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ option.label }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ option.description }}
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Basic Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label for="best_of">{{ t('Best of') }} *</Label>
                    <Select
                        :model-value="format.best_of.toString()"
                        @update:model-value="updateFormat({ best_of: parseInt($event) })"
                    >
                        <SelectTrigger>
                            <SelectValue/>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in bestOfOptions"
                                :key="option.value"
                                :value="option.value.toString()"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="showThirdPlaceMatch" class="flex items-center space-x-2 pt-7">
                    <input
                        id="third_place_match"
                        :checked="format.third_place_match"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        type="checkbox"
                        @change="updateFormat({ third_place_match: $event.target.checked })"
                    />
                    <Label class="text-sm" for="third_place_match">
                        {{ t('Third place match') }}
                    </Label>
                </div>
            </div>

            <!-- Group Stage Settings -->
            <div v-if="showGroupSettings" class="space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-gray-100">
                    {{ t('Group Stage Settings') }}
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="group_count">{{ t('Number of Groups') }} *</Label>
                        <Input
                            id="group_count"
                            :value="format.group_count || 4"
                            max="8"
                            min="2"
                            type="number"
                            @input="updateFormat({ group_count: parseInt($event.target.value) })"
                        />
                    </div>

                    <div v-if="showPlayoffSettings" class="space-y-2">
                        <Label for="playoff_size">{{ t('Playoff Size') }} *</Label>
                        <Select
                            :model-value="format.playoff_size?.toString() || '8'"
                            @update:model-value="updateFormat({ playoff_size: parseInt($event) })"
                        >
                            <SelectTrigger>
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="4">{{ t('4 players') }}</SelectItem>
                                <SelectItem value="8">{{ t('8 players') }}</SelectItem>
                                <SelectItem value="16">{{ t('16 players') }}</SelectItem>
                                <SelectItem value="32">{{ t('32 players') }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p v-if="format.type === 'group_stage'">
                        {{
                            t('Players will be divided into groups for round-robin play. Group winners and runners-up will be ranked by performance.')
                        }}
                    </p>
                    <p v-else-if="format.type === 'group_playoff'">
                        {{
                            t('Players will first compete in round-robin groups. Top performers will advance to elimination playoffs.')
                        }}
                    </p>
                </div>
            </div>

            <!-- Format Summary -->
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                    {{ t('Format Summary') }}
                </h4>
                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <p>
                        <strong>{{ t('Type') }}:</strong>
                        {{ formatOptions.find(opt => opt.value === format.type)?.label }}
                    </p>
                    <p>
                        <strong>{{ t('Match Format') }}:</strong>
                        {{ bestOfOptions.find(opt => opt.value === format.best_of)?.label }}
                    </p>
                    <p v-if="showGroupSettings">
                        <strong>{{ t('Groups') }}:</strong>
                        {{ format.group_count }} {{ t('groups') }}
                        <span v-if="showPlayoffSettings">
              → {{ format.playoff_size }} {{ t('player playoff') }}
            </span>
                    </p>
                    <p v-if="format.third_place_match">
                        <strong>{{ t('Additional') }}:</strong>
                        {{ t('Third place match included') }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
