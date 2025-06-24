// resources/js/pages/Widgets/TableMatch.vue
<script lang="ts" setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {MinusIcon, PlusIcon} from 'lucide-vue-next'

interface MatchData {
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
        races_to: number
    } | null
    tournament: {
        id: number
        name: string
        races_to: number
    }
    table: {
        id: number
        name: string
    }
}

const urlParams = new URLSearchParams(window.location.search)
const tournamentId = urlParams.get('tournament')
const tableId = urlParams.get('table')
const theme = urlParams.get('theme') || 'dark'

const matchData = ref<MatchData | null>(null)
const isLoading = ref(true)
const isUpdating = ref(false)
const error = ref<string | null>(null)

let dataPollingInterval: number | null = null

const isMatchFinished = computed(() => {
    if (!matchData.value?.active_match) return false
    const match = matchData.value.active_match
    return match.player1_score >= match.races_to || match.player2_score >= match.races_to
})

const themeClasses = computed(() => {
    switch (theme) {
        case 'light':
            return 'bg-gray-100 text-gray-900'
        case 'transparent':
            return 'bg-transparent text-white'
        default:
            return 'bg-gray-900 text-white'
    }
})

const cardClasses = computed(() => {
    switch (theme) {
        case 'light':
            return 'bg-white border-gray-200'
        case 'transparent':
            return 'bg-black/40 backdrop-blur-sm border-white/20'
        default:
            return 'bg-gray-800 border-gray-700'
    }
})

const buttonClasses = computed(() => {
    switch (theme) {
        case 'light':
            return {
                increment: 'bg-green-500 hover:bg-green-600 active:bg-green-700',
                decrement: 'bg-red-500 hover:bg-red-600 active:bg-red-700',
                disabled: 'bg-gray-300 cursor-not-allowed'
            }
        case 'transparent':
            return {
                increment: 'bg-green-500/80 hover:bg-green-500/90 active:bg-green-500',
                decrement: 'bg-red-500/80 hover:bg-red-500/90 active:bg-red-500',
                disabled: 'bg-gray-500/30 cursor-not-allowed'
            }
        default:
            return {
                increment: 'bg-green-600 hover:bg-green-700 active:bg-green-800',
                decrement: 'bg-red-600 hover:bg-red-700 active:bg-red-800',
                disabled: 'bg-gray-700 cursor-not-allowed'
            }
    }
})

const fetchMatchData = async (): Promise<void> => {
    if (!tournamentId || !tableId) {
        error.value = 'Missing tournament or table ID'
        return
    }

    try {
        const response = await fetch(`/api/widgets/table-match/tournaments/${tournamentId}/tables/${tableId}`)
        if (!response.ok) throw new Error(`HTTP ${response.status}`)

        matchData.value = await response.json()
        error.value = null
    } catch (err: any) {
        error.value = 'Failed to load match data'
        console.error('Failed to fetch match data:', err)
    } finally {
        isLoading.value = false
    }
}

const updateScore = async (player: 'player1' | 'player2', action: 'increment' | 'decrement') => {
    if (isUpdating.value || !matchData.value?.active_match || isMatchFinished.value) return

    const currentScore = matchData.value.active_match[`${player}_score`]
    if (action === 'decrement' && currentScore <= 0) return
    if (action === 'increment' && currentScore >= matchData.value.active_match.races_to) return

    isUpdating.value = true

    try {
        const response = await fetch(`/api/widgets/table-match/tournaments/${tournamentId}/tables/${tableId}/score`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({player, action})
        })

        if (!response.ok) throw new Error(`HTTP ${response.status}`)

        const result = await response.json()

        if (matchData.value?.active_match) {
            matchData.value.active_match.player1_score = result.player1_score
            matchData.value.active_match.player2_score = result.player2_score
            matchData.value.active_match.status = result.status
        }
    } catch (err: any) {
        console.error('Failed to update score:', err)
        await fetchMatchData()
    } finally {
        isUpdating.value = false
    }
}

const startPolling = (): void => {
    fetchMatchData()
    dataPollingInterval = setInterval(fetchMatchData, 10000) as unknown as number
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
    <div :class="[themeClasses, 'min-h-screen p-4 font-sans select-none']">
        <!-- Loading -->
        <div v-if="isLoading" class="flex items-center justify-center h-64">
            <div class="text-xl animate-pulse">Loading...</div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex items-center justify-center h-64">
            <div class="text-center">
                <div class="text-red-400 text-lg mb-4">{{ error }}</div>
                <button
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg text-base"
                    @click="fetchMatchData"
                >
                    Retry
                </button>
            </div>
        </div>

        <!-- No Active Match -->
        <div v-else-if="!matchData?.active_match" class="flex items-center justify-center h-64">
            <div :class="[cardClasses, 'rounded-lg border p-8 text-center']">
                <div class="text-2xl font-bold mb-2">{{ matchData?.tournament.name }}</div>
                <div class="text-lg opacity-70 mb-4">{{ matchData?.table.name }}</div>
                <div class="text-base opacity-50">No active match</div>
            </div>
        </div>

        <!-- Active Match -->
        <div v-else class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold">{{ matchData.tournament.name }}</h1>
                <p class="text-sm opacity-70 mt-1">
                    {{ matchData.active_match.round_display }} • {{ matchData.table.name }}
                </p>
            </div>

            <!-- Match Finished Message -->
            <div v-if="isMatchFinished" class="mb-6 p-4 bg-green-500/20 rounded-lg text-center">
                <p class="text-lg font-bold text-green-400">Match Finished!</p>
                <p class="text-sm opacity-70 mt-1">Waiting for admin verification</p>
            </div>

            <!-- Score Board -->
            <div class="grid grid-cols-2 gap-4 md:gap-8">
                <!-- Player 1 -->
                <div :class="[cardClasses, 'rounded-xl border p-6']">
                    <h2 class="text-lg font-bold text-center mb-4 truncate">
                        {{ matchData.active_match.player1?.full_name || 'Player 1' }}
                    </h2>

                    <div class="text-center mb-6">
                        <div
                            :class="[
                                'text-6xl md:text-7xl font-bold',
                                matchData.active_match.player1_score >= matchData.active_match.races_to
                                    ? 'text-green-500'
                                    : ''
                            ]"
                        >
                            {{ matchData.active_match.player1_score }}
                        </div>
                        <div class="text-sm opacity-60 mt-1">
                            Race to {{ matchData.active_match.races_to }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button
                            :disabled="isUpdating || matchData.active_match.player1_score <= 0 || isMatchFinished"
                            :class="[
                                'py-8 rounded-lg text-white font-semibold transition-all touch-manipulation active:scale-95',
                                isUpdating || matchData.active_match.player1_score <= 0 || isMatchFinished
                                    ? buttonClasses.disabled
                                    : buttonClasses.decrement
                            ]"
                            @click="updateScore('player1', 'decrement')"
                        >
                            <MinusIcon class="w-8 h-8 mx-auto"/>
                        </button>

                        <button
                            :disabled="isUpdating || isMatchFinished"
                            :class="[
                                'py-8 rounded-lg text-white font-semibold transition-all touch-manipulation active:scale-95',
                                isUpdating || isMatchFinished
                                    ? buttonClasses.disabled
                                    : buttonClasses.increment
                            ]"
                            @click="updateScore('player1', 'increment')"
                        >
                            <PlusIcon class="w-8 h-8 mx-auto"/>
                        </button>
                    </div>
                </div>

                <!-- Player 2 -->
                <div :class="[cardClasses, 'rounded-xl border p-6']">
                    <h2 class="text-lg font-bold text-center mb-4 truncate">
                        {{ matchData.active_match.player2?.full_name || 'Player 2' }}
                    </h2>

                    <div class="text-center mb-6">
                        <div
                            :class="[
                                'text-6xl md:text-7xl font-bold',
                                matchData.active_match.player2_score >= matchData.active_match.races_to
                                    ? 'text-green-500'
                                    : ''
                            ]"
                        >
                            {{ matchData.active_match.player2_score }}
                        </div>
                        <div class="text-sm opacity-60 mt-1">
                            Race to {{ matchData.active_match.races_to }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button
                            :disabled="isUpdating || matchData.active_match.player2_score <= 0 || isMatchFinished"
                            :class="[
                                'py-8 rounded-lg text-white font-semibold transition-all touch-manipulation active:scale-95',
                                isUpdating || matchData.active_match.player2_score <= 0 || isMatchFinished
                                    ? buttonClasses.disabled
                                    : buttonClasses.decrement
                            ]"
                            @click="updateScore('player2', 'decrement')"
                        >
                            <MinusIcon class="w-8 h-8 mx-auto"/>
                        </button>

                        <button
                            :disabled="isUpdating || isMatchFinished"
                            :class="[
                                'py-8 rounded-lg text-white font-semibold transition-all touch-manipulation active:scale-95',
                                isUpdating || isMatchFinished
                                    ? buttonClasses.disabled
                                    : buttonClasses.increment
                            ]"
                            @click="updateScore('player2', 'increment')"
                        >
                            <PlusIcon class="w-8 h-8 mx-auto"/>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Match Info -->
            <div class="mt-6 text-center text-sm opacity-60">
                {{ matchData.active_match.match_code }} • {{ matchData.active_match.status }}
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Prevent text selection on touch devices */
* {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Optimize for touch */
.touch-manipulation {
    touch-action: manipulation;
}

/* Remove tap highlight on mobile */
button {
    -webkit-tap-highlight-color: transparent;
}

/* Better touch feedback */
button:active {
    transform: scale(0.95);
}

.font-sans {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
