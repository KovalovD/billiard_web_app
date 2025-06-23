// resources/js/types/api.ts

export interface User {
    id: number;
    firstname: string;
    lastname: string;
    name?: string;
    email: string;
    phone?: string;
    is_admin: boolean;
    avatar?: string;
    home_city?: City | null;
    home_club?: Club | null;
}

export interface City {
    id: number;
    name: string;
    country: Country;
}

export interface Country {
    id: number;
    name: string;
    flag_path?: string;
}

export interface Club {
    id: number;
    name: string;
    city?: string;
    country?: string;
    tables?: ClubTable[];
}

export interface ClubTable {
    id: number;
    club_id: number;
    name: string;
    stream_url: string | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
    club?: Club;
}

export interface Game {
    id: number;
    name: string;
    type: string;
    is_multiplayer?: boolean;
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
    max_players: number;
    max_score: number;
    invite_days_expire: number;
    rating_change_for_winners_rule?: string | RatingRuleItem[];
    rating_change_for_losers_rule?: string | RatingRuleItem[];
}

export interface RatingRuleItem {
    range: [number, number];
    strong: number;
    weak: number;
}

export interface LoginResponse {
    user: User;
    token: string;
}

export interface League {
    id: number;
    name: string;
    picture?: string | null;
    details?: string | null;
    has_rating: boolean;
    started_at?: string | null;
    finished_at?: string | null;
    start_rating: number;
    rating_change_for_winners_rule: any;
    rating_change_for_losers_rule: any;
    created_at?: string;
    updated_at?: string;
    matches_count?: number;
    invite_days_expire: number;
    max_players: number;
    max_score: number;
    active_players?: number;
    game_id?: number;
    game?: string;
    game_type?: string;
    game_multiplayer?: boolean;
    grand_final_fund_accumulated?: number;
}

export interface Rating {
    id: number;
    player: Player;
    rating: number;
    position: number;
    is_active: boolean;
    is_confirmed: boolean;
    league_id: number;
    user_id: number;
    hasOngoingMatches: boolean;
    matches_count: number;
    wins_count: number;
    losses_count: number;
    league?: League;
    created_at?: string;
    last_player_rating_id?: number;
}

export interface Player {
    id: number;
    name: string;
}

export interface MatchGame {
    id: number;
    status: string;
    league_id: number;
    stream_url?: string | null;
    details?: string | null;
    first_rating_id: number;
    second_rating_id: number;
    first_user_score: number;
    second_user_score: number;
    winner_rating_id?: number;
    loser_rating_id?: number;
    result_confirmed?: Array<{ key: number; score: string }>;
    rating_change_for_winner?: number;
    rating_change_for_loser?: number;
    first_rating_before_game?: number;
    second_rating_before_game?: number;
    invitation_sent_at?: string;
    invitation_available_till?: string;
    invitation_accepted_at?: string;
    finished_at?: string;
    created_at?: string;
    updated_at?: string;
    club?: Club;
    league?: League;
    firstPlayer?: {
        user: User;
        rating: Rating;
    };
    secondPlayer?: {
        user: User;
        rating: Rating;
    };
}

export interface SendGamePayload {
    stream_url?: string;
    details?: string;
    club_id?: number | null;
}

export interface SendResultPayload {
    first_user_score: number;
    second_user_score: number;
}

export interface ApiError {
    status: any;
    response: any;
    message: string;
    data?: {
        errors?: Record<string, string[]>;
        message?: string;
    };
}

export interface CreateMultiplayerGamePayload {
    name: string;
    max_players?: number | null;
    registration_ends_at?: string | null;
    allow_player_targeting?: boolean;
    entrance_fee?: number;
    first_place_percent?: number;
    second_place_percent?: number;
    grand_final_percent?: number;
    penalty_fee?: number;
}

export interface MultiplayerGamePlayer {
    id: number;
    division: 'Elite' | 'S' | 'A' | 'B' | 'C' | '';
    user: User;
    lives: number;
    turn_order: number | null;
    cards: {
        skip_turn?: boolean;
        pass_turn?: boolean;
        hand_shot?: boolean;
        handicap?: boolean;
    };
    finish_position?: number | null;
    eliminated_at?: string | null;
    joined_at: string;
    is_current_turn?: boolean;
    rating_points?: number;
    prize_amount?: number;
    penalty_paid?: boolean;
    time_fund_contribution?: number;
}

export interface MultiplayerGame {
    id: number;
    official_rating_id: number;
    league_id: number;
    game_id: number;
    game_type: string;
    name: string;
    status: 'registration' | 'in_progress' | 'completed' | 'finished';
    initial_lives: number;
    max_players: number | null;
    registration_ends_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string;
    active_players_count: number;
    total_players_count: number;
    current_turn_player_id: number | null;
    is_registration_open: boolean;
    moderator_user_id: number | null;
    allow_player_targeting: boolean;
    is_current_user_moderator: boolean;
    entrance_fee: number;
    first_place_percent: number;
    second_place_percent: number;
    grand_final_percent: number;
    penalty_fee: number;
    financial_data?: {
        entrance_fee: number;
        total_prize_pool: number;
        first_place_prize: number;
        second_place_prize: number;
        grand_final_fund: number;
        penalty_fee: number;
        time_fund_total: number;
    };
    current_user_player?: {
        id: number;
        lives: number;
        turn_order: number | null;
        user: User;
        cards: {
            skip_turn?: boolean;
            pass_turn?: boolean;
            hand_shot?: boolean;
        };
        finish_position?: number | null;
        eliminated_at?: string | null;
        joined_at: string;
        is_current_turn: boolean;
        rating_points?: number;
        prize_amount?: number;
        penalty_paid?: boolean;
    };
    active_players: MultiplayerGamePlayer[];
    eliminated_players: MultiplayerGamePlayer[];
}

export interface RegisterCredentials {
    firstname: string;
    lastname: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
}

export interface GameTypeStats {
    [key: string]: {
        matches: number;
        wins: number;
        losses: number;
        win_rate: number;
    }
}

export interface UserStats {
    total_matches: number;
    completed_matches: number;
    wins: number;
    losses: number;
    win_rate: number;
    leagues_count: number;
    highest_rating: number;
    average_rating: number;
}

export interface OfficialRating {
    id: number;
    name: string;
    description: string | number;
    is_active: boolean;
    initial_rating: number;
    calculation_method: 'tournament_points' | 'elo' | 'custom';
    rating_rules?: any[] | null;
    players_count: number;
    tournaments_count: number;
    created_at: string;
    updated_at: string;
    game_type: 'pool' | 'pyramid' | 'snooker';
    game_type_name: string;
    available_games?: Game[];
    players?: OfficialRatingPlayer[];
    tournaments?: OfficialRatingTournament[];
    top_players?: OfficialRatingPlayer[];
}

export interface OfficialRatingPlayer {
    id: number;
    division: 'Elite' | 'S' | 'A' | 'B' | 'C';
    official_rating_id: number;
    user_id: number;
    rating_points: number;
    total_bonus_amount: number;
    total_achievement_amount: number;
    total_killer_pool_prize_amount: number;
    total_money_earned: number;
    total_prize_amount: number;
    position: number;
    tournaments_played: number;
    tournaments_won: number;
    win_rate: number;
    last_tournament_at?: string | null;
    is_active: boolean;
    is_top_player: boolean;
    created_at: string;
    updated_at: string;
    user?: User;
    official_rating?: OfficialRating;
}

export interface OfficialRatingTournament {
    id: number;
    name: string;
    start_date: string;
    end_date?: string;
    status: string;
    city?: string;
    country?: string;
    club?: string;
    players_count: number;
    rating_coefficient: number;
    is_counting: boolean;
}

export interface RatingDelta {
    current_position: number;
    position_before: number;
    position_delta: number;
    current_points: number;
    points_before: number;
    points_delta: number;
    bonus_amount_delta: number;
    achievement_amount_delta: number;
    prize_amount_delta: number;
}

export interface CreateOfficialRatingPayload {
    name: string;
    description: string | number;
    game_type: string;
    is_active?: boolean;
    initial_rating: number | null;
    calculation_method?: 'tournament_points' | 'elo' | 'custom';
    rating_rules: any[] | null | undefined;
}

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

// Tournament types
export type TournamentStatus = 'upcoming' | 'active' | 'completed' | 'cancelled';
export type TournamentStage = 'registration' | 'seeding' | 'group' | 'bracket' | 'completed';
export type TournamentType =
    'single_elimination'
    | 'double_elimination'
    | 'double_elimination_full'
    | 'round_robin'
    | 'groups'
    | 'groups_playoff'
    | 'team_groups_playoff';
export type SeedingMethod = 'random' | 'rating' | 'manual';
export type TournamentPlayerStatus = 'applied' | 'confirmed' | 'rejected' | 'eliminated' | 'dnf';
export type EliminationRound =
    'groups'
    | 'round_128'
    | 'round_64'
    | 'round_32'
    | 'round_16'
    | 'quarterfinals'
    | 'semifinals'
    | 'finals'
    | 'third_place';
export type MatchStage = 'bracket' | 'group' | 'third_place';
export type MatchStatus = 'pending' | 'ready' | 'in_progress' | 'verification' | 'completed' | 'cancelled';
export type BracketSide = 'upper' | 'lower';
export type BracketType = 'single' | 'double_upper' | 'double_lower';

export interface Tournament {
    id: number;
    name: string;
    regulation?: string;
    details?: string;
    status: TournamentStatus;
    stage: TournamentStage;
    status_display: string;
    stage_display: string;
    game_id: number;
    city_id?: number;
    club_id?: number;
    start_date: string;
    end_date: string;
    application_deadline?: string;
    max_participants?: number;
    entry_fee: number;
    prize_pool: number;
    prize_distribution?: number[];
    place_prizes?: number[];
    place_bonuses?: number[];
    place_rating_points?: number[];
    organizer?: string;
    format?: string;
    tournament_type: TournamentType;
    tournament_type_display: string;
    group_size_min?: number;
    group_size_max?: number;
    playoff_players_per_group?: number;
    races_to: number;
    has_third_place_match: boolean;
    seeding_method: SeedingMethod;
    seeding_method_display: string;
    players_count: number;
    confirmed_players_count: number;
    pending_applications_count: number;
    requires_application: boolean;
    seeding_completed: boolean;
    brackets_generated: boolean;
    auto_approve_applications: boolean;
    is_old: boolean;
    seeding_completed_at?: string;
    groups_completed_at?: string;
    created_at: string;
    updated_at: string;

    // Relations
    game?: Game;
    city?: City;
    club?: Club;
    players?: TournamentPlayer[];
    matches?: TournamentMatch[];
    groups?: TournamentGroup[];
    brackets?: TournamentBracket[];
    table_widgets?: TournamentTableWidget[];
    official_ratings?: Array<{
        id: number;
        name: string;
        rating_coefficient: number;
        is_counting: boolean;
    }>;
}

export interface TournamentPlayer {
    is_winner: boolean;
    id: number;
    tournament_id: number;
    user_id: number;
    position?: number;
    seed_number?: number;
    group_code?: string;
    group_position?: number;
    rating?: number;
    is_confirmed: boolean;
    is_pending: boolean;
    is_rejected: boolean;
    group_wins: number;
    group_losses: number;
    group_games_diff: number;
    elimination_round?: EliminationRound;
    elimination_round_display?: string;
    rating_points: number;
    prize_amount: number;
    bonus_amount: number;
    achievement_amount: number;
    total_amount: number;
    status: TournamentPlayerStatus;
    status_display: string;
    registered_at?: string;
    applied_at?: string;
    confirmed_at?: string;
    rejected_at?: string;
    created_at: string;
    updated_at: string;

    // Relations
    user?: User;
    tournament?: Tournament;
}

export interface TournamentMatch {
    id: number;
    tournament_id: number;
    match_code?: string;
    stage: MatchStage;
    stage_display: string;
    round?: EliminationRound;
    round_display?: string;
    bracket_position?: number;
    bracket_side?: BracketSide;
    bracket_side_display?: string;
    player1_id?: number;
    player2_id?: number;
    winner_id?: number;
    player1_score: number;
    player2_score: number;
    races_to?: number;
    previous_match1_id?: number;
    previous_match2_id?: number;
    next_match_id?: number;
    loser_next_match_id?: number;
    club_table_id?: number;
    stream_url?: string;
    status: MatchStatus;
    status_display: string;
    scheduled_at?: string;
    started_at?: string;
    completed_at?: string;
    admin_notes?: string;
    created_at: string;
    updated_at: string;

    // Relations
    player1?: User;
    player2?: User;
    winner?: User;
    club_table?: ClubTable;
    previous_match1?: TournamentMatch;
    previous_match2?: TournamentMatch;
    tournament?: {
        id: number;
        name: string;
        races_to: number;
    };
}

export interface TournamentGroup {
    id: number;
    tournament_id: number;
    group_code: string;
    group_size: number;
    advance_count: number;
    is_completed: boolean;
    created_at: string;
    updated_at: string;

    // Relations
    players?: TournamentPlayer[];
    matches?: TournamentMatch[];
    tournament?: {
        id: number;
        name: string;
    };
}

export interface TournamentBracket {
    id: number;
    tournament_id: number;
    bracket_type: BracketType;
    bracket_type_display: string;
    total_rounds: number;
    players_count: number;
    bracket_structure?: any;
    created_at: string;
    updated_at: string;

    // Relations
    matches?: TournamentMatch[];
    tournament?: {
        id: number;
        name: string;
        tournament_type: TournamentType;
    };
}

export interface TournamentTableWidget {
    id: number;
    tournament_id: number;
    club_table_id: number;
    current_match_id?: number;
    widget_url?: string;
    player_widget_url?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;

    // Relations
    club_table?: ClubTable;
    current_match?: TournamentMatch;
    tournament?: {
        id: number;
        name: string;
    };
}

export interface CreateTournamentPayload {
    name: string;
    regulation?: string;
    details?: string;
    game_id: number;
    city_id?: number;
    club_id?: number;
    start_date: string;
    end_date: string;
    application_deadline?: string;
    max_participants?: number;
    entry_fee?: number;
    prize_pool?: number;
    prize_distribution?: number[];
    place_prizes?: number[];
    place_bonuses?: number[];
    place_rating_points?: number[];
    organizer?: string;
    format?: string;
    tournament_type: TournamentType;
    group_size_min?: number;
    group_size_max?: number;
    playoff_players_per_group?: number;
    races_to?: number;
    has_third_place_match?: boolean;
    seeding_method?: SeedingMethod;
    requires_application?: boolean;
    auto_approve_applications?: boolean;
    official_rating_id?: number;
    rating_coefficient?: number;
}

export interface UpdateTournamentMatchPayload {
    player1_score?: number;
    player2_score?: number;
    club_table_id?: number;
    stream_url?: string;
    status?: MatchStatus;
    scheduled_at?: string;
    admin_notes?: string;
}

export interface StartTournamentMatchPayload {
    club_table_id: number;
    stream_url?: string;
}

export interface FinishTournamentMatchPayload {
    player1_score: number;
    player2_score: number;
}

export interface UpdateTournamentPlayerSeedingPayload {
    seed_number: number;
}

export interface AssignTournamentGroupPayload {
    group_code: string;
}
