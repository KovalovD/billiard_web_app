<template>
    <!-- This component doesn't render anything, it just handles WebSocket events -->
    <div></div>
</template>

<script lang="ts" setup>
import {onMounted, onUnmounted, watch} from 'vue';
import type {
    MatchUpdateEvent,
    ParticipantUpdateEvent,
    ScheduleUpdateEvent,
    TournamentUpdateEvent
} from '@/types/tournament';
import {useTournamentStore} from "@/stores/useTournamentStore";

// Props
const props = defineProps<{
    tournamentId?: number | string;
    stageId?: number | string;
    autoConnect?: boolean;
}>();

// Store
const tournamentStore = useTournamentStore();

// Echo channels
let tournamentChannel: any = null;
let stageChannel: any = null;
let publicChannel: any = null;

// Initialize Echo connection
function initializeEcho() {
    if (!window.Echo) {
        console.error('Laravel Echo not initialized');
        return;
    }

    // Connect to tournament channel if ID provided
    if (props.tournamentId) {
        connectTournamentChannel(props.tournamentId);
    }

    // Connect to stage channel if ID provided
    if (props.stageId) {
        connectStageChannel(props.stageId);
    }

    // Always connect to public channel for general updates
    connectPublicChannel();
}

// Connect to private tournament channel
function connectTournamentChannel(tournamentId: number | string) {
    try {
        // Disconnect from previous channel if exists
        if (tournamentChannel) {
            window.Echo.leave(`private-tournament.${tournamentChannel.name.split('.')[1]}`);
        }

        // Connect to new channel
        tournamentChannel = window.Echo.private(`private-tournament.${tournamentId}`)
            .listen('.match.updated', (event: MatchUpdateEvent) => {
                handleMatchUpdate(event);
            })
            .listen('.schedule.updated', (event: ScheduleUpdateEvent) => {
                handleScheduleUpdate(event);
            })
            .listen('.participant.updated', (event: ParticipantUpdateEvent) => {
                handleParticipantUpdate(event);
            })
            .listen('.tournament.status.changed', (event: TournamentUpdateEvent) => {
                handleTournamentStatusChange(event);
            })
            .listen('.bracket.regenerated', () => {
                handleBracketRegenerated();
            })
            .error((error: any) => {
                console.error('Tournament channel error:', error);
            });

        console.log(`Connected to tournament channel: ${tournamentId}`);
    } catch (error) {
        console.error('Failed to connect to tournament channel:', error);
    }
}

// Connect to private stage channel
function connectStageChannel(stageId: number | string) {
    try {
        // Disconnect from previous channel if exists
        if (stageChannel) {
            window.Echo.leave(`private-stage.${stageChannel.name.split('.')[1]}`);
        }

        // Connect to new channel
        stageChannel = window.Echo.private(`private-stage.${stageId}`)
            .listen('.standings.updated', () => {
                handleStandingsUpdate();
            })
            .listen('.participants.changed', () => {
                handleParticipantsChanged();
            })
            .error((error: any) => {
                console.error('Stage channel error:', error);
            });

        console.log(`Connected to stage channel: ${stageId}`);
    } catch (error) {
        console.error('Failed to connect to stage channel:', error);
    }
}

// Connect to public channel for general updates
function connectPublicChannel() {
    try {
        publicChannel = window.Echo.channel('tournaments')
            .listen('.tournament.created', (event: { tournament: any }) => {
                handleTournamentCreated(event.tournament);
            })
            .listen('.tournament.started', (event: TournamentUpdateEvent) => {
                handleTournamentStarted(event);
            })
            .listen('.tournament.completed', (event: TournamentUpdateEvent) => {
                handleTournamentCompleted(event);
            });

        console.log('Connected to public tournaments channel');
    } catch (error) {
        console.error('Failed to connect to public channel:', error);
    }
}

// Event Handlers
function handleMatchUpdate(event: MatchUpdateEvent) {
    console.log('Match updated:', event);

    // Fetch updated match data
    if (props.tournamentId) {
        tournamentStore.fetchMatch(props.tournamentId, event.match_id)
            .catch(error => console.error('Failed to fetch updated match:', error));
    }

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:match-updated', {
        detail: event
    }));
}

function handleScheduleUpdate(event: ScheduleUpdateEvent) {
    console.log('Schedule updated:', event);

    // Update match in store
    const match = tournamentStore.getMatchById(event.match_id);
    if (match) {
        tournamentStore.handleScheduleUpdate(event);
    }

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:schedule-updated', {
        detail: event
    }));
}

function handleParticipantUpdate(event: ParticipantUpdateEvent) {
    console.log('Participant updated:', event);

    // Fetch updated participant data
    if (props.tournamentId && props.stageId) {
        tournamentStore.fetchParticipants(props.tournamentId, props.stageId)
            .catch(error => console.error('Failed to fetch updated participants:', error));
    }

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:participant-updated', {
        detail: event
    }));
}

function handleTournamentStatusChange(event: TournamentUpdateEvent) {
    console.log('Tournament status changed:', event);

    // Update tournament in store if it's the current one
    if (tournamentStore.currentTournament?.id === event.tournament_id) {
        if (event.status) {
            tournamentStore.currentTournament.status = event.status;
        }
    }

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:status-changed', {
        detail: event
    }));
}

function handleBracketRegenerated() {
    console.log('Bracket regenerated');

    // Reload all matches for current stage
    if (props.tournamentId && props.stageId) {
        tournamentStore.fetchStage(props.tournamentId, props.stageId)
            .catch(error => console.error('Failed to reload stage after bracket regeneration:', error));
    }

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:bracket-regenerated'));
}

function handleStandingsUpdate() {
    console.log('Standings updated');

    // Emit event for other components to refresh standings
    window.dispatchEvent(new CustomEvent('tournament:standings-updated'));
}

function handleParticipantsChanged() {
    console.log('Participants changed');

    // Reload participants for current stage
    if (props.tournamentId && props.stageId) {
        tournamentStore.fetchParticipants(props.tournamentId, props.stageId)
            .catch(error => console.error('Failed to reload participants:', error));
    }

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:participants-changed'));
}

function handleTournamentCreated(tournament: any) {
    console.log('New tournament created:', tournament);

    // Add to tournaments list if we're on the listing page
    tournamentStore.tournaments.unshift(tournament);

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:created', {
        detail: tournament
    }));
}

function handleTournamentStarted(event: TournamentUpdateEvent) {
    console.log('Tournament started:', event);

    // Update tournament status in list
    const tournament = tournamentStore.tournaments.find(t => t.id === event.tournament_id);
    if (tournament) {
        tournament.status = 'ongoing';
    }

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:started', {
        detail: event
    }));
}

function handleTournamentCompleted(event: TournamentUpdateEvent) {
    console.log('Tournament completed:', event);

    // Update tournament status in list
    const tournament = tournamentStore.tournaments.find(t => t.id === event.tournament_id);
    if (tournament) {
        tournament.status = 'completed';
    }

    // Emit event for other components
    window.dispatchEvent(new CustomEvent('tournament:completed', {
        detail: event
    }));
}

// Disconnect from all channels
function disconnect() {
    if (tournamentChannel) {
        window.Echo.leave(`private-tournament.${props.tournamentId}`);
        tournamentChannel = null;
    }

    if (stageChannel) {
        window.Echo.leave(`private-stage.${props.stageId}`);
        stageChannel = null;
    }

    if (publicChannel) {
        window.Echo.leave('tournaments');
        publicChannel = null;
    }
}

// Lifecycle
onMounted(() => {
    if (props.autoConnect !== false) {
        initializeEcho();
    }
});

onUnmounted(() => {
    disconnect();
});

// Watch for prop changes
watch(() => props.tournamentId, (newId, oldId) => {
    if (newId !== oldId && newId) {
        connectTournamentChannel(newId);
    }
});

watch(() => props.stageId, (newId, oldId) => {
    if (newId !== oldId && newId) {
        connectStageChannel(newId);
    }
});

// Expose methods for manual control
defineExpose({
    connect: initializeEcho,
    disconnect,
    reconnect: () => {
        disconnect();
        initializeEcho();
    }
});
</script>
