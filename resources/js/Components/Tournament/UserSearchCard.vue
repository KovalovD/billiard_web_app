<!-- resources/js/Components/Tournament/UserSearchCard.vue -->
<script lang="ts" setup>
import {computed} from 'vue';
import {Button} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {UserPlusIcon, StarIcon, TrophyIcon} from 'lucide-vue-next';

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    rating?: number;
    avatar?: string;
    home_city?: {
        name: string;
        country: { name: string };
    };
    statistics?: {
        tournaments_played: number;
        tournaments_won: number;
        current_rating: number;
    };
}

interface Props {
    user: User;
    disabled?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    add: [userId: number];
}>();

const {t} = useLocale();

const fullName = computed(() => `${props.user.firstname} ${props.user.lastname}`);

const ratingColor = computed(() => {
    const rating = props.user.rating || props.user.statistics?.current_rating || 0;
    if (rating >= 2000) return 'text-red-600 dark:text-red-400';
    if (rating >= 1800) return 'text-orange-600 dark:text-orange-400';
    if (rating >= 1600) return 'text-yellow-600 dark:text-yellow-400';
    if (rating >= 1400) return 'text-green-600 dark:text-green-400';
    return 'text-blue-600 dark:text-blue-400';
});

const ratingBadge = computed(() => {
    const rating = props.user.rating || props.user.statistics?.current_rating || 0;
    if (rating >= 2000) return 'Master';
    if (rating >= 1800) return 'Expert';
    if (rating >= 1600) return 'Advanced';
    if (rating >= 1400) return 'Intermediate';
    return 'Beginner';
});

const handleAdd = () => {
    if (!props.disabled) {
        emit('add', props.user.id);
    }
};
</script>

<template>
    <div
        class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
        <div class="flex items-center space-x-4">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <div
                    v-if="user.avatar"
                    :style="{ backgroundImage: `url(${user.avatar})` }"
                    class="h-12 w-12 rounded-full bg-gray-300 dark:bg-gray-600 bg-cover bg-center"
                />
                <div
                    v-else
                    class="h-12 w-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium"
                >
                    {{ user.firstname.charAt(0) }}{{ user.lastname.charAt(0) }}
                </div>
            </div>

            <!-- User Info -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ fullName }}
                    </h4>

                    <!-- Rating Badge -->
                    <span
                        v-if="user.rating || user.statistics?.current_rating"
                        :class="['inline-flex items-center px-2 py-1 text-xs font-medium rounded-full', ratingColor, 'bg-gray-100 dark:bg-gray-800']"
                    >
            <StarIcon class="h-3 w-3 mr-1"/>
            {{ user.rating || user.statistics?.current_rating }}
          </span>

                    <!-- Skill Level -->
                    <span
                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-300">
            {{ ratingBadge }}
          </span>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                    {{ user.email }}
                </p>

                <!-- Additional Info -->
                <div class="flex items-center space-x-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
          <span v-if="user.home_city">
            📍 {{ user.home_city.name }}, {{ user.home_city.country.name }}
          </span>
                    <span v-if="user.statistics?.tournaments_played">
            <TrophyIcon class="h-3 w-3 inline mr-1"/>
            {{ user.statistics.tournaments_played }} {{ t('tournaments') }}
          </span>
                    <span v-if="user.statistics?.tournaments_won">
            🏆 {{ user.statistics.tournaments_won }} {{ t('wins') }}
          </span>
                </div>
            </div>
        </div>

        <!-- Add Button -->
        <div class="flex-shrink-0">
            <Button
                :disabled="disabled"
                size="sm"
                @click="handleAdd"
            >
                <UserPlusIcon class="h-4 w-4 mr-2"/>
                {{ t('Add') }}
            </Button>
        </div>
    </div>
</template>
