<!-- resources/js/Components/Tournament/BracketSettings.vue -->
<script lang="ts" setup>
import {ref} from 'vue';
import {Button, Label, Modal, Select, SelectContent, SelectItem, SelectTrigger, SelectValue} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    format: 'single_elimination' | 'double_elimination';
    seeding: 'manual' | 'random' | 'rating_based';
    bestOf: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
    'update:format': [format: 'single_elimination' | 'double_elimination'];
    'update:seeding': [seeding: 'manual' | 'random' | 'rating_based'];
    'update:best-of': [bestOf: number];
}>();

const {t} = useLocale();

// Local state for settings
const localFormat = ref(props.format);
const localSeeding = ref(props.seeding);
const localBestOf = ref(props.bestOf);

// Methods
const saveSettings = () => {
    emit('update:format', localFormat.value);
    emit('update:seeding', localSeeding.value);
    emit('update:best-of', localBestOf.value);
    emit('close');
};

const resetSettings = () => {
    localFormat.value = props.format;
    localSeeding.value = props.seeding;
    localBestOf.value = props.bestOf;
};

const handleClose = () => {
    resetSettings();
    emit('close');
};

// Options
const formatOptions = [
    {value: 'single_elimination', label: t('Single Elimination'), description: t('One loss eliminates')},
    {value: 'double_elimination', label: t('Double Elimination'), description: t('Two losses eliminate')}
];

const seedingOptions = [
    {value: 'random', label: t('Random'), description: t('Random bracket positions')},
    {value: 'rating_based', label: t('Rating Based'), description: t('Seeded by player ratings')},
    {value: 'manual', label: t('Manual'), description: t('Manually arrange positions')}
];

const bestOfOptions = [
    {value: 1, label: t('Best of 1')},
    {value: 3, label: t('Best of 3')},
    {value: 5, label: t('Best of 5')},
    {value: 7, label: t('Best of 7')},
    {value: 9, label: t('Best of 9')}
];
</script>

<template>
    <Modal
        :show="show"
        :title="t('Bracket Settings')"
        max-width="lg"
        @close="handleClose"
    >
        <div class="space-y-6">
            <!-- Tournament Format -->
            <div class="space-y-3">
                <Label>{{ t('Tournament Format') }}</Label>
                <div class="space-y-3">
                    <div
                        v-for="option in formatOptions"
                        :key="option.value"
                        class="relative"
                    >
                        <input
                            :id="option.value"
                            v-model="localFormat"
                            :value="option.value"
                            class="peer sr-only"
                            name="format"
                            type="radio"
                        />
                        <label
                            :for="option.value"
                            class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 dark:peer-checked:bg-blue-900/20"
                        >
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

            <!-- Seeding Method -->
            <div class="space-y-3">
                <Label>{{ t('Seeding Method') }}</Label>
                <div class="space-y-3">
                    <div
                        v-for="option in seedingOptions"
                        :key="option.value"
                        class="relative"
                    >
                        <input
                            :id="`seeding_${option.value}`"
                            v-model="localSeeding"
                            :value="option.value"
                            class="peer sr-only"
                            name="seeding"
                            type="radio"
                        />
                        <label
                            :for="`seeding_${option.value}`"
                            class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 dark:peer-checked:bg-blue-900/20"
                        >
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

            <!-- Match Format -->
            <div class="space-y-2">
                <Label for="best_of">{{ t('Match Format') }}</Label>
                <Select v-model="localBestOf">
                    <SelectTrigger>
                        <SelectValue/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in bestOfOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-sm text-gray-500">
                    {{ t('First to win :frames frames', {frames: Math.ceil(localBestOf / 2)}) }}
                </p>
            </div>

            <!-- Settings Preview -->
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ t('Settings Preview') }}</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">{{ t('Format') }}:</span>
                        <span>{{ formatOptions.find(o => o.value === localFormat)?.label }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">{{ t('Seeding') }}:</span>
                        <span>{{ seedingOptions.find(o => o.value === localSeeding)?.label }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">{{ t('Match Format') }}:</span>
                        <span>{{ bestOfOptions.find(o => o.value === localBestOf)?.label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleClose">
                {{ t('Cancel') }}
            </Button>
            <Button @click="saveSettings">
                {{ t('Apply Settings') }}
            </Button>
        </template>
    </Modal>
</template>
