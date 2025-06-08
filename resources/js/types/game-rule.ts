import type {OfficialRating} from '@/types/api';

export interface GameRule {
    id: number;
    official_rating_id: number;
    rules: string;
    created_at: string;
    updated_at: string;
    official_rating?: OfficialRating;
}

export interface CreateGameRuleData {
    official_rating_id: number;
    rules: string;
}

export interface UpdateGameRuleData {
    rules: string;
}
