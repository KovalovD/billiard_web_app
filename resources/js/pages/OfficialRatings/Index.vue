// resources/js/Pages/OfficialRatings/Index.vue
<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useAuth} from '@/composables/useAuth';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {OfficialRating} from '@/types/api';
import {Head, Link, router} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {
    CalendarIcon,
    CheckCircleIcon,
    ChevronDownIcon,
    PlusIcon,
    StarIcon,
    TrophyIcon,
    UserIcon,
    XCircleIcon
} from 'lucide-vue-next';
import {computed, nextTick, onMounted, ref, watch} from 'vue';
import {scrollToUserElement} from '@/utils/scrollToUser';
import {useToastStore} from '@/stores/toast';
import UserAvatar from "@/Components/Core/UserAvatar.vue";

defineOptions({layout: AuthenticatedLayout});

const {isAdmin, isAuthenticated, user} = useAuth();
const {t} = useLocale();
const {setSeoMeta, generateBreadcrumbJsonLd, getAlternateLanguageUrls} = useSeo();
const toastStore = useToastStore();

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

// Define table columns for ratings
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
        label: t('#'),
        align: 'left' as const,
        width: '60px',
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
            isChampion: oneYearRatingData.value.indexOf(player) === 0,
            user: player.user,
            position: player.position
        })
    },
    {
        key: 'rating',
        label: t('Rating'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: any) => ({
            points: player.rating,
            isCurrentUser: isCurrentUserInOneYear(player),
            position: player.position
        })
    },
    {
        key: 'earned_money',
        label: t('Prize'),
        align: 'center' as const,
        mobileLabel: t('₴'),
        render: (player: any) => ({
            amount: player.prize_amount || 0,
            isCurrentUser: isCurrentUserInOneYear(player),
            position: player.position
        })
    },
    {
        key: 'bonus',
        label: t('Bonus'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: any) => ({
            amount: player.bonus_amount || 0,
            isCurrentUser: isCurrentUserInOneYear(player),
            position: player.position
        })
    },
    {
        key: 'killer_pool_amount',
        label: t('Killer'),
        align: 'center' as const,
        hideOnTablet: true,
        render: (player: any) => ({
            amount: player.killer_pool_amount || 0,
            isCurrentUser: isCurrentUserInOneYear(player),
            position: player.position
        })
    },
    {
        key: 'total',
        label: t('Total'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: any) => ({
            amount: (player.prize_amount || 0) + (player.achievement_amount || 0) + (player.killer_pool_amount || 0),
            isCurrentUser: isCurrentUserInOneYear(player),
            position: player.position
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

const scrollToUser = async () => {
    if (!currentUserInOneYear.value || !user.value) return;

    await nextTick();

    const success = await scrollToUserElement(user.value.id);

    if (!success) {
        toastStore.error(t('Could not find your position in the list'));
    }
};

const getPositionBadgeClass = (position: number): string => {
    switch (position) {
        case 1:
            return 'bg-gradient-to-r from-amber-500 to-yellow-500 text-white shadow-sm';
        case 2:
            return 'bg-gradient-to-r from-gray-400 to-gray-500 text-white shadow-sm';
        case 3:
            return 'bg-gradient-to-r from-orange-600 to-orange-700 text-white shadow-sm';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }) + '₴';
};

const getRowClass = (player: any): string => {
    const baseClass = 'transition-colors duration-200';
    const position = oneYearRatingData.value.indexOf(player) + 1;

    if (isCurrentUserInOneYear(player)) {
        return `${baseClass} bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/30 border-l-4 border-indigo-500`;
    }

    // Special styling for top 3
    if (position <= 3) {
        return `${baseClass} bg-gradient-to-r from-amber-50/50 to-transparent hover:from-amber-100/50 dark:from-amber-900/10 dark:hover:from-amber-900/20`;
    }

    return baseClass;
};

const getRatingRowClass = (): string => {
    return 'cursor-pointer transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-700/50';
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

// Handle table click handlers
const setupTableClickHandlers = () => {
    // Only setup handlers for ratings tab
    if (activeTab.value !== 'ratings') return;

    nextTick(() => {
        // Setup desktop table row handlers
        const tableContainer = document.querySelector('[data-rating-table]');
        if (tableContainer) {
            const rows = tableContainer.querySelectorAll('tbody tr[data-rating-slug]');
            rows.forEach(row => {
                const ratingId = row.getAttribute('data-rating-slug');
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

        // Setup mobile card handlers
        const mobileCards = document.querySelectorAll('.mobile-card[data-rating-slug]');
        mobileCards.forEach(card => {
            const ratingId = card.getAttribute('data-rating-slug');
            if (ratingId) {
                // Remove existing listeners to prevent duplicates
                const newCard = card.cloneNode(true) as HTMLElement;
                card.parentNode?.replaceChild(newCard, card);

                // Add new listeners to the entire card
                newCard.addEventListener('click', (e) => {
                    // Prevent click if clicking on interactive elements
                    const target = e.target as HTMLElement;
                    if (target.closest('button, a, input, select, textarea')) {
                        return;
                    }
                    router.visit(`/official-ratings/${ratingId}`);
                });
                newCard.addEventListener('keydown', (e: KeyboardEvent) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        router.visit(`/official-ratings/${ratingId}`);
                    }
                });
            }
        });
    });
};

onMounted(() => {
    const currentPath = window.location.pathname;
    const tabSpecificKeywords = activeTab.value === 'one-year'
        ? ['annual rankings', 'годовой рейтинг', 'yearly standings', 'годовая таблица', 'prize money leaders', 'лидеры по призовым']
        : ['rating systems', 'рейтинговые системы', 'ELO rankings', 'рейтинги ELO', 'skill ratings', 'рейтинги навыков'];

    setSeoMeta({
        title: activeTab.value === 'one-year'
            ? t('Annual Billiard Player Rankings 2025 - Top Prize Winners | WinnerBreak')
            : t('Official Billiard Rating Systems - Professional Pool Rankings | WinnerBreak'),
        description: activeTab.value === 'one-year'
            ? t('View the 2025 annual billiard player rankings. See top players by total prize money, tournament wins, bonus points, and killer pool earnings. Track who leads the professional billiards circuit in Ukraine and worldwide.')
            : t('Explore official billiard rating systems and professional player rankings. Track ELO ratings, tournament standings, and player performance across 8-ball, 9-ball, and snooker competitions. Join rated tournaments to improve your ranking.'),
        keywords: [
            ...tabSpecificKeywords,
            'billiard rankings 2025', 'бильярдные рейтинги 2025',
            'pool player ratings', 'рейтинги игроков в пул',
            'professional billiards standings', 'профессиональные турнирные таблицы',
            'tournament rankings', 'турнирные рейтинги',
            'player statistics', 'статистика игроков',
            'skill level ratings', 'рейтинги уровня мастерства',
            'competitive pool rankings', 'рейтинги соревновательного пула',
            'Ukraine billiard rankings', 'украинские бильярдные рейтинги',
            'prize money standings', 'таблица призовых',
            'championship points', 'чемпионские очки',
            'WinnerBreak ratings', 'рейтинги ВиннерБрейк'
        ],
        ogType: 'website',
        ogImage: activeTab.value === 'one-year' ? '/images/annual-rankings.jpg' : '/images/ratings-preview.jpg',
        canonicalUrl: `${window.location.origin}${currentPath}`,
        robots: 'index, follow',
        alternateLanguages: getAlternateLanguageUrls(currentPath),
        additionalMeta: [
            {name: 'rating:type', content: activeTab.value === 'one-year' ? 'annual' : 'systems'},
            {name: 'sport', content: 'Billiards'},
            {property: 'article:section', content: 'Sports Rankings'}
        ],
        jsonLd: {
            "@context": "https://schema.org",
            "@graph": [
                generateBreadcrumbJsonLd([
                    {name: t('Home'), url: window.location.origin},
                    {name: t('Official Ratings'), url: `${window.location.origin}/official-ratings`}
                ]),
                {
                    "@type": "WebPage",
                    "name": activeTab.value === 'one-year' ? t('Annual Player Rankings') : t('Rating Systems'),
                    "description": activeTab.value === 'one-year'
                        ? t('Annual billiard player rankings by prize money and achievements')
                        : t('Official billiard rating systems and rankings'),
                    "url": `${window.location.origin}/official-ratings${activeTab.value === 'one-year' ? '?tab=one-year' : ''}`
                },
                {
                    "@type": "ItemList",
                    "name": activeTab.value === 'one-year' ? t('Top Players by Earnings') : t('Rating Systems'),
                    "itemListElement": activeTab.value === 'one-year'
                        ? oneYearRatingData.value.slice(0, 10).map((player, index) => ({
                            "@type": "ListItem",
                            "position": index + 1,
                            "item": {
                                "@type": "Person",
                                "name": player.user?.full_name || `${player.user?.firstname} ${player.user?.lastname}`,
                                "award": `Position ${index + 1} - ${formatCurrency(player.prize_amount || 0)}`
                            }
                        }))
                        : ratings.value.filter(r => r.is_active).map((rating, index) => ({
                            "@type": "ListItem",
                            "position": index + 1,
                            "item": {
                                "@type": "SportsActivityLocation",
                                "name": rating.name,
                                "description": rating.description,
                                "sport": rating.game_type_name || "Billiards"
                            }
                        }))
                }
            ]
        }
    });

    fetchRatings();
    fetchOneYearRating();
});

// Watch for tab changes and re-setup handlers
watch(activeTab, (newTab) => {
    if (newTab === 'ratings') {
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
    <Head
        :title="activeTab === 'one-year' ? t('One Year Billiard Player Rankings') : t('Official Billiard Rating Systems')"/>

    <div class="py-4 sm:py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header - Compact -->
            <header class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{
                            t('Official Ratings')
                        }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{
                            t('Professional billiard player rankings')
                        }}</p>
                </div>

                <!-- Only show create button to authenticated admins -->
                <Link v-if="isAuthenticated && isAdmin && activeTab === 'ratings'"
                      href="/admin/official-ratings/create"
                      aria-label="Create new rating system"
                      class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                    <PlusIcon class="mr-1.5 h-3.5 w-3.5" aria-hidden="true"/>
                    {{ t('Create Rating') }}
                </Link>
            </header>

            <!-- Tab Navigation - Compact -->
            <nav class="mb-4 border-b border-gray-200 dark:border-gray-700 overflow-x-auto" role="navigation"
                 aria-label="Rating tabs">
                <div class="-mb-px flex space-x-4 sm:space-x-6 min-w-max">
                    <button
                        :class="[
                            'py-2.5 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                            activeTab === 'ratings'
                                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        :aria-selected="activeTab === 'ratings'"
                        role="tab"
                        @click="switchTab('ratings')"
                    >
                        {{ t('Rating Systems') }}
                    </button>
                    <button
                        :class="[
                            'py-2.5 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                            activeTab === 'one-year'
                                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        :aria-selected="activeTab === 'one-year'"
                        role="tab"
                        @click="switchTab('one-year')"
                    >
                        {{ t('One Year Rating') }}
                    </button>
                </div>
            </nav>

            <!-- Rating Systems Tab -->
            <main v-if="activeTab === 'ratings'" role="tabpanel">
                <!-- Filters - Compact -->
                <div class="mb-3 flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="showInactiveRatings"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5"
                            type="checkbox"
                            aria-label="Show inactive rating systems"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ t('Show inactive ratings') }}</span>
                    </label>
                </div>

                <Card class="shadow-sm">
                    <CardHeader class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-4">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <StarIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400" aria-hidden="true"/>
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
                                    'data-rating-slug': rating.slug?.toString(),
                                    'role': 'button',
                                    'tabindex': '0',
                                    'aria-label': `View ${rating.name} rating details`
                                })"
                                :mobile-card-mode="true"
                                :row-height="'compact'"
                            >
                                <!-- Custom cell renderers -->
                                <template #cell-name="{ value, item }">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-7 w-7">
                                            <div
                                                class="h-7 w-7 rounded-full bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center shadow-sm">
                                                <StarIcon class="h-3.5 w-3.5 text-white"
                                                          aria-hidden="true"/>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <div :class="[
                                                'text-sm font-medium text-gray-900 dark:text-gray-100',
                                                !item.is_active && 'opacity-60'
                                            ]">
                                                {{ value.name }}
                                            </div>
                                            <div v-if="value.description"
                                                 class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                {{ value.description }}
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #cell-game="{ value }">
                                    <div class="flex items-center text-sm text-gray-900 dark:text-gray-100">
                                        <TrophyIcon class="h-3.5 w-3.5 mr-1.5 text-gray-400" aria-hidden="true"/>
                                        {{ value }}
                                    </div>
                                </template>

                                <template #cell-status="{ value }">
                                    <div class="flex items-center">
                                        <CheckCircleIcon v-if="value" class="h-3.5 w-3.5 text-emerald-500"
                                                         aria-hidden="true"/>
                                        <XCircleIcon v-else class="h-3.5 w-3.5 text-red-500" aria-hidden="true"/>
                                    </div>
                                </template>

                                <template #cell-players="{ value }">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ value }}
                                    </span>
                                </template>

                                <template #cell-tournaments="{ value }">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ value }}
                                    </span>
                                </template>

                                <!-- Mobile card primary info -->
                                <template #mobile-primary="{ item }">
                                    <div
                                        class="flex items-center justify-between mb-2"
                                    >
                                        <div class="flex items-center">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center shadow-sm">
                                                <StarIcon class="h-5 w-5 text-white"/>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ item.name }}
                                                </h3>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ item.game_type_name }}
                                                </p>
                                            </div>
                                        </div>
                                        <div v-if="item.is_active"
                                             class="flex items-center text-emerald-600 dark:text-emerald-400">
                                            <CheckCircleIcon class="h-4 w-4"/>
                                        </div>
                                        <div v-else class="flex items-center text-red-600 dark:text-red-400">
                                            <XCircleIcon class="h-4 w-4"/>
                                        </div>
                                    </div>
                                </template>
                            </DataTable>
                        </div>
                    </CardContent>
                </Card>
            </main>

            <!-- One Year Rating Tab -->
            <main v-if="activeTab === 'one-year'" role="tabpanel">
                <Card class="shadow-sm">
                    <CardHeader class="bg-gradient-to-r from-gray-50 to-amber-50 dark:from-gray-800 dark:to-gray-700 p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <CardTitle class="flex items-center gap-2 text-base">
                                    <CalendarIcon class="h-4 w-4 text-amber-600 dark:text-amber-400"
                                                  aria-hidden="true"/>
                                    {{ t('One Year Rating') }}
                                </CardTitle>
                                <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    {{ t('Combined player ratings from all tournaments in the past year') }}
                                </div>
                            </div>

                            <!-- Find Me Button -->
                            <Button
                                v-if="showScrollToUserButton"
                                class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"
                                size="sm"
                                @click="scrollToUser"
                                aria-label="Find my position in rankings"
                            >
                                <UserIcon class="h-3.5 w-3.5" aria-hidden="true"/>
                                {{ t('Find Me') }} (#{{ currentUserInOneYear?.position }})
                                <ChevronDownIcon class="h-3.5 w-3.5" aria-hidden="true"/>
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
                            :mobile-card-mode="true"
                            :row-height="(player) => {
                                const position = oneYearRatingData.indexOf(player) + 1;
                                return position <= 3 ? 'large' : 'compact';
                            }"
                        >
                            <template #cell-position="{ value }">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center justify-center rounded-full font-bold',
                                            getPositionBadgeClass(value.position),
                                            value.position <= 3 ? 'h-10 w-10 text-lg' : 'h-6 w-6 text-xs'
                                        ]"
                                    >
                                        {{ value.position }}
                                    </span>
                                    <UserIcon
                                        v-if="value.isCurrentUser"
                                        class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                                        title="This is you!"
                                        aria-label="Your position"
                                    />
                                </div>
                            </template>

                            <template #cell-player="{ value, item }">
                                <div class="flex items-center gap-3">
                                    <UserAvatar
                                        :user="item.user"
                                        :size="value.position <= 3 ? 'md' : 'xs'"
                                        priority="tournament_picture"
                                        :exclusive-priority="true"
                                    />
                                    <div>
                                        <p :class="[
                                            'font-medium',
                                            value.position <= 3 ? 'text-base' : 'text-sm',
                                            value.isCurrentUser
                                                ? 'text-indigo-900 dark:text-indigo-100'
                                                : 'text-gray-900 dark:text-gray-100'
                                        ]">
                                            {{ value.name }}
                                            <span v-if="value.isCurrentUser"
                                                  class="text-xs text-indigo-600 dark:text-indigo-400 ml-1">({{
                                                    t('You')
                                                }})</span>
                                        </p>
                                        <p v-if="value.isChampion"
                                           class="text-sm text-amber-600 dark:text-amber-400 font-semibold">
                                            👑 {{ t('Leader') }}
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <template #cell-rating="{ value }">
                                <span :class="[
                                    'font-semibold',
                                    value.position <= 3 ? 'text-base' : 'text-sm',
                                    value.isCurrentUser
                                        ? 'text-indigo-900 dark:text-indigo-100'
                                        : 'text-gray-900 dark:text-gray-100'
                                ]">
                                    {{ value.points }}
                                </span>
                            </template>

                            <template #cell-earned_money="{ value }">
                                <span v-if="value.amount > 0" :class="[
                                    'font-medium text-emerald-600 dark:text-emerald-400',
                                    value.position <= 3 ? 'text-base' : 'text-sm',
                                    value.isCurrentUser ? 'font-semibold' : ''
                                ]">
                                    {{ formatCurrency(value.amount) }}
                                </span>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </template>

                            <template #cell-bonus="{ value }">
                                <span v-if="value.amount > 0" :class="[
                                    'font-medium text-orange-600 dark:text-orange-400',
                                    value.position <= 3 ? 'text-base' : 'text-sm',
                                    value.isCurrentUser ? 'font-semibold' : ''
                                ]">
                                    {{ value.amount }}
                                </span>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </template>

                            <template #cell-killer_pool_amount="{ value }">
                                <span v-if="value.amount > 0" :class="[
                                    'font-medium text-purple-600 dark:text-purple-400',
                                    value.position <= 3 ? 'text-base' : 'text-sm',
                                    value.isCurrentUser ? 'font-semibold' : ''
                                ]">
                                    {{ formatCurrency(value.amount) }}
                                </span>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </template>

                            <template #cell-total="{ value }">
                                <span :class="[
                                    'font-bold text-indigo-600 dark:text-indigo-400',
                                    value.position <= 3 ? 'text-lg' : (value.isCurrentUser ? 'text-base' : 'text-sm')
                                ]">
                                    {{ formatCurrency(value.amount) }}
                                </span>
                            </template>

                            <!-- Mobile card primary info -->
                            <template #mobile-primary="{ item }">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2.5">
                                        <span :class="[
                                            'inline-flex items-center justify-center rounded-full font-bold',
                                            getPositionBadgeClass(item.position),
                                            item.position <= 3 ? 'h-12 w-12 text-lg' : 'h-8 w-8 text-sm'
                                        ]">
                                            {{ item.position }}
                                        </span>
                                        <div>
                                            <h3 :class="[
                                                'font-semibold',
                                                item.position <= 3 ? 'text-base' : 'text-sm',
                                                isCurrentUserInOneYear(item)
                                                    ? 'text-indigo-900 dark:text-indigo-100'
                                                    : 'text-gray-900 dark:text-white'
                                            ]">
                                                {{ item.user?.firstname }} {{ item.user?.lastname }}
                                                <span v-if="isCurrentUserInOneYear(item)"
                                                      class="text-xs text-indigo-600 dark:text-indigo-400 ml-1">({{
                                                        t('You')
                                                    }})</span>
                                            </h3>
                                            <p v-if="item.position === 1"
                                               class="text-sm text-amber-600 dark:text-amber-400 font-semibold">
                                                👑 {{ t('Champion') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p :class="[
                                            'font-bold text-emerald-600 dark:text-emerald-400',
                                            item.position <= 3 ? 'text-base' : 'text-sm'
                                        ]">
                                            {{ formatCurrency(item.prize_amount || 0) }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ item.rating }} {{ t('pts') }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </DataTable>
                    </CardContent>
                </Card>
            </main>
        </div>
    </div>
</template>
