// resources/js/types/tournament.ts
import type {User} from './api';

export type TournamentFormat =
    'single_elimination'
    | 'double_elimination'
    | 'group_stage'
    | 'group_playoff'
    | 'round_robin';
export type SeedingMethod = 'manual' | 'random' | 'rating_based';
export type BestOfRule = 'best_of_1' | 'best_of_3' | 'best_of_5' | 'best_of_7';
export type MatchStatus = 'pending' | 'in_progress' | 'completed' | 'cancelled';
export type BracketType = 'main' | 'upper' | 'lower' | 'final' | 'consolation';

export interface TournamentGroup {
    id: number;
    tournament_id: number;
    name: string;
    display_name?: string;
    group_number: number;
    max_participants: number;
    advance_count: number;
    is_completed: boolean;
    current_participants_count?: number;
    standings?: GroupStanding[];
    players?: TournamentPlayer[];
    teams?: TournamentTeam[];
    matches?: TournamentMatch[];
}

export interface GroupStanding {
    participant_id: number;
    participant_name: string;
    position: number;
    matches_played: number;
    wins: number;
    losses: number;
    games_for: number;
    games_against: number;
    games_difference: number;
    points: number;
    win_percentage: number;
}

export interface TournamentBracket {
    id: number;
    tournament_id: number;
    bracket_type: BracketType;
    bracket_type_display: string;
    total_rounds: number;
    total_participants: number;
    current_round: number;
    is_active: boolean;
    is_completed: boolean;
    bracket_structure?: any;
    participant_positions?: any;
    advancement_rules?: any;
    champion?: any;
    status: {
        bracket_type: string;
        current_round: number;
        total_rounds: number;
        is_completed: boolean;
        champion: any;
    };
    started_at?: string;
    completed_at?: string;
    matches?: TournamentMatch[];
}

export interface TournamentMatch {
    id: number;
    tournament_id: number;
    match_type: 'group' | 'bracket' | 'final';
    match_type_display: string;
    round_number: number;
    match_number: number;
    bracket_type?: BracketType;
    group_id?: number;

    participant_1_id?: number;
    participant_1_type?: 'player' | 'team';
    participant_1_name: string;
    participant_2_id?: number;
    participant_2_type?: 'player' | 'team';
    participant_2_name: string;

    status: MatchStatus;
    status_display: string;
    scores?: any[];
    participant_1_score?: number;
    participant_2_score?: number;
    winner_id?: number;
    winner_type?: 'player' | 'team';
    winner_name?: string;

    scheduled_at?: string;
    started_at?: string;
    completed_at?: string;
    table_number?: number;
    referee?: string;
    notes?: string;
    match_data?: any;

    display_name: string;
    is_completed: boolean;
    is_in_progress: boolean;
    is_pending: boolean;
    result_summary: {
        status: string;
        winner: any;
        score: any;
        detailed_scores?: any;
    };

    group?: TournamentGroup;
    club?: any;
    participant_1?: TournamentPlayer;
    participant_2?: TournamentPlayer;
    team_1?: TournamentTeam;
    team_2?: TournamentTeam;
}

export interface TournamentTeam {
    id: number;
    tournament_id: number;
    name: string;
    short_name?: string;
    display_name: string;
    seed?: number;
    group_id?: number;
    bracket_position?: number;
    is_active: boolean;
    roster_data?: any;
    is_ready_to_compete: boolean;
    has_minimum_players: boolean;
    has_captain: boolean;
    roster_summary: {
        total_players: number;
        active_players: number;
        substitutes: number;
        captain: any;
        has_minimum_players: boolean;
        is_ready: boolean;
    };
    statistics?: any;
    group?: TournamentGroup;
    players?: TournamentPlayer[];
    captain?: TournamentPlayer;
}

export interface TournamentPlayer {
    id: number;
    tournament_id: number;
    user_id: number;
    position?: number;
    seed?: number;
    bracket_position?: number;
    group_id?: number;
    team_id?: number;
    team_role?: 'captain' | 'player' | 'substitute';
    rating_points: number;
    matches_played: number;
    matches_won: number;
    matches_lost: number;
    games_won: number;
    games_lost: number;
    win_percentage: number;
    prize_amount: number;
    bonus_amount: number;
    achievement_amount: number;
    total_amount: number;
    bracket_path?: any;
    group_standings?: any;
    status: 'applied' | 'confirmed' | 'rejected' | 'eliminated' | 'dnf';
    status_display: string;
    is_confirmed: boolean;
    is_pending: boolean;
    is_rejected: boolean;
    is_winner: boolean;
    is_in_top_three: boolean;
    registered_at: string;
    applied_at?: string;
    confirmed_at?: string;
    rejected_at?: string;
    user?: User;
    group?: TournamentGroup;
    team?: TournamentTeam;
}

export interface TournamentStructureOverview {
    tournament: {
        id: number;
        name: string;
        format: TournamentFormat;
        format_display: string;
        seeding_method: SeedingMethod;
        is_team_tournament: boolean;
        status: string;
        is_initialized: boolean;
    };
    participants: {
        total_confirmed: number;
        teams_count: number;
    };
    structure: {
        has_groups: boolean;
        has_brackets: boolean;
        groups_count: number;
        brackets_count: number;
    };
    progress: {
        status: string;
        format: string;
        is_initialized: boolean;
        total_participants: number;
        groups?: any;
        brackets?: any;
        matches: {
            total: number;
            completed: number;
            in_progress: number;
            pending: number;
        };
    };
    groups?: TournamentGroup[];
    brackets?: TournamentBracket[];
    teams?: TournamentTeam[];
}

export interface CreateGroupPayload {
    name: string;
    display_name?: string;
    group_number: number;
    max_participants: number;
    advance_count: number;
}

export interface CreateTeamPayload {
    name: string;
    short_name?: string;
    seed?: number;
    group_id?: number;
    player_ids: number[];
    roster_data?: any;
}

export interface MatchResultPayload {
    participant_1_score: number;
    participant_2_score: number;
    scores?: Array<{
        game_number: number;
        participant_1_score: number;
        participant_2_score: number;
    }>;
    notes?: string;
}

export interface RescheduleMatchPayload {
    scheduled_at: string;
    table_number?: number;
    club_id?: number;
    notes?: string;
}
