<script lang="ts" setup>
import {onMounted, ref} from 'vue';
import {Head, Link} from '@inertiajs/vue3';
import axios from 'axios';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import RegisterModal from '@/Components/Auth/RegisterModal.vue';
import {useAuth} from "@/composables/useAuth";

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
            await axios.post('/api/auth/logout', {deviceName}, {
                headers: {'Authorization': `Bearer ${token}`}
            });
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
    <div v-if="isLoading" class="fixed inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 z-50">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 dark:border-gray-100"></div>
    </div>

    <div v-else>
        <Head :title="$page.props.title || 'B2B League'"/>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="shrink-0 flex items-center">
                                <Link :href="'/dashboard'">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"/>
                                </Link>
                            </div>
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink :active="$page.url === '/dashboard'" :href="'/dashboard'"> Dashboard</NavLink>
                                <NavLink :active="$page.url.startsWith('/leagues')" :href="'/leagues'"> Leagues
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <div v-if="user" class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                    <span class="inline-flex rounded-md">
                      <button
                          class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                          type="button">
                        {{ user.firstname || user.name || 'User' }}
                        <svg class="ml-2 -mr-0.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd"
                                                                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                                      fill-rule="evenodd"/></svg>
                      </button>
                    </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="'/profile/edit'"> Profile</DropdownLink>
                                        <DropdownLink :href="'/profile/stats'"> Statistic</DropdownLink>
                                        <div class="border-t border-gray-200 dark:border-gray-600"/>
                                        <DropdownLink as="button" @click.prevent="handleLogout"> Log Out</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                            <div v-else class="flex space-x-4">
                                <Link class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                      href="/login">
                                    Log In
                                </Link>
                                <button class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none transition ease-in-out duration-150"
                                        @click="openRegisterModal">
                                    Register
                                </button>
                            </div>
                        </div>

                        <div class="-mr-2 flex items-center sm:hidden">
                            <button
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out"
                                @click="showingNavigationDropdown = !showingNavigationDropdown">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"/>
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                     class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :active="$page.url === '/dashboard'" :href="'/dashboard'"> Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :active="$page.url.startsWith('/leagues')" :href="'/leagues'"> Leagues
                        </ResponsiveNavLink>
                    </div>
                    <div v-if="user" class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                {{ user.firstname || user.name || 'User' }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">{{ user.email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="'/profile/edit'"> Profile</ResponsiveNavLink>
                            <ResponsiveNavLink as="button" @click.prevent="handleLogout"> Log Out</ResponsiveNavLink>
                        </div>
                    </div>
                    <div v-else class="py-3 px-4 border-t border-gray-200 dark:border-gray-700 flex space-x-3">
                        <ResponsiveNavLink :href="'/login'">Log In</ResponsiveNavLink>
                        <ResponsiveNavLink as="button" @click="openRegisterModal">Register</ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <main>
                <slot/>
            </main>

            <!-- Registration Modal -->
            <RegisterModal
                :show="showRegisterModal"
                @close="closeRegisterModal"
                @success="handleRegisterSuccess"
            />
        </div>
    </div>
</template>
