<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, League, Rating} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {ArrowLeftIcon, CheckIcon, XIcon} from 'lucide-vue-next';
import {onMounted, ref} from 'vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: number | string;
}>();

const {isAdmin} = useAuth();
const pendingPlayers = ref<Rating[]>([]);
const selectedPlayers = ref<number[]>([]);
const league = ref<League | null>(null);
const isLoading = ref(true);
const isProcessing = ref(false);
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null);

// Fetch the league
const fetchLeague = async () => {
    try {
        league.value = await apiClient<League>(`/api/leagues/${props.leagueId}`);
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to load league',
        };
    }
};

// Fetch pending players
const fetchPendingPlayers = async () => {
    isLoading.value = true;
    try {
        pendingPlayers.value = await apiClient<Rating[]>(`/api/leagues/${props.leagueId}/admin/pending-players`);
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to load pending players',
        };
    } finally {
        isLoading.value = false;
    }
};

// Confirm individual player
const confirmPlayer = async (ratingId: number) => {
    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/admin/confirm-player/${ratingId}`, {
            method: 'post',
        });
        message.value = {
            type: 'success',
            text: 'Player confirmed successfully',
        };
        // Refresh the list
        await fetchPendingPlayers();
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to confirm player',
        };
    } finally {
        isProcessing.value = false;
    }
};

// Reject individual player
const rejectPlayer = async (ratingId: number) => {
    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/admin/reject-player/${ratingId}`, {
            method: 'post',
        });
        message.value = {
            type: 'success',
            text: 'Player rejected successfully',
        };
        // Refresh the list
        await fetchPendingPlayers();
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to reject player',
        };
    } finally {
        isProcessing.value = false;
    }
};

// Bulk confirm selected players
const bulkConfirmPlayers = async () => {
    if (selectedPlayers.value.length === 0) {
        message.value = {
            type: 'error',
            text: 'Please select at least one player to confirm',
        };
        return;
    }

    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/admin/bulk-confirm`, {
            method: 'post',
            data: {
                rating_ids: selectedPlayers.value,
            },
        });
        message.value = {
            type: 'success',
            text: `${selectedPlayers.value.length} players confirmed successfully`,
        };
        selectedPlayers.value = [];
        // Refresh the list
        await fetchPendingPlayers();
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to confirm players',
        };
    } finally {
        isProcessing.value = false;
    }
};

// Check/uncheck all
const toggleSelectAll = (checked: boolean) => {
    if (checked) {
        selectedPlayers.value = pendingPlayers.value.map((player) => player.id);
    } else {
        selectedPlayers.value = [];
    }
};

// Check if admin and redirect if not
onMounted(async () => {
    if (!isAdmin.value) {
        window.location.href = '/leagues';
        return;
    }

    await fetchLeague();
    await fetchPendingPlayers();
});
</script>

<template>
    <Head :title="league ? `Confirm Players - ${league.name}` : 'Confirm Players'"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header with back button -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="`/leagues/${leagueId}`">
                    <Button variant="outline">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        Back to League
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold">
                    {{ league ? `Pending Players - ${league.name}` : 'Pending Players' }}
                </h1>

                <div class="flex space-x-2">
                    <Link :href="`/admin/leagues/${leagueId}/confirmed-players`">
                        <Button variant="outline"> Confirmed Players</Button>
                    </Link>
                </div>
            </div>

            <!-- Status message -->
            <div
                v-if="message"
                :class="`mb-6 rounded-md p-4 ${message.type === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'}`"
            >
                {{ message.text }}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Players Waiting for Confirmation</CardTitle>
                    <CardDescription> Players will only be able to participate in matches after confirmation
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Loading state -->
                    <div v-if="isLoading" class="flex justify-center py-8">
                        <Spinner class="text-primary h-8 w-8"/>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="pendingPlayers.length === 0"
                         class="py-8 text-center text-gray-500 dark:text-gray-400">
                        No pending players to confirm
                    </div>

                    <!-- Players list -->
                    <div v-else>
                        <!-- Bulk action controls -->
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <input
                                    id="select-all"
                                    :checked="selectedPlayers.length === pendingPlayers.length"
                                    class="text-primary focus:border-primary focus:ring-primary focus:ring-opacity-50 rounded border-gray-300 shadow-sm focus:ring"
                                    type="checkbox"
                                    @change="(e) => toggleSelectAll(e.target.checked)"
                                />
                                <label class="text-sm font-medium" for="select-all">Select All</label>
                            </div>

                            <Button :disabled="selectedPlayers.length === 0 || isProcessing"
                                    @click="bulkConfirmPlayers">
                                <Spinner v-if="isProcessing" class="mr-2 h-4 w-4"/>
                                <CheckIcon v-else class="mr-2 h-4 w-4"/>
                                Confirm Selected ({{ selectedPlayers.length }})
                            </Button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 text-xs uppercase dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3">&nbsp;</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Rating</th>
                                    <th class="px-4 py-3">Joined On</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    v-for="player in pendingPlayers"
                                    :key="player.id"
                                    class="border-b hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            :id="`player-${player.id}`"
                                            v-model="selectedPlayers"
                                            :value="player.id"
                                            class="text-primary focus:border-primary focus:ring-primary focus:ring-opacity-50 rounded border-gray-300 shadow-sm focus:ring"
                                            type="checkbox"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-medium">{{ player.player.name }}</td>
                                    <td class="px-4 py-3">{{ player.rating }}</td>
                                    <td class="px-4 py-3">{{ new Date(player.created_at).toLocaleDateString() }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <Button
                                            :disabled="isProcessing"
                                            class="text-green-600 hover:bg-green-100 hover:text-green-800 dark:text-green-400 dark:hover:bg-green-900/20"
                                            size="sm"
                                            variant="ghost"
                                            @click="confirmPlayer(player.id)"
                                        >
                                            <CheckIcon class="mr-1 h-4 w-4"/>
                                            Confirm
                                        </Button>
                                        <Button
                                            :disabled="isProcessing"
                                            class="ml-2 text-red-600 hover:bg-red-100 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-900/20"
                                            size="sm"
                                            variant="ghost"
                                            @click="rejectPlayer(player.id)"
                                        >
                                            <XIcon class="mr-1 h-4 w-4"/>
                                            Reject
                                        </Button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
