// resources/js/Components/PendingConfirmationBanner.vue
<script lang="ts" setup>
import {AlertTriangleIcon} from 'lucide-vue-next';
import {computed} from 'vue';

interface Props {
    isConfirmed?: boolean;
    leagueName?: string;
}

const props = defineProps<Props>();

const showBanner = computed(() => props.isConfirmed === false);
</script>

<template>
    <div v-if="showBanner"
         class="mb-6 border-l-4 border-amber-400 bg-amber-50 p-4 dark:border-amber-500 dark:bg-amber-900/20">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <AlertTriangleIcon class="h-5 w-5 text-amber-400"/>
            </div>
            <div class="ml-3">
                <p class="text-sm text-amber-700 dark:text-amber-400">
                    Your participation in {{ leagueName || 'this league' }} is pending admin confirmation. You cannot
                    participate in matches until an
                    admin confirms your participation.
                </p>
            </div>
        </div>
    </div>
</template>
