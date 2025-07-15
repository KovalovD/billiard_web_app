<script lang="ts" setup>
import RegisterModal from '@/Components/Auth/RegisterModal.vue';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/Components/ui';
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
import AuthenticatedLayout from "@/layouts/AuthenticatedLayout.vue";
import {useLocale} from "@/composables/useLocale";

const {t} = useLocale();

// State for modal visibility
const showRegisterModal = ref(false);
const {setSeoMeta, generateFAQJsonLd} = useSeo();

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

defineOptions({
    layout: AuthenticatedLayout,
});

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
        title: 'WinnerBreak - Professional Billiard League Platform | Join Now',
        description: 'Join WinnerBreak - the premier billiard league management platform in Ukraine and worldwide. Compete in professional leagues and tournaments, track your ELO rating, challenge players, win prizes. Free registration. Start your professional billiards journey today.',
        keywords: [
            'WinnerBreak', 'ВиннерБрейк',
            'billiard league platform', 'платформа бильярдных лиг',
            'pool tournament software', 'программа для турниров по пулу',
            'billiards management system', 'система управления бильярдом',
            'professional pool leagues', 'профессиональные лиги пула',
            'ELO rating system', 'система рейтинга ELO',
            'online billiard registration', 'онлайн регистрация на бильярд',
            'tournament management', 'управление турнирами',
            'league standings tracker', 'трекер турнирной таблицы',
            'billiard statistics', 'статистика бильярда',
            'player rankings', 'рейтинги игроков',
            'challenge system', 'система вызовов',
            'Ukraine billiards', 'украинский бильярд',
            'Lviv pool leagues', 'львовские лиги пула',
            'free billiard platform', 'бесплатная платформа для бильярда',
            'multiplayer pool games', 'многопользовательские игры в пул',
            'prize pool tournaments', 'турниры с призовым фондом'
        ],
        ogType: 'website',
        ogImage: '/images/winnerbreak-hero.jpg',
        canonicalUrl: window.location.origin,
        robots: 'index, follow',
        alternateLanguages: [
            {lang: 'en', url: `${window.location.origin}/en`},
            {lang: 'uk', url: `${window.location.origin}/uk`},
            {lang: 'ru', url: `${window.location.origin}/ru`}
        ],
        additionalMeta: [
            {name: 'apple-mobile-web-app-capable', content: 'yes'},
            {name: 'application-name', content: 'WinnerBreak'},
        ],
        jsonLd: {
            "@context": "https://schema.org",
            "@graph": [
                {
                    "@type": "WebSite",
                    "@id": `${window.location.origin}/#website`,
                    "url": window.location.origin,
                    "name": "WinnerBreak",
                    "description": "Professional billiard league management platform for competitive players",
                    "publisher": {
                        "@id": `${window.location.origin}/#organization`
                    },
                    "potentialAction": {
                        "@type": "SearchAction",
                        "target": {
                            "@type": "EntryPoint",
                            "urlTemplate": `${window.location.origin}/search?q={search_term_string}`
                        },
                        "query-input": "required name=search_term_string"
                    },
                    "inLanguage": ["en-US", "uk-UA", "ru-RU"]
                },
                {
                    "@type": "Organization",
                    "@id": `${window.location.origin}/#organization`,
                    "name": "WinnerBreak",
                    "alternateName": "Winner Break",
                    "url": window.location.origin,
                    "logo": {
                        "@type": "ImageObject",
                        "url": `${window.location.origin}/images/logo.png`,
                        "width": 600,
                        "height": 600
                    },
                    "image": `${window.location.origin}/images/winnerbreak-hero.jpg`,
                    "description": "Professional billiard league management platform for competitive players worldwide",
                    "foundingDate": "2023",
                    "foundingLocation": {
                        "@type": "Place",
                        "address": {
                            "@type": "PostalAddress",
                            "addressLocality": "Lviv",
                            "addressRegion": "Lviv Oblast",
                            "addressCountry": "UA"
                        }
                    },
                    "areaServed": [
                        {
                            "@type": "Country",
                            "name": "Ukraine"
                        },
                        {
                            "@type": "Country",
                            "name": "Poland"
                        }
                    ],
                    "sameAs": [
                        "https://facebook.com/winnerbreak",
                        "https://twitter.com/winnerbreak",
                        "https://instagram.com/winnerbreak",
                        "https://youtube.com/winnerbreak",
                        "https://linkedin.com/company/winnerbreak"
                    ],
                    "contactPoint": [
                        {
                            "@type": "ContactPoint",
                            "telephone": "+380-98-443-8205",
                            "contactType": "customer service",
                            "areaServed": "UA",
                            "availableLanguage": ["Ukrainian", "English"],
                            "contactOption": ["TollFree", "HearingImpairedSupported"]
                        }
                    ]
                },
                {
                    "@type": "WebApplication",
                    "name": "WinnerBreak Platform",
                    "operatingSystem": "Web Browser",
                    "applicationCategory": "SportsApplication",
                    "aggregateRating": {
                        "@type": "AggregateRating",
                        "ratingValue": "4.8",
                        "ratingCount": "1250"
                    },
                    "offers": {
                        "@type": "Offer",
                        "price": "0",
                        "priceCurrency": "USD",
                        "priceValidUntil": "2025-12-31"
                    },
                    "featureList": features.map(f => f.title)
                },
                generateFAQJsonLd([
                    {
                        question: t("How do I join a billiard league?"),
                        answer: t("Create a free account, browse available leagues, and click 'Join League'. You'll need admin approval for some leagues.")
                    },
                    {
                        question: t("What is the ELO rating system?"),
                        answer: t("ELO is a skill rating system that tracks your performance. Win matches against higher-rated players to increase your rating faster.")
                    },
                    {
                        question: t("How much does it cost to use WinnerBreak?"),
                        answer: t("WinnerBreak is free to use. Tournament entry fees and league dues are set by organizers.")
                    },
                    {
                        question: t("Can I create my own league or tournament?"),
                        answer: t("Yes! Admin users can create and manage leagues and tournaments. Contact support for admin access.")
                    },
                    {
                        question: t("What billiard games are supported?"),
                        answer: t("We support 8-ball, 9-ball, 10-ball, straight pool, snooker, and other popular billiard games.")
                    }
                ])
            ]
        }
    });
});

</script>

<template>
    <Head title="Professional Billiard League Platform"/>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800">
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
    </div>

    <!-- Registration Modal -->
    <RegisterModal
        :show="showRegisterModal"
        @close="closeRegisterModal"
        @error="handleRegisterError"
        @success="handleRegisterSuccess"
    />
</template>
