import {defineStore} from 'pinia';
import {ref} from 'vue';
import {CreateGameRuleData, GameRule, UpdateGameRuleData} from '@/types/game-rule';
import {gameRulesApi} from '@/lib/api/game-rules';

export const useGameRulesStore = defineStore('gameRules', () => {
    const rules = ref<GameRule[]>([]);
    const currentRule = ref<GameRule | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const fetchRules = async () => {
        isLoading.value = true;
        error.value = null;
        try {
            rules.value = await gameRulesApi.getAll();
        } catch (e) {
            error.value = 'Failed to fetch rules';
            console.error(e);
        } finally {
            isLoading.value = false;
        }
    };

    const fetchRuleByRating = async (ratingId: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            currentRule.value = await gameRulesApi.getByRating(ratingId);
        } catch (e) {
            error.value = 'Failed to fetch rule';
            console.error(e);
        } finally {
            isLoading.value = false;
        }
    };

    const createRule = async (data: CreateGameRuleData) => {
        isLoading.value = true;
        error.value = null;
        try {
            const newRule = await gameRulesApi.create(data);
            rules.value.push(newRule);
            return newRule;
        } catch (e) {
            error.value = 'Failed to create rule';
            console.error(e);
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const updateRule = async (id: number, data: UpdateGameRuleData) => {
        isLoading.value = true;
        error.value = null;
        try {
            const updatedRule = await gameRulesApi.update(id, data);
            const index = rules.value.findIndex(rule => rule.id === id);
            if (index !== -1) {
                rules.value[index] = updatedRule;
            }
            if (currentRule.value?.id === id) {
                currentRule.value = updatedRule;
            }
            return updatedRule;
        } catch (e) {
            error.value = 'Failed to update rule';
            console.error(e);
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteRule = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            await gameRulesApi.delete(id);
            rules.value = rules.value.filter(rule => rule.id !== id);
            if (currentRule.value?.id === id) {
                currentRule.value = null;
            }
        } catch (e) {
            error.value = 'Failed to delete rule';
            console.error(e);
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        rules,
        currentRule,
        isLoading,
        error,
        fetchRules,
        fetchRuleByRating,
        createRule,
        updateRule,
        deleteRule
    };
});
