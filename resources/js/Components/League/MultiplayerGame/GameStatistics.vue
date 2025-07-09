<script lang="ts" setup>
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {BarChart3Icon, CreditCardIcon, HeartIcon, TrophyIcon} from 'lucide-vue-next';

// eslint-disable-next-line
const props = defineProps<{
    statistics: any;
}>();
const {t} = useLocale();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('uk-UA', {style: 'currency', currency: 'UAH'})
        .format(amount)
        .replace('UAH', '₴');
};
</script>

<template>
    <div class="space-y-6">
        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">{{ t('Total Shots') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-2">
                        <BarChart3Icon class="h-4 w-4 text-gray-400"/>
                        <span class="text-2xl font-bold">{{ statistics.total_shots }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">8 {{ t('Balls Potted') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-2">
                        <TrophyIcon class="h-4 w-4 text-gray-400"/>
                        <span class="text-2xl font-bold">{{ statistics.total_balls_potted }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">{{ t('Cards Used') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-2">
                        <CreditCardIcon class="h-4 w-4 text-gray-400"/>
                        <span class="text-2xl font-bold">{{ statistics.total_cards_used }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">{{ t('Total Rebuys') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-2">
                        <HeartIcon class="h-4 w-4 text-gray-400"/>
                        <span class="text-2xl font-bold">{{ statistics.total_rebuys }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ formatCurrency(statistics.total_rebuy_amount) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Player Statistics Table -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('Player Statistics') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">{{ t('Player') }}</th>
                            <th class="text-center py-2">{{ t('Shots') }}</th>
                            <th class="text-center py-2">8 {{ t('Balls Potted') }}</th>
                            <th class="text-center py-2">{{ t('Lives +/-') }}</th>
                            <th class="text-center py-2">{{ t('Cards') }}</th>
                            <th class="text-center py-2">{{ t('Turns') }}</th>
                            <th class="text-center py-2">{{ t('Rebuys') }}</th>
                            <th class="text-right py-2">{{ t('Total Paid') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="player in statistics.players" :key="player.user.id" class="border-b">
                            <td class="py-2">
                                <div>
                                    <p class="font-medium">{{ player.user.name }}</p>
                                    <p v-if="player.finish_position" class="text-xs text-gray-500">
                                        {{ t('Position') }}: {{ player.finish_position }}
                                    </p>
                                </div>
                            </td>
                            <td class="text-center py-2">{{ player.stats.shots_taken }}</td>
                            <td class="text-center py-2">{{ player.stats.balls_potted }}</td>
                            <td class="text-center py-2">
                                <span class="text-green-600">+{{ player.stats.lives_gained }}</span>
                                /
                                <span class="text-red-600">-{{ player.stats.lives_lost }}</span>
                            </td>
                            <td class="text-center py-2">{{ player.stats.cards_used }}</td>
                            <td class="text-center py-2">{{ player.stats.turns_played }}</td>
                            <td class="text-center py-2">
                                    <span v-if="player.rebuy_count > 0" class="font-medium">
                                        {{ player.rebuy_count }}
                                    </span>
                                <span v-else>—</span>
                            </td>
                            <td class="text-right py-2 font-medium">
                                {{ formatCurrency(player.total_paid) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Rebuy History -->
        <Card v-if="statistics?.rebuy_history?.length > 0">
            <CardHeader>
                <CardTitle>{{ t('Rebuy History') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div v-for="(rebuy, index) in statistics?.rebuy_history" :key="index"
                         class="flex items-center justify-between py-2 border-b last:border-0">
                        <div>
                            <p class="font-medium">{{ rebuy.user_name }}</p>
                            <p class="text-sm text-gray-500">
                                {{ new Date(rebuy.timestamp).toLocaleString() }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium">{{ formatCurrency(rebuy.amount) }}</p>
                            <p class="text-sm text-gray-500">
                                <span v-if="rebuy.type === 'new_player'" class="text-green-600">
                                    {{ t('New Player') }}
                                </span>
                                <span v-else>
                                    {{ t('Rebuy') }} #{{ rebuy.round }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
