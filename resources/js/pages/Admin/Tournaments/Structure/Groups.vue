<!-- resources/js/Components/Tournament/Group/GroupManager.vue -->
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
import {useLocale} from '@/composables/useLocale';
import type {TournamentGroup, TournamentPlayer} from '@/types/tournament';
import {Edit2Icon, PlusIcon, Shuffle, TrashIcon, Users} from 'lucide-vue-next';
import {computed, ref} from 'vue';

const props = defineProps<{
    groups: TournamentGroup[];
    players: TournamentPlayer[];
    isTeamTournament: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    'create-groups': [groups: Array<{
        name: string;
        display_name?: string;
        group_number: number;
        max_participants: number;
        advance_count: number
    }>];
    'assign-players': [method: 'auto' | 'manual', assignments?: any[]];
    'update-group': [groupId: number, data: any];
    'delete-group': [groupId: number];
}>();

const {t} = useLocale();

const showCreateModal = ref(false);
const showAssignModal = ref(false);
const assignmentMethod = ref<'auto' | 'manual'>('auto');
const manualAssignments = ref<Map<number, number>>(new Map());

const newGroups = ref([{
    name: 'Group A',
    display_name: '',
    group_number: 1,
    max_participants: 4,
    advance_count: 2
}]);

const unassignedPlayers = computed(() => {
    return props.players.filter(p => !p.group_id && p.is_confirmed);
});

const playersInGroup = (groupId: number) => {
    return props.players.filter(p => p.group_id === groupId);
};

const addGroup = () => {
    const nextNumber = newGroups.value.length + 1;
    newGroups.value.push({
        name: `Group ${String.fromCharCode(64 + nextNumber)}`,
        display_name: '',
        group_number: nextNumber,
        max_participants: 4,
        advance_count: 2
    });
};

const removeGroup = (index: number) => {
    newGroups.value.splice(index, 1);
    // Update group numbers
    newGroups.value.forEach((group, i) => {
        group.group_number = i + 1;
        group.name = `Group ${String.fromCharCode(65 + i)}`;
    });
};

const handleCreateGroups = () => {
    emit('create-groups', newGroups.value);
    showCreateModal.value = false;
    newGroups.value = [{
        name: 'Group A',
        display_name: '',
        group_number: 1,
        max_participants: 4,
        advance_count: 2
    }];
};

const handleAssignPlayers = () => {
    if (assignmentMethod.value === 'manual') {
        const assignments = Array.from(manualAssignments.value.entries()).map(([playerId, groupId]) => ({
            player_id: playerId,
            group_id: groupId
        }));
        emit('assign-players', 'manual', assignments);
    } else {
        emit('assign-players', 'auto');
    }
    showAssignModal.value = false;
    manualAssignments.value.clear();
};

const getGroupClass = (group: TournamentGroup) => {
    const colors = [
        'border-blue-500 bg-blue-50 dark:bg-blue-900/20',
        'border-green-500 bg-green-50 dark:bg-green-900/20',
        'border-purple-500 bg-purple-50 dark:bg-purple-900/20',
        'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
        'border-red-500 bg-red-50 dark:bg-red-900/20',
        'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20',
        'border-pink-500 bg-pink-50 dark:bg-pink-900/20',
        'border-teal-500 bg-teal-50 dark:bg-teal-900/20'
    ];
    return colors[(group.group_number - 1) % colors.length];
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header Actions -->
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ t('Group Management') }}</h3>
            <div class="flex space-x-3">
                <Button v-if="groups.length === 0" @click="showCreateModal = true">
                    <PlusIcon class="mr-2 h-4 w-4"/>
                    {{ t('Create Groups') }}
                </Button>
                <Button v-if="groups.length > 0 && unassignedPlayers.length > 0" variant="outline"
                        @click="showAssignModal = true">
                    <Shuffle class="mr-2 h-4 w-4"/>
                    {{ t('Assign Players') }}
                </Button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-8">
            <Spinner class="h-8 w-8 text-primary"/>
        </div>

        <!-- Groups Grid -->
        <div v-else-if="groups.length > 0" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <Card
                v-for="group in groups"
                :key="group.id"
                :class="['border-2', getGroupClass(group)]"
            >
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span>{{ group.display_name || group.name }}</span>
                        <div class="flex space-x-1">
                            <Button size="sm" variant="ghost">
                                <Edit2Icon class="h-4 w-4"/>
                            </Button>
                            <Button class="text-red-600 hover:text-red-700" size="sm" variant="ghost">
                                <TrashIcon class="h-4 w-4"/>
                            </Button>
                        </div>
                    </CardTitle>
                    <CardDescription>
                        {{ playersInGroup(group.id).length }}/{{ group.max_participants }} {{ t('players') }}
                        • {{ t('Top :count advance', {count: group.advance_count}) }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div
                            v-for="player in playersInGroup(group.id)"
                            :key="player.id"
                            class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded"
                        >
                            <div class="flex items-center space-x-2">
                                <span v-if="player.seed" class="text-xs font-medium text-gray-500">#{{
                                        player.seed
                                    }}</span>
                                <span class="text-sm">{{ player.user?.firstname }} {{ player.user?.lastname }}</span>
                            </div>
                        </div>
                        <div v-if="playersInGroup(group.id).length === 0" class="text-center py-4 text-gray-500">
                            {{ t('No players assigned') }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Empty State -->
        <Card v-else class="text-center py-12">
            <CardContent>
                <Users class="mx-auto h-12 w-12 text-gray-400 mb-4"/>
                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t('No Groups Created') }}</p>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ t('Create groups to organize players for the group stage.') }}
                </p>
                <Button class="mt-4" @click="showCreateModal = true">
                    <PlusIcon class="mr-2 h-4 w-4"/>
                    {{ t('Create Groups') }}
                </Button>
            </CardContent>
        </Card>

        <!-- Create Groups Modal -->
        <Modal :show="showCreateModal" :title="t('Create Groups')" @close="showCreateModal = false">
            <div class="space-y-4">
                <div v-for="(group, index) in newGroups" :key="index" class="p-4 border rounded-lg space-y-3">
                    <div class="flex justify-between items-center">
                        <h4 class="font-medium">{{ group.name }}</h4>
                        <Button
                            v-if="newGroups.length > 1"
                            size="sm"
                            variant="ghost"
                            @click="removeGroup(index)"
                        >
                            <TrashIcon class="h-4 w-4"/>
                        </Button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label>{{ t('Display Name') }}</Label>
                            <Input
                                v-model="group.display_name"
                                :placeholder="group.name"
                            />
                        </div>
                        <div>
                            <Label>{{ t('Max Players') }}</Label>
                            <Input
                                v-model.number="group.max_participants"
                                max="16"
                                min="2"
                                type="number"
                            />
                        </div>
                    </div>

                    <div>
                        <Label>{{ t('Players to Advance') }}</Label>
                        <Input
                            v-model.number="group.advance_count"
                            :max="group.max_participants - 1"
                            min="1"
                            type="number"
                        />
                    </div>
                </div>

                <Button class="w-full" variant="outline" @click="addGroup">
                    <PlusIcon class="mr-2 h-4 w-4"/>
                    {{ t('Add Another Group') }}
                </Button>
            </div>

            <template #footer>
                <Button variant="outline" @click="showCreateModal = false">
                    {{ t('Cancel') }}
                </Button>
                <Button @click="handleCreateGroups">
                    {{ t('Create Groups') }}
                </Button>
            </template>
        </Modal>

        <!-- Assign Players Modal -->
        <Modal :show="showAssignModal" :title="t('Assign Players to Groups')" @close="showAssignModal = false">
            <div class="space-y-4">
                <div>
                    <Label>{{ t('Assignment Method') }}</Label>
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center">
                            <input
                                v-model="assignmentMethod"
                                class="mr-2"
                                type="radio"
                                value="auto"
                            />
                            <span>{{ t('Automatic (Distribute evenly based on seeding)') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="assignmentMethod"
                                class="mr-2"
                                type="radio"
                                value="manual"
                            />
                            <span>{{ t('Manual Assignment') }}</span>
                        </label>
                    </div>
                </div>

                <div v-if="assignmentMethod === 'manual'" class="space-y-2 max-h-60 overflow-y-auto">
                    <div
                        v-for="player in unassignedPlayers"
                        :key="player.id"
                        class="flex items-center justify-between p-2 border rounded"
                    >
                        <span class="text-sm">
                            {{ player.user?.firstname }} {{ player.user?.lastname }}
                            <span v-if="player.seed" class="text-xs text-gray-500">(#{{ player.seed }})</span>
                        </span>
                        <select
                            class="ml-2 rounded border-gray-300"
                            @change="manualAssignments.set(player.id, Number($event.target.value))"
                        >
                            <option value="">{{ t('Select Group') }}</option>
                            <option
                                v-for="group in groups"
                                :key="group.id"
                                :value="group.id"
                            >
                                {{ group.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t(':count unassigned players', {count: unassignedPlayers.length}) }}
                </div>
            </div>

            <template #footer>
                <Button variant="outline" @click="showAssignModal = false">
                    {{ t('Cancel') }}
                </Button>
                <Button @click="handleAssignPlayers">
                    {{ t('Assign Players') }}
                </Button>
            </template>
        </Modal>
    </div>
</template>
