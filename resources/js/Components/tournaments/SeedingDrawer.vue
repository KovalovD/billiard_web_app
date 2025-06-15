<template>
    <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <div
            v-if="isOpen"
            class="fixed right-0 top-0 h-full w-96 bg-white dark:bg-gray-800 shadow-2xl z-50 flex flex-col"
        >
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Tournament Seeding
                    </h2>
                    <button
                        @click="$emit('close')"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        <XIcon class="w-5 h-5"/>
                    </button>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ participants.length }} participants
                </p>
            </div>

            <!-- Seeding Method Selector -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Seeding Method
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="method in seedingMethods"
                        :key="method.value"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                        @click="selectedMethod = method.value"
                        :class="[
              selectedMethod === method.value
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
                    >
                        <component :is="method.icon" class="w-4 h-4 mx-auto mb-1"/>
                        {{ method.label }}
                    </button>
                </div>

                <!-- Method Options -->
                <div v-if="selectedMethod === 'random'" class="mt-4">
                    <label class="flex items-center">
                        <input
                            v-model="avoidSameClub"
                            type="checkbox"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
              Avoid same club in early rounds
            </span>
                    </label>
                </div>

                <div v-if="selectedMethod === 'snake'" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Number of Groups
                    </label>
                    <input
                        v-model.number="groupCount"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700"
                        min="2"
                        max="16"
                        type="number"
                    />
                </div>
            </div>

            <!-- Participants List -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div class="space-y-2">
                    <div
                        v-for="(participant, index) in localParticipants"
                        :key="participant.id"
                        :class="{ 'transition-transform': !isDragging }"
                        :draggable="selectedMethod === 'manual'"
                        class="relative"
                        @dragend="handleDragEnd"
                        @dragstart="handleDragStart($event, index)"
                        @drop="handleDrop($event, index)"
                        @dragover.prevent
                        @dragenter.prevent
                    >
                        <div
                            :class="{
                'opacity-50': draggedIndex === index,
                'border-t-2 border-blue-500': dropIndex === index && draggedIndex !== null && draggedIndex < index,
                'border-b-2 border-blue-500': dropIndex === index && draggedIndex !== null && draggedIndex > index
              }"
                            class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition-shadow"
                        >
                            <div
                                v-if="selectedMethod === 'manual'"
                                class="cursor-move p-1 mr-2"
                            >
                                <GripVerticalIcon class="w-4 h-4 text-gray-400"/>
                            </div>

                            <div
                                class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mr-3">
                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                  {{ index + 1 }}
                </span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ participant.display_name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                  <span v-if="participant.user?.home_club">
                    {{ participant.user.home_club.name }}
                  </span>
                                    <span v-else-if="participant.team?.club">
                    {{ participant.team.club.name }}
                  </span>
                                    <span v-if="participant.rating_snapshot" class="ml-2">
                    Rating: {{ participant.rating_snapshot }}
                  </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                <span
                    v-if="participant.seed !== index + 1"
                    class="text-xs px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded"
                >
                  Changed
                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Groups (for snake seeding) -->
            <div
                v-if="selectedMethod === 'snake' && groupPreview.length > 0"
                class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 max-h-64 overflow-y-auto"
            >
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Group Preview
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div
                        v-for="(group, index) in groupPreview"
                        :key="index"
                        class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3"
                    >
                        <h4 class="text-sm font-semibold mb-2">
                            Group {{ String.fromCharCode(65 + index) }}
                        </h4>
                        <div class="space-y-1">
                            <div
                                v-for="participant in group"
                                :key="participant.id"
                                class="text-xs text-gray-600 dark:text-gray-400"
                            >
                                {{ participant.seed }}. {{ participant.display_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
            <span v-if="hasChanges" class="flex items-center text-yellow-600 dark:text-yellow-400">
              <AlertCircleIcon class="w-4 h-4 mr-1"/>
              Unsaved changes
            </span>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="resetSeeding"
                            :disabled="!hasChanges"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Reset
                        </button>
                        <button
                            @click="applySeeding"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Apply Seeding
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Backdrop -->
    <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            @click="$emit('close')"
            class="fixed inset-0 bg-black/50 z-40"
        />
    </Transition>
</template>

<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {AlertCircleIcon, GripVerticalIcon, ShuffleIcon, TrophyIcon, UsersIcon, XIcon} from 'lucide-vue-next';

// Types
interface Participant {
    id: number;
    display_name: string;
    seed: number;
    rating_snapshot: number;
    user?: {
        home_club?: {
            id: number;
            name: string;
        };
    };
    team?: {
        club?: {
            id: number;
            name: string;
        };
    };
}

// Props & Emits
const props = defineProps<{
    participants: Participant[];
    isOpen: boolean;
}>();

const emit = defineEmits<{
    close: [];
    seedChange: [payload: Array<{ participantId: number; seed: number }>];
}>();

// State
const localParticipants = ref<Participant[]>([]);
const selectedMethod = ref<'manual' | 'random' | 'rating' | 'snake'>('manual');
const avoidSameClub = ref(true);
const groupCount = ref(4);
const originalOrder = ref<Participant[]>([]);
const draggedIndex = ref<number | null>(null);
const dropIndex = ref<number | null>(null);
const isDragging = ref(false);

// Seeding methods configuration
const seedingMethods = [
    {value: 'manual', label: 'Manual', icon: GripVerticalIcon},
    {value: 'random', label: 'Random', icon: ShuffleIcon},
    {value: 'rating', label: 'By Rating', icon: TrophyIcon},
    {value: 'snake', label: 'Snake', icon: UsersIcon},
];

// Computed
const hasChanges = computed(() => {
    return localParticipants.value.some((p, index) =>
        p.id !== originalOrder.value[index]?.id
    );
});

const groupPreview = computed(() => {
    if (selectedMethod.value !== 'snake' || groupCount.value < 2) {
        return [];
    }

    const groups: Participant[][] = Array(groupCount.value).fill(null).map(() => []);
    let direction = 1;
    let currentGroup = 0;

    localParticipants.value.forEach((participant, index) => {
        groups[currentGroup].push({
            ...participant,
            seed: index + 1
        });

        currentGroup += direction;
        if (currentGroup >= groupCount.value) {
            currentGroup = groupCount.value - 1;
            direction = -1;
        } else if (currentGroup < 0) {
            currentGroup = 0;
            direction = 1;
        }
    });

    return groups;
});

// Methods
function handleDragStart(event: DragEvent, index: number) {
    if (selectedMethod.value !== 'manual') return;

    draggedIndex.value = index;
    isDragging.value = true;
    event.dataTransfer!.effectAllowed = 'move';
    event.dataTransfer!.setData('text/plain', index.toString());
}

function handleDragEnd() {
    draggedIndex.value = null;
    dropIndex.value = null;
    isDragging.value = false;
}

function handleDrop(event: DragEvent, targetIndex: number) {
    event.preventDefault();

    if (draggedIndex.value === null || draggedIndex.value === targetIndex) {
        handleDragEnd();
        return;
    }

    // Reorder participants
    const draggedParticipant = localParticipants.value[draggedIndex.value];
    const newParticipants = [...localParticipants.value];

    // Remove from old position
    newParticipants.splice(draggedIndex.value, 1);

    // Insert at new position
    const insertIndex = draggedIndex.value < targetIndex ? targetIndex - 1 : targetIndex;
    newParticipants.splice(insertIndex, 0, draggedParticipant);

    // Update participants with new seeds
    localParticipants.value = newParticipants.map((p, index) => ({
        ...p,
        seed: index + 1
    }));

    handleDragEnd();
}

function shuffleArray<T>(array: T[]): T[] {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
}

function applySeedingMethod() {
    switch (selectedMethod.value) {
        case 'random':
            applyRandomSeeding();
            break;
        case 'rating':
            applyRatingSeeding();
            break;
        case 'snake':
            applySnakeSeeding();
            break;
        default:
            // Manual - do nothing
            break;
    }
}

function applyRandomSeeding() {
    if (avoidSameClub.value) {
        // Group by club
        const clubGroups = new Map<string, Participant[]>();
        const noClub: Participant[] = [];

        localParticipants.value.forEach(p => {
            const clubId = p.user?.home_club?.id || p.team?.club?.id;
            if (clubId) {
                const key = `club-${clubId}`;
                if (!clubGroups.has(key)) {
                    clubGroups.set(key, []);
                }
                clubGroups.get(key)!.push(p);
            } else {
                noClub.push(p);
            }
        });

        // Shuffle within each group
        clubGroups.forEach((participants, key) => {
            clubGroups.set(key, shuffleArray(participants));
        });

        // Distribute evenly
        const distributed: Participant[] = [];
        const maxRounds = Math.max(...Array.from(clubGroups.values()).map(g => g.length), noClub.length);

        for (let round = 0; round < maxRounds; round++) {
            // Add from each club group
            clubGroups.forEach(group => {
                if (round < group.length) {
                    distributed.push(group[round]);
                }
            });

            // Add from no-club group
            if (round < noClub.length) {
                distributed.push(noClub[round]);
            }
        }

        localParticipants.value = distributed.map((p, index) => ({
            ...p,
            seed: index + 1
        }));
    } else {
        // Simple random shuffle
        localParticipants.value = shuffleArray(localParticipants.value).map((p, index) => ({
            ...p,
            seed: index + 1
        }));
    }
}

function applyRatingSeeding() {
    localParticipants.value = [...localParticipants.value]
        .sort((a, b) => (b.rating_snapshot || 0) - (a.rating_snapshot || 0))
        .map((p, index) => ({
            ...p,
            seed: index + 1
        }));
}

function applySnakeSeeding() {
    // First sort by rating
    applyRatingSeeding();
    // The snake pattern will be shown in the preview
}

function resetSeeding() {
    localParticipants.value = [...originalOrder.value];
}

function applySeeding() {
    const changes = localParticipants.value.map((p, index) => ({
        participantId: p.id,
        seed: index + 1
    }));

    emit('seedChange', changes);
    emit('close');
}

// Watchers
watch(() => props.participants, (newParticipants) => {
    localParticipants.value = [...newParticipants].sort((a, b) => a.seed - b.seed);
    originalOrder.value = [...localParticipants.value];
}, {immediate: true});

watch(selectedMethod, () => {
    if (selectedMethod.value !== 'manual') {
        applySeedingMethod();
    }
});

watch([avoidSameClub, groupCount], () => {
    if (selectedMethod.value !== 'manual') {
        applySeedingMethod();
    }
});
</script>

<style scoped>
/* Smooth transitions for drag and drop */
.transition-transform {
    transition: transform 0.2s ease;
}

/* Hide drag ghost image */
[draggable="true"] {
    -webkit-user-drag: element;
}
</style>
