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

    // Prevent decrement below 0
    const currentScore = matchData.value.active_match[`${player}_score`]
    if (action === 'decrement' && currentScore <= 0) return

    // Prevent increment above races_to
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
        // Reload data on error
        await fetchMatchData()
    } finally {
        isUpdating.value = false
    }
}

const startPolling = (): void => {
    fetchMatchData()
    // Poll every 10 seconds to check for match changes
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
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4 select-none">
        <!-- Loading -->
        <div v-if="isLoading" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="text-2xl font-semibold text-gray-600 dark:text-gray-400">Loading...</div>
            </div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="text-2xl font-semibold text-red-600">{{ error }}</div>
                <button
                    class="mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg text-lg"
                    @click="fetchMatchData"
                >
                    Retry
                </button>
            </div>
        </div>

        <!-- No Active Match -->
        <div v-else-if="!matchData?.active_match" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                    {{ matchData?.tournament.name }}
                </div>
                <div class="text-2xl text-gray-600 dark:text-gray-400 mb-2">
                    {{ matchData?.table.name }}
                </div>
                <div class="text-xl text-gray-500 dark:text-gray-500 mt-8">
                    No active match
                </div>
            </div>
        </div>

        <!-- Active Match -->
        <div v-else class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ matchData.tournament.name }}
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">
                    {{ matchData.active_match.round_display }} • {{ matchData.table.name }}
                </p>
            </div>

            <!-- Match Finished Message -->
            <div v-if="isMatchFinished" class="mb-8 p-6 bg-green-100 dark:bg-green-900/30 rounded-xl text-center">
                <p class="text-2xl font-bold text-green-800 dark:text-green-300">
                    Match Finished!
                </p>
                <p class="text-lg text-green-700 dark:text-green-400 mt-2">
                    Waiting for admin verification
                </p>
            </div>

            <!-- Score Board -->
            <div class="grid grid-cols-2 gap-8">
                <!-- Player 1 -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-center mb-8 text-gray-800 dark:text-gray-200">
                        {{ matchData.active_match.player1?.full_name || 'Player 1' }}
                    </h2>

                    <div class="text-center mb-8">
                        <div :class="matchData.active_match.player1_score >= matchData.active_match.races_to ? 'text-green-600' : 'text-gray-800 dark:text-gray-200'"
                             class="text-8xl font-bold">
                            {{ matchData.active_match.player1_score }}
                        </div>
                        <div class="text-lg text-gray-600 dark:text-gray-400 mt-2">
                            Race to {{ matchData.active_match.races_to }}
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button
                            :disabled="isUpdating || matchData.active_match.player1_score <= 0 || isMatchFinished"
                            class="flex-1 py-6 bg-red-500 hover:bg-red-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl text-2xl font-semibold transition-colors touch-manipulation"
                            @click="updateScore('player1', 'decrement')"
                        >
                            <MinusIcon class="w-8 h-8 mx-auto"/>
                        </button>

                        <button
                            :disabled="isUpdating || isMatchFinished"
                            class="flex-1 py-6 bg-green-500 hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl text-2xl font-semibold transition-colors touch-manipulation"
                            @click="updateScore('player1', 'increment')"
                        >
                            <PlusIcon class="w-8 h-8 mx-auto"/>
                        </button>
                    </div>
                </div>

                <!-- Player 2 -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-center mb-8 text-gray-800 dark:text-gray-200">
                        {{ matchData.active_match.player2?.full_name || 'Player 2' }}
                    </h2>

                    <div class="text-center mb-8">
                        <div :class="matchData.active_match.player2_score >= matchData.active_match.races_to ? 'text-green-600' : 'text-gray-800 dark:text-gray-200'"
                             class="text-8xl font-bold">
                            {{ matchData.active_match.player2_score }}
                        </div>
                        <div class="text-lg text-gray-600 dark:text-gray-400 mt-2">
                            Race to {{ matchData.active_match.races_to }}
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button
                            :disabled="isUpdating || matchData.active_match.player2_score <= 0 || isMatchFinished"
                            class="flex-1 py-6 bg-red-500 hover:bg-red-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl text-2xl font-semibold transition-colors touch-manipulation"
                            @click="updateScore('player2', 'decrement')"
                        >
                            <MinusIcon class="w-8 h-8 mx-auto"/>
                        </button>

                        <button
                            :disabled="isUpdating || isMatchFinished"
                            class="flex-1 py-6 bg-green-500 hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl text-2xl font-semibold transition-colors touch-manipulation"
                            @click="updateScore('player2', 'increment')"
                        >
                            <PlusIcon class="w-8 h-8 mx-auto"/>
                        </button>
                    </div>
                </div>
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
</style>
