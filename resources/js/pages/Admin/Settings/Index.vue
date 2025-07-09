<script lang="ts" setup>
import {ref, onMounted, watch, computed} from 'vue';
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
    Checkbox,
    DataTable,
} from '@/Components/ui';
import type {Country, City, Club, ClubTable, Game} from '@/types/api';
import {
    GlobeIcon,
    MapPinIcon,
    BuildingIcon,
    TableIcon,
    GamepadIcon,
    PlusIcon,
    EditIcon,
    Trash2Icon,
    SearchIcon,
    CheckCircleIcon,
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
    } catch (error) {
        toast.error(t('Error'), t('Failed to load clubs'));
    } finally {
        clubsData.value.loading = false;
    }
};

const loadTables = async () => {
    tablesData.value.loading = true;
    try {
        // Load all tables across all clubs
        let allTables: ClubTable[] = [];

        if (tablesData.value.clubFilter !== null && tablesData.value.clubFilter !== undefined) {
            // Load tables for specific club
            const tables = await adminSettings.fetchClubTables(tablesData.value.clubFilter);
            allTables = tables.map(table => ({
                ...table,
                club_id: tablesData.value.clubFilter,
                club: tablesData.value.clubs.find(c => c.id === tablesData.value.clubFilter)
            }));
        } else {
            // Load tables for all clubs
            const clubsResponse = await adminSettings.fetchClubs({per_page: 100});
            tablesData.value.clubs = clubsResponse.data;

            for (const club of clubsResponse.data) {
                try {
                    const tables = await adminSettings.fetchClubTables(club.id);
                    allTables.push(...tables.map(table => ({
                        ...table,
                        club_id: club.id,
                        club: club
                    })));
                } catch (error) {
                    console.error(`Failed to load tables for club ${club.id}`, error);
                }
            }
        }

        // Apply search filter
        if (tablesData.value.searchQuery) {
            allTables = allTables.filter(table =>
                table.name.toLowerCase().includes(tablesData.value.searchQuery.toLowerCase()) ||
                table.club?.name.toLowerCase().includes(tablesData.value.searchQuery.toLowerCase())
            );
        }

        // Manual pagination
        const startIndex = (tablesData.value.currentPage - 1) * tablesData.value.perPage;
        const endIndex = startIndex + tablesData.value.perPage;
        tablesData.value.items = allTables.slice(startIndex, endIndex);
        tablesData.value.totalPages = Math.ceil(allTables.length / tablesData.value.perPage);
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

// Reorder tables
const reorderTables = async (direction: 'up' | 'down', table: ClubTable, clubId: number) => {
    if (!clubId) return;

    try {
        // Get all tables for this club
        const clubTables = tablesData.value.items.filter(t => t.club_id === clubId);
        const currentIndex = clubTables.findIndex(t => t.id === table.id);

        if (currentIndex === -1) return;

        const targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;

        if (targetIndex < 0 || targetIndex >= clubTables.length) return;

        // Swap positions
        [clubTables[currentIndex], clubTables[targetIndex]] = [clubTables[targetIndex], clubTables[currentIndex]];

        // Update sort orders
        const reorderedTables = clubTables.map((t, i) => ({
            id: t.id,
            sort_order: i + 1
        }));

        await adminSettings.reorderClubTables(clubId, reorderedTables);
        toast.success(t('Success'), t('Tables reordered successfully'));
        await loadTables();
    } catch (error) {
        toast.error(t('Error'), t('Failed to reorder tables'));
    }
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
    }
});
</script>

<template>
    <Head :title="t('Admin Settings')"/>

    <div class="py-6 sm:py-8 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ t('System Settings') }}
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ t('Manage countries, cities, clubs, tables, and games') }}
                </p>
            </div>

            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8">
                    <button
                        v-for="(config, tab) in tabConfig"
                        :key="tab"
                        @click="activeTab = tab"
                        :class="[
                            activeTab === tab
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                            'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        <component
                            :is="config.icon"
                            :class="[
                                activeTab === tab
                                    ? 'text-indigo-500 dark:text-indigo-400'
                                    : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300',
                                '-ml-0.5 mr-2 h-5 w-5'
                            ]"
                        />
                        {{ config.label }}
                    </button>
                </nav>
            </div>

            <!-- Content -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>{{ tabConfig[activeTab].label }}</CardTitle>
                            <CardDescription>
                                {{ t('Manage your :entities', {entities: tabConfig[activeTab].label.toLowerCase()}) }}
                            </CardDescription>
                        </div>
                        <Button @click="openCreateModal" class="gap-2">
                            <PlusIcon class="h-4 w-4"/>
                            {{ t('Add New') }}
                        </Button>
                    </div>
                </CardHeader>

                <CardContent>
                    <!-- Countries Content -->
                    <template v-if="activeTab === 'countries'">
                        <div class="mb-6">
                            <div class="relative">
                                <SearchIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                                <Input
                                    v-model="countriesData.searchQuery"
                                    :placeholder="t('Search countries...')"
                                    class="pl-10"
                                />
                            </div>
                        </div>

                        <div v-if="countriesData.loading" class="flex justify-center py-8">
                            <Spinner class="h-8 w-8"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="countryColumns"
                            :data="countriesData.items"
                            :empty-message="t('No countries found')"
                        >
                            <template #cell-flag_path="{ value }">
                                <span v-if="value" class="text-2xl">{{ value }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <template #cell-cities_count="{ value }">
                                <span class="font-medium">{{ value || 0 }}</span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="ghost" @click="openEditModal(item)">
                                        <EditIcon class="h-4 w-4"/>
                                    </Button>
                                    <Button size="sm" variant="ghost" @click="openDeleteModal(item)">
                                        <Trash2Icon class="h-4 w-4"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Cities Content -->
                    <template v-if="activeTab === 'cities'">
                        <div class="mb-6 space-y-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <div class="relative">
                                        <SearchIcon
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                                        <Input
                                            v-model="citiesData.searchQuery"
                                            :placeholder="t('Search cities...')"
                                            class="pl-10"
                                        />
                                    </div>
                                </div>
                                <div class="w-full sm:w-64">
                                    <Select v-model="citiesData.countryFilter">
                                        <SelectTrigger>
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

                        <div v-if="citiesData.loading" class="flex justify-center py-8">
                            <Spinner class="h-8 w-8"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="cityColumns"
                            :data="citiesData.items"
                            :empty-message="t('No cities found')"
                        >
                            <template #cell-country="{ item }">
                                {{ item.country?.name }}
                            </template>

                            <template #cell-clubs_count="{ value }">
                                <span class="font-medium">{{ value || 0 }}</span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="ghost" @click="openEditModal(item)">
                                        <EditIcon class="h-4 w-4"/>
                                    </Button>
                                    <Button size="sm" variant="ghost" @click="openDeleteModal(item)">
                                        <Trash2Icon class="h-4 w-4"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Clubs Content -->
                    <template v-if="activeTab === 'clubs'">
                        <div class="mb-6 space-y-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <div class="relative">
                                        <SearchIcon
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                                        <Input
                                            v-model="clubsData.searchQuery"
                                            :placeholder="t('Search clubs...')"
                                            class="pl-10"
                                        />
                                    </div>
                                </div>
                                <div class="w-full sm:w-64">
                                    <Select v-model="clubsData.cityFilter">
                                        <SelectTrigger>
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

                        <div v-if="clubsData.loading" class="flex justify-center py-8">
                            <Spinner class="h-8 w-8"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="clubColumns"
                            :data="clubsData.items"
                            :empty-message="t('No clubs found')"
                        >
                            <template #cell-city="{ item }">
                                {{ item.city?.name }} ({{ item.city?.country?.name }})
                            </template>

                            <template #cell-tables_count="{ value }">
                                <span class="font-medium">{{ value || 0 }}</span>
                            </template>

                            <template #cell-leagues_count="{ value }">
                                <span class="font-medium">{{ value || 0 }}</span>
                            </template>

                            <template #cell-tournaments_count="{ value }">
                                <span class="font-medium">{{ value || 0 }}</span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="ghost" @click="openEditModal(item)">
                                        <EditIcon class="h-4 w-4"/>
                                    </Button>
                                    <Button size="sm" variant="ghost" @click="openDeleteModal(item)">
                                        <Trash2Icon class="h-4 w-4"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Tables Content -->
                    <template v-if="activeTab === 'tables'">
                        <div class="mb-6 space-y-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <div class="relative">
                                        <SearchIcon
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                                        <Input
                                            v-model="tablesData.searchQuery"
                                            :placeholder="t('Search tables...')"
                                            class="pl-10"
                                        />
                                    </div>
                                </div>
                                <div class="w-full sm:w-64">
                                    <Select v-model="tablesData.clubFilter">
                                        <SelectTrigger>
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

                        <div v-if="tablesData.loading" class="flex justify-center py-8">
                            <Spinner class="h-8 w-8"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="tableColumns"
                            :data="tablesData.items"
                            :empty-message="t('No tables found')"
                        >
                            <template #cell-club="{ item }">
                                {{ item.club?.name }} ({{ item.club?.city?.name }})
                            </template>

                            <template #cell-stream_url="{ value }">
                                <span v-if="value" class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ value }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <template #cell-is_active="{ value }">
                                <CheckCircleIcon v-if="value" class="h-5 w-5 text-green-500"/>
                                <XCircleIcon v-else class="h-5 w-5 text-red-500"/>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-2">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        @click="openEditModal(item)"
                                    >
                                        <EditIcon class="h-4 w-4"/>
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        @click="openDeleteModal(item)"
                                    >
                                        <Trash2Icon class="h-4 w-4"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Games Content -->
                    <template v-if="activeTab === 'games'">
                        <div class="mb-6 space-y-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <div class="relative">
                                        <SearchIcon
                                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                                        <Input
                                            v-model="gamesData.searchQuery"
                                            :placeholder="t('Search games...')"
                                            class="pl-10"
                                        />
                                    </div>
                                </div>
                                <div class="w-full sm:w-64">
                                    <Select v-model="gamesData.typeFilter">
                                        <SelectTrigger>
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

                        <div v-if="gamesData.loading" class="flex justify-center py-8">
                            <Spinner class="h-8 w-8"/>
                        </div>

                        <DataTable
                            v-else
                            :columns="gameColumns"
                            :data="gamesData.items"
                            :empty-message="t('No games found')"
                        >
                            <template #cell-type="{ value }">
                                <span class="capitalize">{{ value }}</span>
                            </template>

                            <template #cell-is_multiplayer="{ value }">
                                <CheckCircleIcon v-if="value" class="h-5 w-5 text-green-500"/>
                                <XCircleIcon v-else class="h-5 w-5 text-gray-400"/>
                            </template>

                            <template #cell-leagues_count="{ value }">
                                <span class="font-medium">{{ value || 0 }}</span>
                            </template>

                            <template #cell-tournaments_count="{ value }">
                                <span class="font-medium">{{ value || 0 }}</span>
                            </template>

                            <template #cell-actions="{ item }">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="ghost" @click="openEditModal(item)">
                                        <EditIcon class="h-4 w-4"/>
                                    </Button>
                                    <Button size="sm" variant="ghost" @click="openDeleteModal(item)">
                                        <Trash2Icon class="h-4 w-4"/>
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </template>

                    <!-- Pagination -->
                    <div
                        v-if="currentTabData.totalPages > 1"
                        class="mt-6 flex items-center justify-between"
                    >
                        <Button
                            variant="outline"
                            :disabled="currentTabData.currentPage === 1"
                            @click="currentTabData.currentPage--"
                        >
                            {{ t('Previous') }}
                        </Button>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{
                                t('Page :page of :total', {
                                    page: currentTabData.currentPage,
                                    total: currentTabData.totalPages
                                })
                            }}
                        </span>
                        <Button
                            variant="outline"
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

    <!-- Create Modal -->
    <Modal :show="showCreateModal" @close="showCreateModal = false">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">
                {{ t('Create :type', {type: tabConfig[activeTab].label}) }}
            </h3>

            <form @submit.prevent="createItem" class="space-y-4">
                <!-- Country form -->
                <template v-if="activeTab === 'countries'">
                    <div>
                        <Label for="country-name">{{ t('Name') }}</Label>
                        <Input
                            id="country-name"
                            v-model="countryForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="country-flag">{{ t('Flag Emoji') }}</Label>
                        <Input
                            id="country-flag"
                            v-model="countryForm.flag_path"
                            placeholder="🇺🇦"
                        />
                    </div>
                </template>

                <!-- City form -->
                <template v-else-if="activeTab === 'cities'">
                    <div>
                        <Label for="city-name">{{ t('Name') }}</Label>
                        <Input
                            id="city-name"
                            v-model="cityForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="city-country">{{ t('Country') }}</Label>
                        <Select v-model="cityForm.country_id" required>
                            <SelectTrigger id="city-country">
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
                        <Label for="club-name">{{ t('Name') }}</Label>
                        <Input
                            id="club-name"
                            v-model="clubForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="club-city">{{ t('City') }}</Label>
                        <Select v-model="clubForm.city_id" required>
                            <SelectTrigger id="club-city">
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
                        <Label for="table-club">{{ t('Club') }}</Label>
                        <Select v-model="tableForm.club_id" required>
                            <SelectTrigger id="table-club">
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
                        <Label for="table-name">{{ t('Name') }}</Label>
                        <Input
                            id="table-name"
                            v-model="tableForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="table-stream">{{ t('Stream URL') }}</Label>
                        <Input
                            id="table-stream"
                            v-model="tableForm.stream_url"
                            type="url"
                            placeholder="https://..."
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="table-active"
                            v-model="tableForm.is_active"
                        />
                        <Label for="table-active">{{ t('Active') }}</Label>
                    </div>
                </template>

                <!-- Game form -->
                <template v-else-if="activeTab === 'games'">
                    <div>
                        <Label for="game-name">{{ t('Name') }}</Label>
                        <Input
                            id="game-name"
                            v-model="gameForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="game-type">{{ t('Type') }}</Label>
                        <Select v-model="gameForm.type" required>
                            <SelectTrigger id="game-type">
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
                        <Label for="game-rules">{{ t('Rules') }}</Label>
                        <Textarea
                            id="game-rules"
                            v-model="gameForm.rules"
                            rows="3"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="game-multiplayer"
                            v-model="gameForm.is_multiplayer"
                        />
                        <Label for="game-multiplayer">{{ t('Multiplayer') }}</Label>
                    </div>
                </template>

                <div class="flex justify-end gap-3 pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="showCreateModal = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        :disabled="adminSettings.isLoading.value"
                    >
                        <Spinner v-if="adminSettings.isLoading.value" class="mr-2 h-4 w-4"/>
                        {{ t('Create') }}
                    </Button>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Edit Modal -->
    <Modal :show="showEditModal" @close="showEditModal = false">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">
                {{ t('Edit :type', {type: tabConfig[activeTab].label}) }}
            </h3>

            <form @submit.prevent="updateItem" class="space-y-4">
                <!-- Same form fields as create modal -->
                <!-- Country form -->
                <template v-if="activeTab === 'countries'">
                    <div>
                        <Label for="edit-country-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-country-name"
                            v-model="countryForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="edit-country-flag">{{ t('Flag Emoji') }}</Label>
                        <Input
                            id="edit-country-flag"
                            v-model="countryForm.flag_path"
                            placeholder="🇺🇦"
                        />
                    </div>
                </template>

                <!-- City form -->
                <template v-else-if="activeTab === 'cities'">
                    <div>
                        <Label for="edit-city-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-city-name"
                            v-model="cityForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="edit-city-country">{{ t('Country') }}</Label>
                        <Select v-model="cityForm.country_id" required>
                            <SelectTrigger id="edit-city-country">
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
                        <Label for="edit-club-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-club-name"
                            v-model="clubForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="edit-club-city">{{ t('City') }}</Label>
                        <Select v-model="clubForm.city_id" required>
                            <SelectTrigger id="edit-club-city">
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
                        <Label for="edit-table-club">{{ t('Club') }}</Label>
                        <Select v-model="tableForm.club_id" required>
                            <SelectTrigger id="edit-table-club">
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
                        <Label for="edit-table-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-table-name"
                            v-model="tableForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="edit-table-stream">{{ t('Stream URL') }}</Label>
                        <Input
                            id="edit-table-stream"
                            v-model="tableForm.stream_url"
                            type="url"
                            placeholder="https://..."
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="edit-table-active"
                            v-model="tableForm.is_active"
                        />
                        <Label for="edit-table-active">{{ t('Active') }}</Label>
                    </div>
                </template>

                <!-- Game form -->
                <template v-else-if="activeTab === 'games'">
                    <div>
                        <Label for="edit-game-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-game-name"
                            v-model="gameForm.name"
                            required
                        />
                    </div>
                    <div>
                        <Label for="edit-game-type">{{ t('Type') }}</Label>
                        <Select v-model="gameForm.type" required>
                            <SelectTrigger id="edit-game-type">
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
                        <Label for="edit-game-rules">{{ t('Rules') }}</Label>
                        <Textarea
                            id="edit-game-rules"
                            v-model="gameForm.rules"
                            rows="3"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="edit-game-multiplayer"
                            v-model="gameForm.is_multiplayer"
                        />
                        <Label for="edit-game-multiplayer">{{ t('Multiplayer') }}</Label>
                    </div>
                </template>

                <div class="flex justify-end gap-3 pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="showEditModal = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        :disabled="adminSettings.isLoading.value"
                    >
                        <Spinner v-if="adminSettings.isLoading.value" class="mr-2 h-4 w-4"/>
                        {{ t('Update') }}
                    </Button>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Delete Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4 text-red-600">
                {{ t('Delete :type', {type: tabConfig[activeTab].label}) }}
            </h3>

            <p class="text-gray-600 dark:text-gray-400 mb-6">
                {{
                    t('Are you sure you want to delete :name? This action cannot be undone.', {
                        name: deletingItem?.name
                    })
                }}
            </p>

            <div class="flex justify-end gap-3">
                <Button
                    variant="outline"
                    @click="showDeleteModal = false"
                >
                    {{ t('Cancel') }}
                </Button>
                <Button
                    variant="destructive"
                    @click="deleteItem"
                    :disabled="adminSettings.isLoading.value"
                >
                    <Spinner v-if="adminSettings.isLoading.value" class="mr-2 h-4 w-4"/>
                    {{ t('Delete') }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
