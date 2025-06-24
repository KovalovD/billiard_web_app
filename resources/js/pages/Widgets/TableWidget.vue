// resources/js/pages/Widgets/TableWidget.vue
<script lang="ts" setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {TrophyIcon} from 'lucide-vue-next'

interface WidgetData {
    tournament: {
        id: number
        name: string
        races_to: number
        game_name: string | null
    }
    table: {
        id: number
        name: string
        stream_url: string | null
    }
    active_match: {
        id: number
        match_code: string
        round_display: string
        stage_display: string
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

const fetchWidgetData = async (): Promise<void> => {
    if (!tournamentId || !tableId) {
        error.value = 'Missing tournament or table ID'
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

        <!-- Active Match -->
        <div v-else-if="widgetData?.active_match" class="w-full h-full flex gap-2 items-center">
            <!-- Match Score -->
            <div
                :class="[activeMatchClasses, 'rounded border flex-1 px-4 py-2 h-full flex items-center justify-between']">
                <!-- Player 1 -->
                <div class="flex-1 min-w-0">
                    <div class="text-right">
                        <h2 class="text-base font-bold leading-tight truncate">
                            {{ widgetData.active_match.player1?.full_name || 'TBD' }}
                        </h2>
                        <div class="text-xs opacity-60 mt-0.5">
                            {{ widgetData.active_match.match_code }}
                        </div>
                    </div>
                </div>

                <!-- Score -->
                <div class="px-6 text-center flex-shrink-0">
                    <div class="text-3xl font-bold">
                        <span
                            :class="getScoreColor(widgetData.active_match.player1_score, widgetData.active_match.player2_score, true)">
                            {{ widgetData.active_match.player1_score }}
                        </span>
                        <span class="mx-2 opacity-50 text-2xl">-</span>
                        <span
                            :class="getScoreColor(widgetData.active_match.player1_score, widgetData.active_match.player2_score, false)">
                            {{ widgetData.active_match.player2_score }}
                        </span>
                    </div>
                    <div class="text-[10px] opacity-50 mt-0.5">Race to {{ widgetData.tournament.races_to }}</div>
                </div>

                <!-- Player 2 -->
                <div class="flex-1 min-w-0">
                    <div class="text-left">
                        <h2 class="text-base font-bold leading-tight truncate">
                            {{ widgetData.active_match.player2?.full_name || 'TBD' }}
                        </h2>
                        <div class="text-xs opacity-60 mt-0.5">
                            {{ widgetData.table.name }}
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
                        <div class="text-xs opacity-60">{{ widgetData.table.name }} • No Active Match</div>
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
</style>
