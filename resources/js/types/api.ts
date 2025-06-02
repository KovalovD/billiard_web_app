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
    user: User;
    lives: number;
    turn_order: number | null;
    cards: {
        skip_turn?: boolean;
        pass_turn?: boolean;
        hand_shot?: boolean;
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
    league_id: number;
    game_id: number;
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
    game?: Game;
    players?: OfficialRatingPlayer[];
    tournaments?: OfficialRatingTournament[];
    top_players?: OfficialRatingPlayer[];
}

export interface OfficialRatingPlayer {
    id: number;
    official_rating_id: number;
    user_id: number;
    rating_points: number;
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

export interface CreateOfficialRatingPayload {
    name: string;
    description: string | number;
    game_id: number;
    is_active: boolean;
    initial_rating: number | null;
    calculation_method?: 'tournament_points' | 'elo' | 'custom';
    rating_rules: any[] | null | undefined;
}


export interface Tournament {
    id: number;
    name: string;
    regulation?: string;
    details?: string;
    status: 'upcoming' | 'active' | 'completed' | 'cancelled';
    status_display: string;
    game_id: number;
    city_id?: number;
    club_id?: number;
    start_date: string; // Now datetime string
    end_date: string;   // Now datetime strin
    application_deadline?: string; // datetime string
    max_participants?: number;
    entry_fee: number;
    prize_pool: number;
    prize_distribution?: number[];
    organizer?: string;
    format?: string;
    players_count: number;
    confirmed_players_count: number;
    pending_applications_count: number;
    requires_application: boolean;
    auto_approve_applications: boolean;
    is_registration_open: boolean;
    can_accept_applications: boolean;
    is_active: boolean;
    is_completed: boolean;
    created_at: string;
    updated_at: string;

    // Relations
    game?: Game;
    city?: City;
    club?: Club;
    winner?: TournamentPlayer;
    top_players?: TournamentPlayer[];
    official_ratings?: Array<{
        id: number;
        name: string;
        rating_coefficient: number;
        is_counting: boolean;
    }>;
}

export interface TournamentPlayer {
    id: number;
    tournament_id: number;
    user_id: number;
    position?: number;
    rating_points: number;
    prize_amount: number;
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
    created_at: string;
    updated_at: string;

    // Relations
    user?: User;
    tournament?: Tournament;
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
    organizer?: string;
    format?: string;
    official_rating_id?: number;
    rating_coefficient?: number;
}
