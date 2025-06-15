// resources/js/types/tournament.d.ts

import type {City, Club, User} from './api';

// Tournament Types
export interface OfficialTournament {
    id: number;
    name: string;
    discipline: '8-ball' | '9-ball' | '10-ball' | 'snooker' | 'straight-pool';
    start_at: string;
    end_at: string;
    city_id: number | null;
    club_id: number | null;
    entry_fee: number;
    prize_pool: number;
    format: TournamentFormat;
    status: 'upcoming' | 'ongoing' | 'completed';
    participants_count?: number;
    created_at: string;
    updated_at: string;

    // Relations
    city?: City;
    club?: Club;
    stages?: OfficialStage[];
    pool_tables?: OfficialPoolTable[];
}

export interface TournamentFormat {
    race_to?: number;
    alternate_break?: boolean;
    ball_in_hand?: boolean;

    [key: string]: any;
}

// Stage Types
export interface OfficialStage {
    id: number;
    tournament_id: number;
    type: StageType;
    number: number;
    settings: StageSettings;
    participants_count?: number;
    matches_count?: number;
    is_complete?: boolean;
    created_at: string;
    updated_at: string;

    // Relations
    tournament?: OfficialTournament;
    participants?: OfficialParticipant[];
    matches?: OfficialMatch[];
}

export type StageType = 'single_elim' | 'double_elim' | 'swiss' | 'group' | 'round_robin' | 'custom';

export interface StageSettings {
    best_of?: number;
    third_place_match?: boolean;
    groups_count?: number;
    players_per_group?: number;
    advance_per_group?: number;
    race_to?: number;

    [key: string]: any;
}

// Match Types
export interface OfficialMatch {
    id: number;
    stage_id: number;
    table_id: number | null;
    round: number;
    bracket: string;
    scheduled_at: string | null;
    status: MatchStatus;
    metadata: MatchMetadata;
    created_at: string;
    updated_at: string;

    // Relations
    stage?: OfficialStage;
    table?: OfficialPoolTable;
    match_sets?: OfficialMatchSet[];
}

export type MatchStatus = 'pending' | 'ongoing' | 'finished' | 'walkover';

export interface MatchMetadata {
    match_number?: number;
    participant1_id?: number;
    participant2_id?: number;
    next_match_winner?: number | string;
    next_match_loser?: string;
    is_final?: boolean;
    is_semifinal?: boolean;
    is_grand_final?: boolean;
    is_grand_final_reset?: boolean;
    is_third_place?: boolean;
    is_drop_round?: boolean;
    is_group_match?: boolean;
    group?: string;
    position_in_round?: number;
    feeder_matches?: number[];
    walkover_winner_id?: number;
    walkover_at?: string;

    [key: string]: any;
}

export interface OfficialMatchSet {
    id: number;
    match_id: number;
    set_no: number;
    winner_participant_id: number;
    score_json: {
        participant1: number;
        participant2: number;
    };
    created_at: string;
    updated_at: string;
}

// Participant Types
export interface OfficialParticipant {
    id: number;
    stage_id: number;
    user_id: number | null;
    team_id: number | null;
    seed: number;
    rating_snapshot: number;
    display_name: string;
    is_bye: boolean;
    stats?: ParticipantStats;
    created_at: string;
    updated_at: string;

    // Relations
    stage?: OfficialStage;
    user?: User;
    team?: OfficialTeam;
}

export interface ParticipantStats {
    matches_won: number;
    matches_lost: number;
    sets_won: number;
    sets_lost: number;
    sets_difference: number;
}

// Team Types
export interface OfficialTeam {
    id: number;
    tournament_id: number;
    name: string;
    club_id: number | null;
    seed: number;
    created_at: string;
    updated_at: string;

    // Relations
    tournament?: OfficialTournament;
    club?: Club;
    members?: User[];
}

export interface OfficialTeamMember {
    id: number;
    team_id: number;
    user_id: number;
    created_at: string;
    updated_at: string;

    // Relations
    team?: OfficialTeam;
    user?: User;
}

// Table Types
export interface OfficialPoolTable {
    id: number;
    tournament_id: number;
    name: string;
    cloth_speed: string;
    created_at: string;
    updated_at: string;

    // Relations
    tournament?: OfficialTournament;
    matches?: OfficialMatch[];
}

export interface OfficialScheduleSlot {
    id: number;
    tournament_id: number;
    table_id: number;
    start_at: string;
    end_at: string;
    created_at: string;
    updated_at: string;

    // Relations
    tournament?: OfficialTournament;
    table?: OfficialPoolTable;
}

// Request Payloads
export interface CreateTournamentPayload {
    name: string;
    discipline: '8-ball' | '9-ball' | '10-ball' | 'snooker' | 'straight-pool';
    start_at: string;
    end_at: string;
    city_id?: number;
    club_id?: number;
    entry_fee?: number;
    prize_pool?: number;
    format?: TournamentFormat;
}

export interface UpdateTournamentPayload extends Partial<CreateTournamentPayload> {
}

export interface CreateStagePayload {
    type: StageType;
    settings?: StageSettings;
}

export interface UpdateStagePayload extends Partial<CreateStagePayload> {
}

export interface AddParticipantPayload {
    user_id?: number;
    team_id?: number;
    seed?: number;
    rating_snapshot?: number;
}

export interface ApplySeedingPayload {
    method: 'manual' | 'random' | 'rating' | 'previous';
    avoid_same_club?: boolean;
    group_count?: number;
    seeds?: number[];
    previous_tournament_id?: number;
}

export interface GenerateBracketPayload {
    include_third_place?: boolean;
    group_count?: number;
}

export interface UpdateMatchScorePayload {
    status: 'ongoing' | 'finished';
    sets: Array<{
        winner_id: number;
        score1: number;
        score2: number;
    }>;
}

export interface ScheduleMatchPayload {
    table_id: number;
    scheduled_at: string;
}

// WebSocket Event Types
export interface MatchUpdateEvent {
    match_id: number;
    scores: any;
    status: MatchStatus;
}

export interface ScheduleUpdateEvent {
    match_id: number;
    table_id: number;
    start_at: string;
}

export interface ParticipantUpdateEvent {
    participant_id: number;
    seed?: number;
    stats?: ParticipantStats;
}

export interface TournamentUpdateEvent {
    tournament_id: number;
    status?: 'upcoming' | 'ongoing' | 'completed';

    [key: string]: any;
}
