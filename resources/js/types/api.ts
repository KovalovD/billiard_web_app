// resources/js/Types/api.ts

// --- Auth ---
// Определяем структуру пользователя на основе UserFactory и ожиданий
export interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    phone?: string | null;
    email_verified_at?: string | null;
    is_admin?: boolean; // Убедись, что бэкенд отдает это поле
    // Добавь другие поля при необходимости (например, name, если бэкенд его формирует)
    name?: string; // Добавим опционально, если UserResource его создает
}

// Ответ API логина теперь может просто содержать пользователя (или быть пустым при успехе)
// Сессия устанавливается через cookie
export interface LoginResponse {
    user?: User; // Пользователь опционален, главное - успешный статус 2xx
}


// --- Leagues & Games ---
// Структура игры
export interface Game {
    id: number;
    name: string;
    type?: string; // GameType enum (Pool, Snooker, etc.) - опционально, если не всегда приходит
    is_multiplayer?: boolean;
}

// Структура элемента правил рейтинга
export interface RatingRuleItem {
    range: [number, number];
    strong: number;
    weak: number;
}

// Структура Лиги
export interface League {
    id: number;
    name: string;
    picture: string | null;
    details: string | null;
    has_rating: boolean;
    started_at: string | null; // Формат 'YYYY-MM-DD HH:MM:SS' или ISO
    finished_at: string | null; // Формат 'YYYY-MM-DD HH:MM:SS' или ISO
    start_rating: number;
    rating_change_for_winners_rule: RatingRuleItem[];
    rating_change_for_losers_rule: RatingRuleItem[];
    created_at: string | null;
    updated_at: string | null;
    matches_count?: number; // Сделаем опциональным на всякий случай
    // game - строка, как ты указал в последнем JSON
    game: string | null;
    // game_id нужен для форм
    game_id: number;
    rating_type?: string; // Тип рейтинга, например 'elo'
}

// Структура для форм создания/редактирования Лиги
export interface LeaguePayload {
    name: string;
    game_id: number | null;
    picture?: string | null; // Для загрузки файла нужна отдельная логика
    details?: string | null;
    has_rating?: boolean;
    started_at?: string | null; // Отправлять в ISO или 'YYYY-MM-DD HH:MM:SS'
    finished_at?: string | null;
    start_rating: number;
    // Правила рейтинга отправляем как JSON строки для простоты Textarea
    rating_change_for_winners_rule?: string | null;
    rating_change_for_losers_rule?: string | null;
}

// --- Players & Ratings ---
// Структура игрока (из RatingResource)
export interface Player {
    id: number; // User ID
    name: string; // Готовое имя
}

// Структура Рейтинга (из эндпоинта /players)
export interface Rating {
    id: number; // ID самой записи рейтинга
    player: Player; // Вложенный объект игрока
    rating: number;
    position: number;
    is_active?: boolean;
}

// --- Matches ---
// Предполагаемая структура матча (нужно уточнять по MatchGameResource/Model)
export interface MatchGame {
    id: number;
    league_id: number;
    sender_id: number;
    receiver_id: number;
    sender?: Player | null; // Опционально
    receiver?: Player | null; // Опционально
    status: 'pending' | 'accepted' | 'declined' | 'finished' | 'result_pending' | string; // Добавим string для гибкости
    sender_score?: number | null;
    receiver_score?: number | null;
    details?: string | null;
    stream_url?: string | null;
    club_id?: number | null;
    played_at?: string | null;
    created_at?: string;
    updated_at?: string;
}

// Структура для отправки вызова
export interface SendGamePayload {
    stream_url?: string | null;
    details?: string | null;
    club_id?: number | string | null;
}

// Структура для отправки результата
export interface SendResultPayload {
    first_user_score: number;
    second_user_score: number;
}

// --- API Error ---
// Ошибка валидации Laravel
export interface ApiValidationError {
    message: string; // "The given data was invalid."
    errors: Record<string, string[]>; // { field_name: ["Error message 1"] }
}

// Общий тип ошибки API клиента
export interface ApiError extends Error {
    response?: import('axios').AxiosResponse; // Ответ от axios
    data?: { // Данные из тела ответа ошибки
        message?: string;
        errors?: Record<string, string[]>;
        // Другие возможные поля
    }
}

// Тип для ответа API, который оборачивает данные в ключ 'data'
// (например, от стандартных ResourceCollection)
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

// Тип для ответа API, который оборачивает один ресурс в ключ 'data'
// (например, от стандартного Resource)
export interface ApiItemResponse<T> {
    data: T;
}
