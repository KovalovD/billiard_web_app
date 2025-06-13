<!-- resources/js/Components/Tournament/PrizeDistribution.vue -->
<script lang="ts" setup>
import {computed} from 'vue';
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {DollarSignIcon, GiftIcon, TrendingUpIcon, TrophyIcon} from 'lucide-vue-next';
import type {Tournament} from '@/types/api';

interface PrizeDistribution {
    totalPool: number;
    totalDistributed: number;
    breakdown: Array<{
        position: number;
        percentage: number;
        amount: number;
    }>;
}

interface PlayerStanding {
    position: number;
    player: {
        firstname: string;
        lastname: string;
    };
    prize_amount: number;
    bonus_amount: number;
    achievement_amount: number;
    total_amount: number;
}

interface Props {
    distribution: PrizeDistribution;
    standings: PlayerStanding[];
    tournament: Tournament | null;
}

const props = defineProps<Props>();

const {t} = useLocale();

// Computed
const prizeBreakdown = computed(() => {
    return props.distribution.breakdown.map(item => {
        const recipient = props.standings.find(s => s.position === item.position);
        return {
            ...item,
            recipient: recipient ? `${recipient.player.firstname} ${recipient.player.lastname}` : null,
            actualAmount: recipient?.prize_amount || 0
        };
    });
});

const bonusDistribution = computed(() => {
    return props.standings
        .filter(s => s.bonus_amount > 0)
        .map(s => ({
            player: `${s.player.firstname} ${s.player.lastname}`,
            position: s.position,
            amount: s.bonus_amount,
            reason: getBonusReason(s.position)
        }));
});

const achievementDistribution = computed(() => {
    return props.standings
        .filter(s => s.achievement_amount > 0)
        .map(s => ({
            player: `${s.player.firstname} ${s.player.lastname}`,
            position: s.position,
            amount: s.achievement_amount,
            achievement: getAchievementType(s.position)
        }));
});

const totalAwarded = computed(() => {
    return props.standings.reduce((sum, s) => sum + s.total_amount, 0);
});

const unawarded = computed(() => {
    return Math.max(0, props.distribution.totalPool - totalAwarded.value);
});

// Methods
const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + '₴';
};

const getPositionLabel = (position: number): string => {
    switch (position) {
        case 1:
            return t('Champion');
        case 2:
            return t('Runner-up');
        case 3:
            return t('Third Place');
        case 4:
            return t('Fourth Place');
        default:
            return t('Position :pos', {pos: position});
    }
};

const getBonusReason = (position: number): string => {
    switch (position) {
        case 1:
            return t('Championship Bonus');
        case 2:
            return t('Runner-up Bonus');
        case 3:
            return t('Third Place Bonus');
        default:
            return t('Performance Bonus');
    }
};

const getAchievementType = (position: number): string => {
    // Mock achievement types
    const achievements = [
        t('Perfect Game'),
        t('Comeback Victory'),
        t('Highest Break'),
        t('Most Frames Won'),
        t('Best Newcomer')
    ];
    return achievements[position % achievements.length];
};
</script>

<template>
    <div class="space-y-8">
        <!-- Prize Pool Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <Card>
                <CardContent class="p-6 text-center">
                    <DollarSignIcon class="mx-auto h-12 w-12 text-green-600 mb-4"/>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ formatCurrency(distribution.totalPool) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Prize Pool') }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <TrophyIcon class="mx-auto h-12 w-12 text-yellow-600 mb-4"/>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ formatCurrency(totalAwarded) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Total Awarded') }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <TrendingUpIcon class="mx-auto h-12 w-12 text-blue-600 mb-4"/>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ Math.round((totalAwarded / distribution.totalPool) * 100) }}%
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Distribution Rate') }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6 text-center">
                    <GiftIcon class="mx-auto h-12 w-12 text-purple-600 mb-4"/>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ formatCurrency(unawarded) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Remaining') }}</div>
                </CardContent>
            </Card>
        </div>

        <!-- Main Prize Distribution -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <TrophyIcon class="h-5 w-5 text-yellow-600"/>
                    {{ t('Prize Distribution') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="prize in prizeBreakdown"
                        :key="prize.position"
                        class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                    >
                        <div class="flex items-center space-x-4">
                            <div
                                :class="['w-12 h-12 rounded-full flex items-center justify-center text-white font-bold',
                         prize.position === 1 ? 'bg-yellow-500' :
                         prize.position === 2 ? 'bg-gray-400' :
                         prize.position === 3 ? 'bg-orange-500' : 'bg-blue-500']"
                            >
                                {{ prize.position }}
                            </div>

                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ getPositionLabel(prize.position) }}
                                </div>
                                <div v-if="prize.recipient" class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ prize.recipient }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ prize.percentage }}% {{ t('of prize pool') }}
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ formatCurrency(prize.actualAmount) }}
                            </div>
                            <div v-if="prize.actualAmount !== prize.amount" class="text-sm text-gray-500 line-through">
                                {{ formatCurrency(prize.amount) }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Bonus Distribution -->
        <Card v-if="bonusDistribution.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <GiftIcon class="h-5 w-5 text-purple-600"/>
                    {{ t('Bonus Distribution') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div
                        v-for="bonus in bonusDistribution"
                        :key="`bonus-${bonus.position}`"
                        class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800"
                    >
                        <div>
                            <div class="font-medium text-purple-800 dark:text-purple-200">
                                {{ bonus.player }}
                            </div>
                            <div class="text-sm text-purple-600 dark:text-purple-300">
                                {{ bonus.reason }}
                            </div>
                        </div>
                        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                            {{ formatCurrency(bonus.amount) }}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Achievement Awards -->
        <Card v-if="achievementDistribution.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <TrendingUpIcon class="h-5 w-5 text-orange-600"/>
                    {{ t('Achievement Awards') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div
                        v-for="achievement in achievementDistribution"
                        :key="`achievement-${achievement.position}`"
                        class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800"
                    >
                        <div>
                            <div class="font-medium text-orange-800 dark:text-orange-200">
                                {{ achievement.player }}
                            </div>
                            <div class="text-sm text-orange-600 dark:text-orange-300">
                                🏆 {{ achievement.achievement }}
                            </div>
                        </div>
                        <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                            {{ formatCurrency(achievement.amount) }}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Distribution Summary -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('Distribution Summary') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ standings.filter(s => s.total_amount > 0).length }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Players Paid') }}</div>
                    </div>

                    <div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ formatCurrency(standings.reduce((sum, s) => sum + s.prize_amount, 0)) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Prize Money') }}</div>
                    </div>

                    <div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ formatCurrency(standings.reduce((sum, s) => sum + s.bonus_amount, 0)) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Bonus Money') }}</div>
                    </div>

                    <div>
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ formatCurrency(standings.reduce((sum, s) => sum + s.achievement_amount, 0)) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Achievement Awards') }}</div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
