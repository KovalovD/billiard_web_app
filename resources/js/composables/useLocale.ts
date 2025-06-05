import {apiClient} from '@/lib/apiClient';
import {computed, ref, watch} from 'vue';

interface Translations {
    [namespace: string]: {
        [key: string]: string;
    };
}

interface LocaleData {
    current_locale: string;
    available_locales: string[];
    translations: Translations;
}

// Глобальний стейт для локалізації
const currentLocale = ref<string>('en');
const availableLocales = ref<string[]>(['en', 'uk']);
const translations = ref<Translations>({});
const isLoading = ref(false);
const error = ref<string | null>(null);

// Локальне зберігання ключа локалі
const LOCALE_STORAGE_KEY = 'app_locale';

// Функція для збереження локалі в localStorage
const saveLocaleToStorage = (locale: string) => {
    try {
        localStorage.setItem(LOCALE_STORAGE_KEY, locale);
// eslint-disable-next-line
    } catch (e) {
        // Silent fail для випадків, коли localStorage недоступний
    }
};

// Функція для отримання локалі з localStorage
const getLocaleFromStorage = (): string | null => {
    try {
        return localStorage.getItem(LOCALE_STORAGE_KEY);
// eslint-disable-next-line
    } catch (e) {
        return null;
    }
};

// Функція для визначення локалі браузера
const getBrowserLocale = (): string => {
    if (typeof navigator !== 'undefined') {
        const browserLang = navigator.language.split('-')[0];
        return availableLocales.value.includes(browserLang) ? browserLang : 'en';
    }
    return 'en';
};

export function useLocale() {
    // Ініціалізація локалі
    const initializeLocale = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            // Спочатку спробуємо отримати дані з сервера
            const response = await apiClient<LocaleData>('/api/locale/current');

            currentLocale.value = response.current_locale;
            availableLocales.value = response.available_locales;
            translations.value = response.translations;

            // Зберігаємо в localStorage
            saveLocaleToStorage(currentLocale.value);
// eslint-disable-next-line
        } catch (err) {
            // Якщо сервер недоступний, використовуємо збережену локаль або браузерну
            const savedLocale = getLocaleFromStorage();
            const browserLocale = getBrowserLocale();

            currentLocale.value = savedLocale || browserLocale;

            // Завантажуємо базові переклади
            await loadTranslations(currentLocale.value);
        } finally {
            isLoading.value = false;
        }
    };

    // Зміна локалі
    const setLocale = async (locale: string) => {
        if (!availableLocales.value.includes(locale)) {
            throw new Error(`Locale "${locale}" is not supported`);
        }

        if (locale === currentLocale.value) {
            return; // Локаль вже встановлена
        }

        isLoading.value = true;
        error.value = null;

        try {
            // Відправляємо запит на сервер для зміни локалі
            await apiClient('/api/locale/set', {
                method: 'POST',
                data: {locale}
            });

            // Оновлюємо локальний стейт
            currentLocale.value = locale;
            saveLocaleToStorage(locale);

            // Завантажуємо нові переклади
            await loadTranslations(locale);
        } catch (err: any) {
            error.value = err.message || 'Failed to change locale';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

// Завантаження перекладів для конкретної локалі
    const loadTranslations = async (locale: string) => {
        try {
            const response = await apiClient<LocaleData>('/api/locale/current');
            translations.value = response.translations;
// eslint-disable-next-line
        } catch (err) {
            // Якщо не вдалося завантажити з сервера, використовуємо статичні переклади
            translations.value = await getStaticTranslations(locale);
        }
    };

    // Статичні переклади як fallback
    const getStaticTranslations = async (locale: string): Promise<Translations> => {
        const staticTranslations: Record<string, Translations> = {
            en: {
                common: {
                    welcome: 'Welcome',
                    loading: 'Loading...',
                    error: 'Error',
                    success: 'Success',
                    cancel: 'Cancel',
                    save: 'Save',
                    delete: 'Delete',
                    edit: 'Edit',
                    create: 'Create',
                    view: 'View',
                    back: 'Back',
                    search: 'Search',
                    language: 'Language',
                    english: 'English',
                    ukrainian: 'Ukrainian'
                },
                navigation: {
                    dashboard: 'Dashboard',
                    leagues: 'Leagues',
                    tournaments: 'Tournaments',
                    profile: 'Profile',
                    settings: 'Settings',
                    logout: 'Log Out'
                }
            },
            uk: {
                common: {
                    welcome: 'Ласкаво просимо',
                    loading: 'Завантаження...',
                    error: 'Помилка',
                    success: 'Успіх',
                    cancel: 'Скасувати',
                    save: 'Зберегти',
                    delete: 'Видалити',
                    edit: 'Редагувати',
                    create: 'Створити',
                    view: 'Переглянути',
                    back: 'Назад',
                    search: 'Пошук',
                    language: 'Мова',
                    english: 'Англійська',
                    ukrainian: 'Українська'
                },
                navigation: {
                    dashboard: 'Головна',
                    leagues: 'Ліги',
                    tournaments: 'Турніри',
                    profile: 'Профіль',
                    settings: 'Налаштування',
                    logout: 'Вийти'
                }
            }
        };

        return staticTranslations[locale] || staticTranslations.en;
    };

    // Функція для отримання перекладу
    const t = (key: string, params?: Record<string, string | number>): string => {
        const keys = key.split('.');
        let value: any = translations.value;

        // Проходимо по ключах для отримання значення
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                // Якщо переклад не знайдено, повертаємо ключ
                return key;
            }
        }

        if (typeof value !== 'string') {
            return key;
        }

        // Замінюємо параметри в рядку
        if (params) {
            return value.replace(/:(\w+)/g, (match, paramKey) => {
                return params[paramKey]?.toString() || match;
            });
        }

        return value;
    };

    // Функція для перевірки, чи є переклад
    const hasTranslation = (key: string): boolean => {
        const keys = key.split('.');
        let value: any = translations.value;

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return false;
            }
        }

        return typeof value === 'string';
    };

    // Отримання назви мови
    const getLanguageName = (locale: string): string => {
        const languageNames: Record<string, string> = {
            en: t('common.english'),
            uk: t('common.ukrainian')
        };
        return languageNames[locale] || locale;
    };

    // Computed для реактивності
    const isEnglish = computed(() => currentLocale.value === 'en');
    const isUkrainian = computed(() => currentLocale.value === 'uk');
    const currentLanguageName = computed(() => getLanguageName(currentLocale.value));

    // Слідкуємо за змінами локалі для оновлення документа
    watch(currentLocale, (newLocale) => {
        if (typeof document !== 'undefined') {
            document.documentElement.lang = newLocale;
            document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr'; // На майбутнє для RTL мов
        }
    });

    return {
        // Стейт
        currentLocale: computed(() => currentLocale.value),
        availableLocales: computed(() => availableLocales.value),
        translations: computed(() => translations.value),
        isLoading: computed(() => isLoading.value),
        error: computed(() => error.value),

        // Computed значення
        isEnglish,
        isUkrainian,
        currentLanguageName,

        // Методи
        initializeLocale,
        setLocale,
        loadTranslations,
        t,
        hasTranslation,
        getLanguageName
    };
}

// Глобальна ініціалізація (викликається один раз при запуску додатку)
export const initGlobalLocale = async () => {
    const {initializeLocale} = useLocale();
    await initializeLocale();
};
