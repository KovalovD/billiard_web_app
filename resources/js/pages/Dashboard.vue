// resources/js/pages/Dashboard.vue
<script lang="ts" setup>
import ActiveMatchesModal from '@/Components/ActiveMatchesModal.vue';
import RecentTournamentsCard from '@/Components/RecentTournamentsCard.vue';
import UserLeaguesCard from '@/Components/UserLeaguesCard.vue';
import UserTournamentsCard from '@/Components/UserTournamentsCard.vue';
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {League, MatchGame, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {LogInIcon} from 'lucide-vue-next';
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
const leagues = useLeagues();
const recentLeagues = ref<League[]>([]);
const isLoadingLeagues = ref(false);

// Active matches modal state
const showActiveMatchesModal = ref(false);
const activeMatches = ref<MatchGame[]>([]);
const userLeaguesRef = ref<InstanceType<typeof UserLeaguesCard> | null>(null);
const userTournamentsRef = ref<InstanceType<typeof UserTournamentsCard> | null>(null);

// Pending applications modal state (for tournaments)
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
    // You could show a modal or notification here if needed
};

// Handle match declined - simply refresh the leagues card (authenticated users only)
const handleMatchDeclined = () => {
    if (userLeaguesRef.value && isAuthenticated.value) {
        userLeaguesRef.value.refreshData();
    }
};

onMounted(async () => {
    isLoadingLeagues.value = true;
    try {
        const { data, execute } = leagues.fetchLeagues();
        await execute();

        if (data.value) {
            recentLeagues.value = data.value.slice(0, 5); // Get 5 most recent leagues
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
    <Head title="Dashboard" />

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="mb-6 overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="mb-4 text-2xl font-semibold">
                        {{
                            isAuthenticated
                                ? t('Welcome back, :name!', {name: user?.firstname || t('User')})
                                : t('Welcome to WinnerBreak!')
                        }}
                    </h1>

                    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="rounded-lg bg-indigo-50 p-6 dark:bg-indigo-900/30">
                            <h3 class="mb-2 text-lg font-medium text-indigo-800 dark:text-indigo-300">{{ t('Join Leagues') }}</h3>
                            <p class="mb-4 text-indigo-600 dark:text-indigo-400">
                                {{ t('find_leagues_text') }}
                            </p>
                            <Link class="font-medium text-indigo-700 hover:underline dark:text-indigo-300"
                                  href="/leagues">{{ t('Browse') }}
                                {{ t('Leagues') }} →
                            </Link>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-6 dark:bg-emerald-900/30">
                            <h3 class="mb-2 text-lg font-medium text-emerald-800 dark:text-emerald-300">{{ t('Join Tournaments') }}</h3>
                            <p class="mb-4 text-emerald-600 dark:text-emerald-400">
                                {{ t('find_tournaments_text') }}
                            </p>
                            <Link class="font-medium text-emerald-700 hover:underline dark:text-emerald-300"
                                  href="/tournaments">{{ t('Browse') }}
                                {{ t('Tournaments') }} →
                            </Link>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-6 dark:bg-amber-900/30">
                            <h3 class="mb-2 text-lg font-medium text-amber-800 dark:text-amber-300">
                                {{ isAuthenticated ? t('Track Progress') : t('View Ratings') }}
                            </h3>
                            <p class="mb-4 text-amber-600 dark:text-amber-400">
                                {{
                                    isAuthenticated
                                        ? t('monitor_performance_text')
                                        : t('explore_tournaments_text')
                                }}
                            </p>
                            <Link class="font-medium text-amber-700 hover:underline dark:text-amber-300"
                                  href="/official-ratings">
                                {{ t('View Ratings') }} →
                            </Link>
                        </div>
                    </div>

                    <!-- Guest login prompt -->
                    <div v-if="!isAuthenticated" class="mt-6 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-blue-800 dark:text-blue-300">{{ t('Ready to compete?') }}</h3>
                                <p class="text-blue-600 dark:text-blue-400">{{ t('Join WinnerBreak to participate in leagues, tournaments, and track your progress.') }}</p>
                            </div>
                            <div class="flex space-x-2">
                                <Link :href="route('login')">
                                    <button
                                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        <LogInIcon class="mr-2 h-4 w-4"/>
                                        {{ t('Login') }}
                                    </button>
                                </Link>
                                <Link :href="route('register')">
                                    <button
                                        class="inline-flex items-center rounded-md border border-blue-300 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-blue-600 dark:bg-gray-800 dark:text-blue-400 dark:hover:bg-gray-700">
                                        {{ t('Register') }}
                                    </button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Activity Cards - Only for authenticated users -->
            <div v-if="isAuthenticated" class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Your Leagues - Now emits event when active matches are found -->
                <UserLeaguesCard ref="userLeaguesRef" @active-matches-found="handleActiveMatchesFound"/>

                <!-- Your Tournaments - Now emits event when pending applications are found -->
                <UserTournamentsCard ref="userTournamentsRef"
                                     @pending-applications-found="handlePendingApplicationsFound"/>
            </div>

            <!-- Recent Activity -->
            <div class="mb-6">
                <RecentTournamentsCard/>
            </div>

            <!-- Recent Leagues (available to everyone) -->
            <div class="mb-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Recent Leagues') }}</CardTitle>
                        <CardDescription>
                            {{
                                isAuthenticated
                                    ? t('New and active leagues you might be interested in')
                                    : t('Explore active billiard leagues')
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingLeagues" class="py-4 text-center text-gray-500 dark:text-gray-400">{{ t('Loading leagues...') }}
                        </div>
                        <div v-else-if="recentLeagues.length === 0" class="py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ t('No leagues found. Check back later.') }}
                        </div>
                        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-for="league in recentLeagues" :key="league.id" class="py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ league.name }}</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Game: {{ league.game }}</p>
                                    </div>
                                    <Link :href="`/leagues/${league.id}`" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                        {{ t('View') }}
                                    </Link>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <!-- Admin Actions (if admin and authenticated) -->
            <Card v-if="isAuthenticated && user?.is_admin">
                <CardHeader>
                    <CardTitle>{{ t('Admin Actions') }}</CardTitle>
                    <CardDescription>{{ t('Manage leagues, tournaments and system settings') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4">
                        <Link
                            class="inline-flex items-center rounded-md border border-transparent bg-purple-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase ring-purple-300 transition hover:bg-purple-700 focus:border-purple-800 focus:ring focus:outline-none active:bg-purple-800 disabled:opacity-25"
                            href="admin/leagues/create"
                        >
                            {{ t('Create New League') }}
                        </Link>
                        <Link
                            class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase ring-green-300 transition hover:bg-green-700 focus:border-green-800 focus:ring focus:outline-none active:bg-green-800 disabled:opacity-25"
                            href="/admin/tournaments/create"
                        >
                            {{ t('Create New Tournament') }}
                        </Link>
                        <Link
                            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase ring-blue-300 transition hover:bg-blue-700 focus:border-blue-800 focus:ring focus:outline-none active:bg-blue-800 disabled:opacity-25"
                            href="/admin/official-ratings/create"
                        >
                            {{ t('Create Official Rating') }}
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Active Matches Modal - Only for authenticated users -->
    <ActiveMatchesModal
        v-if="isAuthenticated"
        :active-matches="activeMatches"
        :show="showActiveMatchesModal"
        @close="showActiveMatchesModal = false"
        @declined="handleMatchDeclined"
    />
</template>
