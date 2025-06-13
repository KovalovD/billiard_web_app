// resources/js/types/tournament.ts - Additional types for tournament system

// Add these to existing api.ts or import them there

import {Tournament, TournamentPlayer} from "@/types/api";

export type {Tournament, TournamentPlayer};

export interface MatchSchedule {
    id: number;
    tournamentId: number;
    round: number;
    tableNumber?: number;
    scheduledAt: string;
    playerA: TournamentPlayer;
    playerB: TournamentPlayer;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    scoreA?: number;
    scoreB?: number;
    frames?: Array<{ frameNumber: number; winner: 'A' | 'B' }>;
}
export interface Match {
    id: number;
    tournament_id: number;
    round: number;
    bracket_position?: number;
    group_id?: number;
    player_a_id: number;
    player_b_id: number;
    player_a?: TournamentPlayer;
    player_b?: TournamentPlayer;
    scheduled_at?: string;
    started_at?: string;
    completed_at?: string;
    table_number?: number;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'walkover';
    score_a: number;
    score_b: number;
    frames?: Frame[];
    winner_id?: number;
    notes?: string;
    stream_url?: string;
}

export interface Frame {
    id: number;
    match_id: number;
    frame_number: number;
    winner_id: number;
    break_points?: number;
    duration_seconds?: number;
    notes?: string;
}

export interface Group {
    id: number;
    tournament_id: number;
    name: string;
    description?: string;
    players: TournamentPlayer[];
    matches?: Match[];
    standings: GroupStanding[];
    advance_count: number; // How many advance to playoffs
    created_at: string;
    updated_at: string;
}

export interface GroupStanding {
    id: number;
    group_id: number;
    player_id: number;
    player: TournamentPlayer;
    matches_played: number;
    wins: number;
    losses: number;
    draws: number;
    frames_won: number;
    frames_lost: number;
    frame_difference: number;
    points: number;
    position: number;
    qualified: boolean;
}

export interface BracketNode {
    id: number;
    tournament_id: number;
    round: number;
    position: number;
    match_id?: number;
    match?: Match;
    player_a_id?: number;
    player_b_id?: number;
    player_a?: { id: number; name: string; seed?: number };
    player_b?: { id: number; name: string; seed?: number };
    winner_id?: number;
    winner?: { id: number; name: string };
    next_match_id?: number;
    previous_match_a_id?: number;
    previous_match_b_id?: number;
    is_bye: boolean;
    bracket_type: 'upper' | 'lower' | 'grand_final';
}

export interface TournamentBracket {
    id: number;
    tournament_id: number;
    type: 'single_elimination' | 'double_elimination' | 'round_robin' | 'swiss' | 'group_playoff';
    rounds: number;
    nodes: BracketNode[];
    settings: {
        best_of: number;
        third_place_match: boolean;
        seeding_method: 'manual' | 'random' | 'rating_based';
        bye_placement: 'top' | 'bottom' | 'spread';
    };
    created_at: string;
    updated_at: string;
}

export interface Team {
    id: number;
    tournament_id: number;
    name: string;
    players: TournamentPlayer[];
    captain_id?: number;
    seed_position?: number;
    created_at: string;
    updated_at: string;
}

export interface TournamentSettings {
    format: {
        type: 'single_elimination' | 'double_elimination' | 'group_stage' | 'group_playoff' | 'round_robin' | 'swiss';
        best_of: number;
        rounds?: number;
        group_count?: number;
        playoff_size?: number;
        third_place_match: boolean;
    };
    seeding: {
        method: 'manual' | 'random' | 'rating_based';
        custom_order?: number[];
    };
    teams?: {
        enabled: boolean;
        team_size: number;
        max_teams: number;
        allow_mixed: boolean;
    };
    schedule: {
        start_time: string;
        end_time: string;
        break_duration: number; // minutes
        match_duration: number; // minutes
        tables_count: number;
        auto_schedule: boolean;
    };
    scoring: {
        points_for_win: number;
        points_for_draw: number;
        points_for_loss: number;
        frame_advantage: boolean; // Count frame difference
    };
}

// Extended Tournament Player for bracket/group context
export interface TournamentPlayerExtended extends TournamentPlayer {
    seed_position?: number;
    group_id?: number;
    team_id?: number;
    eliminated_in_round?: number;
    current_opponent?: TournamentPlayer;
    next_match?: Match;
    statistics?: {
        matches_played: number;
        matches_won: number;
        matches_lost: number;
        frames_won: number;
        frames_lost: number;
        frame_percentage: number;
        average_match_duration: number;
        longest_match_duration: number;
        break_points_total: number;
        highest_break: number;
    };
}

// API Response types
export interface TournamentBracketResponse {
    bracket: TournamentBracket;
    matches: Match[];
    can_edit: boolean;
    current_round: number;
    total_rounds: number;
}

export interface TournamentGroupsResponse {
    groups: Group[];
    can_edit: boolean;
    playoff_bracket?: TournamentBracket;
    advancement_rules: {
        per_group: number;
        wildcards: number;
        tiebreaker_rules: string[];
    };
}

export interface TournamentScheduleResponse {
    matches: Match[];
    tables: Array<{
        id: number;
        name: string;
        location?: string;
        available: boolean;
    }>;
    time_slots: string[];
    conflicts: Array<{
        type: 'player_conflict' | 'table_conflict' | 'time_conflict';
        message: string;
        match_ids: number[];
    }>;
}

export interface TournamentResultsResponse {
    final_standings: TournamentPlayerExtended[];
    bracket?: TournamentBracket;
    groups?: Group[];
    statistics: {
        total_matches: number;
        total_frames: number;
        average_match_duration: string;
        longest_match: {
            duration: string;
            players: string[];
        };
        highest_break: {
            points: number;
            player: string;
            match: string;
        };
        most_frames_won: {
            count: number;
            player: string;
        };
    };
    prize_distribution: Array<{
        position: number;
        player_id: number;
        player_name: string;
        amount: number;
        rating_change: number;
    }>;
}
