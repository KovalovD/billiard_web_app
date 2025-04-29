<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { LogOutIcon, UserIcon, SettingsIcon, LogInIcon } from 'lucide-vue-next';
import { Spinner, Button } from '@/Components/ui'; // Import Button

const auth = useAuth();
const showingNavigationDropdown = ref(false);

const handleLogout = async () => {
    try {
        await auth.logout();
        // Logout now handles redirection internally
    } catch (error) {
        console.error('Logout error:', error);
        // Fallback redirect if logout fails
        window.location.href = '/login';
    }
};

const goToProfile = () => {
    // Use window.location for more reliable navigation between auth state changes
    window.location.href = route('profile.edit');
};

const goToLogin = () => {
    // Use window.location instead of router.visit for more reliable auth state transitions
    window.location.href = route('login');
};
</script>

<template>
    <div v-if="!auth.isAuthInitialized.value" class="fixed inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 z-50">
        <Spinner class="w-10 h-10 text-primary"/>
    </div>
    <div v-else>
        <Head :title="$page.props.title || 'B2B League'" />
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="shrink-0 flex items-center">
                                <Link :href="auth.isAuthenticated.value ? route('dashboard') : '/'">
                                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                            </div>
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')" v-if="auth.isAuthenticated.value"> Dashboard </NavLink>
                                <NavLink :href="route('leagues.index')" :active="route().current('leagues.*')"> Leagues </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <div v-if="!auth.isAuthenticated.value">
                                <Button @click="goToLogin" variant="outline">
                                    <LogInIcon class="w-4 h-4 mr-2"/> Login
                                </Button>
                            </div>
                            <div v-else-if="auth.user.value" class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                         <span class="inline-flex rounded-md">
                                             <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                                 {{ auth.user.value?.firstname || auth.user.value?.name || 'User' }}
                                                  <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                             </button>
                                         </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> <UserIcon class="w-4 h-4 mr-2"/> Profile </DropdownLink>
                                        <DropdownLink v-if="auth.isAdmin.value" href="#"> <SettingsIcon class="w-4 h-4 mr-2"/> Admin Panel </DropdownLink>
                                        <div class="border-t border-gray-200 dark:border-gray-600" />
                                        <DropdownLink as="button" @click="handleLogout"> <LogOutIcon class="w-4 h-4 mr-2"/> Log Out </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-mr-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"> <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /> <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /> </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" v-if="auth.isAuthenticated.value"> Dashboard </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('leagues.index')" :active="route().current('leagues.*')"> Leagues </ResponsiveNavLink>
                    </div>
                    <div v-if="!auth.isAuthenticated.value" class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink as="button" @click="goToLogin"> Login </ResponsiveNavLink>
                        </div>
                    </div>
                    <div v-else-if="auth.user.value" class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
                        <div class="px-4">
                            <div class="font-medium text-base text-gray-800 dark:text-gray-200"> {{ auth.user.value?.firstname || auth.user.value?.name || 'User' }} </div>
                            <div class="font-medium text-sm text-gray-500">{{ auth.user.value?.email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                            <ResponsiveNavLink v-if="auth.isAdmin.value" href="#"> Admin Panel </ResponsiveNavLink>
                            <ResponsiveNavLink as="button" @click="handleLogout"> Log Out </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header v-if="auth.isAuthenticated.value && ($slots.header || $page.props.header)" class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"> <slot name="header"> <h2 v-if="$page.props.header" class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"> {{ $page.props.header }} </h2> </slot> </div>
            </header>

            <main> <slot /> </main>
        </div>
    </div>
</template>
