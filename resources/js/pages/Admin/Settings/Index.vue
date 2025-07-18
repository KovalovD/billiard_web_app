// resources/js/Pages/Admin/Settings/Index.vue
<script lang="ts" setup>
import {computed, onMounted, ref, watch} from 'vue';
import {Head} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {useAdminSettings} from '@/composables/useAdminSettings';
import {useLocale} from '@/composables/useLocale';
import {useToastStore} from '@/stores/toast';
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Checkbox,
    DataTable,
    Input,
    Label,
    Modal,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner,
    Textarea,
} from '@/Components/ui';
import type {City, Club, ClubTable, Country, Game} from '@/types/api';
import {
    BuildingIcon,
    CheckCircleIcon,
    EditIcon,
    GamepadIcon,
    GlobeIcon,
    MapPinIcon,
    PlusIcon,
    SearchIcon,
    TableIcon,
    Trash2Icon,
    XCircleIcon,
} from 'lucide-vue-next';

defineOptions({layout: AuthenticatedLayout});

const {t} = useLocale();
const toast = useToastStore();
const adminSettings = useAdminSettings();

// Active tab
const activeTab = ref<'countries' | 'cities' | 'clubs' | 'tables' | 'games'>('countries');

// Data states for each tab - completely independent
const countriesData = ref({
    items: [] as Country[],
    loading: false,
    currentPage: 1,
    perPage: 20,
    totalPages: 1,
    searchQuery: '',
});

const citiesData = ref({
    items: [] as City[],
    countries: [] as Country[], // For filters and forms
    loading: false,
    currentPage: 1,
    perPage: 20,
    totalPages: 1,
    searchQuery: '',
    countryFilter: null as number | null,
});

const clubsData = ref({
    items: [] as Club[],
    cities: [] as City[], // For filters and forms
    loading: false,
    currentPage: 1,
    perPage: 20,
    totalPages: 1,
    searchQuery: '',
    cityFilter: null as number | null,
});

const tablesData = ref({
    items: [] as ClubTable[],
    clubs: [] as Club[], // For filters and forms
    loading: false,
    currentPage: 1,
    perPage: 20,
    totalPages: 1,
    searchQuery: '',
    clubFilter: null as number | null,
});

const gamesData = ref({
    items: [] as Game[],
    loading: false,
    currentPage: 1,
    perPage: 20,
    totalPages: 1,
    searchQuery: '',
    typeFilter: null as string | null,
});

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingItem = ref<any>(null);
const deletingItem = ref<any>(null);

// Form states
const countryForm = ref({name: '', flag_path: ''});
const cityForm = ref({name: '', country_id: null as number | null});
const clubForm = ref({name: '', city_id: null as number | null});
const tableForm = ref({
    name: '',
    club_id: null as number | null,
    stream_url: '',
    is_active: true,
    sort_order: 0
});
const gameForm = ref({
    name: '',
    type: 'pool',
    rules: '',
    is_multiplayer: false
});

// Game types
const gameTypes = [
    {value: 'pool', label: t('Pool')},
    {value: 'pyramid', label: t('Pyramid')},
    {value: 'snooker', label: t('Snooker')},
];

// Tab icons
const tabConfig = {
    countries: {icon: GlobeIcon, label: t('Countries')},
    cities: {icon: MapPinIcon, label: t('Cities')},
    clubs: {icon: BuildingIcon, label: t('Clubs')},
    tables: {icon: TableIcon, label: t('Tables')},
    games: {icon: GamepadIcon, label: t('Games')},
};

// Load functions for each tab
const loadCountries = async () => {
    countriesData.value.loading = true;
    try {
        const response = await adminSettings.fetchCountries({
            search: countriesData.value.searchQuery,
            page: countriesData.value.currentPage,
            per_page: countriesData.value.perPage,
        });
        countriesData.value.items = response.data;
        countriesData.value.totalPages = response.meta.last_page;
// eslint-disable-next-line
    } catch (error) {
        toast.error(t('Error'), t('Failed to load countries'));
    } finally {
        countriesData.value.loading = false;
    }
};

const loadCities = async () => {
    citiesData.value.loading = true;
    try {
        // Build params object only with defined values
        const params: any = {
            search: citiesData.value.searchQuery,
            page: citiesData.value.currentPage,
            per_page: citiesData.value.perPage,
        };

        // Only add country_id if it has a value
        if (citiesData.value.countryFilter !== null && citiesData.value.countryFilter !== undefined) {
            params.country_id = citiesData.value.countryFilter;
        }

        // Load cities
        const response = await adminSettings.fetchCities(params);
        citiesData.value.items = response.data;
        citiesData.value.totalPages = response.meta.last_page;

        // Load countries for filters if not loaded
        if (citiesData.value.countries.length === 0) {
            const countriesResponse = await adminSettings.fetchCountries({per_page: 100});
            citiesData.value.countries = countriesResponse.data;
        }
// eslint-disable-next-line
    } catch (error) {
        toast.error(t('Error'), t('Failed to load cities'));
    } finally {
        citiesData.value.loading = false;
    }
};

const loadClubs = async () => {
    clubsData.value.loading = true;
    try {
        // Build params object only with defined values
        const params: any = {
            search: clubsData.value.searchQuery,
            page: clubsData.value.currentPage,
            per_page: clubsData.value.perPage,
        };

        // Only add city_id if it has a value
        if (clubsData.value.cityFilter !== null && clubsData.value.cityFilter !== undefined) {
            params.city_id = clubsData.value.cityFilter;
        }

        // Load clubs
        const response = await adminSettings.fetchClubs(params);
        clubsData.value.items = response.data;
        clubsData.value.totalPages = response.meta.last_page;

        // Load cities for filters if not loaded
        if (clubsData.value.cities.length === 0) {
            const citiesResponse = await adminSettings.fetchCities({per_page: 100});
            clubsData.value.cities = citiesResponse.data;
        }
// eslint-disable-next-line
    } catch (error) {
        toast.error(t('Error'), t('Failed to load clubs'));
    } finally {
        clubsData.value.loading = false;
    }
};

const loadTables = async () => {
    tablesData.value.loading = true;
    try {
        // Build params object only with defined values
        const params: any = {
            search: tablesData.value.searchQuery,
            page: tablesData.value.currentPage,
            per_page: tablesData.value.perPage,
        };

        // Only add club_id if it has a value
        if (tablesData.value.clubFilter !== null && tablesData.value.clubFilter !== undefined) {
            params.club_id = tablesData.value.clubFilter;
        }

        // Load tables
        const response = await adminSettings.fetchClubTables(params);
        tablesData.value.items = response.data;
        tablesData.value.totalPages = response.meta.last_page;

        // Load clubs for filters if not loaded
        if (tablesData.value.clubs.length === 0) {
            const clubsResponse = await adminSettings.fetchClubs({per_page: 100});
            tablesData.value.clubs = clubsResponse.data;
        }
// eslint-disable-next-line
    } catch (error) {
        toast.error(t('Error'), t('Failed to load tables'));
    } finally {
        tablesData.value.loading = false;
    }
};

const loadGames = async () => {
    gamesData.value.loading = true;
    try {
        // Build params object only with defined values
        const params: any = {
            search: gamesData.value.searchQuery,
            page: gamesData.value.currentPage,
            per_page: gamesData.value.perPage,
        };

        // Only add type if it has a value
        if (gamesData.value.typeFilter !== null && gamesData.value.typeFilter !== undefined) {
            params.type = gamesData.value.typeFilter;
        }

        const response = await adminSettings.fetchGames(params);
        gamesData.value.items = response.data;
        gamesData.value.totalPages = response.meta.last_page;
// eslint-disable-next-line
    } catch (error) {
        toast.error(t('Error'), t('Failed to load games'));
    } finally {
        gamesData.value.loading = false;
    }
};

// Load data based on active tab
const loadData = async () => {
    switch (activeTab.value) {
        case 'countries':
            await loadCountries();
            break;
        case 'cities':
            await loadCities();
            break;
        case 'clubs':
            await loadClubs();
            break;
        case 'tables':
            await loadTables();
            break;
        case 'games':
            await loadGames();
            break;
    }
};

// Create item
const createItem = async () => {
    try {
        switch (activeTab.value) {
            case 'countries':
                await adminSettings.createCountry(countryForm.value);
                toast.success(t('Success'), t('Country created successfully'));
                break;

            case 'cities':
                await adminSettings.createCity(cityForm.value as any);
                toast.success(t('Success'), t('City created successfully'));
                break;

            case 'clubs':
                await adminSettings.createClub(clubForm.value as any);
                toast.success(t('Success'), t('Club created successfully'));
                break;

            case 'tables':
                if (!tableForm.value.club_id) {
                    toast.error(t('Error'), t('Please select a club'));
                    return;
                }
                await adminSettings.createClubTable(tableForm.value.club_id, {
                    name: tableForm.value.name,
                    club_id: tableForm.value.club_id,
                    stream_url: tableForm.value.stream_url,
                    is_active: tableForm.value.is_active,
                    sort_order: tableForm.value.sort_order
                });
                toast.success(t('Success'), t('Table created successfully'));
                break;

            case 'games':
                await adminSettings.createGame(gameForm.value);
                toast.success(t('Success'), t('Game created successfully'));
                break;
        }

        showCreateModal.value = false;
        resetForms();
        await loadData();
    } catch (error: any) {
        toast.error(t('Error'), error.message || t('Failed to create item'));
    }
};

// Update item
const updateItem = async () => {
    if (!editingItem.value) return;

    try {
        switch (activeTab.value) {
            case 'countries':
                await adminSettings.updateCountry(editingItem.value.id, countryForm.value);
                toast.success(t('Success'), t('Country updated successfully'));
                break;

            case 'cities':
                await adminSettings.updateCity(editingItem.value.id, cityForm.value as any);
                toast.success(t('Success'), t('City updated successfully'));
                break;

            case 'clubs':
                await adminSettings.updateClub(editingItem.value.id, clubForm.value as any);
                toast.success(t('Success'), t('Club updated successfully'));
                break;

            case 'tables':
                if (!editingItem.value.club_id) {
                    toast.error(t('Error'), t('Club ID missing'));
                    return;
                }
                await adminSettings.updateClubTable(
                    editingItem.value.club_id,
                    editingItem.value.id,
                    {
                        name: tableForm.value.name,
                        club_id: tableForm.value.club_id,
                        stream_url: tableForm.value.stream_url,
                        is_active: tableForm.value.is_active,
                        sort_order: tableForm.value.sort_order
                    }
                );
                toast.success(t('Success'), t('Table updated successfully'));
                break;

            case 'games':
                await adminSettings.updateGame(editingItem.value.id, gameForm.value);
                toast.success(t('Success'), t('Game updated successfully'));
                break;
        }

        showEditModal.value = false;
        editingItem.value = null;
        resetForms();
        await loadData();
    } catch (error: any) {
        toast.error(t('Error'), error.message || t('Failed to update item'));
    }
};

// Delete item
const deleteItem = async () => {
    if (!deletingItem.value) return;

    try {
        switch (activeTab.value) {
            case 'countries':
                await adminSettings.deleteCountry(deletingItem.value.id);
                toast.success(t('Success'), t('Country deleted successfully'));
                break;

            case 'cities':
                await adminSettings.deleteCity(deletingItem.value.id);
                toast.success(t('Success'), t('City deleted successfully'));
                break;

            case 'clubs':
                await adminSettings.deleteClub(deletingItem.value.id);
                toast.success(t('Success'), t('Club deleted successfully'));
                break;

            case 'tables':
                if (!deletingItem.value.club_id) {
                    toast.error(t('Error'), t('Club ID missing'));
                    return;
                }
                await adminSettings.deleteClubTable(deletingItem.value.club_id, deletingItem.value.id);
                toast.success(t('Success'), t('Table deleted successfully'));
                break;

            case 'games':
                await adminSettings.deleteGame(deletingItem.value.id);
                toast.success(t('Success'), t('Game deleted successfully'));
                break;
        }

        showDeleteModal.value = false;
        deletingItem.value = null;
        await loadData();
    } catch (error: any) {
        toast.error(t('Error'), error.message || t('Failed to delete item'));
    }
};

// Open create modal
const openCreateModal = () => {
    resetForms();
    showCreateModal.value = true;
};

// Open edit modal
const openEditModal = (item: any) => {
    editingItem.value = item;

    switch (activeTab.value) {
        case 'countries':
            countryForm.value = {
                name: item.name,
                flag_path: item.flag_path || '',
            };
            break;

        case 'cities':
            cityForm.value = {
                name: item.name,
                country_id: item.country_id,
            };
            break;

        case 'clubs':
            clubForm.value = {
                name: item.name,
                city_id: item.city_id,
            };
            break;

        case 'tables':
            tableForm.value = {
                name: item.name,
                club_id: item.club_id,
                stream_url: item.stream_url || '',
                is_active: item.is_active,
                sort_order: item.sort_order,
            };
            break;

        case 'games':
            gameForm.value = {
                name: item.name,
                type: item.type,
                rules: item.rules || '',
                is_multiplayer: item.is_multiplayer,
            };
            break;
    }

    showEditModal.value = true;
};

// Open delete modal
const openDeleteModal = (item: any) => {
    deletingItem.value = item;
    showDeleteModal.value = true;
};

// Reset forms
const resetForms = () => {
    countryForm.value = {name: '', flag_path: ''};
    cityForm.value = {name: '', country_id: null};
    clubForm.value = {name: '', city_id: null};
    tableForm.value = {name: '', club_id: null, stream_url: '', is_active: true, sort_order: 0};
    gameForm.value = {name: '', type: 'pool', rules: '', is_multiplayer: false};
};


// Table columns
const countryColumns = computed(() => [
    {key: 'name', label: t('Name'), sortable: true},
    {key: 'flag_path', label: t('Flag'), align: 'center' as const},
    {key: 'cities_count', label: t('Cities'), align: 'center' as const},
    {key: 'actions', label: t('Actions'), align: 'right' as const},
]);

const cityColumns = computed(() => [
    {key: 'name', label: t('Name'), sortable: true},
    {key: 'country', label: t('Country'), sortable: true},
    {key: 'clubs_count', label: t('Clubs'), align: 'center' as const},
    {key: 'actions', label: t('Actions'), align: 'right' as const},
]);

const clubColumns = computed(() => [
    {key: 'name', label: t('Name'), sortable: true},
    {key: 'city', label: t('City'), sortable: true},
    {key: 'tables_count', label: t('Tables'), align: 'center' as const},
    {key: 'leagues_count', label: t('Leagues'), align: 'center' as const},
    {key: 'tournaments_count', label: t('Tournaments'), align: 'center' as const},
    {key: 'actions', label: t('Actions'), align: 'right' as const},
]);

const tableColumns = computed(() => [
    {key: 'name', label: t('Name'), sortable: true},
    {key: 'club', label: t('Club'), sortable: true},
    {key: 'stream_url', label: t('Stream URL')},
    {key: 'is_active', label: t('Active'), align: 'center' as const},
    {key: 'actions', label: t('Actions'), align: 'right' as const},
]);

const gameColumns = computed(() => [
    {key: 'name', label: t('Name'), sortable: true},
    {key: 'type', label: t('Type'), align: 'center' as const},
    {key: 'is_multiplayer', label: t('Multiplayer'), align: 'center' as const},
    {key: 'leagues_count', label: t('Leagues'), align: 'center' as const},
    {key: 'tournaments_count', label: t('Tournaments'), align: 'center' as const},
    {key: 'actions', label: t('Actions'), align: 'right' as const},
]);

// Watch for tab changes
watch(activeTab, () => {
    loadData();
});

// Watch for filter changes - each tab has its own watchers
watch(() => [countriesData.value.searchQuery, countriesData.value.currentPage], () => {
    if (activeTab.value === 'countries') loadCountries();
});

watch(() => [citiesData.value.searchQuery, citiesData.value.countryFilter, citiesData.value.currentPage], () => {
    if (activeTab.value === 'cities') loadCities();
});

watch(() => [clubsData.value.searchQuery, clubsData.value.cityFilter, clubsData.value.currentPage], () => {
    if (activeTab.value === 'clubs') loadClubs();
});

watch(() => [tablesData.value.searchQuery, tablesData.value.clubFilter, tablesData.value.currentPage], () => {
    if (activeTab.value === 'tables') loadTables();
});

watch(() => [gamesData.value.searchQuery, gamesData.value.typeFilter, gamesData.value.currentPage], () => {
    if (activeTab.value === 'games') loadGames();
});

// Load initial data
onMounted(() => {
    loadData();
});

// Computed properties for current tab data
const currentTabData = computed(() => {
    switch (activeTab.value) {
        case 'countries':
            return countriesData.value;
        case 'cities':
            return citiesData.value;
        case 'clubs':
            return clubsData.value;
        case 'tables':
            return tablesData.value;
        case 'games':
            return gamesData.value;
        default:
            return null;
    }
});
</script>

<template>
    <Head :title="t('Admin Settings')"/>

    <div class="py-4 sm:py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header - Compact -->
            <div class="mb-4">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                    {{ t('System Settings') }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t('Manage countries, cities, clubs, tables, and games') }}
                </p>
            </div>

            <!-- Tabs - Compact -->
            <div class="mb-3 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-4 sm:space-x-6">
                    <button
                        v-for="(config, tab) in tabConfig"
                        :key="tab"
                        @click="activeTab = tab"
                        :class="[
                            activeTab === tab
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                            'group inline-flex items-center py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        <component
                            :is="config.icon"
                            :class="[
                                activeTab === tab
                                    ? 'text-indigo-500 dark:text-indigo-400'
                                    : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300',
                                'mr-1.5 h-3.5 w-3.5'
                            ]"
                        />
                        {{ config.label }}
                    </button>
                </nav>
            </div>

            <!-- Content -->
            <Card class="shadow-sm">
                <CardHeader class="p-3 sm:p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base">{{ tabConfig[activeTab].label }}</CardTitle>
                            <CardDescription class="text-xs">
                                {{ t('Manage your :entities', {entities: tabConfig[activeTab].label.toLowerCase()}) }}
                            </CardDescription>
                        </div>
                        <Button @click="openCreateModal" class="gap-1.5" size="sm">
                            <PlusIcon class="h-3.5 w-3.5"/>
                            {{ t('Add New') }}
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="p-3 sm:p-4 pt-0 sm:pt-0">
                    <!-- Countries Content -->
                    <template v-if="activeTab === 'countries'">
                        <div class="mb-3">
                            <div class="relative">
                                <SearchIcon class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"/>
                                <Input
                                    v-model="countriesData.searchQuery"
                                    :placeholder="t('Search countries...')"
                                    class="pl-8 h-8 text-sm"
                                />
                            </div>
                        </div>

                        <div v-if="countriesData.loading" class="flex justify-center py-6">
                            <Spinner class="h-6 w-6"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="countryColumns"
                            :data="countriesData.items"
                            :empty-message="t('No countries found')"
                            :compact-mode="true"
                            :row-height="'compact'"
                        >
                            <template #cell-flag_path="{ value }">
                                <span v-if="value" class="text-lg">{{ value }}</span>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </template>

                            <template #cell-cities_count="{ value }">
                                <span class="font-medium text-sm">{{ value || 0 }}</span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-1">
                                    <Button size="icon" variant="ghost" @click="openEditModal(item)" class="h-7 w-7">
                                        <EditIcon class="h-3.5 w-3.5"/>
                                    </Button>
                                    <Button size="icon" variant="ghost" @click="openDeleteModal(item)" class="h-7 w-7">
                                        <Trash2Icon class="h-3.5 w-3.5"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Cities Content -->
                    <template v-if="activeTab === 'cities'">
                        <div class="mb-3 space-y-2">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="flex-1">
                                    <div class="relative">
                                        <SearchIcon
                                            class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"/>
                                        <Input
                                            v-model="citiesData.searchQuery"
                                            :placeholder="t('Search cities...')"
                                            class="pl-8 h-8 text-sm"
                                        />
                                    </div>
                                </div>
                                <div class="w-full sm:w-48">
                                    <Select v-model="citiesData.countryFilter">
                                        <SelectTrigger class="h-8 text-sm">
                                            <SelectValue :placeholder="t('All Countries')"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="null">{{ t('All Countries') }}</SelectItem>
                                            <SelectItem
                                                v-for="country in citiesData.countries"
                                                :key="country.id"
                                                :value="country.id"
                                            >
                                                {{ country.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <div v-if="citiesData.loading" class="flex justify-center py-6">
                            <Spinner class="h-6 w-6"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="cityColumns"
                            :data="citiesData.items"
                            :empty-message="t('No cities found')"
                            :compact-mode="true"
                            :row-height="'compact'"
                        >
                            <template #cell-country="{ item }">
                                <span class="text-sm">{{ item.country?.name }}</span>
                            </template>

                            <template #cell-clubs_count="{ value }">
                                <span class="font-medium text-sm">{{ value || 0 }}</span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-1">
                                    <Button size="icon" variant="ghost" @click="openEditModal(item)" class="h-7 w-7">
                                        <EditIcon class="h-3.5 w-3.5"/>
                                    </Button>
                                    <Button size="icon" variant="ghost" @click="openDeleteModal(item)" class="h-7 w-7">
                                        <Trash2Icon class="h-3.5 w-3.5"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Clubs Content -->
                    <template v-if="activeTab === 'clubs'">
                        <div class="mb-3 space-y-2">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="flex-1">
                                    <div class="relative">
                                        <SearchIcon
                                            class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"/>
                                        <Input
                                            v-model="clubsData.searchQuery"
                                            :placeholder="t('Search clubs...')"
                                            class="pl-8 h-8 text-sm"
                                        />
                                    </div>
                                </div>
                                <div class="w-full sm:w-48">
                                    <Select v-model="clubsData.cityFilter">
                                        <SelectTrigger class="h-8 text-sm">
                                            <SelectValue :placeholder="t('All Cities')"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="null">{{ t('All Cities') }}</SelectItem>
                                            <SelectItem
                                                v-for="city in clubsData.cities"
                                                :key="city.id"
                                                :value="city.id"
                                            >
                                                {{ city.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <div v-if="clubsData.loading" class="flex justify-center py-6">
                            <Spinner class="h-6 w-6"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="clubColumns"
                            :data="clubsData.items"
                            :empty-message="t('No clubs found')"
                            :compact-mode="true"
                            :row-height="'compact'"
                        >
                            <template #cell-city="{ item }">
                                <span class="text-sm">{{ item.city?.name }} <span class="text-xs text-gray-500">({{ item.city?.country?.name }})</span></span>
                            </template>

                            <template #cell-tables_count="{ value }">
                                <span class="font-medium text-sm">{{ value || 0 }}</span>
                            </template>

                            <template #cell-leagues_count="{ value }">
                                <span class="font-medium text-sm">{{ value || 0 }}</span>
                            </template>

                            <template #cell-tournaments_count="{ value }">
                                <span class="font-medium text-sm">{{ value || 0 }}</span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-1">
                                    <Button size="icon" variant="ghost" @click="openEditModal(item)" class="h-7 w-7">
                                        <EditIcon class="h-3.5 w-3.5"/>
                                    </Button>
                                    <Button size="icon" variant="ghost" @click="openDeleteModal(item)" class="h-7 w-7">
                                        <Trash2Icon class="h-3.5 w-3.5"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Tables Content -->
                    <template v-if="activeTab === 'tables'">
                        <div class="mb-3 space-y-2">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="flex-1">
                                    <div class="relative">
                                        <SearchIcon
                                            class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"/>
                                        <Input
                                            v-model="tablesData.searchQuery"
                                            :placeholder="t('Search tables...')"
                                            class="pl-8 h-8 text-sm"
                                        />
                                    </div>
                                </div>
                                <div class="w-full sm:w-48">
                                    <Select v-model="tablesData.clubFilter">
                                        <SelectTrigger class="h-8 text-sm">
                                            <SelectValue :placeholder="t('All Clubs')"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="null">{{ t('All Clubs') }}</SelectItem>
                                            <SelectItem
                                                v-for="club in tablesData.clubs"
                                                :key="club.id"
                                                :value="club.id"
                                            >
                                                {{ club.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <div v-if="tablesData.loading" class="flex justify-center py-6">
                            <Spinner class="h-6 w-6"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="tableColumns"
                            :data="tablesData.items"
                            :empty-message="t('No tables found')"
                            :compact-mode="true"
                            :row-height="'compact'"
                        >
                            <template #cell-club="{ item }">
                                <span class="text-sm">{{ item.club?.name }} <span class="text-xs text-gray-500">({{ item.club?.city?.name }})</span></span>
                            </template>

                            <template #cell-stream_url="{ value }">
                                <span v-if="value" class="text-xs text-gray-600 dark:text-gray-400 truncate block max-w-[150px]">
                                    {{ value }}
                                </span>
                                <span v-else class="text-gray-400 text-sm">—</span>
                            </template>

                            <template #cell-is_active="{ value }">
                                <CheckCircleIcon v-if="value" class="h-4 w-4 text-green-500"/>
                                <XCircleIcon v-else class="h-4 w-4 text-red-500"/>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        @click="openEditModal(item)"
                                        class="h-7 w-7"
                                    >
                                        <EditIcon class="h-3.5 w-3.5"/>
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        @click="openDeleteModal(item)"
                                        class="h-7 w-7"
                                    >
                                        <Trash2Icon class="h-3.5 w-3.5"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Games Content -->
                    <template v-if="activeTab === 'games'">
                        <div class="mb-3 space-y-2">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="flex-1">
                                    <div class="relative">
                                        <SearchIcon
                                            class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"/>
                                        <Input
                                            v-model="gamesData.searchQuery"
                                            :placeholder="t('Search games...')"
                                            class="pl-8 h-8 text-sm"
                                        />
                                    </div>
                                </div>
                                <div class="w-full sm:w-48">
                                    <Select v-model="gamesData.typeFilter">
                                        <SelectTrigger class="h-8 text-sm">
                                            <SelectValue :placeholder="t('All Types')"/>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem :value="null">{{ t('All Types') }}</SelectItem>
                                            <SelectItem
                                                v-for="type in gameTypes"
                                                :key="type.value"
                                                :value="type.value"
                                            >
                                                {{ type.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <div v-if="gamesData.loading" class="flex justify-center py-6">
                            <Spinner class="h-6 w-6"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="gameColumns"
                            :data="gamesData.items"
                            :empty-message="t('No games found')"
                            :compact-mode="true"
                            :row-height="'compact'"
                        >
                            <template #cell-type="{ value }">
                                <span class="capitalize text-sm">{{ value }}</span>
                            </template>

                            <template #cell-is_multiplayer="{ value }">
                                <CheckCircleIcon v-if="value" class="h-4 w-4 text-green-500"/>
                                <XCircleIcon v-else class="h-4 w-4 text-gray-400"/>
                            </template>

                            <template #cell-leagues_count="{ value }">
                                <span class="font-medium text-sm">{{ value || 0 }}</span>
                            </template>

                            <template #cell-tournaments_count="{ value }">
                                <span class="font-medium text-sm">{{ value || 0 }}</span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-1">
                                    <Button size="icon" variant="ghost" @click="openEditModal(item)" class="h-7 w-7">
                                        <EditIcon class="h-3.5 w-3.5"/>
                                    </Button>
                                    <Button size="icon" variant="ghost" @click="openDeleteModal(item)" class="h-7 w-7">
                                        <Trash2Icon class="h-3.5 w-3.5"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Pagination - Compact -->
                    <div
                        v-if="currentTabData && currentTabData.totalPages > 1"
                        class="mt-3 flex items-center justify-between"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="currentTabData.currentPage === 1"
                            @click="currentTabData.currentPage--"
                        >
                            {{ t('Previous') }}
                        </Button>
                        <span class="text-xs text-gray-600 dark:text-gray-400">
                            {{
                                t('Page :page of :total', {
                                    page: currentTabData.currentPage,
                                    total: currentTabData.totalPages
                                })
                            }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="currentTabData.currentPage === currentTabData.totalPages"
                            @click="currentTabData.currentPage++"
                        >
                            {{ t('Next') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Create Modal - Compact -->
    <Modal :show="showCreateModal" @close="showCreateModal = false">
        <div class="p-4">
            <h3 class="text-base font-semibold mb-3">
                {{ t('Create :type', {type: tabConfig[activeTab].label}) }}
            </h3>

            <form @submit.prevent="createItem" class="space-y-3">
                <!-- Country form -->
                <template v-if="activeTab === 'countries'">
                    <div>
                        <Label for="country-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="country-name"
                            v-model="countryForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="country-flag" class="text-sm">{{ t('Flag Emoji') }}</Label>
                        <Input
                            id="country-flag"
                            v-model="countryForm.flag_path"
                            placeholder="🇺🇦"
                            class="h-8 text-sm"
                        />
                    </div>
                </template>

                <!-- City form -->
                <template v-else-if="activeTab === 'cities'">
                    <div>
                        <Label for="city-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="city-name"
                            v-model="cityForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="city-country" class="text-sm">{{ t('Country') }}</Label>
                        <Select v-model="cityForm.country_id" required>
                            <SelectTrigger id="city-country" class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select country')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="country in citiesData.countries"
                                    :key="country.id"
                                    :value="country.id"
                                >
                                    {{ country.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>

                <!-- Club form -->
                <template v-else-if="activeTab === 'clubs'">
                    <div>
                        <Label for="club-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="club-name"
                            v-model="clubForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="club-city" class="text-sm">{{ t('City') }}</Label>
                        <Select v-model="clubForm.city_id" required>
                            <SelectTrigger id="club-city" class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select city')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="city in clubsData.cities"
                                    :key="city.id"
                                    :value="city.id"
                                >
                                    {{ city.name }} ({{ city.country?.name }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>

                <!-- Table form -->
                <template v-else-if="activeTab === 'tables'">
                    <div>
                        <Label for="table-club" class="text-sm">{{ t('Club') }}</Label>
                        <Select v-model="tableForm.club_id" required>
                            <SelectTrigger id="table-club" class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select club')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="club in tablesData.clubs"
                                    :key="club.id"
                                    :value="club.id"
                                >
                                    {{ club.name }} ({{ club.city?.name }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="table-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="table-name"
                            v-model="tableForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="table-stream" class="text-sm">{{ t('Stream URL') }}</Label>
                        <Input
                            id="table-stream"
                            v-model="tableForm.stream_url"
                            type="url"
                            placeholder="https://..."
                            class="h-8 text-sm"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="table-active"
                            v-model="tableForm.is_active"
                            class="h-3.5 w-3.5"
                        />
                        <Label for="table-active" class="text-sm">{{ t('Active') }}</Label>
                    </div>
                </template>

                <!-- Game form -->
                <template v-else-if="activeTab === 'games'">
                    <div>
                        <Label for="game-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="game-name"
                            v-model="gameForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="game-type" class="text-sm">{{ t('Type') }}</Label>
                        <Select v-model="gameForm.type" required>
                            <SelectTrigger id="game-type" class="h-8 text-sm">
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in gameTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="game-rules" class="text-sm">{{ t('Rules') }}</Label>
                        <Textarea
                            id="game-rules"
                            v-model="gameForm.rules"
                            rows="2"
                            class="text-sm"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="game-multiplayer"
                            v-model="gameForm.is_multiplayer"
                            class="h-3.5 w-3.5"
                        />
                        <Label for="game-multiplayer" class="text-sm">{{ t('Multiplayer') }}</Label>
                    </div>
                </template>

                <div class="flex justify-end gap-2 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="showCreateModal = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="adminSettings.isLoading.value"
                    >
                        <Spinner v-if="adminSettings.isLoading.value" class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Create') }}
                    </Button>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Edit Modal - Compact -->
    <Modal :show="showEditModal" @close="showEditModal = false">
        <div class="p-4">
            <h3 class="text-base font-semibold mb-3">
                {{ t('Edit :type', {type: tabConfig[activeTab].label}) }}
            </h3>

            <form @submit.prevent="updateItem" class="space-y-3">
                <!-- Same form fields as create modal -->
                <!-- Country form -->
                <template v-if="activeTab === 'countries'">
                    <div>
                        <Label for="edit-country-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="edit-country-name"
                            v-model="countryForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="edit-country-flag" class="text-sm">{{ t('Flag Emoji') }}</Label>
                        <Input
                            id="edit-country-flag"
                            v-model="countryForm.flag_path"
                            placeholder="🇺🇦"
                            class="h-8 text-sm"
                        />
                    </div>
                </template>

                <!-- City form -->
                <template v-else-if="activeTab === 'cities'">
                    <div>
                        <Label for="edit-city-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="edit-city-name"
                            v-model="cityForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="edit-city-country" class="text-sm">{{ t('Country') }}</Label>
                        <Select v-model="cityForm.country_id" required>
                            <SelectTrigger id="edit-city-country" class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select country')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="country in citiesData.countries"
                                    :key="country.id"
                                    :value="country.id"
                                >
                                    {{ country.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>

                <!-- Club form -->
                <template v-else-if="activeTab === 'clubs'">
                    <div>
                        <Label for="edit-club-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="edit-club-name"
                            v-model="clubForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="edit-club-city" class="text-sm">{{ t('City') }}</Label>
                        <Select v-model="clubForm.city_id" required>
                            <SelectTrigger id="edit-club-city" class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select city')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="city in clubsData.cities"
                                    :key="city.id"
                                    :value="city.id"
                                >
                                    {{ city.name }} ({{ city.country?.name }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>

                <!-- Table form -->
                <template v-else-if="activeTab === 'tables'">
                    <div>
                        <Label for="edit-table-club" class="text-sm">{{ t('Club') }}</Label>
                        <Select v-model="tableForm.club_id" required>
                            <SelectTrigger id="edit-table-club" class="h-8 text-sm">
                                <SelectValue :placeholder="t('Select club')"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="club in tablesData.clubs"
                                    :key="club.id"
                                    :value="club.id"
                                >
                                    {{ club.name }} ({{ club.city?.name }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="edit-table-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="edit-table-name"
                            v-model="tableForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="edit-table-stream" class="text-sm">{{ t('Stream URL') }}</Label>
                        <Input
                            id="edit-table-stream"
                            v-model="tableForm.stream_url"
                            type="url"
                            placeholder="https://..."
                            class="h-8 text-sm"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="edit-table-active"
                            v-model="tableForm.is_active"
                            class="h-3.5 w-3.5"
                        />
                        <Label for="edit-table-active" class="text-sm">{{ t('Active') }}</Label>
                    </div>
                </template>

                <!-- Game form -->
                <template v-else-if="activeTab === 'games'">
                    <div>
                        <Label for="edit-game-name" class="text-sm">{{ t('Name') }}</Label>
                        <Input
                            id="edit-game-name"
                            v-model="gameForm.name"
                            required
                            class="h-8 text-sm"
                        />
                    </div>
                    <div>
                        <Label for="edit-game-type" class="text-sm">{{ t('Type') }}</Label>
                        <Select v-model="gameForm.type" required>
                            <SelectTrigger id="edit-game-type" class="h-8 text-sm">
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in gameTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="edit-game-rules" class="text-sm">{{ t('Rules') }}</Label>
                        <Textarea
                            id="edit-game-rules"
                            v-model="gameForm.rules"
                            rows="2"
                            class="text-sm"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="edit-game-multiplayer"
                            v-model="gameForm.is_multiplayer"
                            class="h-3.5 w-3.5"
                        />
                        <Label for="edit-game-multiplayer" class="text-sm">{{ t('Multiplayer') }}</Label>
                    </div>
                </template>

                <div class="flex justify-end gap-2 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="showEditModal = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="adminSettings.isLoading.value"
                    >
                        <Spinner v-if="adminSettings.isLoading.value" class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Update') }}
                    </Button>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Delete Modal - Compact -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <div class="p-4">
            <h3 class="text-base font-semibold mb-3 text-red-600">
                {{ t('Delete :type', {type: tabConfig[activeTab].label}) }}
            </h3>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{
                    t('Are you sure you want to delete :name? This action cannot be undone.', {
                        name: deletingItem?.name
                    })
                }}
            </p>

            <div class="flex justify-end gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="showDeleteModal = false"
                >
                    {{ t('Cancel') }}
                </Button>
                <Button
                    variant="destructive"
                    size="sm"
                    @click="deleteItem"
                    :disabled="adminSettings.isLoading.value"
                >
                    <Spinner v-if="adminSettings.isLoading.value" class="mr-1.5 h-3.5 w-3.5"/>
                    {{ t('Delete') }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
