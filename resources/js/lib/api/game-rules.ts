import axios from 'axios';
import {CreateGameRuleData, GameRule, UpdateGameRuleData} from '@/types/game-rule';

export const gameRulesApi = {
    getAll: async (): Promise<GameRule[]> => {
        const response = await axios.get('/api/game-rules');
        return response.data;
    },

    getByRating: async (ratingId: number): Promise<GameRule | null> => {
        const response = await axios.get(`/api/official-ratings/${ratingId}/rules`);
        return response.data;
    },

    create: async (data: CreateGameRuleData): Promise<GameRule> => {
        const response = await axios.post('/api/game-rules', data);
        return response.data;
    },

    update: async (id: number, data: UpdateGameRuleData): Promise<GameRule> => {
        const response = await axios.put(`/api/game-rules/${id}`, data);
        return response.data;
    },

    delete: async (id: number): Promise<void> => {
        await axios.delete(`/api/game-rules/${id}`);
    }
};
