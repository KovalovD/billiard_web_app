// resources/js/pages/Admin/ConfirmedPlayers.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useLocale} from '@/composables/useLocale';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, League, Rating} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {ArrowLeftIcon, UserMinusIcon} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: number | string;
}>();

const {isAdmin} = useAuth();
const {t} = useLocale();
const confirmedPlayers = ref<Rating[]>([]);
const selectedPlayers = ref<number[]>([]);
const league = ref<League | null>(null);
const isLoading = ref(true);
const isProcessing = ref(false);
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null);

// Fetch the league
const fetchLeague = async () => {
    try {
        league.value = await apiClient<League>(`/api/leagues/${props.leagueId}`);
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Не вдалося завантажити лігу',
        };
    }
};

// Fetch confirmed players
const fetchConfirmedPlayers = async () => {
    isLoading.value = true;
    try {
        confirmedPlayers.value = await apiClient<Rating[]>(`/api/leagues/${props.leagueId}/admin/confirmed-players`);
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Не вдалося завантажити підтверджених гравців',
        };
    } finally {
        isLoading.value = false;
    }
};

// Deactivate individual player
const deactivatePlayer = async (ratingId: number) => {
    if (!confirm(t('Ви впевнені, що хочете деактивувати цього гравця? Він більше не зможе брати участь у цій лізі.'))) {
        return;
    }

    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/admin/deactivate-player/${ratingId}`, {
            method: 'post',
        });
        message.value = {
            type: 'success',
            text: t('Гравця успішно деактивовано'),
        };
        // Refresh the list
        await fetchConfirmedPlayers();
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || t('Не вдалося деактивувати гравця'),
        };
    } finally {
        isProcessing.value = false;
    }
};

// Bulk deactivate selected players
const bulkDeactivatePlayers = async () => {
    if (selectedPlayers.value.length === 0) {
        message.value = {
            type: 'error',
            text: t('Будь ласка, виберіть хоча б одного гравця для деактивації'),
        };
        return;
    }

    if (
        !confirm(
            t('Ви впевнені, що хочете деактивувати :count гравців? Вони більше не зможуть брати участь у цій лізі.', {count: selectedPlayers.value.length}),
        )
    ) {
        return;
    }

    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/admin/bulk-deactivate`, {
            method: 'post',
            data: {
                rating_ids: selectedPlayers.value,
            },
        });
        message.value = {
            type: 'success',
            text: t(':count гравців успішно деактивовано', {count: selectedPlayers.value.length}),
        };
        selectedPlayers.value = [];
        // Refresh the list
        await fetchConfirmedPlayers();
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || t('Не вдалося деактивувати гравців'),
        };
    } finally {
        isProcessing.value = false;
    }
};

// Check/uncheck all
const toggleSelectAll = (checked: boolean) => {
    if (checked) {
        selectedPlayers.value = confirmedPlayers.value.map((player) => player.id);
    } else {
        selectedPlayers.value = [];
    }
};

// Check if admin and redirect if not
onMounted(async () => {
    if (!isAdmin.value) {
        window.location.href = '/leagues';
        return;
    }

    await fetchLeague();
    await fetchConfirmedPlayers();
});
</script>

<template>
    <Head :title="league ? t('Управління гравцями - :league', {league: league.name}) : t('Управління гравцями')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header with back button -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="`/leagues/${leagueId}`">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Назад до ліги') }}
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold">
                    {{ league ? t('Управління гравцями - :league', {league: league.name}) : t('Управління гравцями') }}
                </h1>

                <div class="flex space-x-2">
                    <Link :href="`/admin/leagues/${leagueId}/pending-players`">
                        <Button variant="outline"> {{ t('Непідтверджені гравці') }}</Button>
                    </Link>
                </div>
            </div>

            <!-- Status message -->
            <div
                v-if="message"
                :class="`mb-6 rounded-md p-4 ${message.type === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'}`"
            >
                {{ message.text }}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Підтверджені гравці') }}</CardTitle>
                    <CardDescription> {{ t('Керуйте гравцями, які наразі активні в лізі') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Loading state -->
                    <div v-if="isLoading" class="flex justify-center py-8">
                        <Spinner class="text-primary h-8 w-8"/>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="confirmedPlayers.length === 0"
                         class="py-8 text-center text-gray-500 dark:text-gray-400">
                        {{ t('У цій лізі немає підтверджених гравців') }}
                    </div>

                    <!-- Players list -->
                    <div v-else>
                        <!-- Bulk action controls -->
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <input
                                    id="select-all"
                                    :checked="selectedPlayers.length === confirmedPlayers.length"
                                    class="text-primary focus:border-primary focus:ring-primary focus:ring-opacity-50 rounded border-gray-300 shadow-sm focus:ring"
                                    type="checkbox"
                                    @change="(e) => toggleSelectAll(e.target.checked)"
                                />
                                <label class="text-sm font-medium" for="select-all">{{ t('Вибрати всіх') }}</label>
                            </div>

                            <Button :disabled="selectedPlayers.length === 0 || isProcessing" variant="destructive"
                                    @click="bulkDeactivatePlayers">
                                <Spinner v-if="isProcessing" class="mr-2 h-4 w-4"/>
                                <UserMinusIcon v-else class="mr-2 h-4 w-4"/>
                                {{ t('Деактивувати вибраних') }} ({{ selectedPlayers.length }})
                            </Button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 text-xs uppercase dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3">&nbsp;</th>
                                    <th class="px-4 py-3">{{ t('Позиція') }}</th>
                                    <th class="px-4 py-3">{{ t("Ім'я") }}</th>
                                    <th class="px-4 py-3">{{ t('Рейтинг') }}</th>
                                    <th class="px-4 py-3">{{ t('Статистика (В/П)') }}</th>
                                    <th class="px-4 py-3 text-right">{{ t('Дії') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    v-for="player in confirmedPlayers"
                                    :key="player.id"
                                    class="border-b hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            :id="`player-${player.id}`"
                                            v-model="selectedPlayers"
                                            :value="player.id"
                                            class="text-primary focus:border-primary focus:ring-primary focus:ring-opacity-50 rounded border-gray-300 shadow-sm focus:ring"
                                            type="checkbox"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-medium">{{ player.position }}</td>
                                    <td class="px-4 py-3 font-medium">{{ player.player.name }}</td>
                                    <td class="px-4 py-3">{{ player.rating }}</td>
                                    <td class="px-4 py-3">{{ player.wins_count || 0 }}/{{
                                            player.losses_count || 0
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <Button :disabled="isProcessing" size="sm" variant="destructive"
                                                @click="deactivatePlayer(player.id)">
                                            <UserMinusIcon class="mr-1 h-4 w-4"/>
                                            {{ t('Деактивувати') }}
                                        </Button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
