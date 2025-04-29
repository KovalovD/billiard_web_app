// resources/js/Types/api.ts - Improved version

import type { AxiosResponse } from 'axios';

// --- Auth Types ---
export interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    phone?: string | null;
    email_verified_at?: string | null;
    is_admin?: boolean;
    name?: string; // Combined name field if provided by backend
    created_at?: string;
    updated_at?: string;
}

export interface LoginResponse {
    user: User;
    token: string;
}

export interface LogoutResponse {
    success: boolean;
    message?: string;
}

// --- League & Game Types ---
export interface Game {
    id: number;
    name: string;
    type?: string;
    is_multiplayer?: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface RatingRuleItem {
    range: [number, number];
    strong: number;
    weak: number;
}

export interface League {
    id: number;
    name: string;
    picture: string | null;
    details: string | null;
    has_rating: boolean;
    started_at: string | null;
    finished_at: string | null;
    start_rating: number;
    rating_change_for_winners_rule: RatingRuleItem[];
    rating_change_for_losers_rule: RatingRuleItem[];
    created_at: string | null;
    updated_at: string | null;
    matches_count?: number;
    game: string | null;
    game_id: number;
    rating_type?: string;
}

export interface LeaguePayload {
    name: string;
    game_id: number | null;
    picture?: string | null;
    details?: string | null;
    has_rating?: boolean;
    started_at?: string | null;
    finished_at?: string | null;
    start_rating: number;
    rating_change_for_winners_rule?: string | null;
    rating_change_for_losers_rule?: string | null;
}

// --- Player & Rating Types ---
export interface Player {
    id: number;
    name: string;
    firstname?: string;
    lastname?: string;
    email?: string;
    avatar?: string | null;
}

export interface Rating {
    id: number;
    player: Player;
    rating: number;
    position: number;
    is_active?: boolean;
    matches_count?: number;
    wins_count?: number;
    losses_count?: number;
    created_at?: string;
    updated_at?: string;
}

// --- Match Types ---
export enum MatchStatus {
    PENDING = 'pending',
    ACCEPTED = 'accepted',
    DECLINED = 'declined',
    FINISHED = 'finished',
    RESULT_PENDING = 'result_pending'
}

export interface MatchGame {
    id: number;
    league_id: number;
    game_id?: number;
    first_rating_id: number;
    second_rating_id: number;
    sender_id?: number;
    receiver_id?: number;
    sender?: Player | null;
    receiver?: Player | null;
    first_user_score?: number | null;
    second_user_score?: number | null;
    winner_rating_id?: number | null;
    loser_rating_id?: number | null;
    status: MatchStatus | string;
    details?: string | null;
    stream_url?: string | null;
    club_id?: number | null;
    invitation_sent_at?: string | null;
    invitation_available_till?: string | null;
    invitation_accepted_at?: string | null;
    finished_at?: string | null;
    played_at?: string | null;
    created_at?: string;
    updated_at?: string;
    rating_change_for_winner?: number;
    rating_change_for_loser?: number;
}

export interface SendChallengePayload {
    receiver_id: number;
    stream_url?: string | null;
    details?: string | null;
    club_id?: number | string | null;
}

export interface SendResultPayload {
    first_user_score: number;
    second_user_score: number;
}

// --- API Response/Error Types ---
export interface ApiValidationError {
    message: string;
    errors: Record<string, string[]>;
}

// Enhanced API error with better type support
export interface ApiError extends Error {
    // The response object from axios
    response?: AxiosResponse;

    // The parsed response data
    data?: {
        message?: string;
        errors?: Record<string, string[]>;
        [key: string]: any;
    };

    // HTTP status code (extracted from response for convenience)
    status?: number;

    // Error type for easier handling in catch blocks
    type?: 'auth_failure' | 'validation_error' | 'server_error' | 'network_error' | 'unknown';
}

// Response wrapper types for Laravel API Resources
export interface ApiCollectionResponse<T> {
    data: T[];
    links?: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta?: {
        current_page?: number;
        from?: number;
        last_page?: number;
        links?: { url: string | null; label: string; active: boolean }[];
        path?: string;
        per_page?: number;
        to?: number;
        total?: number;
    };
}

export interface ApiItemResponse<T> {
    data: T;
}

// Pagination params for API requests
export interface PaginationParams {
    page?: number;
    per_page?: number;
    sort_by?: string;
    sort_dir?: 'asc' | 'desc';
    search?: string;
}
