<script lang="ts" setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {TrophyIcon} from 'lucide-vue-next'

interface FrameScore {
    player1: number
    player2: number
    finished?: boolean
}

interface CurrentFrame {
    index: number
    player1_score: number
    player2_score: number
}

interface WidgetData {
    tournament: {
        id: number
        name: string
        races_to: number
        game_name: string | null
        game_type: 'pool' | 'pyramid' | 'snooker'
    }
    table: {
        id: number
        name: string
        stream_url: string | null
    }
    active_match: {
        id: number
        match_code: string
        player1: {
            id: number
            firstname: string
            lastname: string
            full_name: string
        } | null
        player2: {
            id: number
            firstname: string
            lastname: string
            full_name: string
        } | null
        player1_score: number
        player2_score: number
        frame_scores: FrameScore[]
        current_frame: CurrentFrame | null
        status: string
        status_display: string
        started_at: string | null
    } | null
    timestamp: number
}

const urlParams = new URLSearchParams(window.location.search)
const tournamentId = urlParams.get('tournament')
const tableId = urlParams.get('table')
const refreshInterval = parseInt(urlParams.get('refresh') || '3000')
const theme = urlParams.get('theme') || 'dark'

const widgetData = ref<WidgetData | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)

let dataPollingInterval: number | null = null

const isFrameBased = computed(() => {
    return widgetData.value?.tournament.game_type !== 'pool'
})

const gameType = computed(() => {
    return widgetData.value?.tournament.game_type || 'pool'
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

const activeMatchClasses = computed(() => {
    switch (theme) {
        case 'light':
            return 'bg-blue-50 border-blue-200'
        case 'transparent':
            return 'bg-blue-500/20 backdrop-blur-sm border-blue-500/40'
        default:
            return 'bg-blue-900/30 border-blue-700'
    }
})

const frameScoreClasses = computed(() => {
    switch (theme) {
        case 'light':
            return 'text-gray-600'
        case 'transparent':
            return 'text-white/70'
        default:
            return 'text-gray-400'
    }
})

const fetchWidgetData = async (): Promise<void> => {
    if (!tournamentId || !tableId) {
        error.value = t('Missing tournament or table ID')
        return
    }

    try {
        const response = await fetch(`/api/widgets/table/tournaments/${tournamentId}/tables/${tableId}`)
        if (!response.ok) throw new Error(`HTTP ${response.status}`)

        widgetData.value = await response.json()
        error.value = null
    } catch (err: any) {
        error.value = `Failed to fetch: ${err.message}`
        console.error('Failed to fetch widget data:', err)
    } finally {
        isLoading.value = false
    }
}

const getScoreColor = (score1: number, score2: number, isPlayer1: boolean): string => {
    if (score1 === score2) return ''

    if (theme === 'light') {
        return (isPlayer1 && score1 > score2) || (!isPlayer1 && score2 > score1)
            ? 'text-green-600'
            : ''
    }

    return (isPlayer1 && score1 > score2) || (!isPlayer1 && score2 > score1)
        ? 'text-green-400'
        : ''
}

const getFrameWinner = (frame: FrameScore): 'player1' | 'player2' | null => {
    if (gameType.value === 'pyramid') {
        if (frame.player1 === 8) return 'player1'
        if (frame.player2 === 8) return 'player2'
    } else if (gameType.value === 'snooker') {
        if (frame.player1 > frame.player2) return 'player1'
        if (frame.player2 > frame.player1) return 'player2'
    }
    return null
}

const startPolling = (): void => {
    fetchWidgetData()
    dataPollingInterval = setInterval(fetchWidgetData, refreshInterval) as unknown as number
}

const stopPolling = (): void => {
    if (dataPollingInterval) {
        clearInterval(dataPollingInterval)
        dataPollingInterval = null
    }
}

// Mock translation function
const t = (key: string) => key

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
            <div class="text-xs animate-pulse">{{ t('Loading...') }}</div>
        </div>

        <!-- Active Match -->
        <div v-else-if="widgetData?.active_match" class="w-full h-full">
            <!-- Frame-based layout -->
            <div v-if="isFrameBased" class="h-full flex flex-col justify-center">
                <!-- Players and Match Score -->
                <div class="flex items-center justify-between mb-1">
                    <div class="flex-1 min-w-0 text-right pr-3">
                        <span class="text-sm font-bold truncate">
                            {{ widgetData.active_match.player1?.full_name || 'TBD' }}
                        </span>
                    </div>

                    <div class="flex-shrink-0 px-2">
                        <span class="text-2xl font-bold"
                              :class="getScoreColor(widgetData.active_match.player1_score, widgetData.active_match.player2_score, true)">
                            {{ widgetData.active_match.player1_score }}
                        </span>
                        <span class="mx-1 opacity-50 text-xl">-</span>
                        <span class="text-2xl font-bold"
                              :class="getScoreColor(widgetData.active_match.player1_score, widgetData.active_match.player2_score, false)">
                            {{ widgetData.active_match.player2_score }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0 text-left pl-3">
                        <span class="text-sm font-bold truncate">
                            {{ widgetData.active_match.player2?.full_name || 'TBD' }}
                        </span>
                    </div>
                </div>

                <!-- Frame Scores Line -->
                <div class="text-center">
                    <div :class="[frameScoreClasses, 'text-[11px] font-mono']">
                        <template v-if="widgetData.active_match.frame_scores.length > 0">
                            <span v-for="(frame, index) in widgetData.active_match.frame_scores"
                                  :key="index"
                                  v-show="frame.finished"
                                  class="mx-1">
                                <span :class="getFrameWinner(frame) === 'player1' ? (theme === 'light' ? 'text-green-600 font-semibold' : 'text-green-400 font-semibold') : ''">
                                    {{ frame.player1 }}
                                </span>
                                <span class="opacity-50">-</span>
                                <span :class="getFrameWinner(frame) === 'player2' ? (theme === 'light' ? 'text-green-600 font-semibold' : 'text-green-400 font-semibold') : ''">
                                    {{ frame.player2 }}
                                </span>
                            </span>
                            <span v-if="widgetData.active_match.current_frame &&
                                       (widgetData.active_match.current_frame.player1_score > 0 ||
                                        widgetData.active_match.current_frame.player2_score > 0)"
                                  class="mx-1 opacity-80">
                                {{ widgetData.active_match.current_frame.player1_score }}-{{
                                    widgetData.active_match.current_frame.player2_score }}<span class="text-yellow-500">*</span>
                            </span>
                        </template>
                        <span v-else class="opacity-50">{{ t('No frames yet') }}</span>
                    </div>

                    <!-- Match Info -->
                    <div class="text-[10px] opacity-50 mt-0.5">
                        {{ widgetData.active_match.match_code }} • {{ widgetData.table.name }}
                    </div>
                </div>
            </div>

            <!-- Pool layout (original) -->
            <div v-else class="h-full flex gap-2 items-center">
                <div :class="[activeMatchClasses, 'rounded border flex-1 px-3 py-1.5 h-full flex items-center justify-between']">
                    <!-- Player 1 -->
                    <div class="flex-1 min-w-0">
                        <div class="text-right">
                            <h2 class="text-sm font-bold leading-tight truncate">
                                {{ widgetData.active_match.player1?.full_name || 'TBD' }}
                            </h2>
                            <div class="text-[10px] opacity-60 mt-0.5">
                                {{ widgetData.active_match.match_code }}
                            </div>
                        </div>
                    </div>

                    <!-- Score Section -->
                    <div class="px-4 text-center flex-shrink-0">
                        <div class="text-2xl font-bold leading-tight">
                            <span :class="getScoreColor(widgetData.active_match.player1_score, widgetData.active_match.player2_score, true)">
                                {{ widgetData.active_match.player1_score }}
                            </span>
                            <span class="mx-1.5 opacity-50 text-xl">-</span>
                            <span :class="getScoreColor(widgetData.active_match.player1_score, widgetData.active_match.player2_score, false)">
                                {{ widgetData.active_match.player2_score }}
                            </span>
                        </div>
                        <div class="text-[10px] opacity-50 mt-0.5">
                            {{ t('Race to') }} {{ widgetData.tournament.races_to }}
                        </div>
                    </div>

                    <!-- Player 2 -->
                    <div class="flex-1 min-w-0">
                        <div class="text-left">
                            <h2 class="text-sm font-bold leading-tight truncate">
                                {{ widgetData.active_match.player2?.full_name || 'TBD' }}
                            </h2>
                            <div class="text-[10px] opacity-60 mt-0.5">
                                {{ widgetData.table.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Active Match -->
        <div v-else-if="widgetData" class="w-full h-full flex items-center justify-center">
            <div :class="[cardClasses, 'rounded border px-6 py-3 text-center']">
                <div class="flex items-center gap-3">
                    <TrophyIcon class="w-5 h-5 opacity-60"/>
                    <div>
                        <div class="text-sm font-bold">{{ widgetData.tournament.name }}</div>
                        <div class="text-xs opacity-60">{{ widgetData.table.name }} • {{ t('No Active Match') }}</div>
                    </div>
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

/* Ensure proper text rendering at small sizes */
* {
    text-rendering: optimizeLegibility;
}

/* Monospace for frame scores */
.font-mono {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
}
</style>
