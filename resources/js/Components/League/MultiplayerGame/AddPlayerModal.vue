<script lang="ts" setup>
import UnifiedAddPlayerModal from '@/Components/Core/UnifiedAddPlayerModal.vue';
import type {ApiError} from '@/types/api';
import {computed, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    entityType: 'league' | 'match';
    entityId: number | string;
    leagueId?: number | string;
}

interface Emits {
    (e: 'close'): void;

    (e: 'added'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const {t} = useLocale();

const error = ref<string | null>(null);

const modalTitle = computed(() => {
    return props.entityType === 'league'
        ? t('Add Player to League')
        : t('Add Player to Game');
});

const handleClose = () => {
    error.value = null;
    emit('close');
};

const handleAdded = () => {
    error.value = null;
    emit('added');
};

const handleError = (apiError: ApiError) => {
    error.value = apiError.message || t('Failed to add player');
};
</script>

<template>
    <div>
        <UnifiedAddPlayerModal
            :entity-id="entityId"
            :entity-type="entityType"
            :league-id="leagueId"
            :show="show"
            :title="modalTitle"
            @added="handleAdded"
            @close="handleClose"
            @error="handleError"
        />

        <!-- Error Display -->
        <div
            v-if="error"
            class="fixed bottom-4 right-4 z-50 rounded-md bg-red-100 p-4 text-red-600 shadow-lg dark:bg-red-900/30 dark:text-red-400"
        >
            {{ error }}
        </div>
    </div>
</template>
