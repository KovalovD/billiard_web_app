<script lang="ts" setup>
import LivesTracker from '@/Components/LivesTracker.vue'
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {ArrowDownIcon, ArrowRightIcon, HandIcon} from 'lucide-vue-next'

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

interface GameStatus {
    status: string
    current_player_id: number | null
    updated_at: number
    active_players_count: number
}

const urlParams = new URLSearchParams(window.location.search)
const leagueId = urlParams.get('league')
const gameId = urlParams.get('game')
const refreshInterval = parseInt(urlParams.get('refresh') || '3000')
const theme = urlParams.get('theme') || 'dark'
const orientation = urlParams.get('orientation') || 'horizontal'
const leaguePart = urlParams.get('league_part') || 'yes'
const nextPlayerPart = urlParams.get('next_player_part') || 'yes'

const gameData = ref<GameData | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const lastUpdate = ref<Date | null>(null)
const connectionStatus = ref<'connected' | 'disconnected' | 'error'>('disconnected')

let dataPollingInterval: number | null = null
let statusPollingInterval: number | null = null

const isGameActive = computed(() => gameData.value?.game?.status === 'in_progress')

const themeClasses = computed(() => {
    switch (theme) {
        case 'light':
            return 'bg-white text-gray-900 border-gray-200'
        case 'transparent':
            return 'bg-transparent text-white'
        default:
            return 'bg-gray-900 text-white border-gray-700'
    }
})

const cardClasses = computed(() => {
    switch (theme) {
        case 'light':
            return 'bg-white border-gray-200'
        case 'transparent':
            return 'bg-black/30 backdrop-blur-sm border-white/20'
        default:
            return 'bg-gray-800 border-gray-600'
    }
})

const orientationClasses = computed(() =>
    orientation === 'horizontal'
        ? 'flex flex-row items-stretch justify-center gap-2 w-full'
        : 'flex flex-col max-w-md mx-auto gap-4'
)

const fetchGameData = async (): Promise<void> => {
    if (!leagueId || !gameId) {
        error.value = 'Missing league or game ID in URL parameters'
        return
    }
    try {
        const response = await fetch(`/api/widgets/streaming/leagues/${leagueId}/games/${gameId}`)
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        gameData.value = await response.json()
        lastUpdate.value = new Date()
        connectionStatus.value = 'connected'
        error.value = null
    } catch (err: any) {
        error.value = `Failed to fetch game data: ${err.message}`
        connectionStatus.value = 'error'
        console.error('Failed to fetch game data:', err)
    } finally {
        isLoading.value = false
    }
}

const fetchGameStatus = async (): Promise<void> => {
    if (!leagueId || !gameId || !gameData.value) return
    try {
        const response = await fetch(`/api/widgets/streaming/leagues/${leagueId}/games/${gameId}/status`)
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        const status: GameStatus = await response.json()
        const needsRefresh =
            status.status !== gameData.value.game.status ||
            status.current_player_id !== gameData.value.current_player?.user.id ||
            status.active_players_count !== gameData.value.stats.active_players
        if (needsRefresh) await fetchGameData()
        connectionStatus.value = 'connected'
    } catch (err: any) {
        connectionStatus.value = 'error'
        console.error('Failed to fetch game status:', err)
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
        default:
            return null
    }
}

const getCardColor = (cardType: string): string => {
    switch (cardType) {
        case 'skip_turn':
            return 'text-orange-400'
        case 'pass_turn':
            return 'text-blue-400'
        case 'hand_shot':
            return 'text-purple-400'
        default:
            return 'text-gray-400'
    }
}

const startPolling = (): void => {
    fetchGameData()
    dataPollingInterval = setInterval(fetchGameData, refreshInterval * 2) as unknown as number
    statusPollingInterval = setInterval(fetchGameStatus, refreshInterval) as unknown as number
}

const stopPolling = (): void => {
    if (dataPollingInterval) {
        clearInterval(dataPollingInterval)
        dataPollingInterval = null
    }
    if (statusPollingInterval) {
        clearInterval(statusPollingInterval)
        statusPollingInterval = null
    }
}

onMounted(startPolling)
onUnmounted(stopPolling)
</script>

<template>
    <div :class="[themeClasses, orientation === 'horizontal' ? 'p-2' : 'min-h-screen p-4', 'font-sans']">
        <div v-if="error" class="text-center p-8">
            <div class="text-red-400 text-lg mb-2">⚠️ Error</div>
            <div class="text-sm opacity-75">{{ error }}</div>
            <div class="text-xs opacity-50 mt-2">Check URL parameters: ?league=ID&game=ID</div>
        </div>
        <div v-else-if="isLoading" class="text-center p-8">
            <div class="text-lg">🔄 Loading...</div>
        </div>
        <div v-else-if="!isGameActive" class="text-center p-8">
            <div class="text-lg mb-2">🎱 Game Not Active</div>
            <div class="text-sm opacity-75">Status: {{ gameData?.game?.status || 'Unknown' }}</div>
        </div>
        <div v-else-if="gameData" :class="[orientationClasses]">
            <!-- League Info -->
            <div v-if="leaguePart === 'yes'"
                 :class="[
                     cardClasses,
                     'border',
                     orientation === 'horizontal' ? 'px-4 py-2 flex-shrink-0' : 'p-4 flex-1 min-w-[16rem]'
                 ]">
                <div :class="orientation === 'horizontal' ? 'flex items-center gap-3' : 'text-center'">
                    <h1 :class="orientation === 'horizontal' ? 'text-sm font-bold' : 'text-lg font-bold mb-1'">
                        {{ gameData.game.name }}
                    </h1>
                    <div v-if="orientation === 'horizontal'" class="text-xs opacity-75">
                        {{ gameData.game.league_name }} • {{
                            gameData.stats.active_players
                        }}/{{ gameData.stats.total_players }}
                    </div>
                    <div v-else>
                        <div class="text-sm opacity-75">{{ gameData.game.league_name }}</div>
                        <div class="text-xs opacity-60 mt-1">
                            {{ gameData.stats.active_players }}/{{ gameData.stats.total_players }} Players Active
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Player -->
            <div v-if="gameData.current_player"
                 :class="[
                     cardClasses,
                     'border',
                     orientation === 'horizontal' ? 'px-4 py-2 flex-1' : 'p-6 flex-1 min-w-[16rem]'
                 ]">
                <!-- Horizontal Layout -->
                <div v-if="orientation === 'horizontal'" class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-600 text-white text-xs font-semibold">
                            Current
                        </div>
                        <h2 class="text-base font-bold">
                            {{ gameData.current_player.user.full_name }}
                        </h2>
                    </div>

                    <LivesTracker :lives="gameData.current_player.lives" :max-lives="gameData.game.initial_lives"
                                  size="sm"/>

                    <div v-if="gameData.current_player.available_cards_count > 0"
                         class="ml-auto flex items-center gap-1">
                        <div class="flex justify-center gap-1">
                            <template v-for="(available, cardType) in gameData.current_player.cards" :key="cardType">
                                <div
                                    v-if="available"
                                    :class="['flex items-center justify-center w-6 h-6 rounded-full border', `border-current ${getCardColor(cardType)}`]"
                                    :title="cardType.replace('_', ' ')"
                                >
                                    <component :is="getCardIcon(cardType)"
                                               :class="['w-3 h-3', getCardColor(cardType)]"/>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Vertical Layout -->
                <div v-else class="text-center">
                    <div
                        class="inline-flex items-center px-3 py-1 rounded-full bg-green-600 text-white text-sm font-semibold mb-3">
                        🎯 Current Turn
                    </div>
                    <h2 class="text-2xl font-bold mb-2">{{ gameData.current_player.user.full_name }}</h2>
                    <div class="flex justify-center items-center mb-3">
                        <LivesTracker :lives="gameData.current_player.lives" :max-lives="gameData.game.initial_lives"
                                      size="lg"/>
                    </div>
                    <div v-if="gameData.current_player.available_cards_count > 0" class="border-t pt-4">
                        <div class="text-center mb-3">
                            <div class="text-sm opacity-75 mb-2">Available Cards</div>
                            <div class="flex justify-center gap-2">
                                <template v-for="(available, cardType) in gameData.current_player.cards"
                                          :key="cardType">
                                    <div
                                        v-if="available"
                                        :class="['flex items-center justify-center w-8 h-8 rounded-full border-2', `border-current ${getCardColor(cardType)}`]"
                                        :title="cardType.replace('_', ' ')"
                                    >
                                        <component :is="getCardIcon(cardType)"
                                                   :class="['w-4 h-4', getCardColor(cardType)]"/>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Players -->
            <div v-if="gameData.next_players.length > 0 && nextPlayerPart === 'yes'"
                 :class="[
                     cardClasses,
                     'border',
                     orientation === 'horizontal' ? 'px-4 py-2' : 'p-4 flex-1 min-w-[16rem]'
                 ]">
                <!-- Horizontal Layout -->
                <div v-if="orientation === 'horizontal'">
                    <div class="flex items-center gap-3">
                        <div
                            v-for="(player, index) in gameData.next_players.slice(0, 2)"
                            :key="player.user.id"
                            :class="['flex items-center justify-between gap-2 text-xs', index === 0 ? 'font-semibold' : 'opacity-75']"
                        >
                            <div>{{ index + 2 }}. {{ player.user.full_name }}</div>
                            <LivesTracker :lives="player.lives" :max-lives="gameData.game.initial_lives" size="sm"/>
                        </div>
                    </div>
                </div>

                <!-- Vertical Layout -->
                <div v-else>
                    <div class="text-sm font-semibold mb-3 text-center opacity-75">Next Up</div>
                    <div class="space-y-2">
                        <div
                            v-for="(player, index) in gameData.next_players"
                            :key="player.user.id"
                            :class="index === 0 ? 'bg-yellow-600/20' : 'opacity-75'"
                            class="flex items-center justify-between p-2 rounded"
                        >
                            <div class="text-sm">{{ index + 2 }}. {{ player.user.full_name }}</div>
                            <LivesTracker :lives="player.lives" :max-lives="gameData.game.initial_lives" size="sm"/>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Eliminated Players (Vertical only) -->
            <div v-if="gameData.eliminated_players.length > 0 && orientation === 'vertical'"
                 :class="[cardClasses, 'border p-4 flex-1 min-w-[16rem]']">
                <div class="text-sm font-semibold mb-3 text-center opacity-75">Recent Eliminations</div>
                <div class="space-y-2">
                    <div v-for="player in gameData.eliminated_players"
                         :key="player.user.full_name"
                         class="flex items-center justify-between p-2 rounded opacity-75">
                        <div class="text-sm">{{ player.finish_position }}. {{ player.user.full_name }}</div>
                        <div class="text-xs">💀</div>
                    </div>
                </div>
            </div>

            <!-- Connection Status (Vertical) -->
            <div v-if="orientation === 'vertical'" class="w-full text-center text-xs opacity-50">
                <div class="flex items-center justify-center gap-2">
                    <span
                        :class="connectionStatus === 'connected' ? 'text-green-400' :
                                connectionStatus === 'error' ? 'text-red-400' : 'text-yellow-400'"
                    >
                        {{
                            connectionStatus === 'connected' ? '🟢' :
                                connectionStatus === 'error' ? '🔴' : '🟡'
                        }}
                    </span>
                    <span v-if="lastUpdate">
                        Last update: {{ lastUpdate.toLocaleTimeString() }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-sans {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
