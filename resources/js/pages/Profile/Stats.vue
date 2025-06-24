<script lang="ts" setup>
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import {Head, Link} from '@inertiajs/vue3';
import {computed, onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';
// Убедись, что User тип импортирован и соответствует структуре UserResource
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import type {GameTypeStats, MatchGame, Rating, User, UserStats} from '@/types/api';
import {
    ActivityIcon,
    AwardIcon,
    BarChart4Icon,
    PercentIcon,
    SwordIcon,
    TrendingUpIcon,
    TrophyIcon
} from 'lucide-vue-next';
import DataTable from '@/Components/ui/data-table/DataTable.vue';

defineOptions({layout: AuthenticatedLayout});

const {user} = useAuth();
const {t} = useLocale();

// State for user stats
const userRatings = ref<Rating[]>([]);
const userMatches = ref<MatchGame[]>([]); // Предполагаем, что MatchGame включает firstPlayer/secondPlayer с user и rating
const overallStats = ref<UserStats | null>(null);
const gameTypeStats = ref<GameTypeStats | null>(null);
const isLoadingRatings = ref(false);
const isLoadingMatches = ref(false);
const isLoadingStats = ref(false);
const isLoadingGameTypeStats = ref(false);
const errorMessage = ref('');

// Format date for better display
const formatDate = (dateString: string | undefined | null): string => {
    if (!dateString) return t('N/A');

    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const winLossRatio = computed(() => {
    // Проверяем, есть ли overallStats и нужные свойства
    if (!overallStats.value || typeof overallStats.value.wins === 'undefined' || typeof overallStats.value.losses === 'undefined') {
        // Если данных нет, возвращаем 'N/A'
        return t('N/A');
    }

    // Безопасно получаем значения, используя 0 по умолчанию
    const wins = overallStats.value.wins ?? 0;
    const losses = overallStats.value.losses ?? 0;

    if (losses > 0) {
        // Если есть поражения, считаем соотношение
        return (wins / losses).toFixed(2);
    } else {
        // Если поражений нет
        if (wins > 0) {
            // А победы есть -> 'Perfect'
            return t('Perfect');
        } else {
            // Нет ни побед, ни поражений -> 'N/A'
            return t('N/A');
        }
    }
});
// Check if the user is the first player, using the structure from MatchGameResource
const isUserFirstPlayer = (match: MatchGame): boolean => {
    // Проверяем ID пользователя в объекте firstPlayer.user
    return !!user.value && !!match.firstPlayer?.user && match.firstPlayer.user.id === user.value.id;
};

// Get opponent name - using the structure from MatchGameResource
const getOpponentName = (match: MatchGame): string => {
    if (!user.value) return t('Error: User not loaded'); // Защитная проверка

    const userIsP1 = isUserFirstPlayer(match);
    let opponentUser: User | null | undefined;

    if (userIsP1) {
        // Если текущий юзер - P1, оппонент - P2
        // Используем optional chaining (?.) для безопасности
        opponentUser = match.secondPlayer?.user;
    } else {
        // Если текущий юзер - не P1, значит оппонент - P1
        opponentUser = match.firstPlayer?.user;

        // Дополнительная проверка: является ли текущий юзер P2?
        // Чтобы убедиться, что мы правильно определили оппонента
        const userIsP2 = !!match.secondPlayer?.user && match.secondPlayer.user.id === user.value.id;
        if (!userIsP2 && !userIsP1) {
            // Эта ситуация маловероятна, если API возвращает матчи только для текущего юзера
            console.warn(`User ${user.value.id} is neither P1 nor P2 (based on player objects) in match ${match.id}`);
        }
    }

    // Форматируем имя, если данные оппонента есть
    if (opponentUser?.lastname && opponentUser?.firstname) {
        return `${opponentUser.lastname} ${opponentUser.firstname.charAt(0)}.`;
    }

    return t('Unknown Opponent'); // Запасной вариант
};

// Correctly determine if user won the match, using structure from MatchGameResource
const userWonMatch = (match: MatchGame): boolean => {
    // Проверяем статус, ID победителя и наличие текущего пользователя
    if (match.status !== 'completed' || !match.winner_rating_id || !user.value) {
        return false;
    }

    let userRatingId: number | null | undefined = null;

    if (isUserFirstPlayer(match)) {
        // Юзер - P1, его ID рейтинга берем из first_rating_id (или из rating объекта, если нужно)
        userRatingId = match.first_rating_id;
        // Альтернатива: userRatingId = match.firstPlayer?.rating?.id; (если rating содержит id)
    } else {
        // Проверяем, является ли юзер P2
        const userIsP2 = !!match.secondPlayer?.user && match.secondPlayer.user.id === user.value.id;
        if (userIsP2) {
            // Юзер - P2, его ID рейтинга берем из second_rating_id
            userRatingId = match.second_rating_id;
            // Альтернатива: userRatingId = match.secondPlayer?.rating?.id;
        }
    }

    // Возвращаем true, если ID рейтинга пользователя (не null/undefined) совпадает с ID рейтинга победителя
    return !!userRatingId && userRatingId === match.winner_rating_id;
};

// Check if the user actually participated in the match
const didUserParticipate = (match: MatchGame): boolean => {
    if (!user.value) return false;
    const userIsP1 = !!match.firstPlayer?.user && match.firstPlayer.user.id === user.value.id;
    const userIsP2 = !!match.secondPlayer?.user && match.secondPlayer.user.id === user.value.id;
    return userIsP1 || userIsP2;
};

const getRatingChangeClass = (match: MatchGame): string => {
    // Сначала проверяем участие и статус
    if (!didUserParticipate(match) || match.status !== 'completed') {
        return 'text-gray-500 dark:text-gray-400';
    }
    // Если участвовал и матч завершен, проверяем победу
    if (userWonMatch(match)) {
        return 'text-green-600 dark:text-green-400';
    } else {
        // Участвовал, завершен, но не выиграл -> проиграл
        return 'text-red-600 dark:text-red-400';
    }
};

// Get rating change for the user (учитываем участие)
const getUserRatingChange = (match: MatchGame): string | number => {
    if (match.status !== 'completed') return '—'; // Не показываем изменение для незавершенных

    // Сначала проверяем, участвовал ли юзер
    if (!didUserParticipate(match)) {
        return t('N/A'); // Или '—', если пользователь не участвовал
    }

    if (userWonMatch(match)) {
        // Добавляем '+' для положительного изменения
        return `+${match.rating_change_for_winner ?? 0}`;
    } else {
        // Если участвовал и не выиграл - значит проиграл
        return match.rating_change_for_loser ?? 0;
    }
};

// Get match result label (учитываем участие)
const getMatchResult = (match: MatchGame): string => {
    // Обработка незавершенных статусов
    if (match.status !== 'completed') {
        if (match.status === 'pending' || match.status === 'must_be_confirmed') return t('Pending');
        if (match.status === 'in_progress') return t('In Progress');
        // Можно добавить обработку 'cancelled', 'draw' если такие статусы есть
        return match.status.charAt(0).toUpperCase() + match.status.slice(1); // Форматируем другие статусы
    }

    // Для завершенных матчей
    if (!didUserParticipate(match)) {
        return t('N/A'); // Если пользователь не участвовал
    }

    return userWonMatch(match) ? t('Win') : t('Loss');
};

// Get result class (учитываем участие)
const getResultClass = (match: MatchGame): string => {
    // Стили для разных статусов
    if (match.status === 'pending' || match.status === 'must_be_confirmed') {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
    }
    if (match.status === 'in_progress') {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
    if (match.status !== 'completed') {
        // Общий стиль для остальных незавершенных (e.g., cancelled)
        return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
    }

    // Для завершенных матчей
    if (!didUserParticipate(match)) {
        return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'; // Стиль для неучастника
    }

    return userWonMatch(match)
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' // Win
        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'; // Loss
};

// --- КОНЕЦ ИСПРАВЛЕННЫХ ФУНКЦИЙ ---

// Fetch user ratings across all leagues
const fetchUserRatings = async () => {
    if (!user.value?.id) return;

    isLoadingRatings.value = true;
    errorMessage.value = ''; // Сбрасываем ошибку перед запросом
    try {
        userRatings.value = await apiClient<Rating[]>('/api/user/ratings');
    } catch (error) {
        console.error('Failed to load user ratings:', error);
        errorMessage.value = 'Failed to load your league ratings. Please try again later.';
    } finally {
        isLoadingRatings.value = false;
    }
};

// Fetch user match history across all leagues
const fetchUserMatches = async () => {
    if (!user.value?.id) return;

    isLoadingMatches.value = true;
    errorMessage.value = '';
    try {
        // Убедись, что API возвращает MatchGame в формате MatchGameResource
        userMatches.value = await apiClient<MatchGame[]>('/api/user/matches');
    } catch (error) {
        console.error('Failed to load user matches:', error);
        errorMessage.value = 'Failed to load your match history. Please try again later.';
    } finally {
        isLoadingMatches.value = false;
    }
};

// Fetch overall user stats
const fetchOverallStats = async () => {
    if (!user.value?.id) return;

    isLoadingStats.value = true;
    errorMessage.value = '';
    try {
        overallStats.value = await apiClient<UserStats>('/api/user/stats');
    } catch (error) {
        console.error('Failed to load overall stats:', error);
        errorMessage.value = 'Failed to load your statistics. Please try again later.';
    } finally {
        isLoadingStats.value = false;
    }
};

// Fetch game type statistics
const fetchGameTypeStats = async () => {
    if (!user.value?.id) return;

    isLoadingGameTypeStats.value = true;
    errorMessage.value = '';
    try {
        gameTypeStats.value = await apiClient<GameTypeStats>('/api/user/game-type-stats');
    } catch (error) {
        console.error('Failed to load game type stats:', error);
        errorMessage.value = 'Failed to load game type statistics. Please try again later.';
    } finally {
        isLoadingGameTypeStats.value = false;
    }
};

// Get game type display name
const getGameTypeDisplayName = (type: string): string => {
    const types: Record<string, string> = {
        pool: 'Pool',
        pyramid: 'Pyramid',
        snooker: 'Snooker',
        // Добавь другие типы игр, если есть
    };

    return types[type] || type.charAt(0).toUpperCase() + type.slice(1); // Форматируем неизвестные типы
};

// Load data on component mount
onMounted(() => {
    // Вызываем все функции загрузки
    fetchUserRatings();
    fetchUserMatches();
    fetchOverallStats();
    fetchGameTypeStats();
});

// Add columns definitions
const gameTypeColumns = computed(() => [
    {
        key: 'game_type',
        label: t('Game Type'),
        align: 'left' as const,
        render: (stats: any, type: string) => getGameTypeDisplayName(type)
    },
    {
        key: 'matches',
        label: t('Matches'),
        align: 'center' as const,
        render: (stats: any) => stats.matches
    },
    {
        key: 'wins',
        label: t('Wins'),
        align: 'center' as const,
        render: (stats: any) => stats.wins
    },
    {
        key: 'losses',
        label: t('Losses'),
        align: 'center' as const,
        render: (stats: any) => stats.losses
    },
    {
        key: 'win_rate',
        label: t('Win Rate'),
        align: 'center' as const,
        render: (stats: any) => stats.win_rate
    }
]);

const leagueRatingsColumns = computed(() => [
    {
        key: 'league',
        label: t('League'),
        align: 'left' as const,
        render: (rating: any) => rating.league?.name ?? t('Unknown League')
    },
    {
        key: 'game_type',
        label: t('Game Type'),
        align: 'left' as const,
        render: (rating: any) => getGameTypeDisplayName(rating.league?.game_type ?? 'unknown')
    },
    {
        key: 'rating',
        label: t('Rating'),
        align: 'right' as const,
        render: (rating: any) => rating.rating
    },
    {
        key: 'position',
        label: t('Position'),
        align: 'right' as const,
        render: (rating: any) => rating.position
    },
    {
        key: 'matches',
        label: t('Matches'),
        align: 'right' as const,
        render: (rating: any) => rating.matches_count ?? 0
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (rating: any) => rating.is_active
    }
]);

const recentMatchesColumns = computed(() => [
    {
        key: 'date',
        label: t('Date'),
        align: 'left' as const,
        render: (match: any) => formatDate(match.finished_at ?? match.updated_at ?? match.created_at)
    },
    {
        key: 'league',
        label: t('League'),
        align: 'left' as const,
        render: (match: any) => match.league?.name ?? t('N/A')
    },
    {
        key: 'opponent',
        label: t('Opponent'),
        align: 'left' as const,
        render: (match: any) => getOpponentName(match)
    },
    {
        key: 'score',
        label: t('Score (You - Opp.)'),
        align: 'center' as const,
        render: (match: any) => ({
            firstScore: isUserFirstPlayer(match) ? match.first_user_score : match.second_user_score,
            secondScore: isUserFirstPlayer(match) ? match.second_user_score : match.first_user_score
        })
    },
    {
        key: 'result',
        label: t('Result'),
        align: 'center' as const,
        render: (match: any) => ({
            result: getMatchResult(match),
            class: getResultClass(match)
        })
    },
    {
        key: 'rating_change',
        label: t('Rating Change'),
        align: 'center' as const,
        render: (match: any) => ({
            change: getUserRatingChange(match),
            class: getRatingChangeClass(match)
        })
    }
]);
</script>

<template>
    <Head :title="t('Profile Statistics')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <!-- Profile Navigation -->
            <div class="mb-6 flex space-x-4">
                <Link :href="route('profile.edit')">
                    <Button class="bg-gray-100 dark:bg-gray-800" variant="outline">{{ t('Edit Profile') }}</Button>
                </Link>
                <Link :href="route('profile.stats')">
                    <Button class="bg-primary text-primary-foreground" variant="outline">{{ t('Statistics') }}</Button>
                </Link>
            </div>

            <!-- Error message section, keep it as is -->
            <div
                v-if="errorMessage"
                class="relative rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700 dark:border-red-600 dark:bg-red-900/30 dark:text-red-400"
                role="alert"
            >
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"> {{ errorMessage }}</span>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Statistics Overview') }}</CardTitle>
                    <CardDescription>{{ t('Your performance across all leagues') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoadingStats" class="flex min-h-[100px] items-center justify-center py-8">
                        <Spinner class="text-primary h-8 w-8"/>
                    </div>
                    <div v-else-if="overallStats" class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                            <div class="mb-2 flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                                <SwordIcon class="h-5 w-5"/>
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">{{
                                        t('Total Matches')
                                    }}</h3>
                            </div>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {{ overallStats.total_matches ?? 0 }}
                            </p>
                        </div>
                        <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                            <div class="mb-2 flex items-center space-x-2 text-green-600 dark:text-green-400">
                                <TrophyIcon class="h-5 w-5"/>
                                <h3 class="text-sm font-medium text-green-800 dark:text-green-300">{{ t('Wins') }}</h3>
                            </div>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{
                                    overallStats.wins ?? 0
                                }}</p>
                        </div>
                        <div class="rounded-md bg-amber-50 p-4 dark:bg-amber-900/20">
                            <div class="mb-2 flex items-center space-x-2 text-amber-600 dark:text-amber-400">
                                <PercentIcon class="h-5 w-5"/>
                                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-300">{{
                                        t('Win Rate')
                                    }}</h3>
                            </div>
                            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                                {{ overallStats.win_rate ?? 0 }}%</p>
                        </div>
                        <div class="rounded-md bg-purple-50 p-4 dark:bg-purple-900/20">
                            <div class="mb-2 flex items-center space-x-2 text-purple-600 dark:text-purple-400">
                                <BarChart4Icon class="h-5 w-5"/>
                                <h3 class="text-sm font-medium text-purple-800 dark:text-purple-300">
                                    {{ t('League Memberships') }}</h3>
                            </div>
                            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                {{ overallStats.leagues_count ?? 0 }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-gray-500 dark:text-gray-400">
                        {{ t('No overall statistics available.') }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Rating Analytics') }}</CardTitle>
                    <CardDescription>{{ t('Your rating performance metrics') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoadingStats" class="flex min-h-[100px] items-center justify-center py-8">
                        <Spinner class="text-primary h-8 w-8"/>
                    </div>
                    <div v-else-if="overallStats" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800/50">
                            <div class="mb-2 flex items-center space-x-2 text-indigo-600 dark:text-indigo-400">
                                <AwardIcon class="h-5 w-5"/>
                                <h3 class="text-sm font-medium text-indigo-800 dark:text-indigo-300">
                                    {{ t('Highest Rating') }}</h3>
                            </div>
                            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ overallStats.highest_rating ?? 0 }}
                            </p>
                        </div>
                        <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800/50">
                            <div class="mb-2 flex items-center space-x-2 text-teal-600 dark:text-teal-400">
                                <TrendingUpIcon class="h-5 w-5"/>
                                <h3 class="text-sm font-medium text-teal-800 dark:text-teal-300">{{
                                        t('Average Rating')
                                    }}</h3>
                            </div>
                            <p class="text-3xl font-bold text-teal-600 dark:text-teal-400">
                                {{ overallStats.average_rating ?? 0 }}
                            </p>
                        </div>
                        <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-800/50">
                            <div class="mb-2 flex items-center space-x-2 text-pink-600 dark:text-pink-400">
                                <ActivityIcon class="h-5 w-5"/>
                                <h3 class="text-sm font-medium text-pink-800 dark:text-pink-300">{{
                                        t('Win/Loss Ratio')
                                    }}</h3>
                            </div>
                            <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">
                                {{ winLossRatio }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-gray-500 dark:text-gray-400">
                        {{ t('No rating analytics available.') }}
                    </div>
                </CardContent>
            </Card>

            <Card v-if="!isLoadingGameTypeStats && gameTypeStats && Object.keys(gameTypeStats).length > 0">
                <CardHeader>
                    <CardTitle>{{ t('Game Type Performance') }}</CardTitle>
                    <CardDescription>{{ t('Your statistics by game type') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="gameTypeColumns"
                        :compact-mode="true"
                        :data="Object.entries(gameTypeStats).map(([type, stats]) => ({ type, ...stats }))"
                        :empty-message="t('No game type statistics available.')"
                        :loading="isLoadingGameTypeStats"
                    >
                        <template #cell-wins="{ value }">
                            <span class="text-green-600 dark:text-green-400">{{ value }}</span>
                        </template>

                        <template #cell-losses="{ value }">
                            <span class="text-red-600 dark:text-red-400">{{ value }}</span>
                        </template>

                        <template #cell-win_rate="{ value }">
                            <span class="font-semibold">{{ value }}%</span>
                        </template>
                    </DataTable>
                </CardContent>
            </Card>
            <Card v-else-if="isLoadingGameTypeStats">
                <CardHeader>
                    <CardTitle>{{ t('Game Type Performance') }}</CardTitle>
                    <CardDescription>{{ t('Loading game type statistics...') }}</CardDescription>
                </CardHeader>
                <CardContent class="flex min-h-[100px] items-center justify-center py-8">
                    <Spinner class="text-primary h-8 w-8"/>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('League Ratings') }}</CardTitle>
                    <CardDescription>{{ t('Your current ratings across different leagues') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="leagueRatingsColumns"
                        :compact-mode="true"
                        :data="userRatings"
                        :empty-message="t('You haven\'t joined any leagues yet or no ratings found.')"
                        :loading="isLoadingRatings"
                    >
                        <template #cell-position="{ value }">
                            #{{ value ?? t('N/A') }}
                        </template>

                        <template #cell-status="{ value }">
                            <span
                                :class="value ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'"
                                class="rounded-full px-2 py-1 text-xs font-medium"
                            >
                                {{ value ? t('Active') : t('Inactive') }}
                            </span>
                        </template>
                    </DataTable>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Recent Matches') }}</CardTitle>
                    <CardDescription>{{ t('Your most recent match results (up to 15)') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="recentMatchesColumns"
                        :compact-mode="true"
                        :data="userMatches.slice(0, 15)"
                        :empty-message="t('You haven\'t played any matches yet.')"
                        :loading="isLoadingMatches"
                    >
                        <template #cell-score="{ value }">
                            <span class="font-medium whitespace-nowrap">
                                {{ value.firstScore ?? '-' }} : {{ value.secondScore ?? '-' }}
                            </span>
                        </template>

                        <template #cell-result="{ value }">
                            <span :class="value.class"
                                  class="rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap">
                                {{ value.result }}
                            </span>
                        </template>

                        <template #cell-rating_change="{ value }">
                            <span :class="value.class" class="font-medium">
                                {{ value.change }}
                            </span>
                        </template>
                    </DataTable>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
