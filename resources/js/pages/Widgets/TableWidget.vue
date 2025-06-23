<script lang="ts" setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'

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
const layout = urlParams.get('layout') || 'horizontal'
const compact = urlParams.get('compact') === 'true'

const widgetData = ref<WidgetData | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const connectionStatus = ref<'connected' | 'disconnected' | 'error'>('disconnected')

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

const containerClasses = computed(() => {
    const classes = []

    if (theme === 'transparent') {
        classes.push('bg-black/50 backdrop-blur-sm')
    }

    // Adaptive padding based on layout
    if (layout === 'horizontal') {
        classes.push(compact ? 'p-2' : 'p-3')
    } else {
        classes.push('p-4')
    }

    return classes.join(' ')
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
        connectionStatus.value = 'connected'
        error.value = null
// eslint-disable-next-line
    } catch (err: any) {
        error.value = `Failed to fetch data`
        connectionStatus.value = 'error'
    } finally {
        isLoading.value = false
    }
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
    <div :class="[themeClasses, 'font-sans min-h-[100px] max-h-[200px] h-full flex items-center justify-center']">
        <div v-if="error" class="text-center p-2">
            <div class="text-red-400 text-sm">Error: {{ error }}</div>
        </div>

        <div v-else-if="isLoading" class="text-center p-2">
            <div class="text-sm">Loading...</div>
        </div>

        <div v-else-if="widgetData?.active_match"
             :class="[containerClasses, 'rounded-lg w-full h-full flex items-center']">
            <!-- Horizontal Layout (Adaptive Height) -->
            <div v-if="layout === 'horizontal'" class="w-full">
                <!-- Compact Version (100-130px) -->
                <div v-if="compact" class="space-y-1">
                    <!-- Single Line Header -->
                    <div class="flex items-center justify-between text-xs opacity-60">
                        <span>{{ widgetData.tournament.name }} • {{ widgetData.active_match.round_display }}</span>
                        <span>{{ widgetData.table.name }}</span>
                    </div>

                    <!-- Players and Score in One Line -->
                    <div class="flex items-center justify-between">
                        <div class="flex-1 text-right pr-4">
                            <div :class="widgetData.active_match.player1_score > widgetData.active_match.player2_score ? 'text-green-400' : ''"
                                 class="font-semibold">
                                {{ widgetData.active_match.player1?.full_name || 'TBD' }}
                            </div>
                        </div>

                        <div class="text-center px-4">
                            <div class="text-2xl font-bold">
                                <span
                                    :class="widgetData.active_match.player1_score > widgetData.active_match.player2_score ? 'text-green-400' : ''">
                                    {{ widgetData.active_match.player1_score }}
                                </span>
                                <span class="mx-2 opacity-50 text-lg">-</span>
                                <span
                                    :class="widgetData.active_match.player2_score > widgetData.active_match.player1_score ? 'text-green-400' : ''">
                                    {{ widgetData.active_match.player2_score }}
                                </span>
                            </div>
                            <div class="text-xs opacity-50">Race to {{ widgetData.tournament.races_to }}</div>
                        </div>

                        <div class="flex-1 text-left pl-4">
                            <div :class="widgetData.active_match.player2_score > widgetData.active_match.player1_score ? 'text-green-400' : ''"
                                 class="font-semibold">
                                {{ widgetData.active_match.player2?.full_name || 'TBD' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Standard Version (130-200px) -->
                <div v-else class="space-y-2">
                    <!-- Header -->
                    <div class="flex items-center justify-between text-sm opacity-75">
                        <span>{{ widgetData.tournament.name }}</span>
                        <span>{{ widgetData.table.name }}</span>
                    </div>

                    <!-- Match Info -->
                    <div class="text-center">
                        <div class="text-base font-semibold">
                            {{ widgetData.active_match.round_display }}
                            <span v-if="widgetData.active_match.stage_display" class="ml-2">
                                • {{ widgetData.active_match.stage_display }}
                            </span>
                        </div>
                    </div>

                    <!-- Players and Score -->
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-right">
                            <div class="font-bold text-lg">
                                {{ widgetData.active_match.player1?.full_name || 'TBD' }}
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="text-2xl font-bold">
                                <span
                                    :class="widgetData.active_match.player1_score > widgetData.active_match.player2_score ? 'text-green-400' : ''">
                                    {{ widgetData.active_match.player1_score }}
                                </span>
                                <span class="mx-2 opacity-50">-</span>
                                <span
                                    :class="widgetData.active_match.player2_score > widgetData.active_match.player1_score ? 'text-green-400' : ''">
                                    {{ widgetData.active_match.player2_score }}
                                </span>
                            </div>
                            <div class="text-xs opacity-50">Race to {{ widgetData.tournament.races_to }}</div>
                        </div>

                        <div class="text-left">
                            <div class="font-bold text-lg">
                                {{ widgetData.active_match.player2?.full_name || 'TBD' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vertical Layout -->
            <div v-else class="space-y-3 w-full">
                <!-- Header -->
                <div class="text-center">
                    <div class="text-xs opacity-60">{{ widgetData.tournament.name }}</div>
                    <div class="text-sm font-semibold">
                        {{ widgetData.active_match.round_display }}
                    </div>
                </div>

                <!-- Player 1 -->
                <div :class="widgetData.active_match.player1_score > widgetData.active_match.player2_score ? 'bg-green-600/20' : 'bg-gray-800/50'"
                     class="flex items-center justify-between p-2 rounded text-sm">
                    <span class="font-semibold">{{ widgetData.active_match.player1?.full_name || 'TBD' }}</span>
                    <span class="text-xl font-bold">{{ widgetData.active_match.player1_score }}</span>
                </div>

                <!-- Player 2 -->
                <div :class="widgetData.active_match.player2_score > widgetData.active_match.player1_score ? 'bg-green-600/20' : 'bg-gray-800/50'"
                     class="flex items-center justify-between p-2 rounded text-sm">
                    <span class="font-semibold">{{ widgetData.active_match.player2?.full_name || 'TBD' }}</span>
                    <span class="text-xl font-bold">{{ widgetData.active_match.player2_score }}</span>
                </div>

                <!-- Footer -->
                <div class="text-center text-xs opacity-50">
                    {{ widgetData.table.name }} • Race to {{ widgetData.tournament.races_to }}
                </div>
            </div>
        </div>

        <!-- No Active Match -->
        <div v-else-if="widgetData"
             :class="[containerClasses, 'rounded-lg text-center w-full h-full flex items-center justify-center']">
            <div>
                <div class="text-base font-semibold mb-1">{{ widgetData.tournament.name }}</div>
                <div class="text-sm opacity-75">{{ widgetData.table.name }}</div>
                <div class="mt-2 text-lg">No Active Match</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
body {
    margin: 0;
    padding: 0;
}

.font-sans {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
