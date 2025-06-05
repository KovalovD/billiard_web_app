<template>
    <div class="relative">
        <!-- Кнопка перемикача мови -->
        <button
            :class="{
        'bg-gray-50': isDropdownOpen
      }"
            :disabled="isLoading"
            class="flex items-center space-x-2 px-3 py-2 rounded-md border border-gray-200 hover:bg-gray-50 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="toggleDropdown"
        >
            <div class="flex items-center space-x-2">
                <!-- Прапор/іконка поточної мови -->
                <span class="text-lg">
          {{ getCurrentFlag() }}
        </span>

                <!-- Назва поточної мови -->
                <span class="text-sm font-medium text-gray-700">
          {{ currentLanguageName }}
        </span>

                <!-- Іконка стрілки -->
                <svg
                    :class="{ 'rotate-180': isDropdownOpen }"
                    class="w-4 h-4 text-gray-400 transition-transform duration-200"
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

            <!-- Індикатор завантаження -->
            <div
                v-if="isLoading"
                class="w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin"
            />
        </button>

        <!-- Випадаюче меню -->
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
                class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
            >
                <div class="py-1">
                    <button
                        v-for="locale in availableLocales"
                        :key="locale"
                        :class="{
              'bg-gray-100 font-medium': locale === currentLocale
            }"
                        :disabled="isLoading || locale === currentLocale"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                        @click="handleLocaleChange(locale)"
                    >
                        <!-- Прапор/іконка мови -->
                        <span class="text-lg mr-3">
              {{ getFlag(locale) }}
            </span>

                        <!-- Назва мови -->
                        <span>{{ getLanguageName(locale) }}</span>

                        <!-- Індикатор поточної мови -->
                        <svg
                            v-if="locale === currentLocale"
                            class="ml-auto w-4 h-4 text-blue-600"
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

        <!-- Overlay для закриття меню при кліку поза ним -->
        <div
            v-if="isDropdownOpen"
            class="fixed inset-0 z-40"
            @click="closeDropdown"
        />
    </div>
</template>

<script lang="ts" setup>
import {onMounted, onUnmounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

// Composable для роботи з локалізацією
const {
    currentLocale,
    availableLocales,
    currentLanguageName,
    isLoading,
    setLocale,
    getLanguageName
} = useLocale();

// Локальний стейт компонента
const isDropdownOpen = ref(false);

// Методи для роботи з випадаючим меню
const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

const closeDropdown = () => {
    isDropdownOpen.value = false;
};

// Обробка зміни мови
const handleLocaleChange = async (locale: string) => {
    try {
        await setLocale(locale);
        closeDropdown();
    } catch (error) {
        console.error('Failed to change locale:', error);
        // Тут можна додати показ повідомлення про помилку
    }
};

// Функції для отримання прапорців
const getFlag = (locale: string): string => {
    const flags: Record<string, string> = {
        en: '🇺🇸', // або 🇬🇧 для Великобританії
        uk: '🇺🇦'
    };
    return flags[locale] || '🌐';
};

const getCurrentFlag = (): string => {
    return getFlag(currentLocale.value);
};

// Закриття меню при натисканні Escape
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
