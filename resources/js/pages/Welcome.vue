<script lang="ts" setup>
import RegisterModal from '@/Components/Auth/RegisterModal.vue';
import {Button} from '@/Components/ui';
import {Link} from '@inertiajs/vue3';
import {ref} from 'vue';

// State for modal visibility - start as false
const showRegisterModal = ref(false);

const openRegisterModal = () => {
    showRegisterModal.value = true;
};

const closeRegisterModal = () => {
    showRegisterModal.value = false;
};

const handleRegisterSuccess = () => {
    // Registration already redirects to dashboard in RegisterForm component
};

const handleRegisterError = (error: any) => {
    console.error('Registration failed:', error);
};
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth && $page.props.auth.user"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    :href="route('dashboard')"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <button
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        @click="openRegisterModal"
                    >
                        Register
                    </button>
                </template>
            </nav>
        </header>
        <div class="flex w-full items-center justify-center lg:grow">
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row">
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <h1 class="mb-1 font-medium">Welcome to WinnerBreak</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                        The premier league management system. <br/>Join a league, challenge players, and track your
                        progress.
                    </p>
                    <ul class="mb-4 flex flex-col lg:mb-6">
                        <li class="relative flex items-center gap-4 py-2">
                            <span class="relative bg-white py-1 dark:bg-[#161615]">Browse existing leagues</span>
                        </li>
                        <li class="relative flex items-center gap-4 py-2">
                            <span class="relative bg-white py-1 dark:bg-[#161615]">Challenge other players</span>
                        </li>
                        <li class="relative flex items-center gap-4 py-2">
                            <span class="relative bg-white py-1 dark:bg-[#161615]">Track your rating progress</span>
                        </li>
                    </ul>
                    <div class="flex gap-3 text-sm leading-normal">
                        <Link
                            class="inline-block rounded-sm border border-black bg-[#1b1b18] px-5 py-1.5 text-sm leading-normal text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                            :href="route('login')"
                        >
                            Log In
                        </Link>
                        <Button
                            class="inline-block rounded-sm border border-black bg-white px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-black hover:bg-[#f9f9f9] dark:border-[#eeeeec] dark:bg-[#161615] dark:text-[#eeeeec] dark:hover:border-white dark:hover:bg-[#212120]"
                            variant="outline"
                            @click="openRegisterModal"
                        >
                            Register
                        </Button>
                    </div>
                </div>
                <div
                    class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-[#fff2f2] lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-[#1D0002]"
                >
                    <div class="flex h-full items-center justify-center">
                        <div class="text-4xl font-bold">WinnerBreak</div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Registration Modal -->
        <RegisterModal :show="showRegisterModal" @close="closeRegisterModal" @error="handleRegisterError"
                       @success="handleRegisterSuccess"/>
    </div>
</template>
