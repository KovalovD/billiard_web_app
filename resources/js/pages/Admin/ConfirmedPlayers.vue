// resources/js/pages/Admin/ConfirmedPlayers.vue
<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import {onMounted, ref} from 'vue';
import {useAuth} from '@/composables/useAuth';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, League, Rating} from '@/types/api';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {ArrowLeftIcon, UserMinusIcon} from 'lucide-vue-next';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: number | string;
}>();

const {isAdmin} = useAuth();
const confirmedPlayers = ref<Rating[]>([]);
const selectedPlayers = ref<number[]>([]);
const league = ref<League | null>(null);
const isLoading = ref(true);
const isProcessing = ref(false);
const message = ref<{ type: 'success' | 'error', text: string } | null>(null);

// Fetch the league
const fetchLeague = async () => {
    try {
        league.value = await apiClient<League>(`/api/leagues/${props.leagueId}`);
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to load league'
        };
    }
};

// Fetch confirmed players
const fetchConfirmedPlayers = async () => {
    isLoading.value = true;
    try {
        confirmedPlayers.value = await apiClient<Rating[]>(`/api/leagues/${props.leagueId}/admin/confirmed-players`);
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to load confirmed players'
        };
    } finally {
        isLoading.value = false;
    }
};

// Deactivate individual player
const deactivatePlayer = async (ratingId: number) => {
    if (!confirm('Are you sure you want to deactivate this player? They will no longer be able to participate in this league.')) {
        return;
    }

    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/admin/deactivate-player/${ratingId}`, {
            method: 'post'
        });
        message.value = {
            type: 'success',
            text: 'Player deactivated successfully'
        };
        // Refresh the list
        await fetchConfirmedPlayers();
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to deactivate player'
        };
    } finally {
        isProcessing.value = false;
    }
};

// Bulk deactivate selected players
const bulkDeactivatePlayers = async () => {
    if (selectedPlayers.value.length === 0) {
        message.value = {
            type: 'error',
            text: 'Please select at least one player to deactivate'
        };
        return;
    }

    if (!confirm(`Are you sure you want to deactivate ${selectedPlayers.value.length} players? They will no longer be able to participate in this league.`)) {
        return;
    }

    isProcessing.value = true;
    try {
        await apiClient(`/api/leagues/${props.leagueId}/admin/bulk-deactivate`, {
            method: 'post',
            data: {
                rating_ids: selectedPlayers.value
            }
        });
        message.value = {
            type: 'success',
            text: `${selectedPlayers.value.length} players deactivated successfully`
        };
        selectedPlayers.value = [];
        // Refresh the list
        await fetchConfirmedPlayers();
    } catch (error) {
        const apiError = error as ApiError;
        message.value = {
            type: 'error',
            text: apiError.message || 'Failed to deactivate players'
        };
    } finally {
        isProcessing.value = false;
    }
};

// Check/uncheck all
const toggleSelectAll = (checked: boolean) => {
    if (checked) {
        selectedPlayers.value = confirmedPlayers.value.map(player => player.id);
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
    await fetchConfirmedPlayers();
});
</script>

<template>
    <Head :title="league ? `Manage Players - ${league.name}` : 'Manage Players'"/>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header with back button -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="`/leagues/${leagueId}`">
                    <Button variant="outline">
                        <ArrowLeftIcon class="w-4 h-4 mr-2"/>
                        Back to League
                    </Button>
                </Link>

                <h1 class="text-2xl font-semibold">
                    {{ league ? `Manage Players - ${league.name}` : 'Manage Players' }}
                </h1>

                <div class="flex space-x-2">
                    <Link :href="`/admin/leagues/${leagueId}/pending-players`">
                        <Button variant="outline">
                            Pending Players
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Status message -->
            <div v-if="message"
                 :class="`mb-6 p-4 rounded-md ${message.type === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'}`">
                {{ message.text }}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Confirmed Players</CardTitle>
                    <CardDescription>
                        Manage players who are currently active in the league
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Loading state -->
                    <div v-if="isLoading" class="flex justify-center py-8">
                        <Spinner class="w-8 h-8 text-primary"/>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="confirmedPlayers.length === 0"
                         class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No confirmed players in this league
                    </div>

                    <!-- Players list -->
                    <div v-else>
                        <!-- Bulk action controls -->
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <input
                                    id="select-all"
                                    :checked="selectedPlayers.length === confirmedPlayers.length"
                                    class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"
                                    type="checkbox"
                                    @change="e => toggleSelectAll(e.target.checked)"
                                />
                                <label class="text-sm font-medium" for="select-all">Select All</label>
                            </div>

                            <Button
                                :disabled="selectedPlayers.length === 0 || isProcessing"
                                variant="destructive"
                                @click="bulkDeactivatePlayers"
                            >
                                <Spinner v-if="isProcessing" class="w-4 h-4 mr-2"/>
                                <UserMinusIcon v-else class="w-4 h-4 mr-2"/>
                                Deactivate Selected ({{ selectedPlayers.length }})
                            </Button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3">&nbsp;</th>
                                    <th class="px-4 py-3">Position</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Rating</th>
                                    <th class="px-4 py-3">Stats (W/L)</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="player in confirmedPlayers" :key="player.id"
                                    class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <input
                                            :id="`player-${player.id}`"
                                            v-model="selectedPlayers"
                                            :value="player.id"
                                            class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"
                                            type="checkbox"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-medium">{{ player.position }}</td>
                                    <td class="px-4 py-3 font-medium">{{ player.player.name }}</td>
                                    <td class="px-4 py-3">{{ player.rating }}</td>
                                    <td class="px-4 py-3">
                                        {{ player.wins_count || 0 }}/{{ player.losses_count || 0 }}
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <Button
                                            :disabled="isProcessing"
                                            size="sm"
                                            variant="destructive"
                                            @click="deactivatePlayer(player.id)"
                                        >
                                            <UserMinusIcon class="w-4 h-4 mr-1"/>
                                            Deactivate
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
