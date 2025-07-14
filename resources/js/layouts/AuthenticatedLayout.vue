resources/js/Layouts/AuthenticatedLayout.vue

<script lang="ts" setup>
import ApplicationLogo from '@/Components/Core/ApplicationLogo.vue';
import RegisterModal from '@/Components/Auth/RegisterModal.vue';
import Dropdown from '@/Components/ui/dropdown/Dropdown.vue';
import DropdownLink from '@/Components/ui/dropdown/DropdownLink.vue';
import NavLink from '@/Components/Core/NavLink.vue';
import UserAvatar from '@/Components/Core/UserAvatar.vue';
import {useAuth} from '@/composables/useAuth';
import {Head, Link} from '@inertiajs/vue3';
import axios from 'axios';
import {computed, onMounted, ref, watch} from 'vue';
import LocaleSwitcher from "@/Components/Core/LocaleSwitcher.vue";
import {useLocale} from '@/composables/useLocale';
import ToastContainer from '@/Components/Core/ToastContainer.vue';
import MonoDonate from '@/Components/Core/MonoDonate.vue';
import {ChevronDownIcon, MenuIcon, SettingsIcon, XIcon} from 'lucide-vue-next';

const {t} = useLocale();
const {user} = useAuth();
const isLoading = ref(true);
const showingNavigationDropdown = ref(false);
const showRegisterModal = ref(false);

// Mobile menu state
const mobileMenuOpen = ref(false);

// Computed property to check if user is admin
const isAdmin = computed(() => {
    return user.value?.role === 'admin' || user.value?.is_admin === true;
});

// Close mobile menu when route changes
watch(() => window.location.pathname, () => {
    mobileMenuOpen.value = false;
    showingNavigationDropdown.value = false;
});

const getUser = async () => {
    try {
        await useAuth().initializeAuth();
        isLoading.value = false;
    } catch (error) {
        console.error('[Layout] Error fetching user:', error);
        localStorage.removeItem('authToken');
        localStorage.removeItem('authDeviceName');
    }
};

const handleLogout = async () => {
    const token = localStorage.getItem('authToken');
    const deviceName = localStorage.getItem('authDeviceName') || 'web';

    localStorage.removeItem('authToken');
    localStorage.removeItem('authDeviceName');

    if (token) {
        try {
            await axios.post(
                '/api/auth/logout',
                {deviceName},
                {
                    headers: {Authorization: `Bearer ${token}`},
                },
            );
        } catch (error) {
            console.error('[Layout] Logout API call error:', error);
        }
    }

    location.href = '/';
};

const openRegisterModal = () => {
    showRegisterModal.value = true;
    mobileMenuOpen.value = false;
};

const closeRegisterModal = () => {
    showRegisterModal.value = false;
};

const handleRegisterSuccess = () => {
    // The registration will handle the redirection
};

// Prevent body scroll when mobile menu is open
watch(mobileMenuOpen, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

onMounted(() => {
    getUser();
});
</script>

<template>
    <!-- Loading Screen -->
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="relative">
            <div
                class="h-16 w-16 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600 dark:border-gray-700 dark:border-t-indigo-400"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="h-8 w-8 rounded-full bg-indigo-600 dark:bg-indigo-400 animate-pulse"></div>
            </div>
        </div>
    </div>

    <div v-else>
        <Head :title="$page.props.title || 'WinnerBreak'"/>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <ToastContainer/>

            <!-- Navigation -->
            <nav
                class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-200 shadow-sm dark:bg-gray-800/95 dark:border-gray-700">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <!-- Logo and Desktop Nav -->
                        <div class="flex">
                            <div class="flex shrink-0 items-center">
                                <Link :href="'/dashboard'" class="group">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200 transition-transform group-hover:scale-105"/>
                                </Link>
                            </div>

                            <!-- Desktop Navigation -->
                            <div class="hidden space-x-6 sm:-my-px sm:ml-10 lg:flex">
                                <NavLink
                                    :active="$page.url === '/dashboard'"
                                    :href="'/dashboard'"
                                    class="relative text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors"
                                >
                                    {{ t('Dashboard') }}
                                    <span v-if="$page.url === '/dashboard'"
                                          class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"></span>
                                </NavLink>
                                <NavLink
                                    :active="$page.url.startsWith('/leagues')"
                                    :href="'/leagues'"
                                    class="relative text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors"
                                >
                                    {{ t('Leagues') }}
                                    <span v-if="$page.url.startsWith('/leagues')"
                                          class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"></span>
                                </NavLink>
                                <NavLink
                                    :active="$page.url.startsWith('/tournaments')"
                                    :href="'/tournaments'"
                                    class="relative text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors"
                                >
                                    {{ t('Tournaments') }}
                                    <span v-if="$page.url.startsWith('/tournaments')"
                                          class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"></span>
                                </NavLink>
                                <NavLink
                                    :active="$page.url.startsWith('/players')"
                                    :href="'/players'"
                                    class="relative text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors"
                                >
                                    {{ t('Players') }}
                                    <span v-if="$page.url.startsWith('/players')"
                                          class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"></span>
                                </NavLink>
                                <NavLink
                                    :active="$page.url.startsWith('/official-ratings')"
                                    :href="'/official-ratings'"
                                    class="relative text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors"
                                >
                                    {{ t('Official Ratings') }}
                                    <span v-if="$page.url.startsWith('/official-ratings')"
                                          class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"></span>
                                </NavLink>
                                <NavLink
                                    v-if="isAdmin"
                                    :active="$page.url.startsWith('/admin/settings')"
                                    :href="'/admin/settings'"
                                    class="relative text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors"
                                >
                                    {{ t('Settings') }}
                                    <span v-if="$page.url.startsWith('/admin/settings')"
                                          class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"></span>
                                </NavLink>
                            </div>
                        </div>

                        <!-- Desktop Right Side -->
                        <div class="hidden lg:flex lg:items-center lg:space-x-4">
                            <MonoDonate
                                :show-card="false"
                                :show-qr="false"
                                class="text-sm px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-md hover:from-amber-600 hover:to-orange-600 transition-all"
                            />

                            <LocaleSwitcher/>

                            <div v-if="user" class="relative ml-3">
                                <Dropdown align="right" width="56">
                                    <template #trigger>
                                        <button
                                            class="flex items-center space-x-3 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                            <UserAvatar :user="user" size="sm"/>
                                            <span class="hidden sm:block">{{
                                                    user.firstname || user.name || t('User')
                                                }}</span>
                                            <ChevronDownIcon class="h-4 w-4 text-gray-500"/>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="py-1">
                                            <DropdownLink :href="'/profile/edit'"
                                                          class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                                {{ t('Profile') }}
                                            </DropdownLink>
                                            <DropdownLink :href="'/profile/stats'"
                                                          class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                                {{ t('Statistic') }}
                                            </DropdownLink>
                                            <div class="my-1 border-t border-gray-200 dark:border-gray-600"/>
                                            <DropdownLink as="button" @click.prevent="handleLogout"
                                                          class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                                {{ t('Log Out') }}
                                            </DropdownLink>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                            <div v-else class="flex items-center space-x-3">
                                <Link href="/login"
                                      class="text-sm font-medium text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 transition-colors">
                                    {{ t('Log In') }}
                                </Link>
                                <button @click="openRegisterModal"
                                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors shadow-sm">
                                    {{ t('Register') }}
                                </button>
                            </div>
                        </div>

                        <!-- Mobile menu button -->
                        <div class="flex items-center lg:hidden">
                            <button
                                @click="mobileMenuOpen = !mobileMenuOpen"
                                class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-100"
                            >
                                <span class="sr-only">{{ t('Open main menu') }}</span>
                                <MenuIcon v-if="!mobileMenuOpen" class="h-6 w-6"/>
                                <XIcon v-else class="h-6 w-6"/>
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Mobile menu overlay -->
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
                     @click="mobileMenuOpen = false"></div>
            </Transition>

            <!-- Mobile menu panel -->
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-300 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <div v-if="mobileMenuOpen"
                     class="fixed right-0 top-0 bottom-0 z-50 w-full max-w-sm bg-white shadow-2xl dark:bg-gray-800 lg:hidden">
                    <div class="flex h-full flex-col">
                        <!-- Mobile menu header -->
                        <div
                            class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-4">
                            <ApplicationLogo class="h-8 w-auto"/>
                            <button
                                @click="mobileMenuOpen = false"
                                class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <XIcon class="h-5 w-5"/>
                            </button>
                        </div>

                        <!-- Mobile navigation -->
                        <div class="flex-1 overflow-y-auto px-4 py-6">
                            <nav class="space-y-1">
                                <Link
                                    href="/dashboard"
                                    @click="mobileMenuOpen = false"
                                    :class="[$page.url === '/dashboard' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700', 'group flex items-center rounded-lg px-3 py-2.5 text-base font-medium transition-colors']"
                                >
                                    {{ t('Dashboard') }}
                                </Link>
                                <Link
                                    href="/leagues"
                                    @click="mobileMenuOpen = false"
                                    :class="[$page.url.startsWith('/leagues') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700', 'group flex items-center rounded-lg px-3 py-2.5 text-base font-medium transition-colors']"
                                >
                                    {{ t('Leagues') }}
                                </Link>
                                <Link
                                    href="/tournaments"
                                    @click="mobileMenuOpen = false"
                                    :class="[$page.url.startsWith('/tournaments') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700', 'group flex items-center rounded-lg px-3 py-2.5 text-base font-medium transition-colors']"
                                >
                                    {{ t('Tournaments') }}
                                </Link>
                                <Link
                                    href="/players"
                                    @click="mobileMenuOpen = false"
                                    :class="[$page.url.startsWith('/players') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700', 'group flex items-center rounded-lg px-3 py-2.5 text-base font-medium transition-colors']"
                                >
                                    {{ t('Players') }}
                                </Link>
                                <Link
                                    href="/official-ratings"
                                    @click="mobileMenuOpen = false"
                                    :class="[$page.url.startsWith('/official-ratings') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700', 'group flex items-center rounded-lg px-3 py-2.5 text-base font-medium transition-colors']"
                                >
                                    {{ t('Official Ratings') }}
                                </Link>
                                <Link
                                    v-if="isAdmin"
                                    href="/admin/settings"
                                    @click="mobileMenuOpen = false"
                                    :class="[$page.url.startsWith('/admin/settings') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700', 'group flex items-center rounded-lg px-3 py-2.5 text-base font-medium transition-colors']"
                                >
                                    {{ t('Settings') }}
                                </Link>
                            </nav>

                            <!-- User section or auth buttons -->
                            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                                <div v-if="user">
                                    <div class="mb-4 flex items-center gap-3 px-3">
                                        <UserAvatar :user="user" size="md"/>
                                        <div>
                                            <p class="text-base font-medium text-gray-900 dark:text-white">
                                                {{ user.firstname || user.name || t('User') }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</p>
                                        </div>
                                    </div>

                                    <nav class="space-y-1">
                                        <Link
                                            href="/profile/edit"
                                            @click="mobileMenuOpen = false"
                                            class="block rounded-lg px-3 py-2.5 text-base font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            {{ t('Profile') }}
                                        </Link>
                                        <Link
                                            href="/profile/stats"
                                            @click="mobileMenuOpen = false"
                                            class="block rounded-lg px-3 py-2.5 text-base font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            {{ t('Statistic') }}
                                        </Link>
                                        <Link
                                            v-if="isAdmin"
                                            href="/admin/settings"
                                            @click="mobileMenuOpen = false"
                                            class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-base font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            <SettingsIcon class="h-4 w-4"/>
                                            {{ t('Settings') }}
                                        </Link>
                                        <button
                                            @click="handleLogout"
                                            class="block w-full rounded-lg px-3 py-2.5 text-left text-base font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                                        >
                                            {{ t('Log Out') }}
                                        </button>
                                    </nav>
                                </div>
                                <div v-else class="space-y-3">
                                    <Link
                                        href="/login"
                                        @click="mobileMenuOpen = false"
                                        class="block w-full rounded-lg border border-gray-300 py-2.5 text-center font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        {{ t('Log In') }}
                                    </Link>
                                    <button
                                        @click="openRegisterModal"
                                        class="block w-full rounded-lg bg-indigo-600 py-2.5 text-center font-medium text-white hover:bg-indigo-700 transition-colors"
                                    >
                                        {{ t('Register') }}
                                    </button>
                                </div>

                                <!-- Donate and language switcher -->
                                <div class="mt-6 space-y-3">
                                    <MonoDonate :show-card="false" :show-qr="false"
                                                class="w-full justify-center bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg py-2.5 hover:from-amber-600 hover:to-orange-600 transition-all"/>
                                    <LocaleSwitcher :is-mobile="true"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Main content -->
            <main>
                <slot/>
            </main>

            <!-- Registration Modal -->
            <RegisterModal :show="showRegisterModal" @close="closeRegisterModal" @success="handleRegisterSuccess"/>
        </div>
    </div>

    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700" role="contentinfo">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Main Footer Content -->
            <div class="py-8 sm:py-12">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Brand Section -->
                    <div class="lg:col-span-1">
                        <Link :href="'/dashboard'" class="inline-block group">
                            <ApplicationLogo
                                class="h-10 w-auto fill-current text-gray-800 dark:text-gray-200 transition-transform group-hover:scale-105"/>
                        </Link>
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ t('Professional billiard league management platform for players and organizers.') }}
                        </p>
                    </div>

                    <!-- Platform Links -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                            {{ t('Platform') }}
                        </h3>
                        <nav class="space-y-2" aria-label="Platform navigation">
                            <Link href="/dashboard"
                                  class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Dashboard') }}
                            </Link>
                            <Link href="/leagues"
                                  class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Leagues') }}
                            </Link>
                            <Link href="/tournaments"
                                  class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Tournaments') }}
                            </Link>
                            <Link href="/players"
                                  class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Players') }}
                            </Link>
                            <Link href="/official-ratings"
                                  class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Official Ratings') }}
                            </Link>
                            <Link v-if="isAdmin" href="/admin/settings"
                                  class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Settings') }}
                            </Link>
                        </nav>
                    </div>

                    <!-- Support Section -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                            {{ t('Support') }}
                        </h3>
                        <nav class="space-y-2" aria-label="Support links">
                            <Link :href="route('privacy')"
                                  class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Privacy Policy') }}
                            </Link>
                            <Link :href="route('agreement')"
                                  class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Terms of Service') }}
                            </Link>
                            <a href="mailto:support@winnerbreak.com"
                               class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Contact Us') }}
                            </a>
                            <a href="/help"
                               class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ t('Help Center') }}
                            </a>
                        </nav>
                    </div>

                    <!-- Follow Us Section -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                            {{ t('Contact Us') }}
                        </h3>
                        <div class="space-y-3">
                            <!-- Email -->
                            <a href="mailto:info@winnerbreak.com"
                               class="block text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                info@winnerbreak.com
                            </a>

                            <!-- Social Icons -->
                            <div class="flex items-center space-x-3">
                                <a href="https://facebook.com/winnerbreak"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-gray-600 dark:text-gray-400 hover:text-[#4F39F6] transition-all duration-200 hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="1.8"
                                              d="M14.758 21.976v-7.234h2.77c.444 0 .843-.284.908-.71a6.082 6.082 0 0 0-.008-1.803c-.064-.418-.448-.694-.884-.694h-2.786c0-2.424.416-2.776 2.758-2.82.449-.01.852-.296.918-.728.11-.726.066-1.36-.004-1.806-.063-.413-.444-.68-.872-.677-4.14.035-6.498.51-6.498 6.031H8.916c-.417 0-.787.253-.85.653a6.001 6.001 0 0 0 .005 1.816c.065.438.472.738.929.738h2.06v7.259"/>
                                        <rect width="20" height="20" x="2" y="2" stroke="currentColor" stroke-width="2"
                                              rx="5"/>
                                    </svg>
                                </a>
                                <a href="https://instagram.com/winnerbreak"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-gray-600 dark:text-gray-400 hover:text-[#4F39F6] transition-all duration-200 hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
                                        <path fill="currentColor" fill-rule="evenodd"
                                              d="M7.465 1.066C8.638 1.012 9.012 1 12 1c2.988 0 3.362.013 4.534.066 1.172.053 1.972.24 2.672.511.733.277 1.398.71 1.948 1.27.56.549.992 1.213 1.268 1.947.272.7.458 1.5.512 2.67C22.988 8.639 23 9.013 23 12c0 2.987-.013 3.362-.066 4.535-.053 1.17-.24 1.97-.512 2.67a5.4 5.4 0 0 1-1.268 1.949c-.55.56-1.215.992-1.948 1.268-.7.272-1.5.458-2.67.512-1.174.054-1.548.066-4.536.066-2.988 0-3.362-.013-4.535-.066-1.17-.053-1.97-.24-2.67-.512a5.4 5.4 0 0 1-1.949-1.268 5.4 5.4 0 0 1-1.269-1.948c-.271-.7-.457-1.5-.511-2.67C1.012 15.361 1 14.987 1 12c0-2.987.013-3.362.066-4.534.053-1.172.24-1.972.511-2.672a5.4 5.4 0 0 1 1.27-1.948 5.4 5.4 0 0 1 1.947-1.269c.7-.271 1.501-.457 2.671-.511Zm8.979 1.98c-1.16-.053-1.508-.064-4.445-.064-2.937 0-3.285.011-4.445.064-1.073.049-1.655.228-2.043.379-.513.2-.88.437-1.265.822a3.4 3.4 0 0 0-.822 1.265c-.151.388-.33.97-.379 2.043-.053 1.16-.064 1.508-.064 4.445 0 2.937.011 3.285.064 4.445.049 1.073.228 1.655.379 2.043.176.477.457.91.822 1.265.355.365.788.646 1.265.822.388.151.97.33 2.043.379 1.16.053 1.507.064 4.445.064 2.938 0 3.285-.011 4.445-.064 1.073-.049 1.655-.228 2.043-.379.513-.2.88-.437 1.265-.822.365-.355.646-.788.822-1.265.151-.388.33-.97.379-2.043.053-1.16.064-1.508.064-4.445 0-2.937-.011-3.285-.064-4.445-.049-1.073-.228-1.655-.379-2.043-.2-.513-.437-.88-.822-1.265a3.4 3.4 0 0 0-1.265-.822c-.388-.151-.97-.33-2.043-.379Zm-5.85 12.345a3.67 3.67 0 1 0 2.641-6.846 3.67 3.67 0 0 0-2.641 6.846ZM8.002 8.002a5.654 5.654 0 1 1 7.996 7.996 5.654 5.654 0 0 1-7.996-7.996Zm10.906-.814a1.336 1.336 0 1 0-1.834-1.944 1.336 1.336 0 0 0 1.834 1.944Z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </a>
                                <a href="https://telegram.me/winnerbreak"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-gray-600 dark:text-gray-400 hover:text-[#4F39F6] transition-all duration-200 hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
                                        <path stroke="currentColor" stroke-linejoin="round" stroke-width="1.8"
                                              d="m21.2 3.2-19.6 7L7.2 13l8.4-5.6-5.6 7 8.4 5.6 2.8-16.8Z"/>
                                    </svg>
                                </a>
                                <a href="https://youtube.com/@winnerbreak"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-gray-600 dark:text-gray-400 hover:text-[#4F39F6] transition-all duration-200 hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="1.7"
                                              d="M12 20.5c1.81 0 3.545-.179 5.153-.507 2.01-.41 3.014-.614 3.93-1.792.917-1.179.917-2.532.917-5.238v-1.926c0-2.706 0-4.06-.917-5.238-.916-1.178-1.92-1.383-3.93-1.792A25.998 25.998 0 0 0 12 3.5c-1.81 0-3.545.179-5.153.507-2.01.41-3.014.614-3.93 1.792C2 6.978 2 8.331 2 11.037v1.926c0 2.706 0 4.06.917 5.238.916 1.178 1.92 1.383 3.93 1.792 1.608.328 3.343.507 5.153.507Z"/>
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="1.7"
                                              d="M15.962 12.313c-.148.606-.938 1.04-2.517 1.91-1.718.948-2.577 1.42-3.272 1.238a1.7 1.7 0 0 1-.635-.317C9 14.709 9 13.806 9 12s0-2.71.538-3.144c.182-.147.4-.256.635-.317.695-.183 1.554.29 3.272 1.237 1.58.87 2.369 1.305 2.517 1.91.05.207.05.42 0 .627Z"/>
                                    </svg>
                                </a>
                            </div>

                            <!-- Address -->
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ t('Lviv, Heroiv UPA 80') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-gray-200 dark:border-gray-700 py-6">
                <div class="flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ new Date().getFullYear() }} {{ t('WinnerBreak. All rights reserved.') }}
                    </p>

                    <!-- Optional: Donate and Language -->
                    <div class="flex items-center space-x-4">
                        <MonoDonate
                            :show-card="false"
                            :show-qr="false"
                            class="text-xs px-3 py-1 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded hover:from-amber-600 hover:to-orange-600 transition-all"
                        />
                        <div class="scale-90">
                            <LocaleSwitcher/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
