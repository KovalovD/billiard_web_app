<script lang="ts" setup>
import {Button, Input, Label, Modal, Spinner} from '@/Components/ui';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, User} from '@/types/api';
import {SearchIcon, UserPlusIcon, UsersIcon} from 'lucide-vue-next';
import {computed, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    show: boolean;
    entityType: 'league' | 'match' | 'tournament' | 'rating';
    entityId: number | string;
    leagueId?: number | string; // For multiplayer games
    title?: string;
}

interface Emits {
    (e: 'close'): void;

    (e: 'added'): void;

    (e: 'error', error: ApiError): void;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Add Player'
});

const emit = defineEmits<Emits>();
const {t} = useLocale();

// State
const activeTab = ref<'existing' | 'new'>('existing');
const searchQuery = ref('');
const searchResults = ref<User[]>([]);
const isSearching = ref(false);
const isAdding = ref(false);
const selectedUser = ref<User | null>(null);

// New player form
const newPlayerForm = ref({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: '123456789',
    initial_rating: 0
});

// Computed
const modalTitle = computed(() => {
    const entityNames = {
        league: 'League',
        match: 'Game',
        tournament: 'Tournament',
        rating: 'Rating'
    };
    return `Add Player to ${entityNames[props.entityType]}`;
});

const isNewPlayerFormValid = computed(() => {
    return newPlayerForm.value.firstname.trim() !== '' &&
        newPlayerForm.value.lastname.trim() !== '' &&
        newPlayerForm.value.email.trim() !== '' &&
        newPlayerForm.value.phone.trim() !== '';
});

const canAddExistingPlayer = computed(() => {
    return selectedUser.value !== null;
});

// Methods
const resetState = () => {
    activeTab.value = 'existing';
    searchQuery.value = '';
    searchResults.value = [];
    selectedUser.value = null;
    resetNewPlayerForm();
};

const resetNewPlayerForm = () => {
    newPlayerForm.value = {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        password: 'defaultPassword123',
        initial_rating: 1000
    };
};

const searchUsers = async () => {
    if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const results = await apiClient<User[]>('/api/admin/search-users', {
            method: 'get',
            params: {
                query: searchQuery.value,
                limit: 20
            }
        });
        searchResults.value = results || [];
    } catch (error: any) {
        console.error('Failed to search users:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const selectUser = (user: User) => {
    selectedUser.value = user;
    searchQuery.value = `${user.firstname} ${user.lastname}`;
    searchResults.value = [];
};

const getApiEndpoint = (action: 'existing' | 'new'): string => {
    const baseUrls = {
        league: `/api/admin/leagues/${props.entityId}/players/add-${action}`,
        match: `/api/admin/leagues/${props.leagueId}/multiplayer-games/${props.entityId}/players/add-${action}`,
        tournament: `/api/admin/tournaments/${props.entityId}/players/add-${action}`,
        rating: `/api/admin/official-ratings/${props.entityId}/players/add-${action}`
    };

    return baseUrls[props.entityType];
};

const addExistingPlayer = async () => {
    if (!selectedUser.value) return;

    isAdding.value = true;
    try {
        const endpoint = getApiEndpoint('existing');
        const payload: any = {
            user_id: selectedUser.value.id
        };

        // Add initial_rating for rating system
        if (props.entityType === 'rating') {
            payload.initial_rating = newPlayerForm.value.initial_rating;
        }

        await apiClient(endpoint, {
            method: 'post',
            data: payload
        });

        emit('added');
        handleClose();
    } catch (error: any) {
        emit('error', error);
    } finally {
        isAdding.value = false;
    }
};

const addNewPlayer = async () => {
    if (!isNewPlayerFormValid.value) return;

    isAdding.value = true;
    try {
        const endpoint = getApiEndpoint('new');
        await apiClient(endpoint, {
            method: 'post',
            data: newPlayerForm.value
        });

        emit('added');
        handleClose();
    } catch (error: any) {
        emit('error', error);
    } finally {
        isAdding.value = false;
    }
};

const handleClose = () => {
    resetState();
    emit('close');
};

// Watchers
watch(searchQuery, searchUsers);
watch(() => props.show, (show) => {
    if (show) {
        resetState();
    }
});
</script>

<template>
    <Modal :show="show" :title="modalTitle" size="large" @close="handleClose">
        <!-- Tab Navigation -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button
                    :class="[
                        'py-2 px-1 text-sm font-medium border-b-2',
                        activeTab === 'existing'
                            ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                    ]"
                    @click="activeTab = 'existing'"
                >
                    <UsersIcon class="mr-2 inline h-4 w-4"/>
                    {{ t('Add Existing Player') }}
                </button>
                <button
                    :class="[
                        'py-2 px-1 text-sm font-medium border-b-2',
                        activeTab === 'new'
                            ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                    ]"
                    @click="activeTab = 'new'"
                >
                    <UserPlusIcon class="mr-2 inline h-4 w-4"/>
                    {{ t('Add New Player') }}
                </button>
            </nav>
        </div>

        <!-- Add Existing Player Tab -->
        <div v-if="activeTab === 'existing'" class="space-y-4">
            <div>
                <Label for="search">{{ t('Search Players') }}</Label>
                <div class="relative">
                    <SearchIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                    <Input
                        id="search"
                        v-model="searchQuery"
                        :placeholder="t('Search by name or email...')"
                        class="pl-10"
                        type="text"
                    />
                </div>

                <!-- Selected User Display -->
                <div v-if="selectedUser"
                     class="mt-2 rounded-md border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-green-800 dark:text-green-200">
                                {{ selectedUser.firstname }} {{ selectedUser.lastname }}
                            </p>
                            <p class="text-sm text-green-600 dark:text-green-300">{{ selectedUser.email }}</p>
                        </div>
                        <Button size="sm" variant="ghost" @click="selectedUser = null; searchQuery = ''">
                            {{ t('Clear') }}
                        </Button>
                    </div>
                </div>

                <!-- Search Results -->
                <div v-if="isSearching" class="mt-2 text-center text-sm text-gray-500">
                    <Spinner class="mx-auto h-4 w-4"/>
                    <span class="ml-2">{{ t('Searching...') }}</span>
                </div>
                <div v-else-if="searchResults.length > 0 && !selectedUser"
                     class="mt-2 max-h-60 overflow-y-auto rounded-md border">
                    <div
                        v-for="user in searchResults"
                        :key="user.id"
                        class="cursor-pointer p-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                        @click="selectUser(user)"
                    >
                        <div class="font-medium">{{ user.firstname }} {{ user.lastname }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ user.email }}</div>
                    </div>
                </div>
                <div v-else-if="searchQuery.length >= 2 && !selectedUser" class="mt-2 text-sm text-gray-500">
                    {{ t('No users found matching') }} "{{ searchQuery }}"
                </div>
                <div v-else-if="searchQuery.length < 2 && !selectedUser" class="mt-2 text-sm text-gray-500">
                    {{ t('Type at least 2 characters to search') }}
                </div>
            </div>

            <!-- Initial Rating for Rating System -->
            <div v-if="entityType === 'rating'">
                <Label for="existing_initial_rating">{{ t('Initial Rating') }}</Label>
                <Input
                    id="existing_initial_rating"
                    v-model.number="newPlayerForm.initial_rating"
                    min="0"
                    placeholder="1000"
                    type="number"
                />
                <p class="mt-1 text-xs text-gray-500">
                    {{ t('Starting rating for this player') }}
                </p>
            </div>
        </div>

        <!-- Add New Player Tab -->
        <div v-else class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <Label for="firstname">{{ t('First Name *') }}</Label>
                    <Input
                        id="firstname"
                        v-model="newPlayerForm.firstname"
                        placeholder="First name"
                        required
                        type="text"
                    />
                </div>
                <div>
                    <Label for="lastname">{{ t('Last Name *') }}</Label>
                    <Input
                        id="lastname"
                        v-model="newPlayerForm.lastname"
                        placeholder="Last name"
                        required
                        type="text"
                    />
                </div>
            </div>

            <div>
                <Label for="email">{{ t('Email *') }}</Label>
                <Input
                    id="email"
                    v-model="newPlayerForm.email"
                    placeholder="email@example.com"
                    required
                    type="email"
                />
            </div>

            <div>
                <Label for="phone">{{ t('Phone *') }}</Label>
                <Input
                    id="phone"
                    v-model="newPlayerForm.phone"
                    placeholder="+380123456789"
                    required
                    type="tel"
                />
            </div>

            <div>
                <Label for="password">{{ t('Password') }}</Label>
                <Input
                    id="password"
                    v-model="newPlayerForm.password"
                    placeholder="Password"
                    required
                    type="password"
                />
            </div>

            <!-- Initial Rating for Rating System -->
            <div v-if="entityType === 'rating'">
                <Label for="new_initial_rating">{{ t('Initial Rating') }}</Label>
                <Input
                    id="new_initial_rating"
                    v-model.number="newPlayerForm.initial_rating"
                    min="0"
                    placeholder="1000"
                    type="number"
                />
                <p class="mt-1 text-xs text-gray-500">
                    {{ t('Starting rating for this player') }}
                </p>
            </div>
        </div>

        <template #footer>
            <Button variant="outline" @click="handleClose">
                {{ t('Cancel') }}
            </Button>
            <Button
                v-if="activeTab === 'existing'"
                :disabled="!canAddExistingPlayer || isAdding"
                @click="addExistingPlayer"
            >
                <Spinner v-if="isAdding" class="mr-2 h-4 w-4"/>
                {{ isAdding ? t('Adding...') : t('Add Player') }}
            </Button>
            <Button
                v-else
                :disabled="!isNewPlayerFormValid || isAdding"
                @click="addNewPlayer"
            >
                <Spinner v-if="isAdding" class="mr-2 h-4 w-4"/>
                {{ isAdding ? t('Adding...') : t('Add New Player') }}
            </Button>
        </template>
    </Modal>
</template>
