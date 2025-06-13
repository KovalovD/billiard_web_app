<!-- resources/js/pages/Tournaments/Show.vue -->
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import TournamentApplicationCard from '@/Components/Tournament/TournamentApplicationCard.vue';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import {
    ArrowLeftIcon,
    BarChartIcon,
    BracketsIcon,
    CalendarIcon,
    ClipboardListIcon,
    GroupIcon,
    LogInIcon,
    MapPinIcon,
    PencilIcon,
    PlayIcon,
    SettingsIcon,
    StarIcon,
    TrophyIcon,
    UserCheckIcon,
    UserPlusIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import DataTable from '@/Components/ui/data-table/DataTable.vue';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const {isAdmin, isAuthenticated} = useAuth();
const {t} = useLocale();

const tournament = ref<Tournament | null>(null);
const players = ref<TournamentPlayer[]>([]);
const isLoadingTournament = ref(true);
const isLoadingPlayers = ref(true);
const error = ref<string | null>(null);
const activeTab = ref<'info' | 'players' | 'results' | 'applications'>('info');

const sortedPlayers = computed(() => {
    return [...players.value].sort((a, b) => {
      const statusOrder = {confirmed: 1, applied: 2, rejected: 3};
        const aStatus = statusOrder[a.status as keyof typeof statusOrder] || 4;
        const bStatus = statusOrder[b.status as keyof typeof statusOrder] || 4;

        if (aStatus !== bStatus) {
            return aStatus - bStatus;
        }

        if (a.position !== null && b.position !== null && a.position != undefined && b.position !== undefined) {
            return a.position - b.position;
        }

      return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
    });
});

// Computed properties for admin navigation
const showPlayerManagement = computed(() => {
  return isAdmin.value && tournament.value &&
      ['upcoming', 'active'].includes(tournament.value.status);
});

const showBracketEditor = computed(() => {
  return isAdmin.value && tournament.value &&
      ['upcoming', 'active'].includes(tournament.value.status) &&
      tournament.value.confirmed_players_count >= 4;
});

const showGroupManagement = computed(() => {
  return isAdmin.value && tournament.value &&
      ['upcoming', 'active'].includes(tournament.value.status) &&
      tournament.value.format?.includes('group');
});

const showScheduleManagement = computed(() => {
  return isAdmin.value && tournament.value &&
      ['upcoming', 'active'].includes(tournament.value.status);
});

const showResultsManagement = computed(() => {
  return isAdmin.value && tournament.value &&
      ['active', 'completed'].includes(tournament.value.status);
});

const canStartTournament = computed(() => {
  return isAdmin.value && tournament.value &&
      tournament.value.status === 'upcoming' &&
      tournament.value.confirmed_players_count >= 2;
});

// Define table columns for players
const columns = computed(() => [
  {
    key: 'position',
    label: t('Position'),
    align: 'center' as const,
    render: (player: TournamentPlayer) => player.position || '—'
  },
  {
    key: 'player',
    label: t('Player'),
    render: (player: TournamentPlayer) => ({
      name: player.user ? `${player.user.firstname} ${player.user.lastname}` : t('Unknown Player'),
      rating: player.user?.current_rating,
      hasRating: !!player.user?.current_rating
    })
  },
  {
    key: 'status',
    label: t('Status'),
    align: 'center' as const,
    render: (player: TournamentPlayer) => ({
      status: player.status,
      display: player.status_display,
      confirmed: player.is_confirmed,
      pending: player.is_pending,
      rejected: player.is_rejected
    })
  },
  {
    key: 'registration',
    label: t('Registration'),
    hideOnMobile: true,
    render: (player: TournamentPlayer) => player.registered_at ?
        new Date(player.registered_at).toLocaleDateString() : '—'
    }
]);

const fetchTournament = async () => {
    isLoadingTournament.value = true;
    error.value = null;

    try {
        tournament.value = await apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`);
    } catch (err: any) {
      error.value = err.message || t('Failed to load tournament');
      console.error('Error fetching tournament:', err);
    } finally {
        isLoadingTournament.value = false;
    }
};

const fetchPlayers = async () => {
    isLoadingPlayers.value = true;

    try {
        players.value = await apiClient<TournamentPlayer[]>(`/api/tournaments/${props.tournamentId}/players`);
    } catch (err: any) {
      console.error('Error fetching players:', err);
    } finally {
        isLoadingPlayers.value = false;
    }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('uk-UA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatPrizePool = (amount: number): string => {
  if (amount <= 0) return t('N/A');
  return amount.toLocaleString('uk-UA', {
    style: 'currency',
    currency: 'UAH'
  }).replace('UAH', '₴');
};

onMounted(() => {
    fetchTournament();
    fetchPlayers();
});
</script>

<template>
  <Head :title="tournament ? t('Tournament: :name', { name: tournament.name }) : t('Tournament')"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Link href="/tournaments">
                    <Button variant="outline">
                      <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Tournaments') }}
                    </Button>
                </Link>

              <!-- Admin Quick Actions -->
              <div v-if="isAuthenticated && isAdmin && tournament" class="flex flex-wrap gap-2">
                    <Link :href="`/admin/tournaments/${tournament.id}/edit`">
                      <Button size="sm" variant="outline">
                        <PencilIcon class="mr-2 h-4 w-4"/>
                        {{ t('Edit') }}
                        </Button>
                    </Link>

                <Link v-if="showPlayerManagement" :href="`/tournaments/${tournament.id}/players`">
                  <Button size="sm" variant="outline">
                    <UserPlusIcon class="mr-2 h-4 w-4"/>
                    {{ t('Players') }}
                        </Button>
                    </Link>

                <Link v-if="tournament.requires_application && tournament.pending_applications_count > 0"
                          :href="`/admin/tournaments/${tournament.id}/applications`">
                  <Button class="relative" size="sm" variant="outline">
                    <ClipboardListIcon class="mr-2 h-4 w-4"/>
                            {{ t('Applications') }}
                    <span
                        class="ml-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ tournament.pending_applications_count }}
                            </span>
                        </Button>
                    </Link>
                </div>

                <!-- Login prompt for guests -->
                <div v-else-if="!isAuthenticated && tournament" class="text-center">
                    <Link :href="route('login')" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                      <LogInIcon class="mr-1 inline h-4 w-4"/>
                        {{ t('Login to participate') }}
                    </Link>
                </div>
            </div>

          <!-- Admin Management Panel -->
          <div v-if="isAdmin && tournament && ['upcoming', 'active'].includes(tournament.status)" class="mb-8">
            <Card class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20">
              <CardHeader>
                <CardTitle class="flex items-center gap-2 text-blue-800 dark:text-blue-200">
                  <SettingsIcon class="h-5 w-5"/>
                  {{ t('Tournament Management') }}
                </CardTitle>
                <CardDescription class="text-blue-700 dark:text-blue-300">
                  {{ t('Manage all aspects of your tournament') }}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                  <!-- Player Management -->
                  <Link v-if="showPlayerManagement" :href="`/tournaments/${tournament.id}/players`">
                    <Button class="w-full h-20 flex-col" variant="outline">
                      <UsersIcon class="h-6 w-6 mb-2"/>
                      <span class="text-xs">{{ t('Players') }}</span>
                      <span class="text-xs text-gray-500">{{ tournament.confirmed_players_count }}</span>
                    </Button>
                  </Link>

                  <!-- Bracket Editor -->
                  <Link v-if="showBracketEditor" :href="`/tournaments/${tournament.id}/bracket`">
                    <Button class="w-full h-20 flex-col" variant="outline">
                      <BracketsIcon class="h-6 w-6 mb-2"/>
                      <span class="text-xs">{{ t('Bracket') }}</span>
                    </Button>
                  </Link>

                  <!-- Group Management -->
                  <Link v-if="showGroupManagement" :href="`/tournaments/${tournament.id}/groups`">
                    <Button class="w-full h-20 flex-col" variant="outline">
                      <GroupIcon class="h-6 w-6 mb-2"/>
                      <span class="text-xs">{{ t('Groups') }}</span>
                    </Button>
                  </Link>

                  <!-- Schedule Management -->
                  <Link v-if="showScheduleManagement" :href="`/tournaments/${tournament.id}/schedule`">
                    <Button class="w-full h-20 flex-col" variant="outline">
                      <CalendarIcon class="h-6 w-6 mb-2"/>
                      <span class="text-xs">{{ t('Schedule') }}</span>
                    </Button>
                  </Link>

                  <!-- Results Management -->
                  <Link v-if="showResultsManagement" :href="`/tournaments/${tournament.id}/results`">
                    <Button class="w-full h-20 flex-col" variant="outline">
                      <TrophyIcon class="h-6 w-6 mb-2"/>
                      <span class="text-xs">{{ t('Results') }}</span>
                    </Button>
                  </Link>

                  <!-- Statistics -->
                  <Link v-if="tournament.status === 'completed'" :href="`/tournaments/${tournament.id}/results`">
                    <Button class="w-full h-20 flex-col" variant="outline">
                      <BarChartIcon class="h-6 w-6 mb-2"/>
                      <span class="text-xs">{{ t('Stats') }}</span>
                    </Button>
                  </Link>
                </div>

                <!-- Start Tournament Button -->
                <div v-if="canStartTournament" class="mt-4 text-center">
                  <Button class="bg-green-600 hover:bg-green-700" size="lg">
                    <PlayIcon class="mr-2 h-5 w-5"/>
                    {{ t('Start Tournament') }}
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>

            <!-- Loading State -->
            <div v-if="isLoadingTournament" class="p-10 text-center">
              <Spinner class="text-primary mx-auto h-8 w-8"/>
                <p class="mt-2 text-gray-500">{{ t('Loading tournament...') }}</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="mb-6 rounded bg-red-100 p-4 text-red-500">
                {{ t('Error loading tournament: :error', { error }) }}
            </div>

            <!-- Tournament Content -->
          <div v-else-if="tournament">
                <!-- Tournament Header -->
            <div class="mb-8">
              <Card>
                <CardHeader>
                  <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                      <CardTitle class="text-3xl font-bold">{{ tournament.name }}</CardTitle>
                      <CardDescription class="mt-2 text-lg">
                        <div class="flex flex-wrap items-center gap-4">
                                            <span v-if="tournament.game" class="flex items-center">
                                                <TrophyIcon class="mr-1 h-4 w-4"/>
                                                {{ tournament.game.name }}
                                            </span>
                          <span v-if="tournament.city" class="flex items-center">
                                                <MapPinIcon class="mr-1 h-4 w-4"/>
                                                {{ tournament.city.name }}, {{ tournament.city.country.name }}
                                            </span>
                          <span class="flex items-center">
                                                <CalendarIcon class="mr-1 h-4 w-4"/>
                                                {{ formatDate(tournament.start_date) }}
                                            </span>
                        </div>
                      </CardDescription>
                    </div>

                    <div class="mt-4 md:mt-0">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                            tournament.status === 'upcoming' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' :
                                            tournament.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
                                            tournament.status === 'completed' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' :
                                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                        ]"
                                    >
                                        {{ tournament.status_display }}
                                    </span>
                    </div>
                  </div>
                </CardHeader>
                    </Card>
                </div>

                <!-- Tab Navigation -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'info'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'info'"
                        >
                            {{ t('Information') }}
                        </button>
                        <button
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'players'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'players'"
                        >
                            {{ t('Players') }} ({{ tournament.confirmed_players_count }})
                        </button>
                        <button
                            v-if="tournament.requires_application && (isAuthenticated && isAdmin || tournament.pending_applications_count > 0)"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'applications'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'applications'"
                        >
                            {{ t('Applications') }} ({{ tournament.pending_applications_count }})
                        </button>
                        <button
                            v-if="tournament.is_completed"
                            :class="[
                                'py-4 px-1 text-sm font-medium border-b-2',
                                activeTab === 'results'
                                    ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            @click="activeTab = 'results'"
                        >
                            {{ t('Results') }}
                        </button>
                    </nav>
                </div>

            <!-- Tab Content -->
            <div>
              <!-- Information Tab -->
              <div v-if="activeTab === 'info'">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                  <!-- Main Info -->
                  <div class="lg:col-span-2 space-y-6">
                    <Card>
                      <CardHeader>
                        <CardTitle>{{ t('Tournament Details') }}</CardTitle>
                      </CardHeader>
                      <CardContent class="space-y-4">
                        <div v-if="tournament.details" class="prose dark:prose-invert max-w-none">
                          <p>{{ tournament.details }}</p>
                        </div>

                        <div v-if="tournament.regulation" class="prose dark:prose-invert max-w-none">
                          <h4>{{ t('Regulations') }}</h4>
                          <p>{{ tournament.regulation }}</p>
                        </div>

                        <div v-if="tournament.organizer" class="flex items-center">
                          <UserCheckIcon class="mr-2 h-4 w-4 text-gray-500"/>
                          <span class="text-sm">
                                                {{ t('Organized by') }}: <strong>{{ tournament.organizer }}</strong>
                                            </span>
                        </div>
                      </CardContent>
                    </Card>

                    <!-- Application Card for Non-Admins -->
                    <TournamentApplicationCard
                        v-if="!isAdmin"
                        :tournament="tournament"
                    />
                  </div>

                  <!-- Sidebar Info -->
                  <div class="space-y-6">
                    <!-- Quick Stats -->
                    <Card>
                      <CardHeader>
                        <CardTitle>{{ t('Quick Stats') }}</CardTitle>
                      </CardHeader>
                      <CardContent class="space-y-4">
                        <div class="flex justify-between">
                          <span>{{ t('Players') }}:</span>
                          <span class="font-semibold">
                                                {{ tournament.confirmed_players_count }}
                                                <span v-if="tournament.max_participants" class="text-gray-500">
                                                    / {{ tournament.max_participants }}
                                                </span>
                                            </span>
                                        </div>

                        <div v-if="tournament.entry_fee > 0" class="flex justify-between">
                          <span>{{ t('Entry Fee') }}:</span>
                          <span class="font-semibold">{{ formatPrizePool(tournament.entry_fee) }}</span>
                                        </div>

                        <div v-if="tournament.prize_pool > 0" class="flex justify-between">
                          <span>{{ t('Prize Pool') }}:</span>
                          <span class="font-semibold text-green-600">{{ formatPrizePool(tournament.prize_pool) }}</span>
                        </div>

                        <div v-if="tournament.format" class="flex justify-between">
                          <span>{{ t('Format') }}:</span>
                          <span class="font-semibold">{{ tournament.format }}</span>
                                        </div>

                        <div class="flex justify-between">
                          <span>{{ t('End Date') }}:</span>
                          <span class="font-semibold">{{ formatDate(tournament.end_date) }}</span>
                        </div>
                      </CardContent>
                    </Card>

                    <!-- Location -->
                    <Card v-if="tournament.city || tournament.club">
                      <CardHeader>
                        <CardTitle>{{ t('Location') }}</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <div class="space-y-2">
                          <div v-if="tournament.city" class="flex items-center">
                            <MapPinIcon class="mr-2 h-4 w-4 text-gray-500"/>
                            <span>{{ tournament.city.name }}, {{ tournament.city.country.name }}</span>
                          </div>
                          <div v-if="tournament.club" class="flex items-center">
                            <StarIcon class="mr-2 h-4 w-4 text-gray-500"/>
                            <span>{{ tournament.club.name }}</span>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </div>
                </div>
              </div>

              <!-- Players Tab -->
              <div v-if="activeTab === 'players'">
                <Card>
                  <CardHeader>
                    <CardTitle>
                      {{ t('Registered Players') }} ({{ tournament.confirmed_players_count }})
                    </CardTitle>
                  </CardHeader>
                  <CardContent class="p-0">
                    <DataTable
                        :columns="columns"
                        :data="sortedPlayers"
                        :empty-message="t('No players registered yet.')"
                        :loading="isLoadingPlayers"
                                >
                      <!-- Player Cell -->
                      <template #cell-player="{ value }">
                        <div class="flex items-center space-x-3">
                          <div>
                            <div class="font-medium">{{ value.name }}</div>
                            <div v-if="value.hasRating" class="text-sm text-gray-500">
                              {{ t('Rating') }}: {{ value.rating }}
                            </div>
                          </div>
                        </div>
                      </template>

                      <!-- Status Cell -->
                      <template #cell-status="{ value }">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                value.confirmed ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
                                                value.pending ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                                value.rejected ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' :
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                            ]"
                                        >
                                            {{ value.display }}
                                        </span>
                      </template>
                    </DataTable>
                  </CardContent>
                </Card>
              </div>

              <!-- Applications Tab -->
              <div v-if="activeTab === 'applications'">
                <Card>
                            <CardHeader>
                              <CardTitle>{{ t('Tournament Applications') }}</CardTitle>
                              <CardDescription>
                                {{ t('Pending applications: :count', {count: tournament.pending_applications_count}) }}
                              </CardDescription>
                            </CardHeader>
                            <CardContent>
                              <div class="text-center py-8 text-gray-500">
                                <ClipboardListIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                                <p>{{ t('Applications management available in admin panel') }}</p>
                                <Link v-if="isAdmin" :href="`/admin/tournaments/${tournament.id}/applications`"
                                      class="mt-4 inline-block">
                                  <Button>{{ t('Manage Applications') }}</Button>
                                </Link>
                              </div>
                            </CardContent>
                        </Card>
                    </div>

              <!-- Results Tab -->
              <div v-if="activeTab === 'results'">
                <Card>
                  <CardHeader>
                    <CardTitle>{{ t('Tournament Results') }}</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div class="text-center py-8 text-gray-500">
                      <TrophyIcon class="mx-auto h-12 w-12 mb-4 opacity-50"/>
                      <p>{{ t('Tournament results will be displayed here once available') }}</p>
                      <Link v-if="isAdmin" :href="`/tournaments/${tournament.id}/results`" class="mt-4 inline-block">
                        <Button>{{ t('Manage Results') }}</Button>
                      </Link>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          </div>
        </div>
    </div>
</template>
