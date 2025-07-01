// resources/js/pages/Widgets/StreamingWidget.vue
<script lang="ts" setup>
import LivesTracker from '@/Components/League/MultiplayerGame/LivesTracker.vue'
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {ArrowDownIcon, ArrowRightIcon, HandHelpingIcon, HandIcon, TrophyIcon, UsersIcon} from 'lucide-vue-next'

interface GameData {
    game: {
        id: number
        name: string
        status: string
        initial_lives: number
        league_name: string
        game_type: string
    }
    current_player: {
        id: number
        division: string
        user: {
            id: number
            firstname: string
            lastname: string
            full_name: string
        }
        lives: number
        turn_order: number
        cards: Record<string, boolean>
        available_cards_count: number
    } | null
    stats: {
        active_players: number
        total_players: number
        eliminated_players: number
    }
    eliminated_players: Array<{
        user: {
            firstname: string
            lastname: string
            full_name: string
        }
        finish_position: number
        eliminated_at: string
    }>
    next_players: Array<{
        division: string
        user: {
            firstname: string
            lastname: string
            full_name: string
        }
        lives: number
        turn_order: number
    }>
    timestamp: number
}

const urlParams = new URLSearchParams(window.location.search)
const leagueId = urlParams.get('league')
const gameId = urlParams.get('game')
const refreshInterval = parseInt(urlParams.get('refresh') || '3000')
const theme = urlParams.get('theme') || 'dark'
const orientation = urlParams.get('orientation') || 'horizontal'
const showLeague = urlParams.get('show_league') !== 'false'
const showNextPlayers = urlParams.get('show_next') !== 'false'
const showCards = urlParams.get('show_cards') !== 'false'
const showEliminated = urlParams.get('show_eliminated') === 'true' // Only in vertical mode

const gameData = ref<GameData | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const lastUpdate = ref<Date | null>(null)
const connectionStatus = ref<'connected' | 'disconnected' | 'error'>('disconnected')
const isTransitioning = ref(false)
const currentPlayerId = ref<number | null>(null)

let dataPollingInterval: number | null = null

const isGameActive = computed(() => gameData.value?.game?.status === 'in_progress')

const containerClasses = computed(() => {
    if (orientation === 'vertical') {
        return themeClasses.value + ' min-h-screen p-6 font-sans'
    }
    return themeClasses.value + ' h-[100px] p-2 font-sans flex items-center justify-center overflow-hidden'
})

const themeClasses = computed(() => {
    switch (theme) {
        case 'light':
            return 'bg-white text-gray-900'
        case 'transparent':
            return 'bg-transparent text-white'
        default:
            return 'bg-gray-900 text-white'
    }
})

const cardClasses = computed(() => {
    switch (theme) {
        case 'light':
            return 'bg-gray-50 border-gray-200'
        case 'transparent':
            return 'bg-black/40 backdrop-blur-sm border-white/20'
        default:
            return 'bg-gray-800 border-gray-700'
    }
})

const currentPlayerClasses = computed(() => {
    const base = isTransitioning.value ? 'animate-glow' : ''
    switch (theme) {
        case 'light':
            return `bg-green-50 border-green-300 shadow-sm transition-shadow duration-1000 ${base}`
        case 'transparent':
            return `bg-green-500/20 backdrop-blur-sm border-green-500/40 transition-shadow duration-1000 ${base}`
        default:
            return `bg-green-900/30 border-green-700 transition-shadow duration-1000 ${base}`
    }
})

const fetchGameData = async (): Promise<void> => {
    if (!leagueId || !gameId) {
        error.value = 'Missing league or game ID'
        return
    }
    try {
        const response = await fetch(`/api/widgets/killer-pool/leagues/${leagueId}/games/${gameId}`)
        if (!response.ok) throw new Error(`HTTP ${response.status}`)

        const newData = await response.json()

        // Check if current player changed
        if (gameData.value?.current_player?.id !== newData.current_player?.id) {
            isTransitioning.value = true
            setTimeout(() => {
                isTransitioning.value = false
            }, 3000) // Increased to 3 seconds for smoother fade out
        }

        gameData.value = newData
        currentPlayerId.value = newData.current_player?.id || null
        lastUpdate.value = new Date()
        connectionStatus.value = 'connected'
        error.value = null
    } catch (err: any) {
        error.value = `Failed to fetch: ${err.message}`
        connectionStatus.value = 'error'
        console.error('Failed to fetch game data:', err)
    } finally {
        isLoading.value = false
    }
}

const getCardIcon = (cardType: string) => {
    switch (cardType) {
        case 'skip_turn':
            return ArrowDownIcon
        case 'pass_turn':
            return ArrowRightIcon
        case 'hand_shot':
            return HandIcon
        case 'handicap':
            return HandHelpingIcon
        default:
            return null
    }
}

const getCardColor = (cardType: string): string => {
    switch (cardType) {
        case 'skip_turn':
            return 'text-orange-400 border-orange-400'
        case 'pass_turn':
            return 'text-blue-400 border-blue-400'
        case 'hand_shot':
            return 'text-purple-400 border-purple-400'
        case 'handicap':
            return 'text-green-400 border-green-400'
        default:
            return 'text-gray-400 border-gray-400'
    }
}

const startPolling = (): void => {
    fetchGameData()
    dataPollingInterval = setInterval(fetchGameData, refreshInterval) as unknown as number
}

const stopPolling = (): void => {
    if (dataPollingInterval) {
        clearInterval(dataPollingInterval)
        dataPollingInterval = null
    }
}

onMounted(startPolling)
onUnmounted(stopPolling)
</script>

<template>
    <div :class="containerClasses">
        <!-- Error State -->
        <div v-if="error" class="text-center">
            <div :class="orientation === 'vertical' ? 'text-red-400 text-lg' : 'text-red-400 text-xs'">
                ⚠️ {{ error }}
            </div>
        </div>

        <!-- Loading State -->
        <div v-else-if="isLoading" class="text-center">
            <div :class="orientation === 'vertical' ? 'text-lg animate-pulse' : 'text-xs animate-pulse'">
                Loading...
            </div>
        </div>

        <!-- Game Not Active -->
        <div v-else-if="!isGameActive" class="text-center">
            <div :class="orientation === 'vertical' ? 'text-xl font-medium' : 'text-sm font-medium'">
                🎱 {{ gameData?.game?.status || 'Not Active' }}
            </div>
        </div>

        <!-- Horizontal Layout (100px) -->
        <div v-else-if="gameData && orientation === 'horizontal'" class="w-full h-full flex gap-2 items-center">
            <!-- League Info Section -->
            <div v-if="showLeague"
                 :class="[cardClasses, 'rounded border px-3 py-2 h-full flex items-center min-w-[160px] transition-all duration-300']">
                <div class="flex items-center gap-2">
                    <TrophyIcon class="w-4 h-4 opacity-60 flex-shrink-0"/>
                    <div class="min-w-0">
                        <h1 class="text-sm font-bold leading-tight truncate">{{ gameData.game.name }}</h1>
                        <p class="text-xs opacity-60 truncate">{{ gameData.game.league_name }}</p>
                        <div class="flex items-center gap-1 text-xs mt-0.5">
                            <UsersIcon class="w-3 h-3 opacity-60"/>
                            <span class="font-medium transition-all duration-300">{{
                                    gameData.stats.active_players
                                }}/{{ gameData.stats.total_players }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Player Section -->
            <Transition
                name="player-change"
                mode="out-in"
            >
                <div v-if="gameData.current_player"
                     :key="gameData.current_player.id"
                     :class="[currentPlayerClasses, 'rounded border flex-1 px-4 py-2 h-full flex items-center justify-between']">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex flex-col justify-center min-w-0">
                            <div
                                class="text-[10px] font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide leading-none mb-0.5 animate-pulse">
                                NOW PLAYING
                            </div>
                            <h2 class="text-base font-bold leading-tight truncate">
                                {{ gameData.current_player.user.full_name }}
                            </h2>
                            <p class="text-xs opacity-60 leading-none mt-0.5">Division {{
                                    gameData.current_player.division
                                }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <Transition name="lives-update" mode="out-in">
                            <LivesTracker
                                :key="`${gameData.current_player.id}-${gameData.current_player.lives}`"
                                :lives="gameData.current_player.lives"
                                :max-lives="gameData.game.initial_lives"
                                size="sm"
                            />
                        </Transition>

                        <!-- Cards -->
                        <Transition name="cards-update">
                            <div v-if="showCards && gameData.current_player.available_cards_count > 0"
                                 class="flex items-center gap-1">
                                <template v-for="(available, cardType) in gameData.current_player.cards"
                                          :key="cardType">
                                    <Transition name="card-flip">
                                        <div
                                            v-if="available"
                                            :class="['flex items-center justify-center w-7 h-7 rounded-full border transition-all duration-300 hover:scale-110', getCardColor(cardType)]"
                                            :title="cardType.replace('_', ' ')"
                                        >
                                            <component :is="getCardIcon(cardType)"
                                                       :class="['w-3.5 h-3.5', getCardColor(cardType)]"/>
                                        </div>
                                    </Transition>
                                </template>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>

            <!-- Next Players Section -->
            <div v-if="showNextPlayers && gameData.next_players.length > 0"
                 :class="[cardClasses, 'rounded border px-3 py-2 h-full flex flex-col justify-center min-w-[200px] transition-all duration-300']">
                <div class="text-[10px] font-semibold opacity-60 uppercase tracking-wide mb-1">Next Up</div>
                <TransitionGroup name="list-slide" tag="div" class="space-y-0.5">
                    <div
                        v-for="(player, index) in gameData.next_players.slice(0, 2)"
                        :key="`next-${player.user.full_name}-${player.turn_order}`"
                        class="flex items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-[10px] font-medium opacity-40">{{ index + 2 }}.</span>
                            <div class="min-w-0">
                                <p class="text-xs font-medium leading-tight truncate">{{ player.user.full_name }}</p>
                                <p class="text-[10px] opacity-50 leading-none">Div {{ player.division }}</p>
                            </div>
                        </div>
                        <LivesTracker
                            :lives="player.lives"
                            :max-lives="gameData.game.initial_lives"
                            size="xs"
                        />
                    </div>
                </TransitionGroup>
            </div>
        </div>

        <!-- Vertical Layout (Full Height) -->
        <div v-else-if="gameData && orientation === 'vertical'" class="max-w-lg mx-auto space-y-4">
            <!-- League Header -->
            <div v-if="showLeague"
                 :class="[cardClasses, 'rounded-lg border p-6 text-center transition-all duration-300']">
                <div class="flex items-center justify-center gap-3 mb-2">
                    <TrophyIcon class="w-8 h-8 opacity-60"/>
                    <div>
                        <h1 class="text-2xl font-bold">{{ gameData.game.name }}</h1>
                    </div>
                </div>
                <p class="text-lg opacity-70">{{ gameData.game.league_name }}</p>
                <div class="flex items-center justify-center gap-2 text-base mt-3">
                    <UsersIcon class="w-5 h-5 opacity-60"/>
                    <span class="font-medium transition-all duration-300">{{
                            gameData.stats.active_players
                        }}/{{ gameData.stats.total_players }} Players</span>
                </div>
            </div>

            <!-- Current Player -->
            <Transition
                name="player-change-vertical"
                mode="out-in"
            >
                <div v-if="gameData.current_player"
                     :key="gameData.current_player.id"
                     :class="[currentPlayerClasses, 'rounded-lg border p-6']">
                    <div class="text-center">
                        <div
                            class="text-sm font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide mb-2 animate-pulse">
                            🎯 NOW PLAYING
                        </div>
                        <h2 class="text-3xl font-bold mb-1">
                            {{ gameData.current_player.user.full_name }}
                        </h2>
                        <p class="text-lg opacity-70 mb-4">Division {{ gameData.current_player.division }}</p>

                        <Transition name="lives-update" mode="out-in">
                            <div :key="`${gameData.current_player.id}-${gameData.current_player.lives}`"
                                 class="flex justify-center mb-4">
                                <LivesTracker
                                    :lives="gameData.current_player.lives"
                                    :max-lives="gameData.game.initial_lives"
                                    size="lg"
                                />
                            </div>
                        </Transition>

                        <!-- Cards -->
                        <Transition name="cards-update">
                            <div v-if="showCards && gameData.current_player.available_cards_count > 0"
                                 class="pt-4 border-t">
                                <div class="text-sm opacity-60 mb-3">Available Cards</div>
                                <TransitionGroup name="card-flip" tag="div" class="flex justify-center gap-2">
                                    <div
                                        v-for="(available, cardType) in gameData.current_player.cards"
                                        v-show="available"
                                        :key="cardType"
                                        :class="['flex items-center justify-center w-12 h-12 rounded-full border-2 transition-all duration-300 hover:scale-110', getCardColor(cardType)]"
                                        :title="cardType.replace('_', ' ')"
                                    >
                                        <component :is="getCardIcon(cardType)"
                                                   :class="['w-6 h-6', getCardColor(cardType)]"/>
                                    </div>
                                </TransitionGroup>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>

            <!-- Next Players -->
            <div v-if="showNextPlayers && gameData.next_players.length > 0"
                 :class="[cardClasses, 'rounded-lg border p-6 transition-all duration-300']">
                <h3 class="text-lg font-semibold text-center mb-4 opacity-70">Next Players</h3>
                <TransitionGroup name="list-slide-vertical" tag="div" class="space-y-3">
                    <div
                        v-for="(player, index) in gameData.next_players.slice(0, 5)"
                        :key="`next-${player.user.full_name}-${player.turn_order}`"
                        :class="[
                            'flex items-center justify-between p-3 rounded-lg transition-all duration-300',
                            index === 0 ? 'bg-yellow-500/10 border border-yellow-500/30' : 'bg-gray-500/10'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold opacity-60">{{ index + 2 }}.</span>
                            <div>
                                <p class="text-base font-medium">{{ player.user.full_name }}</p>
                                <p class="text-sm opacity-60">Division {{ player.division }}</p>
                            </div>
                        </div>
                        <LivesTracker
                            :lives="player.lives"
                            :max-lives="gameData.game.initial_lives"
                            size="sm"
                        />
                    </div>
                </TransitionGroup>
            </div>

            <!-- Recently Eliminated (Vertical Only) -->
            <Transition name="fade">
                <div v-if="showEliminated && gameData.eliminated_players.length > 0"
                     :class="[cardClasses, 'rounded-lg border p-6 transition-all duration-300']">
                    <h3 class="text-lg font-semibold text-center mb-4 opacity-70">Recently Eliminated</h3>
                    <TransitionGroup name="list-fade" tag="div" class="space-y-2">
                        <div
                            v-for="player in gameData.eliminated_players.slice(-3).reverse()"
                            :key="`eliminated-${player.user.full_name}`"
                            class="flex items-center justify-between p-2 rounded bg-red-500/10 transition-all duration-300"
                        >
                            <div>
                                <p class="text-sm font-medium">{{ player.user.full_name }}</p>
                                <p class="text-xs opacity-60">Position: {{ player.finish_position }}</p>
                            </div>
                            <span class="text-xs opacity-50">❌</span>
                        </div>
                    </TransitionGroup>
                </div>
            </Transition>

            <!-- Last Update Time -->
            <div class="text-center text-sm opacity-50 pt-4">
                Last updated: {{ lastUpdate?.toLocaleTimeString() || 'Never' }}
            </div>
        </div>

        <!-- Connection Status Indicator -->
        <div :class="orientation === 'vertical' ? 'fixed bottom-6 right-6' : 'absolute bottom-1 right-1'">
            <div
                :class="[
                    orientation === 'vertical' ? 'w-3 h-3' : 'w-1.5 h-1.5',
                    'rounded-full transition-all duration-300',
                    connectionStatus === 'connected' ? 'bg-green-500' :
                    connectionStatus === 'error' ? 'bg-red-500' : 'bg-yellow-500'
                ]"
                :title="connectionStatus"
            />
        </div>
    </div>
</template>

<style scoped>
.font-sans {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Ensure proper text rendering at small sizes */
* {
    text-rendering: optimizeLegibility;
}

/* Smooth glow animation for current player change */
@keyframes glow {
    0% {
        box-shadow: 0 0 5px rgba(34, 197, 94, 0.5);
    }
    30% {
        box-shadow: 0 0 20px rgba(34, 197, 94, 0.8), 0 0 30px rgba(34, 197, 94, 0.6);
    }
    60% {
        box-shadow: 0 0 20px rgba(34, 197, 94, 0.8), 0 0 30px rgba(34, 197, 94, 0.6);
    }
    100% {
        box-shadow: 0 0 5px rgba(34, 197, 94, 0.5);
    }
}

.animate-glow {
    animation: glow 3s ease-in-out forwards;
}

/* Player change transitions - Horizontal */
.player-change-enter-active,
.player-change-leave-active {
    transition: all 0.5s ease;
}

.player-change-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.player-change-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

/* Player change transitions - Vertical */
.player-change-vertical-enter-active,
.player-change-vertical-leave-active {
    transition: all 0.5s ease;
}

.player-change-vertical-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

.player-change-vertical-leave-to {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
}

/* Lives update transition */
.lives-update-enter-active,
.lives-update-leave-active {
    transition: all 0.3s ease;
}

.lives-update-enter-from,
.lives-update-leave-to {
    opacity: 0;
    transform: scale(0.8);
}

/* Cards update transition */
.cards-update-enter-active,
.cards-update-leave-active {
    transition: all 0.4s ease;
}

.cards-update-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.cards-update-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

/* Card flip animation */
.card-flip-enter-active,
.card-flip-leave-active {
    transition: all 0.4s ease;
}

.card-flip-enter-from {
    opacity: 0;
    transform: rotateY(90deg);
}

.card-flip-leave-to {
    opacity: 0;
    transform: rotateY(-90deg);
}

/* List slide transitions - Horizontal */
.list-slide-move,
.list-slide-enter-active,
.list-slide-leave-active {
    transition: all 0.5s ease;
}

.list-slide-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.list-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.list-slide-leave-active {
    position: absolute;
}

/* List slide transitions - Vertical */
.list-slide-vertical-move,
.list-slide-vertical-enter-active,
.list-slide-vertical-leave-active {
    transition: all 0.6s ease;
}

.list-slide-vertical-enter-from {
    opacity: 0;
    transform: translateX(-20px);
}

.list-slide-vertical-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

/* Fade transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* List fade transitions */
.list-fade-move,
.list-fade-enter-active,
.list-fade-leave-active {
    transition: all 0.5s ease;
}

.list-fade-enter-from,
.list-fade-leave-to {
    opacity: 0;
    transform: scale(0.95);
}
</style>
