resources/js/Components/Core/LocaleSwitcher.vue

<template>
    <div class="relative">
        <!-- Desktop/Default Button -->
        <button
            v-if="!isMobile"
            :disabled="isLoading"
            :class="{
                'bg-gray-100 dark:bg-gray-700': isDropdownOpen,
                'bg-gray-50 dark:bg-gray-800': !isDropdownOpen
            }"
            class="flex items-center space-x-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="toggleDropdown"
        >
            <div class="flex items-center space-x-2">
                <!-- Current language flag -->
                <span class="text-lg">{{ getLanguageFlag(currentLocale) }}</span>

                <!-- Current language name -->
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ currentLanguageName }}
                </span>

                <!-- Arrow icon -->
                <svg
                    :class="{ 'rotate-180': isDropdownOpen }"
                    class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-transform duration-200"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M19 9l-7 7-7-7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                    />
                </svg>
            </div>

            <!-- Loading indicator -->
            <div
                v-if="isLoading"
                class="w-4 h-4 border-2 border-gray-300 dark:border-gray-600 border-t-indigo-600 dark:border-t-indigo-400 rounded-full animate-spin"
            />
        </button>

        <!-- Mobile Full Width Button -->
        <button
            v-else
            :disabled="isLoading"
            class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="toggleDropdown"
        >
            <div class="flex items-center space-x-3">
                <span class="text-lg">{{ getLanguageFlag(currentLocale) }}</span>
                <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                    {{ currentLanguageName }}
                </span>
            </div>

            <div class="flex items-center space-x-2">
                <div
                    v-if="isLoading"
                    class="w-4 h-4 border-2 border-gray-300 dark:border-gray-600 border-t-indigo-600 dark:border-t-indigo-400 rounded-full animate-spin"
                />
                <svg
                    :class="{ 'rotate-180': isDropdownOpen }"
                    class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-transform duration-200"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M19 9l-7 7-7-7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                    />
                </svg>
            </div>
        </button>

        <!-- Dropdown menu -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isDropdownOpen"
                :class="{
                    'absolute right-0 mt-2 w-48': !isMobile,
                    'absolute left-0 right-0 mt-2 mx-4': isMobile
                }"
                class="rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none z-[100]"
            >
                <div class="py-1">
                    <button
                        v-for="locale in availableLocales"
                        :key="locale"
                        :disabled="isLoading || locale === currentLocale"
                        :class="{
                            'bg-gray-100 dark:bg-gray-700 font-medium': locale === currentLocale,
                            'hover:bg-gray-100 dark:hover:bg-gray-700': locale !== currentLocale
                        }"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                        @click="handleLocaleChange(locale)"
                    >
                        <!-- Language flag -->
                        <span class="text-lg mr-3">{{ getLanguageFlag(locale) }}</span>

                        <!-- Language name -->
                        <span>{{ getLanguageName(locale) }}</span>

                        <!-- Current language indicator -->
                        <svg
                            v-if="locale === currentLocale"
                            class="ml-auto w-4 h-4 text-indigo-600 dark:text-indigo-400"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                clip-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                fill-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Overlay for closing menu on outside click -->
        <div
            v-if="isDropdownOpen"
            class="fixed inset-0 z-[90]"
            @click="closeDropdown"
        />
    </div>
</template>

<script lang="ts" setup>
import {onMounted, onUnmounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

// Props
interface Props {
    isMobile?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isMobile: false
});

// Composable for localization
const {
    currentLocale,
    availableLocales,
    currentLanguageName,
    isLoading,
    setLocale,
    getLanguageName
} = useLocale();

// Local component state
const isDropdownOpen = ref(false);

// Language flags mapping
const languageFlags: Record<string, string> = {
    en: '🇬🇧',
    uk: '🇺🇦',
    es: '🇪🇸',
    de: '🇩🇪',
    fr: '🇫🇷',
    it: '🇮🇹',
    pl: '🇵🇱',
    ru: '🇷🇺'
};

// Get flag for language
const getLanguageFlag = (locale: string): string => {
    return languageFlags[locale] || '🌐';
};

// Methods for dropdown menu
const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

const closeDropdown = () => {
    isDropdownOpen.value = false;
};

// Handle language change
const handleLocaleChange = async (locale: string) => {
    try {
        await setLocale(locale);
        closeDropdown();
    } catch (error) {
        console.error('Failed to change locale:', error);
    }
};

// Close menu on Escape key
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
/* Ensure dropdown appears above other elements */
.z-\[90\] {
    z-index: 90;
}

.z-\[100\] {
    z-index: 100;
}
</style>
