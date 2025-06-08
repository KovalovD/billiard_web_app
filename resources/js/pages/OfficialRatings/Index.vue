<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import TableActions, {type ActionItem} from '@/Components/TableActions.vue';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {OfficialRating} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {
    CheckCircleIcon,
    EyeIcon,
    PencilIcon,
    PlusIcon,
    SettingsIcon,
    StarIcon,
    TrophyIcon,
    UsersIcon,
    XCircleIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {isAdmin, isAuthenticated} = useAuth();
const {t} = useLocale();

const ratings = ref<OfficialRating[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const showInactiveRatings = ref(false);

const filteredRatings = computed(() => {
    if (showInactiveRatings.value) {
        return ratings.value;
    }
    return ratings.value.filter(rating => rating.is_active);
});

// Define table columns
const columns = computed(() => [
    {
        key: 'name',
        label: t('Rating System'),
        align: 'left' as const,
        render: (rating: OfficialRating) => ({
            name: rating.name,
            description: rating.description
        })
    },
    {
        key: 'game',
        label: t('Game'),
        hideOnMobile: true,
        render: (rating: OfficialRating) => rating.game_type_name || 'N/A'
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (rating: OfficialRating) => rating.is_active
    },
    {
        key: 'players',
        label: t('Players'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (rating: OfficialRating) => rating.players_count
    },
    {
        key: 'tournaments',
        label: t('Tournaments'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (rating: OfficialRating) => rating.tournaments_count
    },
    {
        key: 'actions',
        label: t('Actions'),
        align: 'right' as const,
        sticky: true,
        width: '80px'
    }
]);

const fetchRatings = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        ratings.value = await apiClient<OfficialRating[]>('/api/official-ratings');
    } catch (err: any) {
        error.value = err.message || t('Failed to load official ratings');
    } finally {
        isLoading.value = false;
    }
};

const getActions = (rating: OfficialRating): ActionItem[] => {
    const actions: ActionItem[] = [
        {
            label: t('View'),
            icon: EyeIcon,
            href: `/official-ratings/${rating.id}`,
            show: true
        }
    ];

    if (isAuthenticated.value && isAdmin.value) {
        actions.push({
            separator: true,
            show: true
        });
        actions.push({
            label: t('Manage'),
            icon: SettingsIcon,
            href: `/admin/official-ratings/${rating.id}/manage`,
            show: true
        });
        actions.push({
            label: t('Edit'),
            icon: PencilIcon,
            href: `/admin/official-ratings/${rating.id}/edit`,
            show: true
        });
    }

    return actions;
};

onMounted(() => {
    fetchRatings();
});
</script>

<template>
    <Head :title="t('Official Ratings')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">{{ t('Official Ratings') }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ t('Professional billiard player rankings') }}</p>
                </div>

                <!-- Only show create button to authenticated admins -->
                <Link v-if="isAuthenticated && isAdmin" href="/admin/official-ratings/create">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        {{ t('Create Rating') }}
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input
                        v-model="showInactiveRatings"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        type="checkbox"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('Show inactive ratings') }}</span>
                </label>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <StarIcon class="h-5 w-5"/>
                        {{ t('Rating Systems') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <DataTable
                        :columns="columns"
                        :compact-mode="true"
                        :data="filteredRatings"
                        :empty-message="showInactiveRatings ? t('No ratings have been created yet.') : t('No active ratings available.')"
                        :loading="isLoading"
                    >
                        <!-- Custom cell renderers -->
                        <template #cell-name="{ value, item }">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div
                                        class="h-8 w-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                        <StarIcon class="h-4 w-4 text-yellow-600 dark:text-yellow-400"/>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div :class="[
                                        'text-sm font-medium text-gray-900 dark:text-gray-100',
                                        !item.is_active && 'opacity-60'
                                    ]">
                                        {{ value.name }}
                                    </div>
                                    <div v-if="value.description"
                                         class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                        {{ value.description }}
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template #cell-game="{ value }">
                            <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                <TrophyIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                {{ value }}
                            </div>
                        </template>

                        <template #cell-status="{ value }">
                            <div class="flex items-center">
                                <CheckCircleIcon v-if="value" class="h-4 w-4 text-green-500 mr-2"/>
                                <XCircleIcon v-else class="h-4 w-4 text-red-500 mr-2"/>
                                <span
                                    :class="value ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ value ? t('Active') : t('Inactive') }}
                                </span>
                            </div>
                        </template>

                        <template #cell-players="{ value }">
                            <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                <UsersIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                {{ value }}
                            </div>
                        </template>

                        <template #cell-tournaments="{ value }">
                            <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                <TrophyIcon class="h-4 w-4 mr-2 text-gray-400"/>
                                {{ value }}
                            </div>
                        </template>

                        <template #cell-actions="{ item }">
                            <TableActions :actions="getActions(item)"/>
                        </template>
                    </DataTable>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
