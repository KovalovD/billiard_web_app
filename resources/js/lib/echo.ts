// resources/js/lib/echo.ts

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import {apiToken} from '@/lib/apiClient';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo;
    }
}

// Make Pusher available globally
window.Pusher = Pusher;

// Initialize Echo instance
export function initializeEcho(): Echo {
    if (window.Echo) {
        return window.Echo;
    }

    const token = apiToken.value;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS: true,
        encrypted: true,
        auth: {
            headers: {
                Authorization: token ? `Bearer ${token}` : ''
            }
        },
        authEndpoint: '/broadcasting/auth',
        // Additional Pusher options
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        // Custom namespace for Laravel events
        namespace: 'App.OfficialTournaments.Events'
    });

    // Log connection status
    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('✅ Connected to Pusher');
    });

    window.Echo.connector.pusher.connection.bind('error', (error: any) => {
        console.error('❌ Pusher connection error:', error);
    });

    return window.Echo;
}

// Disconnect Echo
export function disconnectEcho(): void {
    if (window.Echo) {
        window.Echo.disconnect();
        console.log('🔌 Disconnected from Pusher');
    }
}

// Reconnect Echo with new token
export function reconnectEcho(): void {
    disconnectEcho();

    // Small delay to ensure proper disconnection
    setTimeout(() => {
        initializeEcho();
    }, 100);
}

// Update auth token for Echo
export function updateEchoAuth(newToken: string | null): void {
    if (window.Echo) {
        window.Echo.options.auth.headers.Authorization = newToken ? `Bearer ${newToken}` : '';

        // Reconnect to apply new auth
        reconnectEcho();
    }
}

// Helper to join private channel with error handling
export function joinPrivateChannel(channelName: string, listeners: Record<string, Function> = {}): any {
    if (!window.Echo) {
        console.error('Echo not initialized');
        return null;
    }

    try {
        const channel = window.Echo.private(channelName);

        // Attach listeners
        Object.entries(listeners).forEach(([event, callback]) => {
            channel.listen(event, callback);
        });

        return channel;
    } catch (error) {
        console.error(`Failed to join private channel ${channelName}:`, error);
        return null;
    }
}

// Helper to join presence channel with error handling
export function joinPresenceChannel(channelName: string, listeners: Record<string, Function> = {}): any {
    if (!window.Echo) {
        console.error('Echo not initialized');
        return null;
    }

    try {
        const channel = window.Echo.join(channelName);

        // Attach listeners
        Object.entries(listeners).forEach(([event, callback]) => {
            if (event === 'here' || event === 'joining' || event === 'leaving') {
                channel[event](callback);
            } else {
                channel.listen(event, callback);
            }
        });

        return channel;
    } catch (error) {
        console.error(`Failed to join presence channel ${channelName}:`, error);
        return null;
    }
}

// Helper to leave channel
export function leaveChannel(channelName: string): void {
    if (window.Echo) {
        window.Echo.leave(channelName);
    }
}

// Tournament-specific channel helpers
export const TournamentChannels = {
    // Join tournament updates channel
    tournament(tournamentId: number | string) {
        return joinPrivateChannel(`tournament.${tournamentId}`, {
            '.match.updated': (data: any) => {
                window.dispatchEvent(new CustomEvent('echo:match.updated', {detail: data}));
            },
            '.schedule.updated': (data: any) => {
                window.dispatchEvent(new CustomEvent('echo:schedule.updated', {detail: data}));
            },
            '.participant.updated': (data: any) => {
                window.dispatchEvent(new CustomEvent('echo:participant.updated', {detail: data}));
            }
        });
    },

    // Join stage updates channel
    stage(stageId: number | string) {
        return joinPrivateChannel(`stage.${stageId}`, {
            '.standings.updated': (data: any) => {
                window.dispatchEvent(new CustomEvent('echo:standings.updated', {detail: data}));
            },
            '.bracket.updated': (data: any) => {
                window.dispatchEvent(new CustomEvent('echo:bracket.updated', {detail: data}));
            }
        });
    },

    // Join match updates channel (for spectators)
    match(matchId: number | string) {
        return joinPresenceChannel(`match.${matchId}`, {
            here: (users: any[]) => {
                window.dispatchEvent(new CustomEvent('echo:match.spectators', {detail: {users}}));
            },
            joining: (user: any) => {
                window.dispatchEvent(new CustomEvent('echo:match.spectator.joined', {detail: {user}}));
            },
            leaving: (user: any) => {
                window.dispatchEvent(new CustomEvent('echo:match.spectator.left', {detail: {user}}));
            },
            '.score.updated': (data: any) => {
                window.dispatchEvent(new CustomEvent('echo:match.score.updated', {detail: data}));
            }
        });
    },

    // Leave all tournament-related channels
    leaveAll(tournamentId?: number | string, stageId?: number | string, matchId?: number | string) {
        if (tournamentId) leaveChannel(`private-tournament.${tournamentId}`);
        if (stageId) leaveChannel(`private-stage.${stageId}`);
        if (matchId) leaveChannel(`presence-match.${matchId}`);
    }
};

// Auto-initialize on import if in browser environment
if (typeof window !== 'undefined' && apiToken.value) {
    initializeEcho();
}
