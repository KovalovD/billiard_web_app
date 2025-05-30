<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {ArrowLeftIcon, CheckIcon, RefreshCwIcon, UserCheckIcon, UserXIcon, XIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

// Data
const tournament = ref<Tournament | null>(null);
const allApplications = ref<TournamentPlayer[]>([]);
const selectedApplicationIds = ref<number[]>([]);
const rejectionReason = ref('');
const showBulkRejectReason = ref(false);

// Loading states
const isLoadingTournament = ref(true);
const isLoadingApplications = ref(true);
const isProcessingApplication = ref(false);

// Error handling
const error = ref<string | null>(null);

// Computed
const pendingApplications = computed(() =>
    allApplications.value.filter(app => app.is_pending || app.is_rejected)
);

const confirmedApplications = computed(() =>
    allApplications.value.filter(app => app.is_confirmed)
);

const rejectedApplications = computed(() =>
    allApplications.value.filter(app => app.is_rejected)
);

const selectedPendingApplications = computed(() =>
    selectedApplicationIds.value.filter(id =>
        pendingApplications.value.some(app => app.id === id)
    )
);

const canBulkConfirm = computed(() => selectedPendingApplications.value.length > 0);
const canBulkReject = computed(() => selectedPendingApplications.value.length > 0);

// Methods
const fetchTournament = async () => {
    isLoadingTournament.value = true;
    try {
        tournament.value = await apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`);
    } catch (err: any) {
        error.value = err.message || 'Failed to load tournament';
    } finally {
        isLoadingTournament.value = false;
    }
};

const fetchApplications = async () => {
    isLoadingApplications.value = true;
    try {
        allApplications.value = await apiClient<TournamentPlayer[]>(
            `/api/admin/tournaments/${props.tournamentId}/applications/all`
        );
    } catch (err: any) {
        error.value = err.message || 'Failed to load applications';
    } finally {
        isLoadingApplications.value = false;
    }
};

const refreshData = async () => {
    await Promise.all([fetchTournament(), fetchApplications()]);
};

const confirmApplication = async (applicationId: number) => {
    isProcessingApplication.value = true;
    try {
        await apiClient(
            `/api/admin/tournaments/${props.tournamentId}/applications/${applicationId}/confirm`,
            {method: 'POST'}
        );
        await fetchApplications();
    } catch (err: any) {
        error.value = err.message || 'Failed to confirm application';
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
        await fetchApplications();
    } catch (err: any) {
        error.value = err.message || 'Failed to reject application';
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
        await fetchApplications();
    } catch (err: any) {
        error.value = err.message || 'Failed to bulk confirm applications';
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
        await fetchApplications();
    } catch (err: any) {
        error.value = err.message || 'Failed to bulk reject applications';
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

const getStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'applied':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'confirmed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
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

onMounted(() => {
    refreshData();
});
</script>

<template>
    <Head :title="tournament ? `Applications: ${tournament.name}` : 'Tournament Applications'"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Tournament Applications</h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ tournament ? tournament.name : 'Loading...' }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Button :disabled="isLoadingApplications" variant="outline" @click="refreshData">
                        <RefreshCwIcon :class="{ 'animate-spin': isLoadingApplications }" class="mr-2 h-4 w-4"/>
                        Refresh
                    </Button>
                    <Button variant="outline" @click="router.visit(`/tournaments/${props.tournamentId}`)">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to Tournament
                    </Button>
                </div>
            </div>

            <!-- Error Display -->
            <div v-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Tournament Info -->
            <Card v-if="tournament" class="mb-6">
                <CardContent class="pt-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ tournament.pending_applications_count }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Pending</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ tournament.confirmed_players_count }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Confirmed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ tournament.players_count }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Applications</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ tournament.max_participants || '∞' }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Max Participants</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Bulk Actions -->
            <Card v-if="pendingApplications.length > 0" class="mb-6">
                <CardHeader>
                    <CardTitle>Bulk Actions</CardTitle>
                    <CardDescription>
                        {{ selectedPendingApplications.length }} of {{ pendingApplications.length }} pending
                        applications selected
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="flex space-x-4">
                            <Button variant="outline" @click="selectAllPending">
                                Select All Pending
                            </Button>
                            <Button :disabled="selectedApplicationIds.length === 0" variant="outline"
                                    @click="clearSelection">
                                Clear Selection
                            </Button>
                        </div>

                        <div class="flex space-x-4">
                            <Button
                                :disabled="!canBulkConfirm || isProcessingApplication"
                                class="bg-green-600 hover:bg-green-700"
                                @click="bulkConfirmApplications"
                            >
                                <UserCheckIcon class="mr-2 h-4 w-4"/>
                                <Spinner v-if="isProcessingApplication" class="mr-2 h-4 w-4"/>
                                Confirm Selected ({{ selectedPendingApplications.length }})
                            </Button>
                            <Button
                                v-if="!showBulkRejectReason"
                                :disabled="!canBulkReject"
                                variant="destructive"
                                @click="showBulkRejectReason = true"
                            >
                                <UserXIcon class="mr-2 h-4 w-4"/>
                                Reject Selected ({{ selectedPendingApplications.length }})
                            </Button>
                        </div>

                        <!-- Bulk Reject Reason -->
                        <div v-if="showBulkRejectReason" class="space-y-3 border-t pt-4">
                            <div class="flex space-x-3">
                                <Button
                                    :disabled="!canBulkReject || isProcessingApplication"
                                    variant="destructive"
                                    @click="bulkRejectApplications"
                                >
                                    <UserXIcon class="mr-2 h-4 w-4"/>
                                    <Spinner v-if="isProcessingApplication" class="mr-2 h-4 w-4"/>
                                    Confirm Rejection
                                </Button>
                                <Button variant="outline" @click="showBulkRejectReason = false; rejectionReason = ''">
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pending Applications -->
            <Card v-if="pendingApplications.length > 0" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserCheckIcon class="h-5 w-5 text-yellow-600"/>
                        Pending Applications ({{ pendingApplications.length }})
                    </CardTitle>
                    <CardDescription>Applications waiting for approval</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="application in pendingApplications"
                            :key="application.id"
                            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg dark:bg-gray-800"
                        >
                            <div class="flex items-center space-x-4">
                                <input
                                    :id="`player-${application.id}`"
                                    v-model="selectedApplicationIds"
                                    :value="application.id"
                                    class="text-primary focus:border-primary focus:ring-primary focus:ring-opacity-50 rounded border-gray-300 shadow-sm focus:ring"
                                    type="checkbox"
                                />
                                <div>
                                    <p class="font-medium">
                                        {{ application.user?.firstname }} {{ application.user?.lastname }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ application.user?.email }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Applied: {{ formatDateTime(application.applied_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span
                                    :class="['px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(application.status)]">
                                    {{ application.status_display }}
                                </span>
                                <Button
                                    :disabled="isProcessingApplication"
                                    class="bg-green-600 hover:bg-green-700"
                                    size="sm"
                                    @click="confirmApplication(application.id)"
                                >
                                    <CheckIcon class="h-4 w-4"/>
                                </Button>
                                <Button
                                    :disabled="isProcessingApplication"
                                    size="sm"
                                    variant="destructive"
                                    @click="rejectApplication(application.id)"
                                >
                                    <XIcon class="h-4 w-4"/>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Confirmed Applications -->
            <Card v-if="confirmedApplications.length > 0" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserCheckIcon class="h-5 w-5 text-green-600"/>
                        Confirmed Players ({{ confirmedApplications.length }})
                    </CardTitle>
                    <CardDescription>Players confirmed for the tournament</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="application in confirmedApplications"
                            :key="application.id"
                            class="flex items-center justify-between p-3 bg-green-50 rounded-lg dark:bg-green-900/20"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ application.user?.firstname }} {{ application.user?.lastname }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Confirmed: {{ formatDateTime(application.confirmed_at) }}
                                </p>
                            </div>
                            <span
                                :class="['px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(application.status)]">
                                {{ application.status_display }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Rejected Applications -->
            <Card v-if="rejectedApplications.length > 0" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserXIcon class="h-5 w-5 text-red-600"/>
                        Rejected Applications ({{ rejectedApplications.length }})
                    </CardTitle>
                    <CardDescription>Applications that were rejected</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="application in rejectedApplications"
                            :key="application.id"
                            class="flex items-center justify-between p-3 bg-red-50 rounded-lg dark:bg-red-900/20"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ application.user?.firstname }} {{ application.user?.lastname }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Rejected: {{ formatDateTime(application.rejected_at) }}
                                </p>
                            </div>
                            <span
                                :class="['px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(application.status)]">
                                {{ application.status_display }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Loading State -->
            <div v-if="isLoadingApplications" class="flex items-center justify-center py-10">
                <Spinner class="text-primary h-8 w-8"/>
                <span class="ml-2 text-gray-500 dark:text-gray-400">Loading applications...</span>
            </div>

            <!-- Empty State -->
            <Card v-if="!isLoadingApplications && allApplications.length === 0">
                <CardContent class="py-10 text-center">
                    <UserCheckIcon class="mx-auto h-12 w-12 text-gray-400"/>
                    <p class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No Applications Yet</p>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        No one has applied to this tournament yet.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
