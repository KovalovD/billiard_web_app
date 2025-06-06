<script lang="ts" setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import RegisterModal from '@/Components/Auth/RegisterModal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import {useAuth} from '@/composables/useAuth';
import {Head, Link} from '@inertiajs/vue3';
import axios from 'axios';
import {onMounted, ref} from 'vue';
import LocaleSwitcher from "@/Components/LocaleSwitcher.vue";
import {useLocale} from '@/composables/useLocale';

// Add to existing code
const {t} = useLocale();
// Simple local state management
const { user } = useAuth();
const isLoading = ref(true);
const showingNavigationDropdown = ref(false);
const showRegisterModal = ref(false);

// Get auth status directly from localStorage/API
const getUser = async () => {
    try {
        await useAuth().initializeAuth();
        isLoading.value = false;
    } catch (error) {
        console.error('[Layout] Error fetching user:', error);
        // Clear localStorage
        localStorage.removeItem('authToken');
        localStorage.removeItem('authDeviceName');
    }
};

// Handle logout with proper session clearing
const handleLogout = async () => {
    const token = localStorage.getItem('authToken');
    const deviceName = localStorage.getItem('authDeviceName') || 'web';

    // First clear local storage
    localStorage.removeItem('authToken');
    localStorage.removeItem('authDeviceName');

    if (token) {
        try {
            // Then call API logout endpoint
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
};

const closeRegisterModal = () => {
    showRegisterModal.value = false;
};

const handleRegisterSuccess = () => {
    // The registration will handle the redirection
};

// Load user on component mount
onMounted(() => {
    getUser();
});
</script>

<template>
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-100 dark:bg-gray-900">
        <div class="h-12 w-12 animate-spin rounded-full border-b-2 border-gray-900 dark:border-gray-100"></div>
    </div>

    <div v-else>
        <Head :title="$page.props.title || 'B2B League'"/>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <div class="flex shrink-0 items-center">
                                <Link :href="'/dashboard'">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"/>
                                </Link>
                            </div>
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink :active="$page.url === '/dashboard'" :href="'/dashboard'">{{
                                        t('Dashboard')
                                    }}
                                </NavLink>
                                <NavLink :active="$page.url.startsWith('/leagues')" :href="'/leagues'"> {{
                                        t('Leagues')
                                    }}
                                </NavLink>
                                <NavLink :active="$page.url.startsWith('/tournaments')" :href="'/tournaments'">
                                    {{ t('Tournaments') }}
                                </NavLink>
                                <NavLink :active="$page.url.startsWith('/official-ratings')"
                                         :href="'/official-ratings'"> {{ t('Official Ratings') }}
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ml-6 sm:flex sm:items-center">
                            <LocaleSwitcher/>
                            <div v-if="user" class="relative ml-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm leading-4 font-medium text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                                type="button"
                                            >
                                                {{ user.firstname || user.name || t('User') }}
                                                <svg
                                                    class="-mr-0.5 ml-2 h-4 w-4"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <path
                                                        clip-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        fill-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="'/profile/edit'"> {{ t('Profile') }}</DropdownLink>
                                        <DropdownLink :href="'/profile/stats'"> {{ t('Statistic') }}</DropdownLink>
                                        <div class="border-t border-gray-200 dark:border-gray-600"/>
                                        <DropdownLink as="button" @click.prevent="handleLogout"> {{
                                                t('Log Out')
                                            }}
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                            <div v-else class="flex space-x-4">
                                <Link
                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-4 py-2 text-sm leading-4 font-medium text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                    href="/login"
                                >
                                    {{ t('Log In') }}
                                </Link>
                                <button
                                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none active:bg-gray-900 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:active:bg-gray-300"
                                    @click="openRegisterModal"
                                >
                                    {{ t('Register') }}
                                </button>
                            </div>
                        </div>

                        <div class="-mr-2 flex items-center sm:hidden">
                            <button
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400"
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        d="M4 6h16M4 12h16M4 18h16"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        d="M6 18L18 6M6 6l12 12"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                     class="sm:hidden">
                    <div class="space-y-1 pt-2 pb-3">
                        <ResponsiveNavLink :active="$page.url === '/dashboard'" :href="'/dashboard'">
                            {{ t('Dashboard') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :active="$page.url.startsWith('/leagues')" :href="'/leagues'">
                            {{ t('Leagues') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :active="$page.url.startsWith('/tournaments')" :href="'/tournaments'">
                            {{ t('Tournaments') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :active="$page.url.startsWith('/official-ratings')"
                                           :href="'/official-ratings'">
                            {{ t('Official Ratings') }}
                        </ResponsiveNavLink>
                    </div>
                    <div v-if="user" class="border-t border-gray-200 pt-4 pb-1 dark:border-gray-700">
                        <div class="px-4">

                            <div class="text-base font-medium text-gray-800 dark:text-gray-200">
                                {{ user.firstname || user.name || t('User') }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <LocaleSwitcher/>
                            <ResponsiveNavLink :href="'/profile/edit'"> {{ t('Profile') }}</ResponsiveNavLink>
                            <ResponsiveNavLink as="button" @click.prevent="handleLogout"> {{
                                    t('Log Out')
                                }}
                            </ResponsiveNavLink>
                        </div>
                    </div>
                    <div v-else class="flex space-x-3 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                        <ResponsiveNavLink :href="'/login'">{{ t('Log In') }}</ResponsiveNavLink>
                        <ResponsiveNavLink as="button" @click="openRegisterModal">{{
                                t('Register')
                            }}
                        </ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <main>
                <slot/>
            </main>

            <!-- Registration Modal -->
            <RegisterModal :show="showRegisterModal" @close="closeRegisterModal" @success="handleRegisterSuccess"/>
        </div>
    </div>
</template>
