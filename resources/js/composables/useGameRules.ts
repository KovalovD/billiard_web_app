import {ref} from 'vue';
import {apiClient} from '@/lib/apiClient';
import type {CreateGameRuleData, GameRule, UpdateGameRuleData} from '@/types/api';

export function useGameRules() {
    const rules = ref<GameRule[]>([]);
    const currentRule = ref<GameRule | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const fetchRules = async () => {
        isLoading.value = true;
        error.value = null;
        try {
            rules.value = await apiClient<GameRule[]>('/api/game-rules');
        } catch (e: any) {
            error.value = e.message || 'Failed to fetch rules';
        } finally {
            isLoading.value = false;
        }
    };

    const fetchRuleByRating = async (ratingId: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await apiClient<GameRule | null>(`/api/official-ratings/${ratingId}/rules`);
            if (!response) {
                currentRule.value = null;
                return null;
            }
            currentRule.value = response;
            return response;
        } catch (e: any) {
            error.value = e.message || 'Failed to fetch rule';
            currentRule.value = null;
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    const createRule = async (data: CreateGameRuleData) => {
        isLoading.value = true;
        error.value = null;
        try {
            const newRule = await apiClient<GameRule>('/api/game-rules', {
                method: 'POST',
                data
            });
            rules.value.push(newRule);
            currentRule.value = newRule;
            return newRule;
        } catch (e: any) {
            error.value = e.message || 'Failed to create rule';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const updateRule = async (id: number, data: UpdateGameRuleData) => {
        isLoading.value = true;
        error.value = null;
        try {
            const updatedRule = await apiClient<GameRule>(`/api/game-rules/${id}`, {
                method: 'PUT',
                data
            });
            const index = rules.value.findIndex(rule => rule.id === id);
            if (index !== -1) {
                rules.value[index] = updatedRule;
            }
            if (currentRule.value?.id === id) {
                currentRule.value = updatedRule;
            }
            return updatedRule;
        } catch (e: any) {
            error.value = e.message || 'Failed to update rule';
            throw e;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteRule = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            await apiClient(`/api/game-rules/${id}`, {
                method: 'DELETE'
            });
            rules.value = rules.value.filter(rule => rule.id !== id);
            if (currentRule.value?.id === id) {
                currentRule.value = null;
            }
        } catch (e: any) {
            error.value = e.message || 'Failed to delete rule';
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
}
