<!-- resources/js/Pages/Leagues/MultiplayerGames/Show.vue -->
<script lang="ts" setup>
import SetModeratorModal from '@/Components/League/MultiplayerGame/SetModeratorModal.vue';
import GameRegistry from '@/Components/League/MultiplayerGame/GameRegistry.vue';
import LivesEditorView from '@/Components/League/MultiplayerGame/LivesEditorView.vue';
import PlayersList from '@/Components/League/MultiplayerGame/PlayersList.vue';
import PrizeSummaryCard from '@/Components/League/MultiplayerGame/PrizeSummaryCard.vue';
import RatingSummaryCard from '@/Components/League/MultiplayerGame/RatingSummaryCard.vue';
import GameActionPanel from '@/Components/League/MultiplayerGame/GameActionPanel.vue';
import TimeFundCard from '@/Components/League/MultiplayerGame/TimeFundCard.vue';
import MultiplayerGameSummary from '@/Components/League/MultiplayerGame/MultiplayerGameSummary.vue';
import RebuyPlayerModal from '@/Components/League/MultiplayerGame/RebuyPlayerModal.vue';
import GameStatistics from '@/Components/League/MultiplayerGame/GameStatistics.vue';
import {
    ArrowLeftIcon,
    BarChart3Icon,
    CheckIcon,
    ClockIcon,
    CopyIcon,
    GamepadIcon,
    LogInIcon,
    MonitorIcon,
    SmileIcon,
    TrophyIcon,
    UserIcon,
    UserPlusIcon,
    UsersIcon,
    WalletIcon
} from 'lucide-vue-next';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {useMultiplayerGames} from '@/composables/useMultiplayerGames';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {League, MultiplayerGame, MultiplayerGamePlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {computed, onMounted, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';
import GameFinishModal from "@/Components/League/MultiplayerGame/GameFinishModal.vue";
import AddPlayerModal from "@/Components/League/MultiplayerGame/AddPlayerModal.vue";
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import {useSeo} from "@/composables/useSeo";

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    leagueId: string | number;
    gameId: string | number;
}>();

const {isAdmin, user, isAuthenticated} = useAuth();
const {t} = useLocale();
const showRebuyModal = ref(false);
const gameStatistics = ref<any>(null);
const isLoadingStatistics = ref(false);
const {
    getMultiplayerGame,
    performGameAction,
    setGameModerator,
    startMultiplayerGame,
    error,
    isLoading: isActionLoading
} = useMultiplayerGames();

const league = ref<League | null>(null);
const game = ref<MultiplayerGame | null>(null);
const isLoadingLeague = ref(true);
const isLoadingGame = ref(true);
const selectedCardType = ref<'skip_turn' | 'pass_turn' | 'hand_shot' | null>(null);
const selectedTargetPlayer = ref<MultiplayerGamePlayer | null>(null);
const selectedActingPlayer = ref<MultiplayerGamePlayer | null>(null);
const showFinishModal = ref(false);
const showModeratorModal = ref(false);
const showMobileAdminMenu = ref(false);
const actionFeedback = ref<{ type: 'success' | 'error', message: string } | null>(null);
const copiedWidgetId = ref<string | null>(null);
const {setSeoMeta, generateBreadcrumbJsonLd} = useSeo();

// Get initial tab from URL query parameter
const getInitialTab = (): 'game' | 'prizes' | 'ratings' | 'timefund' | 'statistics' | 'widget' => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const validTabs = ['game', 'prizes', 'ratings', 'timefund', 'statistics', 'widget'];
    return validTabs.includes(tabParam as string) ? tabParam as any : 'game';
};

const activeTab = ref<'game' | 'prizes' | 'ratings' | 'timefund' | 'statistics' | 'widget'>(getInitialTab());


// Handle tab change and update URL
const switchTab = (tab: 'game' | 'prizes' | 'ratings' | 'timefund' | 'statistics' | 'widget') => {
    activeTab.value = tab;

    // Load statistics when switching to statistics tab
    if (tab === 'statistics') {
        loadStatistics();
    }

    // Update URL without page reload
    const url = new URL(window.location.href);
    if (tab === 'game') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }

    window.history.replaceState({}, '', url.toString());
};

const loadStatistics = async () => {
    if (!game.value || game.value.status === 'registration') return;

    isLoadingStatistics.value = true;
    try {
        gameStatistics.value = await apiClient<any>(
            `/api/leagues/${props.leagueId}/multiplayer-games/${props.gameId}/statistics`
        );
    } catch (err) {
        console.error('Failed to load statistics:', err);
    } finally {
        isLoadingStatistics.value = false;
    }
};

const handleRebuyAdded = async () => {
    await fetchGame();
    showRebuyModal.value = false;
    actionFeedback.value = {
        type: 'success',
        message: t('Player added successfully')
    };

    // Reload statistics if on statistics tab
    if (activeTab.value === 'statistics') {
        await loadStatistics();
    }
};

// Add to existing refs
const showAddPlayerModal = ref(false);

// Widget URL builders
const getWidgetUrl = (params: Record<string, string> = {}): string => {
    const baseUrl = `${window.location.origin}/widgets/killer-pool`;
    const defaultParams = {
        league: props.leagueId.toString(),
        game: props.gameId.toString(),
        theme: 'dark',
        refresh: '3000',
        show_league: 'true',
        show_next: 'true',
        show_cards: 'true'
    };
    const finalParams = {...defaultParams, ...params};
    const queryString = new URLSearchParams(finalParams).toString();
    return `${baseUrl}?${queryString}`;
};

// Widget presets
const widgetPresets = computed(() => [
    {
        id: 'default',
        name: t('Default Widget'),
        description: t('Full widget with all information'),
        url: getWidgetUrl(),
        preview: {
            theme: t('Dark'),
            refresh: t('3 seconds'),
            features: [t('League info'), t('Next players'), t('Cards')]
        }
    },
    {
        id: 'transparent',
        name: t('Transparent Overlay'),
        description: t('Perfect for stream overlays'),
        url: getWidgetUrl({theme: 'transparent', refresh: '2000'}),
        preview: {
            theme: t('Transparent'),
            refresh: t('2 seconds'),
            features: [t('League info'), t('Next players'), t('Cards')]
        }
    },
    {
        id: 'minimal',
        name: t('Minimal View'),
        description: t('Current player only'),
        url: getWidgetUrl({show_league: 'false', show_next: 'false', show_cards: 'false'}),
        preview: {
            theme: t('Dark'),
            refresh: t('3 seconds'),
            features: [t('Current player only')]
        }
    },
    {
        id: 'vertical',
        name: t('Vertical'),
        description: t('For Tablet and TV screens'),
        url: getWidgetUrl({theme: 'dark', refresh: '3000', orientation: 'vertical'}),
        preview: {
            theme: t('Dark'),
            refresh: t('3 seconds'),
            features: [t('League info'), t('Next players'), t('Cards'), t('Vertical')]
        }
    }
]);

// Copy widget URL to clipboard
const copyWidgetUrl = async (widgetId: string, url: string) => {
    try {
        await navigator.clipboard.writeText(url);
        copiedWidgetId.value = widgetId;
        setTimeout(() => {
            copiedWidgetId.value = null;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

// Add method to handle player addition (admin only)
const handlePlayerAdded = async () => {
    if (!isAuthenticated.value || !isAdmin.value) return;
    await fetchGame();
    actionFeedback.value = {
        type: 'success',
        message: t('Player added successfully')
    };
};

// Computed properties
const formattedStatusMessage = computed(() => {
    if (!game.value) return '';

    if (game.value.status === 'registration') {
        if (!isAuthenticated.value) {
            return t('The game is currently in the registration phase. Login to join and participate.');
        }
        return t('The game is currently in the registration phase. Players can join until the game starts.');
    } else if (game.value.status === 'in_progress') {
        if (!isAuthenticated.value) {
            return t('This game is currently in progress. You can watch the live action below.');
        }
        if (isModerator.value) {
            return t('You are the moderator. You can perform actions for any player.');
        } else if (game.value.current_turn_player_id === user.value?.id) {
            return t("It's your turn to play.");
        } else {
            return t('Waiting for the current player to make a move.');
        }
    } else if (game.value.status === 'completed') {
        return t('The game has finished. Check the results below.');
    }

    return '';
});

const isModerator = computed(() => {
    if (!game.value || !user.value || !isAuthenticated.value) return false;
    return game.value.is_current_user_moderator;
});

// Get the current turn player
const currentTurnPlayer = computed(() => {
    if (!game.value) return null;
    return game.value.active_players.find(player => player.is_current_turn) || null;
});

// Format date for display
const formatDate = (dateString: string | null | undefined): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('uk-UK');
};

// Get status badge color class
const statusBadgeClass = computed(() => {
    if (!game.value) return '';

    switch (game.value.status) {
        case 'registration':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'in_progress':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'completed':
        case 'finished':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
});

// Get status display text
const statusText = computed(() => {
    if (!game.value) return '';

    switch (game.value.status) {
        case 'registration':
            return t('Registration Open');
        case 'in_progress':
            return t('In Progress');
        case 'completed':
            return t('Completed');
        case 'finished':
            return t('Finished');
        default:
            return game.value.status;
    }
});

// Add columns definition for completed game results
const resultsColumns = computed(() => [
    {
        key: 'finish_position',
        label: t('Position'),
        align: 'left' as const,
        render: (player: any) => ({
            position: player.finish_position,
            isWinner: player.finish_position === 1
        })
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (player: any) => ({
            name: `${player.user.firstname} ${player.user.lastname}`,
            isYou: isAuthenticated.value && player.user.id === user.value?.id
        })
    },
    {
        key: 'rating_points',
        label: t('Rating Points'),
        align: 'center' as const,
        render: (player: any) => player.rating_points
    },
    {
        key: 'prize_amount',
        label: t('Prize'),
        align: 'right' as const,
        render: (player: any) => player.prize_amount
    }
]);

// Fetch data
const fetchLeague = async () => {
    isLoadingLeague.value = true;
    try {
        league.value = await apiClient<League>(`/api/leagues/${props.leagueId}`);
// eslint-disable-next-line
    } catch (err) {
        // Error handling is managed by the composable
    } finally {
        isLoadingLeague.value = false;
    }
};

const fetchGame = async () => {
    isLoadingGame.value = true;
    try {
        game.value = await getMultiplayerGame(props.leagueId, props.gameId);

        // After fetching the game, check if we need to update the selected acting player
        if (isAuthenticated.value && selectedActingPlayer.value) {
            updateSelectedActingPlayer();
        } else if (isAuthenticated.value && game.value.status === 'in_progress') {
            // Auto-select the current user's player or current turn player when first loading
            selectDefaultActingPlayer();
        }
// eslint-disable-next-line
    } catch (err) {
        // Error handling is managed by the composable
    } finally {
        isLoadingGame.value = false;
    }
};

// Update the selected acting player with fresh data from the game (authenticated users only)
const updateSelectedActingPlayer = () => {
    if (!isAuthenticated.value) return;
    selectDefaultActingPlayer();
};

// Select a default acting player (current user or current turn) - authenticated users only
const selectDefaultActingPlayer = () => {
    if (!game.value || !user.value || !isAuthenticated.value) return;

    // First try to select the current user's player
    const currentTurnUser = game.value.active_players.find(p => p.user.id === game.value?.current_turn_player_id);

    if (currentTurnUser) {
        selectedActingPlayer.value = currentTurnUser;
    } else if (isModerator.value && currentTurnPlayer.value) {
        // If user is moderator, select the current turn player
        selectedActingPlayer.value = currentTurnPlayer.value;
    }
};

// Handle game actions (authenticated users only)
const handleAction = async (
    action: 'increment_lives' | 'decrement_lives' | 'record_turn' | 'set_turn',
    targetUserId?: number,
    actingUserId?: number
) => {
    if (!game.value || !isAuthenticated.value) return;

    actionFeedback.value = null;

    try {
        await performGameAction(
            props.leagueId,
            props.gameId,
            action,
            targetUserId,
            undefined,
            actingUserId
        );
        await fetchGame();

        // Show success feedback
        if (action === 'increment_lives' && game.value) {
            actionFeedback.value = {
                type: 'success',
                message: t('Life successfully incremented')
            };
        }
        if (action === 'decrement_lives' && game.value) {
            actionFeedback.value = {
                type: 'success',
                message: t('Life successfully decremented')
            };
        }

        // If the action was 'record_turn', select the new current turn player
        if (action === 'record_turn' && game.value) {
            const newCurrentTurnPlayer = game.value.active_players.find(p => p.is_current_turn);
            if (newCurrentTurnPlayer) {
                selectedActingPlayer.value = newCurrentTurnPlayer;
                selectedCardType.value = null;
                selectedTargetPlayer.value = null;

                // Show a notification that turn has changed
                actionFeedback.value = {
                    type: 'success',
                    message: t('Turn passed to :player', {player: `${newCurrentTurnPlayer.user.firstname} ${newCurrentTurnPlayer.user.lastname}`})
                };
            }
        }
    } catch (err: any) {
        // Show error feedback
        actionFeedback.value = {
            type: 'error',
            message: err.message || t('Failed to perform :action', {action})
        };
    }
};

// Updated handleUseCard method in Show.vue
const handleUseCard = async (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot' | 'handicap', targetPlayerId?: number, actingUserId?: number, handicapAction?: string) => {
    if (!game.value || !isAuthenticated.value) return;

    actionFeedback.value = null;

    try {
        await performGameAction(
            props.leagueId,
            props.gameId,
            'use_card',
            targetPlayerId,
            cardType,
            actingUserId,
            handicapAction
        );

        // Show success feedback
        let message = t('Used :card card successfully', {card: cardType.replace('_', ' ')});

        // Add more specific messages for each card type
        if (cardType === 'skip_turn') {
            message = t('Skip Turn card used - your turn is skipped, game moves to the next player');
        } else if (cardType === 'pass_turn') {
            const targetPlayer = game.value.active_players.find(p => p.user.id === targetPlayerId);
            message = t('Pass Turn card used - turn passed to :player. After they play, the turn will return to you', {player: `${targetPlayer?.user.firstname} ${targetPlayer?.user.lastname}`});
        } else if (cardType === 'hand_shot') {
            message = t('Hand Shot card used - you can place the cue ball anywhere on the table');
        } else if (cardType === 'handicap') {
            switch (handicapAction) {
                case 'skip_turn':
                    message = t('Handicap card used - Skip Turn: your turn is skipped, game moves to the next player');
                    break;
                case 'take_life':
                    const targetPlayerLife = game.value.active_players.find(p => p.user.id === targetPlayerId);
                    message = t('Handicap card used - Take Life: removed a life from :player', {player: `${targetPlayerLife?.user.firstname} ${targetPlayerLife?.user.lastname}`});
                    break;
                default:
                    message = t('Handicap card used successfully');
            }
        }

        actionFeedback.value = {
            type: 'success',
            message
        };

        // Reset selections
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;

        // Don't clear acting player, just update it with fresh data
        await fetchGame();
    } catch (err: any) {
        // Show error feedback
        actionFeedback.value = {
            type: 'error',
            message: err.message || t('Failed to use card :card', {card: cardType})
        };
    }
};

const handlePlayerAction = (actionData: any) => {
    if (!isAuthenticated.value) return;

    if (actionData.cardType) {
        handleUseCard(
            actionData.cardType,
            actionData.targetPlayerId,
            selectedActingPlayer.value?.user.id,
            actionData.handicapAction
        );
    }
};
const handleSetModerator = async (userId: number) => {
    if (!game.value || !isAuthenticated.value || !isAdmin.value) return;

    actionFeedback.value = null;

    try {
        await setGameModerator(props.leagueId, props.gameId, userId);
        showModeratorModal.value = false;

        actionFeedback.value = {
            type: 'success',
            message: t('Moderator set successfully')
        };

        await fetchGame();
    } catch (err: any) {
        actionFeedback.value = {
            type: 'error',
            message: err.message || t('Failed to set moderator')
        };
    }
};

// User interface selection handlers (authenticated users only)
const selectPlayer = (player: MultiplayerGamePlayer) => {
    if (!isAuthenticated.value) return;

    // When selecting a different acting player, clear the card and target selections
    if (selectedActingPlayer.value?.id !== player.id) {
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;
    }
    selectedActingPlayer.value = selectedActingPlayer.value?.id === player.id ? null : player;
};

const handleStart = async () => {
    if (!isAuthenticated.value || !isAdmin.value) return;

    try {
        await startMultiplayerGame(props.leagueId, props.gameId);
        await fetchGame();
        // eslint-disable-next-line
    } catch (err) {
        // Error is handled by the composable
    }
};

const handleGameFinished = () => {
    fetchLeague();
    fetchGame();
};

// Clear action feedback after 5 seconds
watch(actionFeedback, (newValue) => {
    if (newValue) {
        setTimeout(() => {
            actionFeedback.value = null;
        }, 5000);
    }
});

// Watch for game changes to update UI state
watch(game, (newGame) => {
    if (newGame && newGame.status === 'completed') {
        // Clear selections if game is completed
        selectedActingPlayer.value = null;
        selectedCardType.value = null;
        selectedTargetPlayer.value = null;
    }
}, {deep: true});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('uk-UA', {style: 'currency', currency: 'UAH'})
        .format(amount)
        .replace('UAH', '₴');
};


// Lifecycle hooks
onMounted(() => {
    fetchLeague();
    fetchGame();

    const setSeoAfterLoad = () => {
        if (league.value && game.value) {
            setSeoMeta({
                title: t(':game - :league Multiplayer Game', {game: game.value.name, league: league.value.name}),
                description: t('mp_game_desc_solo', {
                    league: league.value.name,
                    players: game.value.total_players_count,
                    prize: formatCurrency(game.value.entrance_fee * game.value.total_players_count)
                }),
                keywords: [game.value.name, league.value.name, 'multiplayer billiards', 'killer pool', 'elimination game', 'prize pool'],
                ogType: 'website',
                jsonLd: {
                    ...generateBreadcrumbJsonLd([
                        {name: t('Home'), url: window.location.origin},
                        {name: t('Leagues'), url: `${window.location.origin}/leagues`},
                        {name: league.value.name, url: `${window.location.origin}/leagues/${league.value.slug}`},
                        {
                            name: t('Multiplayer Games'),
                            url: `${window.location.origin}/leagues/${league.value.slug}/multiplayer-games`
                        },
                        {
                            name: game.value.name,
                            url: `${window.location.origin}/leagues/${league.value.slug}/multiplayer-games/${game.value.slug}`
                        }
                    ]),
                    "@context": "https://schema.org",
                    "@type": "SportsEvent",
                    "name": game.value.name,
                    "description": t('Multiplayer billiard game in :league', {league: league.value.name}),
                    "sport": "Billiards",
                    "startDate": game.value.started_at,
                    "endDate": game.value.completed_at,
                    "location": {
                        "@type": "VirtualLocation",
                        "name": league.value.name
                    },
                    "organizer": {
                        "@type": "Organization",
                        "name": "WinnerBreak"
                    }
                }
            });
        }
    };

    const unwatch = setInterval(() => {
        if (!isLoadingLeague.value && league.value && !isLoadingGame.value && game.value) {
            setSeoAfterLoad();
            clearInterval(unwatch);
        }
    }, 100);
});
</script>

<template>
    <Head :title="game ? `${game.name} - ${league?.name || t('Multiplayer Game')}` : t('Multiplayer Game')"/>

    <div class="py-4 sm:py-6 lg:py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header with actions -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="`/leagues/${league?.slug}/multiplayer-games`">
                        <Button variant="outline" size="sm">
                            <ArrowLeftIcon class="mr-1.5 h-3.5 w-3.5"/>
                            <span class="hidden sm:inline">{{ t('Back to Games') }}</span>
                            <span class="sm:hidden">{{ t('Back') }}</span>
                        </Button>
                    </Link>
                </div>

                <!-- Admin controls - Desktop -->
                <div v-if="isAuthenticated && (isAdmin || isModerator)" class="hidden sm:flex flex-wrap gap-1.5">
                   <span
                       v-if="game"
                       :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadgeClass]"
                   >
                       {{ statusText }}
                   </span>

                    <Button
                        v-if="isAdmin && game?.status === 'in_progress' && game?.allow_rebuy"
                        variant="secondary"
                        size="sm"
                        @click="showRebuyModal = true"
                    >
                        <UserPlusIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Add/Rebuy Player') }}
                    </Button>

                    <Button
                        v-if="game?.status === 'completed'"
                        variant="secondary"
                        size="sm"
                        @click="showFinishModal = true"
                    >
                        <TrophyIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Finish Game') }}
                    </Button>
                    <Button
                        v-if="game?.status === 'registration'"
                        variant="secondary"
                        size="sm"
                        @click="handleStart"
                    >
                        <TrophyIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Start Game') }}
                    </Button>
                    <Button
                        v-if="game?.status === 'in_progress'"
                        variant="secondary"
                        size="sm"
                        @click="showModeratorModal = true"
                    >
                        <UserIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Change Moderator') }}
                    </Button>
                    <Button
                        v-if="isAdmin && game?.status === 'registration'"
                        variant="secondary"
                        size="sm"
                        @click="showAddPlayerModal = true"
                    >
                        <UserPlusIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Add Player') }}
                    </Button>
                </div>

                <!-- Admin controls - Mobile Menu Button -->
                <Button
                    v-if="isAuthenticated && (isAdmin || isModerator)"
                    size="sm"
                    variant="secondary"
                    class="sm:hidden"
                    @click="showMobileAdminMenu = !showMobileAdminMenu"
                >
                    <UserIcon class="h-3.5 w-3.5"/>
                </Button>

                <!-- Status badge for mobile -->
                <span
                    v-if="game && !isAuthenticated"
                    :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadgeClass]"
                >
                   {{ statusText }}
               </span>
            </div>

            <!-- Mobile Admin Menu -->
            <div
                v-if="isAuthenticated && (isAdmin || isModerator) && showMobileAdminMenu"
                class="sm:hidden mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
            >
                <div class="grid grid-cols-2 gap-1.5">
                    <Button
                        v-if="game?.status === 'completed'"
                        size="sm"
                        variant="secondary"
                        class="w-full"
                        @click="showFinishModal = true; showMobileAdminMenu = false"
                    >
                        <TrophyIcon class="mr-1 h-3 w-3"/>
                        {{ t('Finish') }}
                    </Button>
                    <Button
                        v-if="game?.status === 'registration'"
                        size="sm"
                        variant="secondary"
                        class="w-full"
                        @click="handleStart; showMobileAdminMenu = false"
                    >
                        <TrophyIcon class="mr-1 h-3 w-3"/>
                        {{ t('Start') }}
                    </Button>
                    <Button
                        v-if="game?.status === 'in_progress'"
                        size="sm"
                        variant="secondary"
                        class="w-full"
                        @click="showModeratorModal = true; showMobileAdminMenu = false"
                    >
                        <UserIcon class="mr-1 h-3 w-3"/>
                        {{ t('Moderator') }}
                    </Button>
                    <Button
                        v-if="isAdmin && game?.status === 'registration'"
                        size="sm"
                        variant="secondary"
                        class="w-full"
                        @click="showAddPlayerModal = true; showMobileAdminMenu = false"
                    >
                        <UserPlusIcon class="mr-1 h-3 w-3"/>
                        {{ t('Add') }}
                    </Button>
                </div>
            </div>

            <!-- Page Title -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2.5 mb-2">
                            <div
                                class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center shadow-md">
                                <GamepadIcon class="h-5 w-5 text-white"/>
                            </div>
                            <div>
                                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                    {{ game ? game.name : t('Multiplayer Game') }}
                                    <span v-if="game"
                                          :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadgeClass]">
                                       {{ statusText }}
                                   </span>
                                </h1>
                                <p v-if="league" class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                    {{ league.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Game Stats -->
                        <div v-if="game" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                            <div class="text-center sm:text-left">
                                <div class="flex items-center gap-1.5 justify-center sm:justify-start">
                                    <UsersIcon class="h-3.5 w-3.5 text-gray-400"/>
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                                       {{ game.total_players_count }}
                                   </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Total Players') }}</p>
                            </div>
                            <div class="text-center sm:text-left">
                                <div class="flex items-center gap-1.5 justify-center sm:justify-start">
                                    <TrophyIcon class="h-3.5 w-3.5 text-gray-400"/>
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                                       {{ formatCurrency(game.entrance_fee * game.total_players_count) }}
                                   </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Prize Pool') }}</p>
                            </div>
                            <div v-if="game.initial_lives" class="text-center sm:text-left">
                                <div class="flex items-center gap-1.5 justify-center sm:justify-start">
                                    <WalletIcon class="h-3.5 w-3.5 text-gray-400"/>
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                                       {{ game.initial_lives }}
                                   </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Initial Lives') }}</p>
                            </div>
                            <div v-if="game.started_at" class="text-center sm:text-left">
                                <div class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ new Date(game.started_at).toLocaleDateString() }}
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Start Date') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Message -->
            <div
                v-if="formattedStatusMessage"
                :class="[
                   'mb-4 rounded-lg border p-3',
                   !isAuthenticated
                       ? 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'
                       : 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
               ]"
            >
                <p class="text-sm">{{ formattedStatusMessage }}</p>
            </div>

            <!-- Action Feedback Message -->
            <div
                v-if="actionFeedback"
                :class="actionFeedback.type === 'success'
                   ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300'
                   : 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300'"
                class="mb-4 rounded-lg border p-3"
            >
                <p class="text-sm">{{ actionFeedback.message }}</p>
            </div>

            <!-- Error message from composable -->
            <div v-if="error"
                 class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingLeague || isLoadingGame" class="flex justify-center items-center py-8">
                <div class="text-center">
                    <Spinner class="mx-auto h-6 w-6 text-indigo-600"/>
                    <p class="mt-1.5 text-sm text-gray-500">{{ t('Loading game information...') }}</p>
                </div>
            </div>

            <!-- Game Content -->
            <div v-else-if="game">
                <!-- Registration phase -->
                <div v-if="game.status === 'registration'">
                    <GameRegistry :game="game" :league-id="leagueId" @updated="fetchGame"/>
                </div>

                <!-- Game in progress or completed -->
                <div v-else class="space-y-4">
                    <!-- Game summary -->
                    <MultiplayerGameSummary :game="game"/>

                    <!-- Tab Navigation -->
                    <nav class="mb-4 border-b border-gray-200 dark:border-gray-700 overflow-x-auto" role="navigation"
                         aria-label="Game tabs">
                        <div class="-mb-px flex space-x-4 sm:space-x-6 min-w-max">
                            <button
                                :class="[
                                   'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                   activeTab === 'game'
                                       ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                       : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                               ]"
                                :aria-selected="activeTab === 'game'"
                                role="tab"
                                @click="switchTab('game')"
                            >
                               <span class="flex items-center gap-1.5">
                                   <GamepadIcon class="h-3.5 w-3.5"/>
                                   {{ t('Game') }}
                               </span>
                            </button>
                            <button
                                :class="[
                                   'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                   activeTab === 'prizes'
                                       ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                       : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                               ]"
                                :aria-selected="activeTab === 'prizes'"
                                role="tab"
                                @click="switchTab('prizes')"
                            >
                               <span class="flex items-center gap-1.5">
                                   <TrophyIcon class="h-3.5 w-3.5"/>
                                   {{ t('Prizes') }}
                               </span>
                            </button>
                            <button
                                :class="[
                                   'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                   activeTab === 'ratings'
                                       ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                       : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                               ]"
                                :aria-selected="activeTab === 'ratings'"
                                role="tab"
                                @click="switchTab('ratings')"
                            >
                               <span class="flex items-center gap-1.5">
                                   <SmileIcon class="h-3.5 w-3.5"/>
                                   {{ t('Rating Points') }}
                               </span>
                            </button>
                            <button
                                :class="[
                                   'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                   activeTab === 'timefund'
                                       ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                       : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                               ]"
                                :aria-selected="activeTab === 'timefund'"
                                role="tab"
                                @click="switchTab('timefund')"
                            >
                               <span class="flex items-center gap-1.5">
                                   <ClockIcon class="h-3.5 w-3.5"/>
                                   {{ t('Time Fund') }}
                               </span>
                            </button>
                            <button
                                :class="[
                                   'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                   activeTab === 'statistics'
                                       ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                       : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                               ]"
                                :aria-selected="activeTab === 'statistics'"
                                role="tab"
                                @click="switchTab('statistics')"
                            >
                               <span class="flex items-center gap-1.5">
                                   <BarChart3Icon class="h-3.5 w-3.5"/>
                                   {{ t('Statistics') }}
                               </span>
                            </button>
                            <button
                                v-if="isAuthenticated && (isAdmin || isModerator)"
                                :class="[
                                   'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                   activeTab === 'widget'
                                       ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                       : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                               ]"
                                :aria-selected="activeTab === 'widget'"
                                role="tab"
                                @click="switchTab('widget')"
                            >
                               <span class="flex items-center gap-1.5">
                                   <MonitorIcon class="h-3.5 w-3.5"/>
                                   {{ t('OBS Widget') }}
                               </span>
                            </button>
                        </div>
                    </nav>

                    <!-- Tab Content -->
                    <main role="tabpanel">
                        <!-- Game tab -->
                        <div v-if="activeTab === 'game'" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                <!-- Active Players Panel -->
                                <div class="order-2 lg:order-1 lg:col-span-1">
                                    <Card class="shadow-lg" v-if="game.active_players.length > 0">
                                        <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                            <CardTitle class="flex items-center gap-1.5 text-base">
                                                <UsersIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400"/>
                                                {{ t('Active Players') }}
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent class="p-4">
                                            <PlayersList
                                                :highlight-current-turn="true"
                                                :max-lives="game.initial_lives"
                                                :players="game.active_players"
                                                :selected-player-id="selectedActingPlayer?.id"
                                                :show-controls="isAuthenticated && isModerator && game.status === 'in_progress'"
                                                title=""
                                                @select-player="selectPlayer"
                                            />
                                        </CardContent>
                                    </Card>

                                    <!-- Eliminated Players if any -->
                                    <Card
                                        v-if="game.eliminated_players.length > 0"
                                        :class="game.active_players.length > 0 ? 'mt-4' : ''"
                                    >
                                        <CardHeader class="py-3">
                                            <CardTitle class="text-base">{{ t('Eliminated Players') }}</CardTitle>
                                        </CardHeader>
                                        <CardContent class="p-4">
                                            <div class="divide-y">
                                                <div
                                                    v-for="player in game.eliminated_players"
                                                    :key="player.id"
                                                    class="flex items-center justify-between py-2"
                                                >
                                                    <div class="flex items-center space-x-2.5">
                                                        <div
                                                            class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 text-xs">
                                                            {{ player.finish_position }}
                                                        </div>
                                                        <div>
                                                            <p class="text-sm font-medium">
                                                                {{ player.user.firstname }} {{ player.user.lastname }}
                                                                ({{ player.division }})
                                                            </p>
                                                            <p v-if="isAuthenticated && player.user.id === user?.id"
                                                               class="text-xs text-blue-600">{{ t('(You)') }}</p>
                                                        </div>
                                                    </div>

                                                    <div class="text-xs text-gray-500">
                                                        {{ t('Eliminated:') }} {{ formatDate(player?.eliminated_at) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>

                                <!-- Game Action Panel - Only for authenticated users in progress games -->
                                <div v-if="isAuthenticated && game.status === 'in_progress'"
                                     class="order-1 lg:order-2 lg:col-span-2">
                                    <div v-if="selectedActingPlayer" class="space-y-3">
                                        <GameActionPanel
                                            :is-current-turn="selectedActingPlayer.is_current_turn"
                                            :is-loading="isActionLoading"
                                            :max-lives="game.initial_lives"
                                            :player="selectedActingPlayer"
                                            :target-players="game.active_players.filter(p => p.id !== selectedActingPlayer?.id)"
                                            @decrement-lives="() => handleAction('decrement_lives', selectedActingPlayer?.user.id, selectedActingPlayer?.user.id)"
                                            @increment-lives="() => handleAction('increment_lives', selectedActingPlayer?.user.id, selectedActingPlayer?.user.id)"
                                            @record-turn="() => handleAction('record_turn', undefined, selectedActingPlayer?.user.id)"
                                            @use-card="handlePlayerAction"
                                        />
                                    </div>

                                    <div v-else-if="isModerator" class="space-y-3">
                                        <LivesEditorView
                                            :current-turn-player-id="game.current_turn_player_id"
                                            :is-loading="isActionLoading"
                                            :players="game.active_players"
                                            @decrement-lives="(userId) => handleAction('decrement_lives', userId)"
                                            @increment-lives="(userId) => handleAction('increment_lives', userId)"
                                            @set-turn="(userId) => handleAction('set_turn', userId)"
                                        />
                                    </div>

                                    <div v-else
                                         class="rounded-lg border p-3 text-center text-sm text-gray-500 dark:text-gray-400">
                                        <p>{{ t('Select a player to view their actions or wait for your turn.') }}</p>
                                    </div>
                                </div>

                                <!-- Guest viewing message for in-progress games -->
                                <div v-else-if="!isAuthenticated && game.status === 'in_progress'"
                                     class="order-1 lg:order-2 lg:col-span-2">
                                    <Card>
                                        <CardHeader class="py-3">
                                            <CardTitle class="text-base">{{ t('Game in Progress') }}</CardTitle>
                                        </CardHeader>
                                        <CardContent class="p-4">
                                            <div class="text-center py-6">
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                                    {{
                                                        t('This multiplayer game is currently in progress. You can watch the live action by viewing the player list on the left.')
                                                    }}
                                                </p>
                                                <Link :href="route('login')">
                                                    <Button size="sm">
                                                        <LogInIcon class="mr-1.5 h-3.5 w-3.5"/>
                                                        {{ t('Login to Participate') }}
                                                    </Button>
                                                </Link>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>

                                <!-- Game Completed Summary -->
                                <div v-if="game.status === 'completed' || game.status === 'finished'"
                                     class="order-1 lg:order-2 lg:col-span-2">
                                    <Card>
                                        <CardHeader class="py-3">
                                            <CardTitle class="text-base">{{ t('Game Results') }}</CardTitle>
                                        </CardHeader>
                                        <CardContent class="p-4">
                                            <DataTable
                                                :columns="resultsColumns"
                                                :compact-mode="true"
                                                :data="game.eliminated_players.sort((a, b) => (a.finish_position || 999) - (b.finish_position || 999))"
                                            >
                                                <template #cell-finish_position="{ value }">
                                               <span
                                                   :class="{
                                                       'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': value.isWinner,
                                                       'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200': !value.isWinner
                                                   }"
                                                   class="inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-medium"
                                               >
                                                   {{ value.position }}
                                               </span>
                                                </template>
                                                <template #cell-player="{ value }">
                                                    {{ value.name }}
                                                    <span v-if="value.isYou"
                                                          class="ml-1 text-xs text-blue-600">{{ t('(You)') }}</span>
                                                </template>
                                                <template #cell-rating_points="{ value }">
                                               <span v-if="value"
                                                     class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                   +{{ value }}
                                               </span>
                                                    <span v-else>—</span>
                                                </template>
                                                <template #cell-prize_amount="{ value }">
                                               <span v-if="value && value > 0" class="text-sm font-medium">
                                                   {{
                                                       (value || 0).toLocaleString('uk-UA', {
                                                           style: 'currency',
                                                           currency: 'UAH'
                                                       }).replace('UAH', '₴')
                                                   }}
                                               </span>
                                                    <span v-else>—</span>
                                                </template>
                                            </DataTable>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>
                        </div>

                        <!-- Prizes tab -->
                        <div v-if="activeTab === 'prizes'" class="space-y-4">
                            <PrizeSummaryCard :game="game"/>
                        </div>

                        <!-- Ratings tab -->
                        <div v-if="activeTab === 'ratings'" class="space-y-4">
                            <RatingSummaryCard :game="game"/>
                        </div>

                        <div v-if="activeTab === 'statistics'" class="space-y-4">
                            <div v-if="isLoadingStatistics" class="text-center py-6">
                                <Spinner class="mx-auto h-6 w-6 text-indigo-600"/>
                                <p class="mt-1.5 text-sm text-gray-500">{{ t('Loading statistics...') }}</p>
                            </div>
                            <GameStatistics v-else-if="gameStatistics" :statistics="gameStatistics"/>
                            <div v-else class="text-center py-6 text-sm text-gray-500">
                                {{ t('No statistics available') }}
                            </div>
                        </div>

                        <!-- Time Fund tab -->
                        <div v-if="activeTab === 'timefund'" class="space-y-4">
                            <TimeFundCard :game="game"/>
                        </div>

                        <!-- Widget tab -->
                        <div v-if="activeTab === 'widget' && isAuthenticated && (isAdmin || isModerator)"
                             class="space-y-4">
                            <!-- Instructions -->
                            <Card class="shadow-lg">
                                <CardHeader class="bg-blue-50 dark:bg-blue-900/20 py-3">
                                    <CardTitle class="flex items-center gap-1.5 text-base">
                                        <MonitorIcon class="h-4 w-4 text-blue-600 dark:text-blue-400"/>
                                        {{ t('OBS Stream Widget Setup') }}
                                    </CardTitle>
                                    <CardDescription class="text-xs">
                                        {{
                                            t('Display live game information in your stream with customizable widgets.')
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-4">
                                    <ol class="list-decimal list-inside space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                                        <li>{{ t('Choose a widget preset below or customize parameters') }}</li>
                                        <li>{{ t('Copy the widget URL') }}</li>
                                        <li>{{ t('Add Browser Source in OBS') }}</li>
                                        <li>{{ t('Set size: 1000x100px (width x height)') }}</li>
                                    </ol>
                                </CardContent>
                            </Card>

                            <!-- Widget Presets -->
                            <div class="grid gap-4 md:grid-cols-2">
                                <Card v-for="preset in widgetPresets" :key="preset.id" class="shadow-lg">
                                    <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                        <CardTitle class="flex items-center gap-1.5 text-base">
                                            <MonitorIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400"/>
                                            {{ preset.name }}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="p-4">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{
                                                preset.description
                                            }}</p>

                                        <!-- Preview details -->
                                        <div class="mb-3 space-y-1.5 text-sm">
                                            <div class="flex items-center justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">{{ t('Theme:') }}</span>
                                                <span class="font-medium">{{ preset.preview.theme }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                               <span class="text-gray-600 dark:text-gray-400">{{
                                                       t('Refresh:')
                                                   }}</span>
                                                <span class="font-medium">{{ preset.preview.refresh }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                               <span class="text-gray-600 dark:text-gray-400">{{
                                                       t('Features:')
                                                   }}</span>
                                                <span class="text-xs font-medium">{{
                                                        preset.preview.features.join(', ')
                                                    }}</span>
                                            </div>
                                        </div>

                                        <!-- Copy button -->
                                        <Button
                                            class="w-full"
                                            size="sm"
                                            variant="outline"
                                            @click="copyWidgetUrl(preset.id, preset.url)"
                                        >
                                            <template v-if="copiedWidgetId === preset.id">
                                                <CheckIcon class="mr-1.5 h-3.5 w-3.5 text-green-600"/>
                                                {{ t('Copied!') }}
                                            </template>
                                            <template v-else>
                                                <CopyIcon class="mr-1.5 h-3.5 w-3.5"/>
                                                {{ t('Copy Widget URL') }}
                                            </template>
                                        </Button>

                                        <!-- URL Preview -->
                                        <details class="mt-2.5 cursor-pointer">
                                            <summary
                                                class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                                {{ t('View URL') }}
                                            </summary>
                                            <div class="mt-1.5 rounded bg-gray-100 p-1.5 dark:bg-gray-800">
                                                <code class="text-xs break-all">{{ preset.url }}</code>
                                            </div>
                                        </details>
                                    </CardContent>
                                </Card>
                            </div>
                            <!-- Custom URL Builder Info -->
                            <Card class="shadow-lg">
                                <CardHeader class="bg-gray-50 dark:bg-gray-700/50">
                                    <CardTitle class="flex items-center gap-2">
                                        <MonitorIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                                        {{ t('Custom Widget Parameters') }}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-6">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                        {{ t('You can customize the widget by adding parameters to the URL:') }}
                                    </p>
                                    <div class="space-y-3 text-sm">
                                        <div class="grid grid-cols-3 gap-4">
                                            <span
                                                class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">theme</span>
                                            <span class="col-span-2 text-gray-600 dark:text-gray-400">dark, light, transparent</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4">
                                            <span
                                                class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">refresh</span>
                                            <span class="col-span-2 text-gray-600 dark:text-gray-400">{{
                                                    t('Update interval in milliseconds (1000-10000)')
                                                }}</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4">
                                            <span
                                                class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">show_league</span>
                                            <span class="col-span-2 text-gray-600 dark:text-gray-400">{{
                                                    t('true/false - Show league information')
                                                }}</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4">
                                            <span
                                                class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">show_next</span>
                                            <span class="col-span-2 text-gray-600 dark:text-gray-400">{{
                                                    t('true/false - Show next players')
                                                }}</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4">
                                            <span
                                                class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">show_cards</span>
                                            <span class="col-span-2 text-gray-600 dark:text-gray-400">{{
                                                    t('true/false - Show available cards')
                                                }}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <!-- Authenticated user modals -->
    <template v-if="isAuthenticated">
        <!-- Set Moderator Modal -->
        <SetModeratorModal
            v-if="game && (isAdmin || isModerator)"
            :game="game"
            :show="showModeratorModal"
            @close="showModeratorModal = false"
            @set-moderator="handleSetModerator"
        />

        <GameFinishModal
            v-if="game && (isAdmin || isModerator)"
            :game="game"
            :leagueId="leagueId"
            :show="showFinishModal"
            @close="showFinishModal = false"
            @finished="handleGameFinished"
        />

        <AddPlayerModal
            v-if="isAdmin && game"
            :entity-id="gameId"
            :entity-type="'match'"
            :league-id="leagueId"
            :show="showAddPlayerModal"
            @added="handlePlayerAdded"
            @close="showAddPlayerModal = false"
        />

        <RebuyPlayerModal
            v-if="isAdmin && game?.allow_rebuy"
            :show="showRebuyModal"
            :game="game"
            :league-id="leagueId"
            @close="showRebuyModal = false"
            @added="handleRebuyAdded"
        />
    </template>
</template>
