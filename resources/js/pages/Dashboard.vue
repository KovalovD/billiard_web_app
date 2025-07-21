// resources/js/Pages/Dashboard.vue
<script lang="ts" setup>
import ActiveMatchesModal from '@/Components/League/ActiveMatchesModal.vue';
import RecentTournamentsCard from '@/Components/Core/RecentTournamentsCard.vue';
import UserLeaguesCard from '@/Components/Core/UserLeaguesCard.vue';
import UserTournamentsCard from '@/Components/Core/UserTournamentsCard.vue';
import {useAuth} from '@/composables/useAuth';
import {useLeagues} from '@/composables/useLeagues';
import {useSeo} from '@/composables/useSeo';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type {League, MatchGame, Tournament, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {ArrowRightIcon, LogInIcon, StarIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';
import TournamentMainEvent from "@/Components/Core/TournamentMainEvent.vue";

interface TournamentWithParticipation {
    tournament: any;
    participation: TournamentPlayer;
}

// eslint-disable-next-line
const props = defineProps<{
    mainEventTournament: Tournament | null,
}>();


defineOptions({
    layout: AuthenticatedLayout,
});

const {user, isAuthenticated} = useAuth();
const {t} = useLocale();
const {
    setSeoMeta, getAlternateLanguageUrls
    , generateBreadcrumbJsonLd
    , generateOrganizationJsonLd
} = useSeo();
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
    const currentPath = window.location.pathname;

    setSeoMeta({
        title: isAuthenticated.value
            ? t('Dashboard - Your Billiard Statistics & Performance Analytics')
            : t('Dashboard - Professional Billiard League Management Platform'),
        description: isAuthenticated.value
            ? t('Access your comprehensive billiard statistics dashboard. Track tournament performance, league standings, ELO ratings, match history, win rates, and skill progression in real-time. Monitor your competitive billiards journey.')
            : t('Welcome to WinnerBreak - the ultimate billiard league management platform. Join professional leagues, compete in tournaments, track rankings, challenge players, and elevate your pool game to championship level.'),
        keywords: [
            'billiard dashboard', 'бильярдная панель', 'pool statistics', 'статистика пула',
            'player performance', 'производительность игрока', 'league management', 'управление лигой',
            'tournament tracking', 'отслеживание турниров', 'match history', 'история матчей',
            'ELO rating tracker', 'трекер рейтинга ELO', 'billiard analytics', 'аналитика бильярда',
            'competitive pool', 'соревновательный пул', 'skill progression', 'прогресс навыков',
            'real-time statistics', 'статистика в реальном времени', 'WinnerBreak', 'ВиннерБрейк'
        ],
        ogType: 'website',
        canonicalUrl: `${window.location.origin}${currentPath}`,
        robots: 'index, follow',
        author: 'WinnerBreak Team',
        publisher: 'WinnerBreak',
        alternateLanguages: getAlternateLanguageUrls(currentPath),
        additionalMeta: [
            {name: 'application-name', content: 'WinnerBreak'},
            {name: 'apple-mobile-web-app-title', content: 'WinnerBreak'},
            {name: 'apple-mobile-web-app-capable', content: 'yes'},
            {name: 'apple-mobile-web-app-status-bar-style', content: 'black-translucent'},
            {name: 'format-detection', content: 'telephone=no'},
            {name: 'mobile-web-app-capable', content: 'yes'}
        ],
        jsonLd: {
            ...generateBreadcrumbJsonLd([
                {name: t('Home'), url: window.location.origin},
                {name: t('Dashboard'), url: `${window.location.origin}/dashboard`}
            ]),
            "@graph": [
                generateOrganizationJsonLd(),
                {
                    "@context": "https://schema.org",
                    "@type": "WebApplication",
                    "name": "WinnerBreak Dashboard",
                    "description": isAuthenticated.value
                        ? t('Personal billiard statistics and performance tracking dashboard')
                        : t('Professional billiard league management application'),
                    "applicationCategory": "SportsApplication",
                    "operatingSystem": "Web Browser",
                    "offers": {
                        "@type": "Offer",
                        "price": "0",
                        "priceCurrency": "USD"
                    },
                    "featureList": [
                        t('Real-time match tracking'),
                        t('ELO rating system'),
                        t('Tournament management'),
                        t('League standings'),
                        t('Player statistics'),
                        t('Challenge system')
                    ]
                }
            ]
        }
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

    <!-- Main Event Tournament Banner -->
    <TournamentMainEvent v-if="mainEventTournament" :tournament="mainEventTournament" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
            <!-- Welcome Section -->
            <header class="mb-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-6 sm:px-8 sm:py-8">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                            {{
                                isAuthenticated
                                    ? t('welcome_back', {name: user?.firstname || t('User')})
                                    : t('Welcome to WinnerBreak!')
                            }}
                        </h1>
                        <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
                            {{
                                isAuthenticated ? t('Track your progress and manage your games') : t('Join the professional billiard community')
                            }}
                        </p>

                        <!-- Quick Actions -->
                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <Link href="/leagues" class="group">
                                <article
                                    class="relative overflow-hidden rounded-lg border-2 border-gray-200 bg-gradient-to-br from-white to-gray-50 p-5 transition-all hover:border-indigo-300 hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-700 dark:hover:border-indigo-600">
                                    <div
                                        class="absolute top-0 right-0 -mt-4 -mr-4 h-20 w-20 rounded-full bg-indigo-100 opacity-20 dark:bg-indigo-900/30"></div>
                                    <UsersIcon class="h-7 w-7 text-indigo-600 mb-3 dark:text-indigo-400"/>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                                        {{ t('Join Leagues') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{
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
                                    class="relative overflow-hidden rounded-lg border-2 border-gray-200 bg-gradient-to-br from-white to-gray-50 p-5 transition-all hover:border-emerald-300 hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-700 dark:hover:border-emerald-600">
                                    <div
                                        class="absolute top-0 right-0 -mt-4 -mr-4 h-20 w-20 rounded-full bg-emerald-100 opacity-20 dark:bg-emerald-900/30"></div>
                                    <TrophyIcon class="h-7 w-7 text-emerald-600 mb-3 dark:text-emerald-400"/>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                                        {{ t('Join Tournaments') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
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
                                    class="relative overflow-hidden rounded-lg border-2 border-gray-200 bg-gradient-to-br from-white to-gray-50 p-5 transition-all hover:border-amber-300 hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-700 dark:hover:border-amber-600">
                                    <div
                                        class="absolute top-0 right-0 -mt-4 -mr-4 h-20 w-20 rounded-full bg-amber-100 opacity-20 dark:bg-amber-900/30"></div>
                                    <StarIcon class="h-7 w-7 text-amber-600 mb-3 dark:text-amber-400"/>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                                        {{ isAuthenticated ? t('Track Progress') : t('View Ratings') }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
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
                             class="mt-6 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 rounded-lg p-5 border border-indigo-100 dark:border-indigo-800/50">
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
                <div v-if="isAuthenticated" class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <UserLeaguesCard ref="userLeaguesRef" @active-matches-found="handleActiveMatchesFound"/>
                    <UserTournamentsCard ref="userTournamentsRef"
                                         @pending-applications-found="handlePendingApplicationsFound"/>
                </div>

                <!-- Recent Activity -->
                <section class="mb-6" aria-labelledby="recent-tournaments-heading">
                    <h2 id="recent-tournaments-heading" class="sr-only">{{ t('Recent Tournaments') }}</h2>
                    <RecentTournamentsCard/>
                </section>
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
