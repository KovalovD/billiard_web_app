<!-- resources/js/pages/Admin/Tournaments/Players.vue -->
<script lang="ts" setup>
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
    Spinner
} from '@/Components/ui';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useTournaments} from '@/composables/useTournaments';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {useLocale} from '@/composables/useLocale';
import type {Tournament, TournamentPlayer, User} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CheckIcon,
    PlusIcon,
    RefreshCwIcon,
    SearchIcon,
    TrashIcon,
    UserCheckIcon,
    UserPlusIcon,
    UsersIcon,
    UserXIcon,
    XIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref, watch} from 'vue';
import {apiClient} from '@/lib/apiClient';

defineOptions({layout: AuthenticatedLayout});

const {t} = useLocale();

const props = defineProps<{
    tournamentId: number | string;
}>();

const {
    fetchTournament,
    addPlayerToTournament,
    addNewPlayerToTournament,
    removePlayerFromTournament,
    searchUsers
} = useTournaments();

// Data
const tournament = ref<Tournament | null>(null);
const allPlayers = ref<TournamentPlayer[]>([]);
const searchResults = ref<User[]>([]);
const searchQuery = ref('');
const selectedApplicationIds = ref<number[]>([]);
const rejectionReason = ref('');
const showBulkRejectReason = ref(false);
const newPlayerForm = ref({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: 'defaultPassword123'
});

// Loading states
const isLoadingTournament = ref(true);
const isLoadingPlayers = ref(true);
const isSearching = ref(false);
const isAddingPlayer = ref(false);
const isProcessingApplication = ref(false);

// Modal states
const showAddExistingModal = ref(false);
const showAddNewModal = ref(false);

// Active tab state
const activeTab = ref<'pending' | 'confirmed' | 'rejected' | 'all'>('pending');

// API calls
const tournamentApi = fetchTournament(props.tournamentId);
const addExistingApi = addPlayerToTournament(props.tournamentId);
const addNewApi = addNewPlayerToTournament(props.tournamentId);

// Computed
const pendingApplications = computed(() =>
    allPlayers.value.filter(app => app.is_pending || (app.status === 'applied' && !app.is_confirmed && !app.is_rejected))
);

const confirmedPlayers = computed(() =>
    allPlayers.value.filter(app => app.is_confirmed || app.status === 'confirmed')
);

const rejectedApplications = computed(() =>
    allPlayers.value.filter(app => app.is_rejected || app.status === 'rejected')
);

const filteredPlayers = computed(() => {
    switch (activeTab.value) {
        case 'pending':
            return pendingApplications.value;
        case 'confirmed':
            return confirmedPlayers.value;
        case 'rejected':
            return rejectedApplications.value;
        default:
            return allPlayers.value;
    }
});

const sortedPlayers = computed(() => {
    return [...filteredPlayers.value].sort((a, b) => {
        // Sort by position first (null positions at the end)
        if (a.position === null && b.position === null) {
            return (a.user?.lastname || '').localeCompare(b.user?.lastname || '');
        }
        if (a.position === null) return 1;
        if (b.position === null) return -1;
        return a.position - b.position;
    });
});

const selectedPendingApplications = computed(() =>
    selectedApplicationIds.value.filter(id =>
        pendingApplications.value.some(app => app.id === id)
    )
);

const canBulkConfirm = computed(() => selectedPendingApplications.value.length > 0);
const canBulkReject = computed(() => selectedPendingApplications.value.length > 0);

const canAddPlayers = computed(() => {
    return tournament.value?.is_registration_open || tournament.value?.status !== 'completed';
});

const getStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'applied':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'confirmed':
        case 'registered':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        case 'eliminated':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'dnf':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + '₴';
};

const formatDateTime = (dateString: string | undefined): string => {
    if (!dateString) return '';

    return new Date(dateString).toLocaleString('uk-UK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// DataTable columns for confirmed players
const playersColumns = computed(() => [
    {
        key: 'position',
        label: t('Position'),
        mobileLabel: t('Pos'),
        align: 'center' as const,
        width: '80px',
        render: (player: TournamentPlayer) => player.position || '—'
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (player: TournamentPlayer) => ({
            name: `${player.user?.firstname} ${player.user?.lastname}`,
            email: player.user?.email,
            seed: player.seed_number
        })
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        render: (player: TournamentPlayer) => ({
            status: player.status,
            statusDisplay: player.status_display
        })
    },
    {
        key: 'rating_points',
        label: t('Rating Points'),
        mobileLabel: t('Rating'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: TournamentPlayer) => player.rating_points
    },
    {
        key: 'prize',
        label: t('Prize'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: TournamentPlayer) => player.prize_amount
    },
    {
        key: 'bonus',
        label: t('Bonus'),
        align: 'center' as const,
        hideOnMobile: true,
        render: (player: TournamentPlayer) => player.bonus_amount
    },
    {
        key: 'total',
        label: t('Total'),
        align: 'center' as const,
        render: (player: TournamentPlayer) => player.total_amount
    },
    ...(canAddPlayers.value ? [{
        key: 'actions',
        label: t('Actions'),
        align: 'center' as const,
        width: '100px',
        render: (player: TournamentPlayer) => player.id
    }] : [])
]);

// Watch search query
watch(searchQuery, async (newQuery) => {
    if (newQuery.length >= 2) {
        const {
            data: success,
            execute: searchUsersExecute,
        } = searchUsers(newQuery);
        isSearching.value = true;
        await searchUsersExecute();

        if (success && success.value) {
            searchResults.value = success.value;
        }
        isSearching.value = false;
    } else {
        searchResults.value = [];
    }
});

const loadTournament = async () => {
    isLoadingTournament.value = true;
    const success = await tournamentApi.execute();
    if (success && tournamentApi.data.value) {
        tournament.value = tournamentApi.data.value;
    }
    isLoadingTournament.value = false;
};

const loadPlayers = async () => {
    isLoadingPlayers.value = true;
    try {
        // Load all players/applications from the admin endpoint
        allPlayers.value = await apiClient<TournamentPlayer[]>(
            `/api/admin/tournaments/${props.tournamentId}/applications/all`
        );
    } catch (err: any) {
        console.error('Failed to load players:', err);
    } finally {
        isLoadingPlayers.value = false;
    }
};

const refreshData = async () => {
    await Promise.all([loadTournament(), loadPlayers()]);
};

// Application management functions
const confirmApplication = async (applicationId: number) => {
    isProcessingApplication.value = true;
    try {
        await apiClient(
            `/api/admin/tournaments/${props.tournamentId}/applications/${applicationId}/confirm`,
            {method: 'POST'}
        );
        await loadPlayers();
    } catch (err: any) {
        console.error('Failed to confirm application:', err);
    } finally {
        isProcessingApplication.value = false;
    }
};

const rejectApplication = async (applicationId: number) => {
    isProcessingApplication.value = true;
    try {
        await apiClient(
            `/api/admin/tournaments/${props.tournamentId}/applications/${applicationId}/reject`,
            {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            }
        );
        await loadPlayers();
    } catch (err: any) {
        console.error('Failed to reject application:', err);
    } finally {
        isProcessingApplication.value = false;
    }
};

const bulkConfirmApplications = async () => {
    if (!canBulkConfirm.value) return;

    isProcessingApplication.value = true;
    try {
        await apiClient(
            `/api/admin/tournaments/${props.tournamentId}/applications/bulk-confirm`,
            {
                method: 'POST',
                data: JSON.stringify({application_ids: selectedPendingApplications.value}),
                headers: {'Content-Type': 'application/json'}
            }
        );
        selectedApplicationIds.value = [];
        await loadPlayers();
    } catch (err: any) {
        console.error('Failed to bulk confirm applications:', err);
    } finally {
        isProcessingApplication.value = false;
    }
};

const bulkRejectApplications = async () => {
    if (!canBulkReject.value) return;

    isProcessingApplication.value = true;
    try {
        await apiClient(
            `/api/admin/tournaments/${props.tournamentId}/applications/bulk-reject`,
            {
                method: 'POST',
                data: JSON.stringify({
                    application_ids: selectedPendingApplications.value,
                }),
                headers: {'Content-Type': 'application/json'}
            }
        );
        selectedApplicationIds.value = [];
        rejectionReason.value = '';
        showBulkRejectReason.value = false;
        await loadPlayers();
    } catch (err: any) {
        console.error('Failed to bulk reject applications:', err);
    } finally {
        isProcessingApplication.value = false;
    }
};

const selectAllPending = () => {
    selectedApplicationIds.value = pendingApplications.value.map(app => app.id);
};

const clearSelection = () => {
    selectedApplicationIds.value = [];
};

// Player management functions
const handleAddExistingPlayer = async (userId: number) => {
    isAddingPlayer.value = true;
    const success = await addExistingApi.execute({user_id: userId});
    if (success) {
        searchQuery.value = '';
        await loadPlayers();
    }
    isAddingPlayer.value = false;
};

const handleAddNewPlayer = async () => {
    if (!isNewPlayerFormValid.value) return;

    isAddingPlayer.value = true;
    const success = await addNewApi.execute(newPlayerForm.value);
    if (success) {
        resetNewPlayerForm();
        await loadPlayers();
    }
    isAddingPlayer.value = false;
};

const handleRemovePlayer = async (playerId: number) => {
    if (!confirm(t('Are you sure you want to remove this player from the tournament?'))) {
        return;
    }

    const removeApi = removePlayerFromTournament(props.tournamentId, playerId);
    const success = await removeApi.execute();
    if (success) {
        await loadPlayers();
    }
};

const resetNewPlayerForm = () => {
    newPlayerForm.value = {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        password: 'defaultPassword123'
    };
};

const isNewPlayerFormValid = computed(() => {
    return newPlayerForm.value.firstname.trim() !== '' &&
        newPlayerForm.value.lastname.trim() !== '' &&
        newPlayerForm.value.email.trim() !== '' &&
        newPlayerForm.value.phone.trim() !== '';
});

const isPlayerAlreadyAdded = (userId: number): boolean => {
    return allPlayers.value.some(player => player.user?.id === userId);
};

const handleBackToTournament = () => {
    router.visit(`/tournaments/${tournament.value?.slug}`);
};

onMounted(() => {
    refreshData();
});
</script>

<template>
    <Head :title="tournament ? t('Manage Players: :name', {name: tournament.name}) : t('Manage Tournament Players')"/>

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Mobile-optimized Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ t('Manage Players & Applications') }}</h1>
                    <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : t('Loading...') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button :disabled="isLoadingPlayers" variant="outline" size="sm" @click="refreshData">
                        <RefreshCwIcon :class="{ 'animate-spin': isLoadingPlayers }" class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Refresh') }}</span>
                    </Button>
                    <Button v-if="canAddPlayers" variant="outline" size="sm" @click="showAddExistingModal = true">
                        <UserPlusIcon class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Add Existing Player') }}</span>
                        <span class="sm:hidden">{{ t('Add Existing') }}</span>
                    </Button>
                    <Button v-if="canAddPlayers" size="sm" @click="showAddNewModal = true">
                        <PlusIcon class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Add New Player') }}</span>
                        <span class="sm:hidden">{{ t('Add New') }}</span>
                    </Button>
                    <Button variant="outline" size="sm" @click="handleBackToTournament">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        <span class="hidden sm:inline">{{ t('Back to Tournament') }}</span>
                        <span class="sm:hidden">{{ t('Back') }}</span>
                    </Button>
                </div>
            </div>

            <!-- Tournament Info - Mobile optimized -->
            <Card v-if="tournament" class="mb-6">
                <CardContent class="pt-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-xl sm:text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ tournament.pending_applications_count }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ t('Pending') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl sm:text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ tournament.confirmed_players_count }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{ t('Confirmed') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ tournament.players_count }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{
                                    t('Total Applications')
                                }}
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl sm:text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ tournament.max_participants || '∞' }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">{{
                                    t('Max Participants')
                                }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Bulk Actions for Pending Applications -->
            <Card v-if="pendingApplications.length > 0 && activeTab === 'pending'" class="mb-6">
                <CardHeader>
                    <CardTitle class="text-lg sm:text-xl">{{ t('Bulk Actions') }}</CardTitle>
                    <CardDescription class="text-sm">
                        {{
                            t(':selected of :total pending applications selected', {
                                selected: selectedPendingApplications.length,
                                total: pendingApplications.length
                            })
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <Button variant="outline" size="sm" @click="selectAllPending">
                                {{ t('Select All Pending') }}
                            </Button>
                            <Button :disabled="selectedApplicationIds.length === 0" variant="outline"
                                    size="sm"
                                    @click="clearSelection">
                                {{ t('Clear Selection') }}
                            </Button>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2">
                            <Button
                                :disabled="!canBulkConfirm || isProcessingApplication"
                                class="bg-green-600 hover:bg-green-700"
                                size="sm"
                                @click="bulkConfirmApplications"
                            >
                                <UserCheckIcon class="mr-2 h-4 w-4"/>
                                <Spinner v-if="isProcessingApplication" class="mr-2 h-4 w-4"/>
                                <span class="hidden sm:inline">{{
                                        t('Confirm Selected (:count)', {count: selectedPendingApplications.length})
                                    }}</span>
                                <span class="sm:hidden">{{
                                        t('Confirm (:count)', {count: selectedPendingApplications.length})
                                    }}</span>
                            </Button>
                            <Button
                                v-if="!showBulkRejectReason"
                                :disabled="!canBulkReject"
                                variant="destructive"
                                size="sm"
                                @click="showBulkRejectReason = true"
                            >
                                <UserXIcon class="mr-2 h-4 w-4"/>
                                <span class="hidden sm:inline">{{
                                        t('Reject Selected (:count)', {count: selectedPendingApplications.length})
                                    }}</span>
                                <span class="sm:hidden">{{
                                        t('Reject (:count)', {count: selectedPendingApplications.length})
                                    }}</span>
                            </Button>
                        </div>

                        <!-- Bulk Reject Reason -->
                        <div v-if="showBulkRejectReason" class="space-y-3 border-t pt-4">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <Button
                                    :disabled="!canBulkReject || isProcessingApplication"
                                    variant="destructive"
                                    size="sm"
                                    @click="bulkRejectApplications"
                                >
                                    <UserXIcon class="mr-2 h-4 w-4"/>
                                    <Spinner v-if="isProcessingApplication" class="mr-2 h-4 w-4"/>
                                    {{ t('Confirm Rejection') }}
                                </Button>
                                <Button variant="outline" size="sm"
                                        @click="showBulkRejectReason = false; rejectionReason = ''">
                                    {{ t('Cancel') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tab Navigation -->
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button
                        :class="[
                            'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                            activeTab === 'pending'
                                ? 'border-yellow-500 text-yellow-600 dark:border-yellow-400 dark:text-yellow-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        @click="activeTab = 'pending'"
                    >
                        {{ t('Pending Applications') }} ({{ pendingApplications.length }})
                    </button>
                    <button
                        :class="[
                            'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                            activeTab === 'confirmed'
                                ? 'border-green-500 text-green-600 dark:border-green-400 dark:text-green-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        @click="activeTab = 'confirmed'"
                    >
                        {{ t('Confirmed Players') }} ({{ confirmedPlayers.length }})
                    </button>
                    <button
                        :class="[
                            'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                            activeTab === 'rejected'
                                ? 'border-red-500 text-red-600 dark:border-red-400 dark:text-red-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        @click="activeTab = 'rejected'"
                    >
                        {{ t('Rejected Applications') }} ({{ rejectedApplications.length }})
                    </button>
                    <button
                        :class="[
                            'py-4 px-1 text-sm font-medium border-b-2 whitespace-nowrap',
                            activeTab === 'all'
                                ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                        @click="activeTab = 'all'"
                    >
                        {{ t('All') }} ({{ allPlayers.length }})
                    </button>
                </nav>
            </div>

            <!-- Content based on active tab -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UsersIcon class="h-5 w-5"/>
                        <template v-if="activeTab === 'pending'">
                            {{ t('Pending Applications') }}
                        </template>
                        <template v-else-if="activeTab === 'confirmed'">
                            {{ t('Confirmed Players') }}
                        </template>
                        <template v-else-if="activeTab === 'rejected'">
                            {{ t('Rejected Applications') }}
                        </template>
                        <template v-else>
                            {{ t('All Players & Applications') }}
                        </template>
                    </CardTitle>
                    <CardDescription>
                        <template v-if="activeTab === 'pending'">
                            {{ t('Applications waiting for approval') }}
                        </template>
                        <template v-else-if="activeTab === 'confirmed'">
                            {{ t('Players confirmed for the tournament') }}
                        </template>
                        <template v-else-if="activeTab === 'rejected'">
                            {{ t('Applications that were rejected') }}
                        </template>
                        <template v-else>
                            {{ t('Complete overview of all players and applications') }}
                        </template>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Pending Applications View -->
                    <div v-if="activeTab === 'pending'">
                        <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                            <Spinner class="text-primary h-6 w-6"/>
                        </div>
                        <div v-else-if="pendingApplications.length === 0" class="py-8 text-center text-gray-500">
                            {{ t('No pending applications.') }}
                        </div>
                        <div v-else class="space-y-4">
                            <div
                                v-for="application in pendingApplications"
                                :key="application.id"
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-gray-50 rounded-lg dark:bg-gray-800 gap-3"
                            >
                                <div class="flex items-start sm:items-center space-x-3 sm:space-x-4">
                                    <input
                                        :id="`player-${application.id}`"
                                        v-model="selectedApplicationIds"
                                        :value="application.id"
                                        class="text-primary focus:border-primary focus:ring-primary focus:ring-opacity-50 rounded border-gray-300 shadow-sm focus:ring mt-1 sm:mt-0"
                                        type="checkbox"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm sm:text-base truncate">
                                            {{ application.user?.firstname }} {{ application.user?.lastname }}
                                        </p>
                                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 truncate">
                                            {{ application.user?.email }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ t('Applied: :date', {date: formatDateTime(application.applied_at)}) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    <span
                                        :class="['px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(application.status)]">
                                        {{ application.status_display }}
                                    </span>
                                    <div class="flex gap-1">
                                        <Button
                                            :disabled="isProcessingApplication"
                                            class="bg-green-600 hover:bg-green-700"
                                            size="icon"
                                            @click="confirmApplication(application.id)"
                                        >
                                            <CheckIcon class="h-4 w-4"/>
                                        </Button>
                                        <Button
                                            :disabled="isProcessingApplication"
                                            size="icon"
                                            variant="destructive"
                                            @click="rejectApplication(application.id)"
                                        >
                                            <XIcon class="h-4 w-4"/>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmed Players / Other Views -->
                    <div v-else>
                        <DataTable
                            :columns="playersColumns"
                            :data="sortedPlayers"
                            :loading="isLoadingPlayers"
                            :empty-message="
                                activeTab === 'confirmed' ? t('No confirmed players yet.') :
                                activeTab === 'rejected' ? t('No rejected applications.') :
                                t('No players or applications yet.')
                            "
                            :mobile-card-mode="true"
                        >
                            <!-- Mobile primary info -->
                            <template #mobile-primary="{ item }">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium">
                                            <span v-if="item.position"
                                                  class="font-bold text-blue-600 dark:text-blue-400">#{{
                                                    item.position
                                                }}</span>
                                            <span v-else-if="item.seed_number"
                                                  class="text-gray-500 mr-2">#{{ item.seed_number }}</span>
                                            {{ item.user?.firstname }} {{ item.user?.lastname }}
                                        </p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                            {{ item.user?.email }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                :class="['px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(item.status)]">
                                                {{ item.status_display }}
                                            </span>
                                            <span v-if="item.applied_at || item.confirmed_at || item.rejected_at"
                                                  class="text-xs text-gray-500">
                                                {{
                                                    formatDateTime(item.applied_at || item.confirmed_at || item.rejected_at)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Position column -->
                            <template #cell-position="{ value }">
                                <span v-if="value !== '—'" class="font-bold text-blue-600 dark:text-blue-400">#{{
                                        value
                                    }}</span>
                                <span v-else class="text-gray-400">{{ value }}</span>
                            </template>

                            <!-- Player column -->
                            <template #cell-player="{ value }">
                                <div>
                                    <p class="font-medium">
                                        <span v-if="value.seed" class="text-gray-500 mr-2">#{{ value.seed }}</span>
                                        {{ value.name }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ value.email }}</p>
                                </div>
                            </template>

                            <!-- Status column -->
                            <template #cell-status="{ value }">
                                <span
                                    :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', getStatusBadgeClass(value.status)]"
                                >
                                    {{ value.statusDisplay }}
                                </span>
                            </template>

                            <!-- Rating points column -->
                            <template #cell-rating_points="{ value }">
                                <span v-if="value > 0" class="font-medium">+{{ value }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <!-- Prize column -->
                            <template #cell-prize="{ value }">
                                <span v-if="value > 0" class="font-medium text-green-600 dark:text-green-400">
                                    {{ formatCurrency(value) }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <!-- Bonus column -->
                            <template #cell-bonus="{ value }">
                                <span v-if="value > 0" class="font-medium text-orange-600 dark:text-orange-400">
                                    {{ formatCurrency(value) }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <!-- Total column -->
                            <template #cell-total="{ value }">
                                <span v-if="value > 0" class="font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ formatCurrency(value) }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>

                            <!-- Actions column -->
                            <template #cell-actions="{ value }">
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="handleRemovePlayer(value)"
                                >
                                    <TrashIcon class="h-4 w-4"/>
                                </Button>
                            </template>

                            <!-- Mobile actions -->
                            <template #mobile-actions="{ item }">
                                <div v-if="canAddPlayers" class="mt-3 pt-3 border-t dark:border-gray-700">
                                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                                        <div v-if="item.rating_points > 0">
                                            <span class="text-gray-500">{{ t('Rating') }}:</span>
                                            <span class="font-medium ml-1">+{{ item.rating_points }}</span>
                                        </div>
                                        <div v-if="item.prize_amount > 0">
                                            <span class="text-gray-500">{{ t('Prize') }}:</span>
                                            <span class="font-medium text-green-600 ml-1">{{
                                                    formatCurrency(item.prize_amount)
                                                }}</span>
                                        </div>
                                        <div v-if="item.bonus_amount > 0">
                                            <span class="text-gray-500">{{ t('Bonus') }}:</span>
                                            <span class="font-medium text-orange-600 ml-1">{{
                                                    formatCurrency(item.bonus_amount)
                                                }}</span>
                                        </div>
                                        <div v-if="item.total_amount > 0">
                                            <span class="text-gray-500">{{ t('Total') }}:</span>
                                            <span class="font-bold text-indigo-600 ml-1">{{
                                                    formatCurrency(item.total_amount)
                                                }}</span>
                                        </div>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="w-full"
                                        @click="handleRemovePlayer(item.id)"
                                    >
                                        <TrashIcon class="h-4 w-4 mr-2"/>
                                        {{ t('Remove Player') }}
                                    </Button>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </CardContent>
            </Card>

            <!-- Add Existing Player Modal -->
            <Modal :show="showAddExistingModal" :title="t('Add Existing Player')" @close="showAddExistingModal = false">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="search">{{ t('Search Players') }}</Label>
                        <div class="relative">
                            <SearchIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"/>
                            <Input
                                id="search"
                                v-model="searchQuery"
                                :placeholder="t('Search by name or email...')"
                                class="pl-10"
                            />
                        </div>
                    </div>

                    <div v-if="isSearching" class="flex justify-center py-4">
                        <Spinner class="h-6 w-6"/>
                    </div>

                    <div v-else-if="searchResults.length > 0" class="max-h-60 space-y-2 overflow-y-auto">
                        <div
                            v-for="user in searchResults"
                            :key="user.id"
                            class="flex items-center justify-between rounded-lg border p-3"
                        >
                            <div>
                                <p class="font-medium">{{ user.firstname }} {{ user.lastname }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ user.email }}</p>
                            </div>
                            <Button
                                v-if="!isPlayerAlreadyAdded(user.id)"
                                :disabled="isAddingPlayer"
                                size="sm"
                                @click="handleAddExistingPlayer(user.id)"
                            >
                                {{ t('Add') }}
                            </Button>
                            <span v-else class="text-sm text-gray-500">{{ t('Already added') }}</span>
                        </div>
                    </div>

                    <div v-else-if="searchQuery.length >= 2" class="py-4 text-center text-gray-500">
                        {{ t('No players found') }}
                    </div>

                    <div v-else class="py-4 text-center text-gray-500">
                        {{ t('Type at least 2 characters to search') }}
                    </div>
                </div>

                <template #footer>
                    <Button variant="outline" @click="showAddExistingModal = false">
                        {{ t('Cancel') }}
                    </Button>
                </template>
            </Modal>

            <!-- Add New Player Modal -->
            <Modal :show="showAddNewModal" :title="t('Add New Player')" @close="showAddNewModal = false">
                <form class="space-y-4" @submit.prevent="handleAddNewPlayer">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="firstname">{{ t('First Name *') }}</Label>
                            <Input
                                id="firstname"
                                v-model="newPlayerForm.firstname"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="lastname">{{ t('Last Name *') }}</Label>
                            <Input
                                id="lastname"
                                v-model="newPlayerForm.lastname"
                                required
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="email">{{ t('Email *') }}</Label>
                        <Input
                            id="email"
                            v-model="newPlayerForm.email"
                            required
                            type="email"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">{{ t('Phone *') }}</Label>
                        <Input
                            id="phone"
                            v-model="newPlayerForm.phone"
                            required
                            type="tel"
                        />
                    </div>
                </form>

                <template #footer>
                    <Button variant="outline" @click="showAddNewModal = false">
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        :disabled="!isNewPlayerFormValid || isAddingPlayer"
                        @click="handleAddNewPlayer"
                    >
                        <Spinner v-if="isAddingPlayer" class="mr-2 h-4 w-4"/>
                        {{ isAddingPlayer ? t('Adding...') : t('Add Player') }}
                    </Button>
                </template>
            </Modal>

            <!-- Error Display -->
            <div v-if="addExistingApi.error.value || addNewApi.error.value"
                 class="mt-4 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ t('Error:') }} {{ addExistingApi.error.value?.message || addNewApi.error.value?.message }}
            </div>
        </div>
    </div>
</template>
