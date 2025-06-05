<script lang="ts" setup>
import RegisterModal from '@/Components/Auth/RegisterModal.vue';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/Components/ui';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
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
import {ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

// State for modal visibility
const showRegisterModal = ref(false);
const {t} = useLocale();

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
</script>

<template>
<Head :title="t('Welcome to WinnerBreak')"/>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800">
        <!-- Navigation Header -->
        <nav
            class="relative z-10 bg-white/80 backdrop-blur-sm border-b border-gray-200 dark:bg-gray-900/80 dark:border-gray-700">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <ApplicationLogo
                            class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"/>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden md:flex items-center space-x-8">
                        <Link class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                              href="/leagues">
                            {{ t('Leagues') }}
                        </Link>
                        <Link class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                              href="/tournaments">
                            {{ t('Tournaments') }}
                        </Link>
                        <Link class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                              href="/official-ratings">
                            {{ t('Rankings') }}
                        </Link>
                    </div>

                    <!-- Auth Links -->
                    <div class="flex items-center space-x-4">
                        <template v-if="$page.props.auth && $page.props.auth.user">
                            <Link :href="route('dashboard')"
                                  class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors">
                                {{ t('Dashboard') }}
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')"
                                  class="text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors">
                                <LogInIcon class="mr-1 inline h-4 w-4"/>
                                {{ t('Login') }}
                            </Link>
                            <Button @click="openRegisterModal">
                                <UserIcon class="mr-2 h-4 w-4"/>
                                {{ t('Register') }}
                            </Button>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl">
                        {{ t('Welcome to') }}
                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            WinnerBreak
                        </span>
                    </h1>

                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                        {{ t('The premier billiard league management platform. Join competitive leagues, participate in tournaments, and track your progress with our advanced rating system.') }}
                    </p>

                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <Button size="lg" @click="openRegisterModal">
                            {{ t('Get Started') }}
                        </Button>
                        <Link :href="route('leagues.index.page')">
                            <Button size="lg" variant="outline">
                                {{ t('Browse Leagues') }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-16 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div v-for="stat in stats" :key="stat.label" class="text-center">
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ t(stat.label) }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                        {{ t('Everything you need to compete') }}
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        {{ t('Professional tools for serious billiard players') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="feature in features" :key="feature.title" class="border-0 shadow-lg">
                        <CardHeader>
                            <div
                                :class="['w-12 h-12 rounded-lg flex items-center justify-center mb-4', feature.bgColor]">
                                <component :is="feature.icon" :class="['h-6 w-6', feature.color]"/>
                            </div>
                            <CardTitle class="text-xl">{{ t(feature.title) }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-base">
                                {{ t(feature.description) }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white/50 dark:bg-gray-800/50">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                        {{ t('How it works') }}
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        {{ t('Get started in three simple steps') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="text-center">
                        <div
                            class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">1</span>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ t('Create Account') }}</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ t('Sign up with your basic information and set up your player profile') }}
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl font-bold text-green-600 dark:text-green-400">2</span>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ t('Join League') }}</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ t('Browse and join leagues that match your skill level and game preferences') }}
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mx-auto w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">3</span>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ t('Start Playing') }}</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ t('Challenge players, participate in tournaments, and climb the rankings') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                    {{ t('Ready to break into the competition?') }}
                </h2>
                <p class="mt-6 text-lg text-gray-600 dark:text-gray-300">
                    {{ t('Join thousands of players already competing in leagues and tournaments worldwide.') }}
                </p>
                <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Button size="lg" @click="openRegisterModal">
                        <UserIcon class="mr-2 h-5 w-5"/>
                        {{ t('Create Free Account') }}
                    </Button>
                    <Link :href="route('login')">
                        <Button size="lg" variant="outline">
                            <LogInIcon class="mr-2 h-5 w-5"/>
                            {{ t('Sign In') }}
                        </Button>
                    </Link>
                </div>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ t('No credit card required. Start competing today!') }}
                </p>
            </div>
        </section>

        <!-- Quick Explore Section -->
        <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800/50">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ t('Explore without signing up') }}
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-300">
                        {{ t('Take a look around and see what WinnerBreak has to offer') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <Link :href="route('leagues.index.page')" class="group">
                        <Card class="transition-all hover:shadow-lg group-hover:scale-105">
                            <CardHeader>
                                <div
                                    class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-4">
                                    <TrophyIcon class="h-6 w-6 text-blue-600 dark:text-blue-400"/>
                                </div>
                                <CardTitle>{{ t('Browse Leagues') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription>
                                    {{ t('Explore active leagues and see how players are ranked') }}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link :href="route('tournaments.index.page')" class="group">
                        <Card class="transition-all hover:shadow-lg group-hover:scale-105">
                            <CardHeader>
                                <div
                                    class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-4">
                                    <CalendarIcon class="h-6 w-6 text-green-600 dark:text-green-400"/>
                                </div>
                                <CardTitle>{{ t('View Tournaments') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription>
                                    {{ t('Check out upcoming and completed tournaments') }}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link class="group" href="/official-ratings">
                        <Card class="transition-all hover:shadow-lg group-hover:scale-105">
                            <CardHeader>
                                <div
                                    class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-4">
                                    <StarIcon class="h-6 w-6 text-purple-600 dark:text-purple-400"/>
                                </div>
                                <CardTitle>{{ t('Official Rankings') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription>
                                    {{ t('View professional player rankings and standings') }}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 px-4 sm:px-6 lg:px-8 bg-gray-900 text-white">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col items-center justify-between md:flex-row">
                    <div class="flex items-center space-x-3 mb-4 md:mb-0">
                        <span class="text-xl font-bold">WinnerBreak</span>
                    </div>

                    <div class="flex space-x-6 text-sm text-gray-400">
                        <Link class="hover:text-white transition-colors" href="/leagues">{{ t('Leagues') }}</Link>
                        <Link class="hover:text-white transition-colors" href="/tournaments">{{ t('Tournaments') }}</Link>
                        <Link class="hover:text-white transition-colors" href="/official-ratings">{{ t('Rankings') }}</Link>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm text-gray-400">
                    <p>&copy; 2025 WinnerBreak. {{ t('All rights reserved.') }}</p>
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
