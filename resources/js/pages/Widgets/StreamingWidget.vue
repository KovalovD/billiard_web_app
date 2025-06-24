// resources/js/pages/Widgets/StreamingWidget.vue
<script lang="ts" setup>
import LivesTracker from '@/Components/LivesTracker.vue'
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
const showLeague = urlParams.get('show_league') !== 'false'
const showNextPlayers = urlParams.get('show_next') !== 'false'
const showCards = urlParams.get('show_cards') !== 'false'

const gameData = ref<GameData | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const lastUpdate = ref<Date | null>(null)
const connectionStatus = ref<'connected' | 'disconnected' | 'error'>('disconnected')

let dataPollingInterval: number | null = null

const isGameActive = computed(() => gameData.value?.game?.status === 'in_progress')

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
    switch (theme) {
        case 'light':
            return 'bg-green-50 border-green-300 shadow-sm'
        case 'transparent':
            return 'bg-green-500/20 backdrop-blur-sm border-green-500/40'
        default:
            return 'bg-green-900/30 border-green-700'
    }
})

const fetchGameData = async (): Promise<void> => {
    if (!leagueId || !gameId) {
        error.value = 'Missing league or game ID'
        return
    }
    try {
        const response = await fetch(`/api/widgets/streaming/leagues/${leagueId}/games/${gameId}`)
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        gameData.value = await response.json()
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
    <div :class="[themeClasses, 'h-[100px] p-2 font-sans flex items-center justify-center overflow-hidden']">
        <!-- Error State -->
        <div v-if="error" class="text-center">
            <div class="text-red-400 text-xs">⚠️ {{ error }}</div>
        </div>

        <!-- Loading State -->
        <div v-else-if="isLoading" class="text-center">
            <div class="text-xs animate-pulse">Loading...</div>
        </div>

        <!-- Game Not Active -->
        <div v-else-if="!isGameActive" class="text-center">
            <div class="text-sm font-medium">🎱 {{ gameData?.game?.status || 'Not Active' }}</div>
        </div>

        <!-- Main Widget Content -->
        <div v-else-if="gameData" class="w-full h-full flex gap-2 items-center">
            <!-- League Info Section -->
            <div v-if="showLeague"
                 :class="[cardClasses, 'rounded border px-3 py-2 h-full flex items-center min-w-[160px]']">
                <div class="flex items-center gap-2">
                    <TrophyIcon class="w-4 h-4 opacity-60 flex-shrink-0"/>
                    <div class="min-w-0">
                        <h1 class="text-sm font-bold leading-tight truncate">{{ gameData.game.name }}</h1>
                        <p class="text-xs opacity-60 truncate">{{ gameData.game.league_name }}</p>
                        <div class="flex items-center gap-1 text-xs mt-0.5">
                            <UsersIcon class="w-3 h-3 opacity-60"/>
                            <span class="font-medium">{{
                                    gameData.stats.active_players
                                }}/{{ gameData.stats.total_players }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Player Section -->
            <div v-if="gameData.current_player"
                 :class="[currentPlayerClasses, 'rounded border flex-1 px-4 py-2 h-full flex items-center justify-between']">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex flex-col justify-center min-w-0">
                        <div
                            class="text-[10px] font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide leading-none mb-0.5">
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
                    <LivesTracker
                        :lives="gameData.current_player.lives"
                        :max-lives="gameData.game.initial_lives"
                        size="sm"
                    />

                    <!-- Cards -->
                    <div v-if="showCards && gameData.current_player.available_cards_count > 0"
                         class="flex items-center gap-1">
                        <template v-for="(available, cardType) in gameData.current_player.cards" :key="cardType">
                            <div
                                v-if="available"
                                :class="['flex items-center justify-center w-7 h-7 rounded-full border', getCardColor(cardType)]"
                                :title="cardType.replace('_', ' ')"
                            >
                                <component :is="getCardIcon(cardType)"
                                           :class="['w-3.5 h-3.5', getCardColor(cardType)]"/>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Next Players Section -->
            <div v-if="showNextPlayers && gameData.next_players.length > 0"
                 :class="[cardClasses, 'rounded border px-3 py-2 h-full flex flex-col justify-center min-w-[200px]']">
                <div class="text-[10px] font-semibold opacity-60 uppercase tracking-wide mb-1">Next Up</div>
                <div class="space-y-0.5">
                    <div
                        v-for="(player, index) in gameData.next_players.slice(0, 2)"
                        :key="player.user.full_name"
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
                </div>
            </div>
        </div>

        <!-- Connection Status Indicator -->
        <div class="absolute bottom-1 right-1">
            <div
                :class="[
                    'w-1.5 h-1.5 rounded-full',
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
</style>
