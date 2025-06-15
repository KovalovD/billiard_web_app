<template>
    <div class="space-y-4">
        <!-- Search and Filters -->
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <input
                    v-model="searchQuery"
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
                    placeholder="Search participants..."
                    type="text"
                />
                <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"/>
            </div>

            <div class="flex gap-2">
                <select
                    v-model="sortBy"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                >
                    <option value="seed">Sort by Seed</option>
                    <option value="name">Sort by Name</option>
                    <option value="rating">Sort by Rating</option>
                    <option value="club">Sort by Club</option>
                </select>

                <button
                    :title="`Switch to ${viewMode === 'grid' ? 'list' : 'grid'} view`"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    @click="toggleView"
                >
                    <GridIcon v-if="viewMode === 'list'" class="w-5 h-5"/>
                    <ListIcon v-else class="w-5 h-5"/>
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalParticipants }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Participants</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ uniqueClubs }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Clubs Represented</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ averageRating }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Average Rating</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ byeCount }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">BYEs</div>
            </div>
        </div>

        <!-- Participants List/Grid -->
        <div v-if="viewMode === 'list'" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                        scope="col">
                        Seed
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                        scope="col">
                        Participant
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                        scope="col">
                        Club / Team
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                        scope="col">
                        Rating
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                        scope="col">
                        Status
                    </th>
                    <th v-if="editable" class="relative px-6 py-3" scope="col">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                    v-for="participant in filteredParticipants"
                    :key="participant.id"
                    :class="{
              'opacity-50': participant.is_bye,
              'hover:bg-gray-50 dark:hover:bg-gray-700': !participant.is_bye
            }"
                >
                    <!-- Seed -->
                    <td class="px-6 py-4 whitespace-nowrap">
              <span
                  :class="getSeedClass(participant.seed)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold"
              >
                {{ participant.seed }}
              </span>
                    </td>

                    <!-- Participant -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div
                                    v-if="!participant.is_bye"
                                    class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
                                >
                                    <UserIcon class="h-6 w-6 text-gray-500 dark:text-gray-400"/>
                                </div>
                                <div
                                    v-else
                                    class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"
                                >
                                    <span class="text-xs font-medium text-gray-500">BYE</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ participant.display_name }}
                                </div>
                                <div v-if="participant.user?.email" class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ participant.user.email }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Club/Team -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-gray-100">
                <span v-if="participant.user?.home_club">
                  {{ participant.user.home_club.name }}
                </span>
                            <span v-else-if="participant.team">
                  {{ participant.team.name }}
                  <span class="text-gray-500 dark:text-gray-400">(Team)</span>
                </span>
                            <span v-else class="text-gray-400">—</span>
                        </div>
                        <div v-if="getCity(participant)" class="text-sm text-gray-500 dark:text-gray-400">
                            {{ getCity(participant) }}
                        </div>
                    </td>

                    <!-- Rating -->
                    <td class="px-6 py-4 whitespace-nowrap text-center">
              <span v-if="participant.rating_snapshot" class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ participant.rating_snapshot }}
              </span>
                        <span v-else class="text-sm text-gray-400">—</span>
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap text-center">
              <span
                  :class="getStatusClass(participant)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ getStatusText(participant) }}
              </span>
                    </td>

                    <!-- Actions -->
                    <td v-if="editable" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button
                            v-if="!participant.is_bye"
                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                            @click="$emit('remove', participant.id)"
                        >
                            <TrashIcon class="w-5 h-5"/>
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Grid View -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
                v-for="participant in filteredParticipants"
                :key="participant.id"
                :class="{ 'opacity-50': participant.is_bye }"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow p-4"
            >
                <div class="flex items-start justify-between mb-3">
          <span
              :class="getSeedClass(participant.seed)"
              class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold"
          >
            {{ participant.seed }}
          </span>
                    <button
                        v-if="editable && !participant.is_bye"
                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                        @click="$emit('remove', participant.id)"
                    >
                        <TrashIcon class="w-4 h-4"/>
                    </button>
                </div>

                <div class="flex items-center mb-3">
                    <div
                        v-if="!participant.is_bye"
                        class="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center flex-shrink-0"
                    >
                        <UserIcon class="h-8 w-8 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <div
                        v-else
                        class="h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0"
                    >
                        <span class="text-sm font-medium text-gray-500">BYE</span>
                    </div>
                    <div class="ml-3 overflow-hidden">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ participant.display_name }}
                        </div>
                        <div v-if="participant.rating_snapshot" class="text-sm text-gray-500 dark:text-gray-400">
                            Rating: {{ participant.rating_snapshot }}
                        </div>
                    </div>
                </div>

                <div class="space-y-1 text-sm">
                    <div v-if="getClubName(participant)" class="text-gray-600 dark:text-gray-400 truncate">
                        <BuildingIcon class="inline w-4 h-4 mr-1"/>
                        {{ getClubName(participant) }}
                    </div>
                    <div v-if="getCity(participant)" class="text-gray-500 dark:text-gray-500 truncate">
                        <MapPinIcon class="inline w-4 h-4 mr-1"/>
                        {{ getCity(participant) }}
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
          <span
              :class="getStatusClass(participant)"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium w-full justify-center"
          >
            {{ getStatusText(participant) }}
          </span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredParticipants.length === 0" class="text-center py-12">
            <UsersIcon class="mx-auto h-12 w-12 text-gray-400"/>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No participants found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ searchQuery ? 'Try adjusting your search criteria' : 'No participants have been added yet' }}
            </p>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {computed, ref} from 'vue';
import type {OfficialParticipant} from '@/types/tournament';
import {
    BuildingIcon,
    GridIcon,
    ListIcon,
    MapPinIcon,
    SearchIcon,
    TrashIcon,
    UserIcon,
    UsersIcon
} from 'lucide-vue-next';

// Props
const props = defineProps<{
    participants: OfficialParticipant[];
    editable?: boolean;
}>();

// Emits
const emit = defineEmits<{
    remove: [participantId: number];
}>();

// State
const searchQuery = ref('');
const sortBy = ref<'seed' | 'name' | 'rating' | 'club'>('seed');
const viewMode = ref<'list' | 'grid'>('list');

// Computed
const filteredParticipants = computed(() => {
    let filtered = [...props.participants];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(p => {
            const name = p.display_name.toLowerCase();
            const club = getClubName(p)?.toLowerCase() || '';
            const city = getCity(p)?.toLowerCase() || '';

            return name.includes(query) || club.includes(query) || city.includes(query);
        });
    }

    // Sort
    filtered.sort((a, b) => {
        switch (sortBy.value) {
            case 'seed':
                return a.seed - b.seed;
            case 'name':
                return a.display_name.localeCompare(b.display_name);
            case 'rating':
                return (b.rating_snapshot || 0) - (a.rating_snapshot || 0);
            case 'club':
                const clubA = getClubName(a) || 'ZZZ';
                const clubB = getClubName(b) || 'ZZZ';
                return clubA.localeCompare(clubB);
            default:
                return 0;
        }
    });

    return filtered;
});

const totalParticipants = computed(() =>
    props.participants.filter(p => !p.is_bye).length
);

const uniqueClubs = computed(() => {
    const clubs = new Set<string>();
    props.participants.forEach(p => {
        const club = getClubName(p);
        if (club) clubs.add(club);
    });
    return clubs.size;
});

const averageRating = computed(() => {
    const withRating = props.participants.filter(p => p.rating_snapshot && !p.is_bye);
    if (withRating.length === 0) return '—';

    const sum = withRating.reduce((acc, p) => acc + p.rating_snapshot, 0);
    return Math.round(sum / withRating.length);
});

const byeCount = computed(() =>
    props.participants.filter(p => p.is_bye).length
);

// Methods
function toggleView() {
    viewMode.value = viewMode.value === 'list' ? 'grid' : 'list';
}

function getSeedClass(seed: number): string {
    if (seed <= 4) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
    } else if (seed <= 8) {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    } else if (seed <= 16) {
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    } else {
        return 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
    }
}

function getStatusClass(participant: OfficialParticipant): string {
    if (participant.is_bye) {
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }

    // Check if participant has played any matches
    if (participant.stats && participant.stats.matches_played > 0) {
        return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
    }

    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
}

function getStatusText(participant: OfficialParticipant): string {
    if (participant.is_bye) return 'BYE';

    if (participant.stats && participant.stats.matches_played > 0) {
        const wins = participant.stats.matches_won;
        const losses = participant.stats.matches_lost;
        return `${wins}W - ${losses}L`;
    }

    return 'Ready';
}

function getClubName(participant: OfficialParticipant): string | null {
    return participant.user?.home_club?.name || participant.team?.club?.name || null;
}

function getCity(participant: OfficialParticipant): string | null {
    const club = participant.user?.home_club || participant.team?.club;
    if (!club || !club.city) return null;

    const city = club.name;
    const country = club.country;

    return country ? `${city}, ${country}` : city;
}
</script>

<style scoped>
/* Custom scrollbar for grid view */
@media (max-width: 640px) {
    .grid {
        display: flex;
        flex-direction: column;
    }
}
</style>
