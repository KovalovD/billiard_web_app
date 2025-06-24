<!-- resources/js/pages/OfficialRatings/Index.vue -->
<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {OfficialRating} from '@/types/api';
import {Head, Link, router, usePage} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {
    CalendarIcon,
    CheckCircleIcon,
    ChevronDownIcon,
    PlusIcon,
    StarIcon,
    TrophyIcon,
    UserIcon,
    UsersIcon,
    XCircleIcon
} from 'lucide-vue-next';
import {computed, nextTick, onMounted, ref, watch} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const {isAdmin, isAuthenticated, user} = useAuth();
const {t} = useLocale();
const page = usePage();

// State
const ratings = ref<OfficialRating[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const showInactiveRatings = ref(false);

// Get initial tab from URL query parameter
const getInitialTab = (): 'ratings' | 'one-year' => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    return tabParam === 'one-year' ? 'one-year' : 'ratings';
};

const activeTab = ref<'ratings' | 'one-year'>(getInitialTab());

// One Year Rating state
const oneYearRatingData = ref<any[]>([]);
const isLoadingOneYear = ref(false);
const oneYearError = ref<string | null>(null);
const oneYearTableRef = ref<HTMLElement | null>(null);

// Computed
const filteredRatings = computed(() => {
    if (showInactiveRatings.value) {
        return ratings.value;
    }
    return ratings.value.filter(rating => rating.is_active);
});

const isCurrentUserInOneYear = (player: any): boolean => {
    return isAuthenticated.value && user.value && player.user?.id === user.value.id;
};

const currentUserInOneYear = computed(() => {
    if (!isAuthenticated.value || !user.value) return null;
    const index = oneYearRatingData.value.findIndex(p => p.user?.id === user.value?.id);
    if (index === -1) return null;
    return {
        player: oneYearRatingData.value[index],
        position: index + 1
    };
});

const showScrollToUserButton = computed(() => {
    return currentUserInOneYear.value && oneYearRatingData.value.length > 10;
});

// Define table columns for ratings (removed actions column)
const ratingColumns = computed(() => [
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
    }
]);

// Define table columns for one-year rating
const oneYearColumns = computed(() => [
    {
        key: 'position',
        label: t('Rank'),
        align: 'left' as const,
        render: (player: any) => ({
            position: player.position,
            isCurrentUser: isCurrentUserInOneYear(player)
        })
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (player: any) => ({
            name: `${player.user?.firstname} ${player.user?.lastname}`,
            isCurrentUser: isCurrentUserInOneYear(player),
            isChampion: oneYearRatingData.value.indexOf(player) === 0
        })
    },
    {
        key: 'rating',
        label: t('Rating'),
        align: 'center' as const,
        render: (player: any) => ({
            points: player.rating,
            isCurrentUser: isCurrentUserInOneYear(player)
        })
    },
    {
        key: 'earned_money',
        label: t('Earned Money'),
        align: 'center' as const,
        render: (player: any) => ({
            amount: player.prize_amount || 0,
            isCurrentUser: isCurrentUserInOneYear(player)
        })
    },
    {
        key: 'bonus',
        label: t('Bonus'),
        align: 'center' as const,
        render: (player: any) => ({
            amount: player.bonus_amount || 0,
            isCurrentUser: isCurrentUserInOneYear(player)
        })
    },
    {
        key: 'killer_pool_amount',
        label: t('Killer Pool'),
        align: 'center' as const,
        render: (player: any) => ({
            amount: player.killer_pool_amount || 0,
            isCurrentUser: isCurrentUserInOneYear(player)
        })
    },
    {
        key: 'total',
        label: t('Total'),
        align: 'center' as const,
        render: (player: any) => ({
            amount: (player.prize_amount || 0) + (player.achievement_amount || 0) + (player.killer_pool_amount || 0),
            isCurrentUser: isCurrentUserInOneYear(player)
        })
    }
]);

// Methods
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

const fetchOneYearRating = async () => {
    isLoadingOneYear.value = true;
    oneYearError.value = null;

    try {
        const response = await apiClient<any[]>('/api/official-ratings/one-year-rating');
        // Convert the response to an array if it's an object
        oneYearRatingData.value = Array.isArray(response) ? response : Object.values(response);
    } catch (err: any) {
        oneYearError.value = err.message || t('Failed to load one year rating');
    } finally {
        isLoadingOneYear.value = false;
    }
};

const handleRatingClick = (rating: OfficialRating) => {
    router.visit(`/official-ratings/${rating.id}`);
};

const scrollToUser = async () => {
    if (!currentUserInOneYear.value) return;

    await nextTick();

    // Find the user's row element
    const userRow = document.querySelector(`[data-user-id="${user.value?.id}"]`);
    if (!userRow) return;

    // Scroll the row into view with smooth behavior
    userRow.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

    // Add highlight effect
    userRow.classList.add('animate-pulse', 'bg-blue-100', 'dark:bg-blue-900/30');
    setTimeout(() => {
        userRow.classList.remove('animate-pulse', 'bg-blue-100', 'dark:bg-blue-900/30');
    }, 2000);
};

const getPositionBadgeClass = (position: number): string => {
    switch (position) {
        case 1:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 2:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        case 3:
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + '₴';
};

const getRowClass = (player: any): string => {
    const baseClass = 'transition-colors duration-200';
    if (isCurrentUserInOneYear(player)) {
        return `${baseClass} bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 border-l-4 border-blue-300`;
    }
    return baseClass;
};

const getRatingRowClass = (rating: OfficialRating): string => {
    return 'cursor-pointer transition-colors duration-200';
};

// Handle tab change and update URL
const switchTab = (tab: 'ratings' | 'one-year') => {
    activeTab.value = tab;

    // Update URL without page reload
    const url = new URL(window.location.href);
    if (tab === 'ratings') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }

    window.history.replaceState({}, '', url.toString());
};

// Handle table click events
const setupTableClickHandlers = () => {
    // Only setup handlers for ratings tab
    if (activeTab.value !== 'ratings') return;

    nextTick(() => {
        const tableContainer = document.querySelector('[data-rating-table]');
        if (tableContainer) {
            const rows = tableContainer.querySelectorAll('tbody tr[data-rating-id]');
            rows.forEach(row => {
                const ratingId = row.getAttribute('data-rating-id');
                if (ratingId) {
                    // Remove existing listeners to prevent duplicates
                    const newRow = row.cloneNode(true) as HTMLElement;
                    row.parentNode?.replaceChild(newRow, row);

                    // Add new listeners
                    newRow.addEventListener('click', () => {
                        router.visit(`/official-ratings/${ratingId}`);
                    });
                    newRow.addEventListener('keydown', (e: KeyboardEvent) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            router.visit(`/official-ratings/${ratingId}`);
                        }
                    });
                }
            });
        }
    });
};

// Lifecycle
onMounted(() => {
    fetchRatings();
    fetchOneYearRating();
});

// Watch for tab changes and re-setup handlers
watch(activeTab, () => {
    if (activeTab.value === 'ratings') {
        nextTick(() => {
            setupTableClickHandlers();
        });
    }
});

// Watch for data changes and loading state
watch([() => filteredRatings.value, () => isLoading.value], () => {
    if (!isLoading.value && filteredRatings.value.length > 0 && activeTab.value === 'ratings') {
        nextTick(() => {
            setupTableClickHandlers();
        });
    }
});

// Watch for filter changes
watch(showInactiveRatings, () => {
    if (activeTab.value === 'ratings') {
        nextTick(() => {
            setupTableClickHandlers();
        });
    }
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
                <Link v-if="isAuthenticated && isAdmin && activeTab === 'ratings'"
                      href="/admin/official-ratings/create">
                    <Button>
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        {{ t('Create Rating') }}
                    </Button>
                </Link>
            </div>

            <!-- Tab Navigation -->
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8">
                    <button
                        :class="[
                            'py-4 px-1 text-sm font-medium border-b-2',
                            activeTab === 'ratings'
                                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        @click="switchTab('ratings')"
                    >
                        {{ t('Rating Systems') }}
                    </button>
                    <button
                        :class="[
                            'py-4 px-1 text-sm font-medium border-b-2',
                            activeTab === 'one-year'
                                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        @click="switchTab('one-year')"
                    >
                        {{ t('One Year Rating') }}
                    </button>
                </nav>
            </div>

            <!-- Rating Systems Tab -->
            <div v-if="activeTab === 'ratings'">
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
                        <div data-rating-table>
                            <DataTable
                                :columns="ratingColumns"
                                :compact-mode="true"
                                :data="filteredRatings"
                                :empty-message="showInactiveRatings ? t('No ratings have been created yet.') : t('No active ratings available.')"
                                :loading="isLoading"
                                :row-class="getRatingRowClass"
                                :row-attributes="(rating) => ({
                                    'data-rating-id': rating.id?.toString(),
                                    'role': 'button',
                                    'tabindex': '0',
                                    'aria-label': `View ${rating.name} rating`
                                })"
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
                            </DataTable>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- One Year Rating Tab -->
            <div v-if="activeTab === 'one-year'">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <CalendarIcon class="h-5 w-5"/>
                                    {{ t('One Year Rating') }}
                                </CardTitle>
                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ t('Combined player ratings from all tournaments in the past year') }}
                                </div>
                            </div>

                            <!-- Find Me Button -->
                            <Button
                                v-if="showScrollToUserButton"
                                class="flex items-center gap-2"
                                size="sm"
                                variant="outline"
                                @click="scrollToUser"
                            >
                                <UserIcon class="h-4 w-4"/>
                                {{ t('Find Me') }} (#{{ currentUserInOneYear?.position }})
                                <ChevronDownIcon class="h-4 w-4"/>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <DataTable
                            ref="oneYearTableRef"
                            :columns="oneYearColumns"
                            :compact-mode="true"
                            :data="oneYearRatingData"
                            :empty-message="t('No data available for one year rating.')"
                            :loading="isLoadingOneYear"
                            :row-attributes="(player) => ({
                                'data-user-id': player.user?.id?.toString()
                            })"
                            :row-class="getRowClass"
                        >
                            <template #cell-position="{ value }">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[
                                            'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                            getPositionBadgeClass(value.position)
                                        ]"
                                    >
                                        {{ value.position }}
                                    </span>
                                    <UserIcon
                                        v-if="value.isCurrentUser"
                                        class="h-4 w-4 text-blue-600 dark:text-blue-400"
                                        title="This is you!"
                                    />
                                </div>
                            </template>

                            <template #cell-player="{ value }">
                                <div class="flex items-center gap-2">
                                    <div>
                                        <p :class="[
                                            'font-medium',
                                            value.isCurrentUser
                                                ? 'text-blue-900 dark:text-blue-100'
                                                : 'text-gray-900 dark:text-gray-100'
                                        ]">
                                            {{ value.name }}
                                            <span v-if="value.isCurrentUser"
                                                  class="text-xs text-blue-600 dark:text-blue-400 ml-1">(You)</span>
                                        </p>
                                        <p v-if="value.isChampion"
                                           class="text-sm text-yellow-600 dark:text-yellow-400">
                                            👑 Champion
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <template #cell-rating="{ value }">
                                <span :class="[
                                    'font-bold text-lg',
                                    value.isCurrentUser
                                        ? 'text-blue-900 dark:text-blue-100'
                                        : 'text-gray-900 dark:text-gray-100'
                                ]">
                                    {{ value.points }}
                                </span>
                            </template>

                            <template #cell-earned_money="{ value }">
                                <span v-if="value.amount > 0" :class="[
                                    'font-medium text-green-600 dark:text-green-400',
                                    value.isCurrentUser ? 'font-bold' : ''
                                ]">
                                    {{ formatCurrency(value.amount) }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <template #cell-bonus="{ value }">
                                <span v-if="value.amount > 0" :class="[
                                    'font-medium text-orange-600 dark:text-orange-400',
                                    value.isCurrentUser ? 'font-bold' : ''
                                ]">
                                    {{ value.amount }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <template #cell-killer_pool_amount="{ value }">
                                <span v-if="value.amount > 0" :class="[
                                    'font-medium text-purple-600 dark:text-purple-400',
                                    value.isCurrentUser ? 'font-bold' : ''
                                ]">
                                    {{ formatCurrency(value.amount) }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <template #cell-total="{ value }">
                                <span :class="[
                                    'font-bold text-blue-600 dark:text-blue-400',
                                    value.isCurrentUser ? 'text-lg' : ''
                                ]">
                                    {{ formatCurrency(value.amount) }}
                                </span>
                            </template>
                        </DataTable>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
