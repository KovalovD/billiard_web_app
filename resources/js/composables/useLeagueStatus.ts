import type {League} from '@/types/api';
import {CalendarIcon} from 'lucide-vue-next';

export function useLeagueStatus() {
    const getLeagueStatus = (league: League | null) => {
        if (!league) return null;

        const now = new Date();
        const started = league.started_at ? new Date(league.started_at) : null;
        const finished = league.finished_at ? new Date(league.finished_at) : null;

        if (finished && finished < now) {
            return {
                text: 'Завершено',
                class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                icon: CalendarIcon,
            };
        } else if (started && started > now) {
            return {
                text: 'Незабаром',
                class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                icon: CalendarIcon,
            };
        } else if (started && started <= now && (!finished || finished > now)) {
            return {
                text: 'Активна',
                class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                icon: CalendarIcon,
            };
        }
        return null;
    };

    const canJoinLeague = (league: League | null) => {
        if (!league || !league.has_rating) return false;

        const now = new Date();
        const finished = league.finished_at ? new Date(league.finished_at) : null;
        const started = league.started_at ? new Date(league.started_at) : null;

        // Не дозволяти приєднання, якщо ліга завершилася
        if (finished && finished < now) return false;

        // Не дозволяти приєднання, якщо ліга ще не почалася
        if (started && started > now) return false;

        // Перевірити обмеження на кількість гравців
        return !(league.max_players > 0 && (league.active_players || 0) >= league.max_players);
    };

    const getPlayersText = (league: League | null) => {
        if (!league) return 'Н/Д';

        const active = league.active_players || 0;
        if (league.max_players > 0) {
            return `${active}/${league.max_players} гравців`;
        }
        return `${active} гравців`;
    };

    const getJoinErrorMessage = (league: League | null) => {
        if (!league) return '';

        const now = new Date();
        const finished = league.finished_at ? new Date(league.finished_at) : null;
        const started = league.started_at ? new Date(league.started_at) : null;

        if (finished && finished < now) {
            return 'Ця ліга завершена';
        } else if (started && started > now) {
            return 'Ліга ще не розпочалася';
        } else if (league.max_players > 0 && (league.active_players || 0) >= league.max_players) {
            return `Ліга заповнена (${league.active_players}/${league.max_players})`;
        } else if (!league.has_rating) {
            return 'Рейтинг вимкнено для цієї ліги';
        }
        return '';
    };

    return {
        getLeagueStatus,
        canJoinLeague,
        getPlayersText,
        getJoinErrorMessage,
    };
}
