<!-- resources/js/Components/Tournament/StageTransition.vue -->
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {apiClient} from '@/lib/apiClient';
import type {Tournament} from '@/types/api';
import {router} from '@inertiajs/vue3';
import {AlertCircleIcon, ArrowRightIcon} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';

const props = defineProps<{
    tournament: Tournament;
}>();

const emit = defineEmits<{
    updated: [];
}>();

const {t} = useLocale();

interface StageTransition {
    stage: string;
    label: string;
    description: string;
    requirements: string[];
}

const transitions = ref<StageTransition[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);

const fetchTransitions = async () => {
    try {
        transitions.value = await apiClient<StageTransition[]>(`/api/admin/tournaments/${props.tournament.id}/stage-transitions`);
    } catch (err: any) {
        error.value = err.message || t('Failed to load stage transitions');
    }
};

const transitionToStage = async (newStage: string) => {
    if (!confirm(t('Are you sure you want to proceed to the next stage? This action cannot be undone.'))) {
        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        await apiClient(`/api/admin/tournaments/${props.tournament.id}/stage`, {
            method: 'POST',
            data: {stage: newStage}
        });

        emit('updated');

        // Redirect based on stage
        switch (newStage) {
            case 'seeding':
                router.visit(`/admin/tournaments/${props.tournament.slug}/seeding`);
                break;
            case 'group':
                router.visit(`/admin/tournaments/${props.tournament.slug}/groups`);
                break;
            case 'bracket':
                router.visit(`/admin/tournaments/${props.tournament.slug}/bracket`);
                break;
            case 'completed':
                router.visit(`/admin/tournaments/${props.tournament.slug}/results`);
                break;
        }
    } catch (err: any) {
        error.value = err.message || t('Failed to transition stage');
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchTransitions();
});
</script>

<template>
    <Card v-if="transitions.length > 0" class="border-l-4 border-blue-500">
        <CardHeader>
            <CardTitle>{{ t('Tournament Progress') }}</CardTitle>
            <CardDescription>
                {{ t('Current Stage') }}: <span class="font-medium">{{ tournament.stage_display }}</span>
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="error"
                 class="mb-4 rounded bg-red-100 p-3 text-sm text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <div class="space-y-4">
                <div v-for="transition in transitions" :key="transition.stage">
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div class="flex-1">
                            <h4 class="font-medium">{{ transition.label }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ transition.description }}</p>

                            <div v-if="transition.requirements.length > 0" class="mt-2 space-y-1">
                                <div v-for="req in transition.requirements" :key="req"
                                     class="flex items-center gap-2 text-sm text-yellow-600 dark:text-yellow-400">
                                    <AlertCircleIcon class="h-4 w-4"/>
                                    {{ req }}
                                </div>
                            </div>
                        </div>

                        <Button
                            :disabled="isLoading || transition.requirements.length > 0"
                            @click="transitionToStage(transition.stage)"
                        >
                            {{ t('Proceed') }}
                            <ArrowRightIcon class="ml-2 h-4 w-4"/>
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
