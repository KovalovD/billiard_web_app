<!-- resources/js/Components/Tournament/PlayerList.vue -->
<script lang="ts" setup>
import {computed} from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    Button
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {UserMinusIcon, CrownIcon, StarIcon} from 'lucide-vue-next';
import type {TournamentPlayer, Tournament} from '@/types/api';

interface Props {
    players: TournamentPlayer[];
    tournament: Tournament | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    remove: [userId: number];
}>();

const {t} = useLocale();

const sortedPlayers = computed(() => {
    return [...props.players].sort((a, b) => {
        // Sort by seed position if available, then by registration time
        if (a.seed_position !== null && b.seed_position !== null) {
            return a.seed_position - b.seed_position;
        }
        if (a.seed_position !== null) return -1;
        if (b.seed_position !== null) return 1;
        return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
    });
});

const getRatingColor = (rating: number): string => {
    if (rating >= 2000) return 'text-red-600 dark:text-red-400';
    if (rating >= 1800) return 'text-orange-600 dark:text-orange-400';
    if (rating >= 1600) return 'text-yellow-600 dark:text-yellow-400';
    if (rating >= 1400) return 'text-green-600 dark:text-green-400';
    return 'text-blue-600 dark:text-blue-400';
};

const getSeedDisplay = (position: number | null): string => {
    if (position === null) return '—';
    return `#${position}`;
};

const handleRemove = (userId: number) => {
    emit('remove', userId);
};
</script>

<template>
    <Card class="sticky top-4">
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                {{ t('Registered Players') }}
                <span class="text-sm font-normal text-gray-500">
          {{ players.length }} / {{ tournament?.max_participants || 0 }}
        </span>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="players.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-4">👥</div>
                <p>{{ t('No players registered yet') }}</p>
                <p class="text-sm mt-2">{{ t('Use the tabs above to add players') }}</p>
            </div>

            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                <div
                    v-for="(player, index) in sortedPlayers"
                    :key="player.id"
                    class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                >
                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                        <!-- Seed Position -->
                        <div class="flex-shrink-0 w-8 text-center">
              <span v-if="player.seed_position" class="text-xs font-medium text-gray-600 dark:text-gray-400">
                {{ getSeedDisplay(player.seed_position) }}
              </span>
                            <span v-else class="text-xs font-medium text-gray-400">
                {{ index + 1 }}
              </span>
                        </div>

                        <!-- Player Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ player.user?.firstname }} {{ player.user?.lastname }}
                                </h4>

                                <!-- Seed Badge -->
                                <CrownIcon v-if="player.seed_position === 1" class="h-3 w-3 text-yellow-500"/>

                                <!-- Rating -->
                                <span
                                    v-if="player.user?.rating"
                                    :class="['text-xs font-medium', getRatingColor(player.user.rating)]"
                                >
                  <StarIcon class="h-3 w-3 inline mr-1"/>
                  {{ player.user.rating }}
                </span>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ player.user?.email }}
                            </p>

                            <!-- Additional Info -->
                            <div class="flex items-center space-x-2 mt-1">
                <span v-if="player.status === 'confirmed'"
                      class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium text-green-800 bg-green-100 rounded dark:bg-green-900/30 dark:text-green-300">
                  ✓ {{ t('Confirmed') }}
                </span>
                                <span v-else-if="player.status === 'applied'"
                                      class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium text-yellow-800 bg-yellow-100 rounded dark:bg-yellow-900/30 dark:text-yellow-300">
                  ⏳ {{ t('Pending') }}
                </span>

                                <span v-if="player.team_id" class="text-xs text-blue-600 dark:text-blue-400">
                  👥 {{ t('Team') }} {{ player.team_id }}
                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <div class="flex-shrink-0">
                        <Button
                            class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                            size="sm"
                            variant="ghost"
                            @click="handleRemove(player.user_id)"
                        >
                            <UserMinusIcon class="h-4 w-4"/>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div v-if="players.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                            {{ players.filter(p => p.status === 'confirmed').length }}
                        </div>
                        <div class="text-xs text-gray-500">{{ t('Confirmed') }}</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                            {{ players.filter(p => p.status === 'applied').length }}
                        </div>
                        <div class="text-xs text-gray-500">{{ t('Pending') }}</div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
