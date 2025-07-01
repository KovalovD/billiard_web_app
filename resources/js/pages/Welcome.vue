<script lang="ts" setup>
import RegisterModal from '@/Components/Auth/RegisterModal.vue';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/Components/ui';
import ApplicationLogo from '@/Components/ui/ApplicationLogo.vue';
import {Head, Link} from '@inertiajs/vue3';
import {
    CalendarIcon,
    GamepadIcon,
    LogInIcon,
    StarIcon,
    TrophyIcon,
    UserIcon,
    UsersIcon,
    ZapIcon
} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';
import {useSeo} from '@/composables/useSeo';
import MonoDonate from '@/Components/ui/MonoDonate.vue';

// State for modal visibility
const showRegisterModal = ref(false);
const {setSeoMeta, generateOrganizationJsonLd} = useSeo();

const openRegisterModal = () => {
    showRegisterModal.value = true;
};

const closeRegisterModal = () => {
    showRegisterModal.value = false;
};

const handleRegisterSuccess = () => {
    // Registration already redirects to dashboard in RegisterForm component
    closeRegisterModal();
};

const handleRegisterError = (error: any) => {
    console.error('Registration failed:', error);
};

// Features data
const features = [
    {
        icon: TrophyIcon,
        title: 'Join Leagues',
        description: 'Participate in competitive billiard leagues with players of your skill level',
        color: 'text-yellow-600 dark:text-yellow-400',
        bgColor: 'bg-yellow-50 dark:bg-yellow-900/20'
    },
    {
        icon: CalendarIcon,
        title: 'Tournament Play',
        description: 'Compete in official tournaments and climb the professional rankings',
        color: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-50 dark:bg-blue-900/20'
    },
    {
        icon: UsersIcon,
        title: 'Challenge Players',
        description: 'Send challenges to other players and track your match history',
        color: 'text-green-600 dark:text-green-400',
        bgColor: 'bg-green-50 dark:bg-green-900/20'
    },
    {
        icon: StarIcon,
        title: 'Rating System',
        description: 'Advanced ELO-based rating system tracks your skill progression',
        color: 'text-purple-600 dark:text-purple-400',
        bgColor: 'bg-purple-50 dark:bg-purple-900/20'
    },
    {
        icon: GamepadIcon,
        title: 'Multiplayer Games',
        description: 'Join multiplayer elimination games with prize pools',
        color: 'text-indigo-600 dark:text-indigo-400',
        bgColor: 'bg-indigo-50 dark:bg-indigo-900/20'
    },
    {
        icon: ZapIcon,
        title: 'Real-time Updates',
        description: 'Live match results and instant rating updates',
        color: 'text-orange-600 dark:text-orange-400',
        bgColor: 'bg-orange-50 dark:bg-orange-900/20'
    }
];

// Quick stats (these would typically come from an API)
const stats = [
    {label: 'Active Players', value: '500+'},
    {label: 'Leagues Running', value: '25+'},
    {label: 'Matches Played', value: '10,000+'},
    {label: 'Tournaments Held', value: '100+'}
];

onMounted(() => {
    setSeoMeta({
        title: 'Professional Billiard League Platform',
        description: 'Join WinnerBreak - the premier billiard league management platform. Compete in leagues, tournaments, track your ELO rating, and connect with players worldwide.',
        keywords: ['billiards', 'pool', 'league', 'tournament', 'ELO rating', 'competition', 'sports', 'cue sports', 'professional billiards'],
        ogType: 'website',
        jsonLd: {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "WinnerBreak",
            "url": window.location.origin,
            "description": 'Professional billiard league management platform for competitive players',
            "potentialAction": {
                "@type": "SearchAction",
                "target": `${window.location.origin}/search?q={search_term_string}`,
                "query-input": "required name=search_term_string"
            },
            ...generateOrganizationJsonLd()
        }
    });
});
</script>

<template>
    <Head title="Professional Billiard League Platform"/>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800">
        <!-- Navigation Header -->
        <header
            class="relative z-10 bg-white/80 backdrop-blur-sm border-b border-gray-200 dark:bg-gray-900/80 dark:border-gray-700"
            role="banner">
            <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" role="navigation" aria-label="Main navigation">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <ApplicationLogo
                            class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"
                            aria-label="WinnerBreak Logo"/>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden md:flex items-center space-x-8">
                        <Link
                            class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                            href="/leagues"
                            aria-label="Browse competitive billiard leagues">
                            Leagues
                        </Link>
                        <Link
                            class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                            href="/tournaments"
                            aria-label="View billiard tournaments">
                            Tournaments
                        </Link>
                        <Link
                            class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                            href="/official-ratings"
                            aria-label="Check official player rankings">
                            Rankings
                        </Link>
                    </div>

                    <!-- Auth Links -->
                    <div class="flex items-center space-x-4">
                        <MonoDonate :show-card="false" :show-qr="false" aria-label="Support WinnerBreak"/>
                        <template v-if="$page.props.auth && $page.props.auth.user">
                            <Link :href="route('dashboard')"
                                  class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                                  aria-label="Go to your dashboard">
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')"
                                  class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                                  aria-label="Login to your account">
                                <LogInIcon class="mr-1 inline h-4 w-4" aria-hidden="true"/>
                                Login
                            </Link>
                            <Button @click="openRegisterModal" aria-label="Create new account">
                                <UserIcon class="mr-2 h-4 w-4" aria-hidden="true"/>
                                Register
                            </Button>
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <main>
            <section class="relative px-4 py-20 sm:px-6 lg:px-8" aria-labelledby="hero-heading">
                <div class="mx-auto max-w-7xl">
                    <div class="text-center">
                        <h1 id="hero-heading"
                            class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl">
                            Welcome to
                            <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                WinnerBreak
                            </span>
                        </h1>

                        <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                            The premier billiard league management platform. Join competitive leagues, participate in
                            tournaments, and track your progress with our advanced rating system.
                        </p>

                        <div class="mt-10 flex items-center justify-center gap-x-6">
                            <Button size="lg" @click="openRegisterModal" aria-label="Get started with WinnerBreak">
                                Get Started
                            </Button>
                            <Link :href="route('leagues.index.page')" aria-label="Browse available leagues">
                                <Button size="lg" variant="outline">
                                    Browse Leagues
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Section -->
            <section class="py-16 px-4 sm:px-6 lg:px-8" aria-labelledby="stats-heading">
                <h2 id="stats-heading" class="sr-only">Platform Statistics</h2>
                <div class="mx-auto max-w-7xl">
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div v-for="stat in stats" :key="stat.label" class="text-center">
                            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ stat.label }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="features-heading">
                <div class="mx-auto max-w-7xl">
                    <div class="text-center mb-16">
                        <h2 id="features-heading"
                            class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                            Everything you need to compete
                        </h2>
                        <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                            Professional tools for serious billiard players
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                        <article v-for="feature in features" :key="feature.title">
                            <Card class="border-0 shadow-lg h-full">
                                <CardHeader>
                                    <div
                                        :class="['w-12 h-12 rounded-lg flex items-center justify-center mb-4', feature.bgColor]">
                                        <component :is="feature.icon" :class="['h-6 w-6', feature.color]"
                                                   aria-hidden="true"/>
                                    </div>
                                    <CardTitle class="text-xl">{{ feature.title }}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <CardDescription class="text-base">
                                        {{ feature.description }}
                                    </CardDescription>
                                </CardContent>
                            </Card>
                        </article>
                    </div>
                </div>
            </section>

            <!-- How It Works Section -->
            <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white/50 dark:bg-gray-800/50"
                     aria-labelledby="how-it-works-heading">
                <div class="mx-auto max-w-7xl">
                    <div class="text-center mb-16">
                        <h2 id="how-it-works-heading"
                            class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                            How it works
                        </h2>
                        <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                            Get started in three simple steps
                        </p>
                    </div>

                    <ol class="grid grid-cols-1 gap-8 md:grid-cols-3">
                        <li class="text-center">
                            <div
                                class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-6">
                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                                      aria-hidden="true">1</span>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Create Account</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Sign up with your basic information and set up your player profile
                            </p>
                        </li>

                        <li class="text-center">
                            <div
                                class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-6">
                                <span class="text-2xl font-bold text-green-600 dark:text-green-400"
                                      aria-hidden="true">2</span>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Join League</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Browse and join leagues that match your skill level and game preferences
                            </p>
                        </li>

                        <li class="text-center">
                            <div
                                class="mx-auto w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-6">
                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400"
                                      aria-hidden="true">3</span>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Start Playing</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Challenge players, participate in tournaments, and climb the rankings
                            </p>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-20 px-4 sm:px-6 lg:px-8" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl text-center">
                    <h2 id="cta-heading" class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                        Ready to break into the competition
                    </h2>
                    <p class="mt-6 text-lg text-gray-600 dark:text-gray-300">
                        Join thousands of players already competing in leagues and tournaments worldwide
                    </p>
                    <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Button size="lg" @click="openRegisterModal" aria-label="Create free account">
                            <UserIcon class="mr-2 h-5 w-5" aria-hidden="true"/>
                            Create Free Account
                        </Button>
                        <Link :href="route('login')" aria-label="Sign in to existing account">
                            <Button size="lg" variant="outline">
                                <LogInIcon class="mr-2 h-5 w-5" aria-hidden="true"/>
                                Sign In
                            </Button>
                        </Link>
                    </div>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        No credit card required. Start competing today
                    </p>
                </div>
            </section>

            <!-- Quick Explore Section -->
            <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800/50"
                     aria-labelledby="explore-heading">
                <div class="mx-auto max-w-7xl">
                    <div class="text-center mb-12">
                        <h2 id="explore-heading" class="text-2xl font-bold text-gray-900 dark:text-white">
                            Explore without signing up
                        </h2>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Take a look around and see what WinnerBreak has to offer
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <Link :href="route('leagues.index.page')" class="group"
                              aria-label="Browse active billiard leagues">
                            <Card class="transition-all hover:shadow-lg group-hover:scale-105 h-full">
                                <CardHeader>
                                    <div
                                        class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-4">
                                        <TrophyIcon class="h-6 w-6 text-blue-600 dark:text-blue-400"
                                                    aria-hidden="true"/>
                                    </div>
                                    <CardTitle>Browse Leagues</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <CardDescription>
                                        Explore active leagues and see how players are ranked
                                    </CardDescription>
                                </CardContent>
                            </Card>
                        </Link>

                        <Link :href="route('tournaments.index.page')" class="group"
                              aria-label="View billiard tournaments">
                            <Card class="transition-all hover:shadow-lg group-hover:scale-105 h-full">
                                <CardHeader>
                                    <div
                                        class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-4">
                                        <CalendarIcon class="h-6 w-6 text-green-600 dark:text-green-400"
                                                      aria-hidden="true"/>
                                    </div>
                                    <CardTitle>View Tournaments</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <CardDescription>
                                        Check out upcoming and completed tournaments
                                    </CardDescription>
                                </CardContent>
                            </Card>
                        </Link>

                        <Link class="group" href="/official-ratings" aria-label="View professional player rankings">
                            <Card class="transition-all hover:shadow-lg group-hover:scale-105 h-full">
                                <CardHeader>
                                    <div
                                        class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-4">
                                        <StarIcon class="h-6 w-6 text-purple-600 dark:text-purple-400"
                                                  aria-hidden="true"/>
                                    </div>
                                    <CardTitle>Official Rankings</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <CardDescription>
                                        View professional player rankings and standings
                                    </CardDescription>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="py-12 px-4 sm:px-6 lg:px-8 bg-gray-900 text-white" role="contentinfo">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col items-center justify-between md:flex-row">
                    <div class="flex items-center space-x-3 mb-4 md:mb-0">
                        <span class="text-xl font-bold">WinnerBreak</span>
                    </div>

                    <nav class="flex space-x-6 text-sm text-gray-400" aria-label="Footer navigation">
                        <Link class="hover:text-white transition-colors" href="/leagues">Leagues</Link>
                        <Link class="hover:text-white transition-colors" href="/tournaments">Tournaments</Link>
                        <Link class="hover:text-white transition-colors" href="/official-ratings">Rankings</Link>
                    </nav>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm text-gray-400">
                    <p>&copy; 2025 WinnerBreak. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Registration Modal -->
    <RegisterModal
        :show="showRegisterModal"
        @close="closeRegisterModal"
        @error="handleRegisterError"
        @success="handleRegisterSuccess"
    />
</template>
