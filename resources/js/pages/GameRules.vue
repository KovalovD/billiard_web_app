<template>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-4">Game Rules Management</h1>
            <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ error }}
            </div>
        </div>

        <!-- Rules List -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Rules List</h2>
            <div v-if="isLoading" class="flex justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
            </div>
            <div v-else-if="rules.length === 0" class="text-gray-500 text-center py-4">
                No rules found
            </div>
            <div v-else class="space-y-4">
                <div v-for="rule in rules" :key="rule.id" class="border rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-medium">{{ rule.official_rating?.name || 'Unnamed Rating' }}</h3>
                            <p class="text-gray-600 mt-2 whitespace-pre-wrap">{{ rule.rules }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                class="text-blue-600 hover:text-blue-800"
                                @click="editRule(rule)"
                            >
                                Edit
                            </button>
                            <button
                                class="text-red-600 hover:text-red-800"
                                @click="deleteRule(rule.id)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rule Editor -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">{{ editingRule ? 'Edit Rule' : 'Create New Rule' }}</h2>
            <form class="space-y-4" @submit.prevent="saveRule">
                <div v-if="!editingRule">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Rating
                    </label>
                    <select
                        v-model="newRule.official_rating_id"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required
                    >
                        <option value="">Select a rating</option>
                        <option v-for="rating in ratings" :key="rating.id" :value="rating.id">
                            {{ rating.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Rules
                    </label>
                    <textarea
                        :value="editingRule ? editingRule.rules : newRule.rules"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required
                        rows="6"
                        @input="e => editingRule ? editingRule.rules = e.target.value : newRule.rules = e.target.value"
                    ></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button
                        v-if="editingRule"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        type="button"
                        @click="cancelEdit"
                    >
                        Cancel
                    </button>
                    <button
                        :disabled="isLoading"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        type="submit"
                    >
                        {{ editingRule ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {onMounted, ref} from 'vue';
import {useGameRulesStore} from '@/stores/game-rules';
import type {CreateGameRuleData, GameRule} from '@/types/game-rule';
import type {OfficialRating} from '@/types/official-rating';

const store = useGameRulesStore();
const ratings = ref<OfficialRating[]>([]);
const editingRule = ref<GameRule | null>(null);
const newRule = ref<CreateGameRuleData>({
    official_rating_id: 0,
    rules: ''
});

const {rules, isLoading, error} = storeToRefs(store);

onMounted(async () => {
    await store.fetchRules();
    // TODO: Fetch ratings list
});

const editRule = (rule: GameRule) => {
    editingRule.value = {...rule};
};

const cancelEdit = () => {
    editingRule.value = null;
};

const saveRule = async () => {
    try {
        if (editingRule.value) {
            await store.updateRule(editingRule.value.id, {rules: editingRule.value.rules});
            editingRule.value = null;
        } else {
            await store.createRule(newRule.value);
            newRule.value = {
                official_rating_id: 0,
                rules: ''
            };
        }
    } catch (e) {
        console.error('Failed to save rule:', e);
    }
};

const deleteRule = async (id: number) => {
    if (confirm('Are you sure you want to delete this rule?')) {
        try {
            await store.deleteRule(id);
        } catch (e) {
            console.error('Failed to delete rule:', e);
        }
    }
};
</script>
