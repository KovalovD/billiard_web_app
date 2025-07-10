<script lang="ts" setup>
import ActiveMatchesModal from '@/Components/League/ActiveMatchesModal.vue';
import RecentTournamentsCard from '@/Components/Core/RecentTournamentsCard.vue';
import UserLeaguesCard from '@/Components/Core/UserLeaguesCard.vue';
import UserTournamentsCard from '@/Components/Core/UserTournamentsCard.vue';
import {Card, CardContent, CardHeader} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {League, MatchGame, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {ArrowRightIcon, LogInIcon, PlusIcon, StarIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';

interface TournamentWithParticipation {
    tournament: any;
    participation: TournamentPlayer;
}

defineOptions({
    layout: AuthenticatedLayout,
});

const {user, isAuthenticated} = useAuth();
const {t} = useLocale();
const {setSeoMeta} = useSeo();
const leagues = useLeagues();
const recentLeagues = ref<League[]>([]);
const isLoadingLeagues = ref(false);

// Active matches modal state
const showActiveMatchesModal = ref(false);
const activeMatches = ref<MatchGame[]>([]);
const userLeaguesRef = ref<InstanceType<typeof UserLeaguesCard> | null>(null);
const userTournamentsRef = ref<InstanceType<typeof UserTournamentsCard> | null>(null);

// Pending applications modal state
const pendingApplications = ref<TournamentWithParticipation[]>([]);

// Handle active matches found in user leagues (authenticated users only)
const handleActiveMatchesFound = (matches: MatchGame[]) => {
    if (!isAuthenticated.value) return;
    activeMatches.value = matches;
    if (matches.length > 0) {
        showActiveMatchesModal.value = true;
    }
};

// Handle pending tournament applications found (authenticated users only)
const handlePendingApplicationsFound = (applications: TournamentWithParticipation[]) => {
    if (!isAuthenticated.value) return;
    pendingApplications.value = applications;
};

// Handle match declined - simply refresh the leagues card (authenticated users only)
const handleMatchDeclined = () => {
    if (userLeaguesRef.value && isAuthenticated.value) {
        userLeaguesRef.value.refreshData();
    }
};

onMounted(async () => {
    setSeoMeta({
        title: isAuthenticated.value ? t('Dashboard - Your Billiard Statistics') : t('Dashboard - Billiard League Platform'),
        description: isAuthenticated.value
            ? t('View your billiard league statistics, upcoming matches, tournament standings, and track your progress in the competitive billiards community.')
            : t('Access your WinnerBreak dashboard to manage leagues, tournaments, matches, and track your billiard performance.'),
        keywords: ['billiard dashboard', 'player statistics', 'league management', 'tournament tracking', 'match history'],
        ogType: 'website'
    });

    isLoadingLeagues.value = true;
    try {
        const {data, execute} = leagues.fetchLeagues();
        await execute();

        if (data.value) {
            recentLeagues.value = data.value.slice(0, 5);
        }
        // eslint-disable-next-line
    } catch (error) {
        // Silent error handling for better UX
    } finally {
        isLoadingLeagues.value = false;
    }
});
</script>

<template>
    <Head
        :title="isAuthenticated ? t('Dashboard - Your Billiard Statistics') : t('Dashboard - Billiard League Platform')"/>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
            <!-- Welcome Section -->
            <header class="mb-8">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-8 sm:px-8 sm:py-10">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                            {{
                                isAuthenticated
                                    ? t('welcome_back', {name: user?.firstname || t('User')})
                                    : t('Welcome to WinnerBreak!')
                            }}
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            {{
                                isAuthenticated ? t('Track your progress and manage your games') : t('Join the professional billiard community')
                            }}
                        </p>

                        <!-- Quick Actions -->
                        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <Link href="/leagues" class="group">
                                <article
                                    class="relative overflow-hidden rounded-lg border-2 border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 transition-all hover:border-indigo-300 hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-700 dark:hover:border-indigo-600">
                                    <div
                                        class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-indigo-100 opacity-20 dark:bg-indigo-900/30"></div>
                                    <UsersIcon class="h-8 w-8 text-indigo-600 mb-3 dark:text-indigo-400"/>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        {{ t('Join Leagues') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{
                                            t('find_leagues_text')
                                        }}</p>
                                    <div
                                        class="flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">
                                        {{ t('Browse Leagues') }}
                                        <ArrowRightIcon
                                            class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1"/>
                                    </div>
                                </article>
                            </Link>

                            <Link href="/tournaments" class="group">
                                <article
                                    class="relative overflow-hidden rounded-lg border-2 border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 transition-all hover:border-emerald-300 hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-700 dark:hover:border-emerald-600">
                                    <div
                                        class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-emerald-100 opacity-20 dark:bg-emerald-900/30"></div>
                                    <TrophyIcon class="h-8 w-8 text-emerald-600 mb-3 dark:text-emerald-400"/>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        {{ t('Join Tournaments') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                        {{ t('find_tournaments_text') }}</p>
                                    <div
                                        class="flex items-center text-sm font-medium text-emerald-600 dark:text-emerald-400 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">
                                        {{ t('Browse Tournaments') }}
                                        <ArrowRightIcon
                                            class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1"/>
                                    </div>
                                </article>
                            </Link>

                            <Link href="/official-ratings" class="group sm:col-span-2 lg:col-span-1">
                                <article
                                    class="relative overflow-hidden rounded-lg border-2 border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 transition-all hover:border-amber-300 hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-700 dark:hover:border-amber-600">
                                    <div
                                        class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-amber-100 opacity-20 dark:bg-amber-900/30"></div>
                                    <StarIcon class="h-8 w-8 text-amber-600 mb-3 dark:text-amber-400"/>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        {{ isAuthenticated ? t('Track Progress') : t('View Ratings') }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                        {{
                                            isAuthenticated ? t('monitor_performance_text') : t('explore_tournaments_text')
                                        }}
                                    </p>
                                    <div
                                        class="flex items-center text-sm font-medium text-amber-600 dark:text-amber-400 group-hover:text-amber-700 dark:group-hover:text-amber-300">
                                        {{ t('View Ratings') }}
                                        <ArrowRightIcon
                                            class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1"/>
                                    </div>
                                </article>
                            </Link>
                        </div>

                        <!-- Guest CTA -->
                        <div v-if="!isAuthenticated"
                             class="mt-8 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 rounded-lg p-6 border border-indigo-100 dark:border-indigo-800/50">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{
                                            t('Ready to compete?')
                                        }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{
                                            t('Join WinnerBreak to participate in leagues, tournaments, and track your progress.')
                                        }}
                                    </p>
                                </div>
                                <div class="flex gap-3">
                                    <Link :href="route('login')"
                                          class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        <LogInIcon class="mr-2 h-4 w-4"/>
                                        {{ t('Login') }}
                                    </Link>
                                    <Link :href="route('register')"
                                          class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 transition-colors">
                                        {{ t('Register') }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main>
                <!-- User Activity Cards - Only for authenticated users -->
                <div v-if="isAuthenticated" class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <UserLeaguesCard ref="userLeaguesRef" @active-matches-found="handleActiveMatchesFound"/>
                    <UserTournamentsCard ref="userTournamentsRef"
                                         @pending-applications-found="handlePendingApplicationsFound"/>
                </div>

                <!-- Recent Activity -->
                <section class="mb-8" aria-labelledby="recent-tournaments-heading">
                    <h2 id="recent-tournaments-heading" class="sr-only">{{ t('Recent Tournaments') }}</h2>
                    <RecentTournamentsCard/>
                </section>

                <!-- Recent Leagues -->
                <section class="mb-8" aria-labelledby="recent-leagues-heading">
                    <Card class="border-0 shadow-sm">
                        <CardHeader class="border-b border-gray-100 dark:border-gray-800 pb-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ t('Recent Leagues') }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{
                                            isAuthenticated ? t('New and active leagues you might be interested in') : t('Explore active billiard leagues')
                                        }}
                                    </p>
                                </div>
                                <Link :href="route('leagues.index.page')"
                                      class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                                    {{ t('View all') }}
                                </Link>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="isLoadingLeagues" class="flex items-center justify-center py-12">
                                <div
                                    class="animate-spin h-8 w-8 border-4 border-gray-200 border-t-indigo-600 rounded-full"></div>
                            </div>
                            <div v-else-if="recentLeagues.length === 0" class="py-12 text-center">
                                <UsersIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3"/>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ t('No leagues found. Check back later.') }}</p>
                            </div>
                            <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                                <div v-for="league in recentLeagues" :key="league.id"
                                     class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{
                                                    league.name
                                                }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('Game') }}:
                                                {{ league.game }}</p>
                                        </div>
                                        <Link :href="`/leagues/${league.id}`"
                                              class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            {{ t('View') }} →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <!-- Admin Actions -->
                <Card v-if="isAuthenticated && user?.is_admin"
                      class="border-0 shadow-sm bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10">
                    <CardHeader class="border-b border-purple-100 dark:border-purple-800/50 pb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{
                                    t('Admin Actions')
                                }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ t('Manage leagues, tournaments and system settings') }}</p>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <Link href="admin/leagues/create"
                                  class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <PlusIcon class="h-4 w-4"/>
                                {{ t('Create League') }}
                            </Link>
                            <Link href="/admin/tournaments/create"
                                  class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <PlusIcon class="h-4 w-4"/>
                                {{ t('Create Tournament') }}
                            </Link>
                            <Link href="/admin/official-ratings/create"
                                  class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <PlusIcon class="h-4 w-4"/>
                                {{ t('Create Rating') }}
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </div>
    </div>

    <!-- Active Matches Modal -->
    <ActiveMatchesModal
        v-if="isAuthenticated"
        :active-matches="activeMatches"
        :show="showActiveMatchesModal"
        @close="showActiveMatchesModal = false"
        @declined="handleMatchDeclined"
    />
</template>
