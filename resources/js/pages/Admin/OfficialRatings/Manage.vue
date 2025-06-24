<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useOfficialRatings} from '@/composables/useOfficialRatings';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {OfficialRating} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {ArrowLeftIcon, RefreshCwIcon, SettingsIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    ratingId: number | string;
}>();

const {
    fetchOfficialRating,
    recalculateRatingPositions
} = useOfficialRatings();

const {t} = useLocale();

const rating = ref<OfficialRating | null>(null);
const isLoading = ref(true);
const isRecalculating = ref(false);
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);

const managementOptions = [
    {
        title: t('Manage Players'),
        description: t('Add, remove, and manage players in this rating'),
        icon: UsersIcon,
        href: `/admin/official-ratings/${props.ratingId}/players`,
        color: 'blue'
    },
    {
        title: t('Manage Tournaments'),
        description: t('Associate tournaments with this rating and manage coefficients'),
        icon: TrophyIcon,
        href: `/admin/official-ratings/${props.ratingId}/tournaments`,
        color: 'green'
    },
    {
        title: t('Edit Rating Settings'),
        description: t('Modify rating configuration and settings'),
        icon: SettingsIcon,
        href: `/admin/official-ratings/${props.ratingId}/edit`,
        color: 'purple'
    }
];

const quickActions = [
    {
        title: t('Recalculate Positions'),
        description: t('Recalculate all player positions based on current ratings'),
        icon: RefreshCwIcon,
        action: 'recalculate',
        color: 'orange'
    }
];

const fetchData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const ratingResponse = await fetchOfficialRating(props.ratingId, true);
        if (ratingResponse.execute) {
            const success = await ratingResponse.execute();
            if (success) {
                rating.value = ratingResponse.data.value!;
            }
        }
    } catch (err: any) {
        error.value = err.message || t('Failed to load rating data');
    } finally {
        isLoading.value = false;
    }
};

const handleRecalculate = async () => {
    isRecalculating.value = true;
    error.value = null;
    successMessage.value = null;

    try {
        const recalculateAction = recalculateRatingPositions(props.ratingId);
        const success = await recalculateAction.execute();

        if (success) {
            successMessage.value = t('Player positions recalculated successfully!');
            await fetchData(); // Refresh data
        } else if (recalculateAction.error.value) {
            error.value = recalculateAction.error.value.message || t('Failed to recalculate positions');
        }
    } catch (err: any) {
        error.value = err.message || t('Failed to recalculate positions');
    } finally {
        isRecalculating.value = false;
    }
};

const handleQuickAction = async (action: string) => {
    if (action === 'recalculate') {
        await handleRecalculate();
    }
};

const getColorClasses = (color: string) => {
    const colorMap: Record<string, string> = {
        blue: 'bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 border-blue-200 dark:border-blue-800',
        green: 'bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30 border-green-200 dark:border-green-800',
        purple: 'bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/20 dark:hover:bg-purple-900/30 border-purple-200 dark:border-purple-800',
        orange: 'bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:hover:bg-orange-900/30 border-orange-200 dark:border-orange-800'
    };
    return colorMap[color] || colorMap.blue;
};

const getIconColorClasses = (color: string) => {
    const colorMap: Record<string, string> = {
        blue: 'text-blue-600 dark:text-blue-400',
        green: 'text-green-600 dark:text-green-400',
        purple: 'text-purple-600 dark:text-purple-400',
        orange: 'text-orange-600 dark:text-orange-400'
    };
    return colorMap[color] || colorMap.blue;
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head :title="rating ? t('Manage Rating: :name', {name: rating.name}) : t('Manage Official Rating')"/>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="`/official-ratings/${props.ratingId}`">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Rating') }}
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ t('Rating Management') }}
                </h1>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-10">
                <Spinner class="text-primary h-8 w-8"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">{{ t('Loading rating...') }}</span>
            </div>

            <!-- Error Message -->
            <div v-else-if="error"
                 class="mb-6 rounded bg-red-100 p-4 text-red-500 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Success Message -->
            <div v-if="successMessage"
                 class="mb-6 rounded bg-green-100 p-4 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <template v-if="!isLoading && rating">
                <!-- Rating Overview -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <SettingsIcon class="h-5 w-5"/>
                            {{ rating.name }}
                        </CardTitle>
                        <CardDescription>
                            {{ rating.game?.name }} • {{ rating.players_count }} {{ t('Players') }} • {{
                                rating.tournaments_count
                            }} {{ t('Tournaments') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ rating.players_count }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Active Players') }}</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ rating.tournaments_count }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Tournaments') }}</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ rating.initial_rating }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Initial Rating') }}</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                                <div
                                    :class="rating.is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                    class="text-2xl font-bold">
                                    {{ rating.is_active ? t('Active') : t('Inactive') }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ t('Status') }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Management Options -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 mb-8">
                    <Link
                        v-for="option in managementOptions"
                        :key="option.title"
                        :href="option.href"
                        class="block"
                    >
                        <Card
                            :class="[
                                'h-full transition-all duration-200 cursor-pointer border-2',
                                getColorClasses(option.color)
                            ]"
                        >
                            <CardHeader>
                                <CardTitle class="flex items-center gap-3">
                                    <component
                                        :is="option.icon"
                                        :class="['h-6 w-6', getIconColorClasses(option.color)]"
                                    />
                                    {{ option.title }}
                                </CardTitle>
                                <CardDescription>
                                    {{ option.description }}
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    </Link>
                </div>

                <!-- Quick Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Quick Actions') }}</CardTitle>
                        <CardDescription>
                            {{ t('Perform common management tasks quickly') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div
                                v-for="action in quickActions"
                                :key="action.title"
                                :class="[
                                    'p-4 border-2 rounded-lg transition-all duration-200 cursor-pointer',
                                    getColorClasses(action.color)
                                ]"
                                @click="handleQuickAction(action.action)"
                            >
                                <div class="flex items-start gap-3">
                                    <component
                                        :is="action.icon"
                                        :class="[
                                            'h-5 w-5 mt-0.5',
                                            getIconColorClasses(action.color),
                                            { 'animate-spin': action.action === 'recalculate' && isRecalculating }
                                        ]"
                                    />
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ action.title }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ action.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Activity Summary -->
                <Card v-if="rating.top_players && rating.top_players.length > 0" class="mt-8">
                    <CardHeader>
                        <CardTitle>{{ t('Top Players') }}</CardTitle>
                        <CardDescription>
                            {{ t('Current top performers in this rating') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(player, index) in rating.top_players.slice(0, 5)"
                                :key="player.id"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-800"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'flex items-center justify-center h-8 w-8 rounded-full text-sm font-medium',
                                            index === 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                            index === 1 ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' :
                                            index === 2 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' :
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                        ]"
                                    >
                                        {{ player.position }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ player.user?.firstname }} {{
                                                player.user?.lastname
                                            }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ t(':count tournaments played', {count: player.tournaments_played}) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-lg">{{ player.rating_points }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ t(':percent% win rate', {percent: player.win_rate}) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </div>
</template>
