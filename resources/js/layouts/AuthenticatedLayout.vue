resources/js/Layouts/AuthenticatedLayout.vue

<script lang="ts" setup>
import ApplicationLogo from '@/Components/ui/ApplicationLogo.vue';
import RegisterModal from '@/Components/Auth/RegisterModal.vue';
import Dropdown from '@/Components/ui/dropdown/Dropdown.vue';
import DropdownLink from '@/Components/ui/dropdown/DropdownLink.vue';
import NavLink from '@/Components/ui/NavLink.vue';
import UserAvatar from '@/Components/ui/UserAvatar.vue';
import {useAuth} from '@/composables/useAuth';
import {Head, Link} from '@inertiajs/vue3';
import axios from 'axios';
import {onMounted, ref, watch} from 'vue';
import LocaleSwitcher from "@/Components/ui/LocaleSwitcher.vue";
import {useLocale} from '@/composables/useLocale';
import ToastContainer from '@/Components/ui/ToastContainer.vue';
import MonoDonate from '@/Components/ui/MonoDonate.vue';
import {ChevronDownIcon, MenuIcon, XIcon} from 'lucide-vue-next';

const {t} = useLocale();
const {user} = useAuth();
const isLoading = ref(true);
const showingNavigationDropdown = ref(false);
const showRegisterModal = ref(false);

// Mobile menu state
const mobileMenuOpen = ref(false);

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
                                    <LocaleSwitcher class="w-full"/>
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
</template>
