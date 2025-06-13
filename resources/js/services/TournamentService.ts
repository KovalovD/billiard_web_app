// resources/js/services/TournamentService.ts
import type {
    Tournament,
    TournamentPlayer,
    Group,
    BracketNode
} from '@/types/tournament';
import type {
    CreateTournamentPayload,
} from '@/types/api';

export interface TournamentFormat {
    type: 'single_elimination' | 'double_elimination' | 'group_stage' | 'group_playoff';
    settings: {
        rounds?: number;
        bestOf?: number;
        lowerBracket?: boolean;
        groupCount?: number;
        playoffSize?: number;
    };
}

export interface SeedingOptions {
    type: 'manual' | 'random' | 'rating_based';
    customOrder?: number[];
}

export interface TeamConfig {
    teamSize: number;
    maxTeams: number;
    allowMixedTeams: boolean;
}

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

export interface GroupStanding {
    playerId: number;
    player: TournamentPlayer;
    wins: number;
    losses: number;
    framesWon: number;
    framesLost: number;
    frameDifference: number;
    points: number;
    position: number;
}

class TournamentService {
    // Existing methods from the codebase
    async fetchTournaments(filters?: Record<string, any>): Promise<Tournament[]> {
        // Mock data for now
        return [
            {
                id: 1,
                name: "Spring Open 2024",
                status: 'upcoming',
                game_id: 1,
                start_date: "2024-03-15T10:00:00Z",
                end_date: "2024-03-17T18:00:00Z",
                max_participants: 32,
                entry_fee: 100,
                prize_pool: 2500,
                players_count: 24,
                confirmed_players_count: 20,
                pending_applications_count: 4
            }
        ] as Tournament[];
    }

    async createTournament(payload: CreateTournamentPayload & {
        format: TournamentFormat;
        seeding: SeedingOptions;
        teamConfig?: TeamConfig;
    }): Promise<Tournament> {
        // Mock creation
        await new Promise(resolve => setTimeout(resolve, 500));

        return {
            id: Date.now(),
            ...payload,
            status: 'upcoming',
            players_count: 0,
            confirmed_players_count: 0,
            pending_applications_count: 0
        } as Tournament;
    }

    // Player Management
    async searchUsers(query: string): Promise<any[]> {
        // Mock user search
        const mockUsers = [
            {id: 1, firstname: 'John', lastname: 'Doe', email: 'john@example.com', rating: 1850},
            {id: 2, firstname: 'Jane', lastname: 'Smith', email: 'jane@example.com', rating: 1720},
            {id: 3, firstname: 'Mike', lastname: 'Johnson', email: 'mike@example.com', rating: 1950}
        ];

        return mockUsers.filter(user =>
            user.firstname.toLowerCase().includes(query.toLowerCase()) ||
            user.lastname.toLowerCase().includes(query.toLowerCase()) ||
            user.email.toLowerCase().includes(query.toLowerCase())
        );
    }

    async addPlayerToTournament(tournamentId: number, playerId: number): Promise<TournamentPlayer> {
        await new Promise(resolve => setTimeout(resolve, 300));

        return {
            id: Date.now(),
            tournament_id: tournamentId,
            user_id: playerId,
            status: 'confirmed',
            seed_position: null,
            group_id: null
        } as TournamentPlayer;
    }

    async removePlayerFromTournament(tournamentId: number, playerId: number): Promise<void> {
        await new Promise(resolve => setTimeout(resolve, 300));
    }

    // Bracket Management
    async generateBracket(tournamentId: number, format: TournamentFormat, seeding: SeedingOptions): Promise<BracketNode[]> {
        await new Promise(resolve => setTimeout(resolve, 500));

        // Mock bracket generation
        const mockBracket: BracketNode[] = [
            {
                id: 1,
                round: 1,
                position: 1,
                playerA: {id: 1, name: 'John Doe', seed: 1},
                playerB: {id: 8, name: 'Mike Wilson', seed: 8},
                winner: null,
                nextMatchId: 5
            },
            {
                id: 2,
                round: 1,
                position: 2,
                playerA: {id: 4, name: 'Sarah Connor', seed: 4},
                playerB: {id: 5, name: 'Alex Turner', seed: 5},
                winner: null,
                nextMatchId: 5
            }
        ];

        return mockBracket;
    }

    async updateBracketNode(tournamentId: number, nodeId: number, update: Partial<BracketNode>): Promise<BracketNode> {
        await new Promise(resolve => setTimeout(resolve, 300));

        return {
            id: nodeId,
            ...update
        } as BracketNode;
    }

    // Group Stage Management
    async createGroups(tournamentId: number, groupCount: number, players: TournamentPlayer[]): Promise<Group[]> {
        await new Promise(resolve => setTimeout(resolve, 500));

        const groups: Group[] = [];
        const playersPerGroup = Math.ceil(players.length / groupCount);

        for (let i = 0; i < groupCount; i++) {
            const groupPlayers = players.slice(i * playersPerGroup, (i + 1) * playersPerGroup);

            groups.push({
                id: i + 1,
                name: `Group ${String.fromCharCode(65 + i)}`, // A, B, C, etc.
                tournament_id: tournamentId,
                players: groupPlayers,
                standings: groupPlayers.map((player, index) => ({
                    playerId: player.user_id,
                    player,
                    wins: 0,
                    losses: 0,
                    framesWon: 0,
                    framesLost: 0,
                    frameDifference: 0,
                    points: 0,
                    position: index + 1
                }))
            });
        }

        return groups;
    }

    async generateGroupMatches(tournamentId: number, groupId: number, format: 'round_robin' | 'swiss'): Promise<MatchSchedule[]> {
        await new Promise(resolve => setTimeout(resolve, 400));

        // Mock round-robin matches
        return [
            {
                id: 1,
                tournamentId,
                round: 1,
                tableNumber: 1,
                scheduledAt: new Date(Date.now() + 86400000).toISOString(), // Tomorrow
                playerA: {id: 1, name: 'John Doe'} as TournamentPlayer,
                playerB: {id: 2, name: 'Jane Smith'} as TournamentPlayer,
                status: 'scheduled'
            }
        ];
    }

    async updateGroupStandings(tournamentId: number, groupId: number): Promise<GroupStanding[]> {
        await new Promise(resolve => setTimeout(resolve, 300));

        // Mock updated standings
        return [
            {
                playerId: 1,
                player: {id: 1, name: 'John Doe'} as TournamentPlayer,
                wins: 2,
                losses: 0,
                framesWon: 6,
                framesLost: 2,
                frameDifference: 4,
                points: 6,
                position: 1
            }
        ];
    }

    // Match Scheduling
    async fetchSchedule(tournamentId: number, date?: string): Promise<MatchSchedule[]> {
        await new Promise(resolve => setTimeout(resolve, 300));

        return [
            {
                id: 1,
                tournamentId,
                round: 1,
                tableNumber: 1,
                scheduledAt: new Date().toISOString(),
                playerA: {id: 1, name: 'John Doe'} as TournamentPlayer,
                playerB: {id: 2, name: 'Jane Smith'} as TournamentPlayer,
                status: 'scheduled'
            }
        ];
    }

    async updateMatchSchedule(tournamentId: number, matchId: number, updates: Partial<MatchSchedule>): Promise<MatchSchedule> {
        await new Promise(resolve => setTimeout(resolve, 300));

        return {
            id: matchId,
            tournamentId,
            ...updates
        } as MatchSchedule;
    }

    // Results Management
    async submitMatchResult(tournamentId: number, matchId: number, result: {
        scoreA: number;
        scoreB: number;
        frames: Array<{ frameNumber: number; winner: 'A' | 'B' }>;
    }): Promise<MatchSchedule> {
        await new Promise(resolve => setTimeout(resolve, 400));

        return {
            id: matchId,
            tournamentId,
            status: 'completed',
            ...result
        } as MatchSchedule;
    }

    async getTournamentResults(tournamentId: number): Promise<{
        finalStandings: TournamentPlayer[];
        statistics: Record<string, any>;
        prizeDistribution: Array<{ position: number; amount: number; playerId: number }>;
    }> {
        await new Promise(resolve => setTimeout(resolve, 500));

        return {
            finalStandings: [
                {
                    id: 1,
                    tournament_id: tournamentId,
                    user_id: 1,
                    position: 1,
                    prize_amount: 1000,
                    rating_points: 50
                } as TournamentPlayer
            ],
            statistics: {
                totalMatches: 15,
                totalFrames: 89,
                averageMatchDuration: '45 minutes'
            },
            prizeDistribution: [
                {position: 1, amount: 1000, playerId: 1},
                {position: 2, amount: 600, playerId: 2},
                {position: 3, amount: 400, playerId: 3}
            ]
        };
    }
}

export const tournamentService = new TournamentService();
export default tournamentService;
