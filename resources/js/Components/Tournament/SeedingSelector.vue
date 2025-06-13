<!-- resources/js/Components/Tournament/SeedingSelector.vue -->
<script lang="ts" setup>
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Label
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';

interface SeedingOptions {
    method: 'manual' | 'random' | 'rating_based';
    custom_order?: number[];
}

interface Props {
    seeding: SeedingOptions;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:seeding': [seeding: SeedingOptions];
}>();

const {t} = useLocale();

const seedingMethods = [
    {
        value: 'random',
        label: t('Random Shuffle'),
        description: t('Players are randomly assigned to bracket positions'),
        icon: '🎲',
        recommended: true
    },
    {
        value: 'rating_based',
        label: t('Rating Based'),
        description: t('Players are seeded based on their current ratings'),
        icon: '📊',
        recommended: false
    },
    {
        value: 'manual',
        label: t('Manual Seeding'),
        description: t('Tournament organizer manually arranges bracket positions'),
        icon: '✋',
        recommended: false
    }
];

const updateSeeding = (updates: Partial<SeedingOptions>) => {
    emit('update:seeding', {...props.seeding, ...updates});
};

const handleMethodChange = (method: SeedingOptions['method']) => {
    const newSeeding: SeedingOptions = {
        method,
        custom_order: method === 'manual' ? [] : undefined
    };

    emit('update:seeding', newSeeding);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ t('Seeding Options') }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <!-- Seeding Method Selection -->
            <div class="space-y-3">
                <Label>{{ t('Seeding Method') }} *</Label>
                <div class="space-y-3">
                    <div
                        v-for="method in seedingMethods"
                        :key="method.value"
                        class="relative"
                    >
                        <input
                            :id="method.value"
                            :checked="seeding.method === method.value"
                            :value="method.value"
                            class="peer sr-only"
                            name="seeding_method"
                            type="radio"
                            @change="handleMethodChange(method.value as SeedingOptions['method'])"
                        />
                        <label
                            :for="method.value"
                            class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 dark:peer-checked:bg-blue-900/20"
                        >
                            <span class="text-2xl">{{ method.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                  <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ method.label }}
                  </span>
                                    <span
                                        v-if="method.recommended"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-900/30 dark:text-green-300"
                                    >
                    {{ t('Recommended') }}
                  </span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ method.description }}
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Method-specific Information -->
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div v-if="seeding.method === 'random'" class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                        {{ t('Random Seeding') }}
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>{{
                                t('Players will be randomly assigned to bracket positions when the tournament starts.')
                            }}</p>
                        <p class="mt-2">
                            <strong>{{ t('Pros') }}:</strong>
                            {{ t('Fair for all skill levels, prevents predictable early matchups') }}
                        </p>
                        <p class="mt-1">
                            <strong>{{ t('Cons') }}:</strong>
                            {{ t('Strong players might meet early, reducing competitive balance') }}
                        </p>
                    </div>
                </div>

                <div v-else-if="seeding.method === 'rating_based'" class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                        {{ t('Rating-Based Seeding') }}
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>{{
                                t('Players will be seeded according to their current league ratings, with highest-rated players getting top seeds.')
                            }}</p>
                        <p class="mt-2">
                            <strong>{{ t('Pros') }}:</strong>
                            {{ t('Ensures competitive balance, strongest players separated until later rounds') }}
                        </p>
                        <p class="mt-1">
                            <strong>{{ t('Cons') }}:</strong>
                            {{ t('Requires existing rating data, may discourage newer players') }}
                        </p>
                        <div
                            class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded border border-yellow-200 dark:border-yellow-800">
                            <p class="text-yellow-800 dark:text-yellow-200 text-xs">
                                ⚠️ {{
                                    t('Note: Players without ratings will be placed randomly among unrated participants')
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else-if="seeding.method === 'manual'" class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                        {{ t('Manual Seeding') }}
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>{{
                                t('You will be able to manually arrange all bracket positions after player registration closes.')
                            }}</p>
                        <p class="mt-2">
                            <strong>{{ t('Pros') }}:</strong>
                            {{ t('Full control over matchups, can create compelling storylines') }}
                        </p>
                        <p class="mt-1">
                            <strong>{{ t('Cons') }}:</strong>
                            {{ t('Time-consuming, potential for bias accusations, requires tournament knowledge') }}
                        </p>
                        <div
                            class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800">
                            <p class="text-blue-800 dark:text-blue-200 text-xs">
                                💡 {{
                                    t('Tip: Use manual seeding for special events or when you want specific first-round matchups')
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seeding Summary -->
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                    {{ t('Seeding Summary') }}
                </h4>
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    <p>
                        <strong>{{ t('Selected Method') }}:</strong>
                        {{ seedingMethods.find(m => m.value === seeding.method)?.label }}
                    </p>
                    <p class="mt-1">
                        <strong>{{ t('When Applied') }}:</strong>
                        <span v-if="seeding.method === 'manual'">
              {{ t('After registration closes - you will arrange brackets manually') }}
            </span>
                        <span v-else>
              {{ t('Automatically when tournament bracket is generated') }}
            </span>
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
