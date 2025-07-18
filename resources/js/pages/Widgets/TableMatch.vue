<script lang="ts" setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {CheckIcon, MinusIcon, PlusIcon, XIcon} from 'lucide-vue-next'

interface FrameScore {
    player1: number
    player2: number
    finished?: boolean
}

interface MatchData {
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
        status: string
        races_to: number
    } | null
    tournament: {
        id: number
        name: string
        races_to: number
        game_type: 'pool' | 'pyramid' | 'snooker'
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

const currentFrameIndex = ref(0)
const currentFrameScore = ref({ player1: 0, player2: 0 })

let dataPollingInterval: number | null = null

const isMatchFinished = computed(() => {
    if (!matchData.value?.active_match) return false
    const match = matchData.value.active_match
    return match.player1_score >= match.races_to || match.player2_score >= match.races_to
})

const isFrameBased = computed(() => {
    return matchData.value?.tournament.game_type !== 'pool'
})

const gameType = computed(() => {
    return matchData.value?.tournament.game_type || 'pool'
})

const maxFrameScore = computed(() => {
    return gameType.value === 'pyramid' ? 8 : (gameType.value === 'snooker' ? 147 : 0)
})

const canFinishFrame = computed(() => {
    if (!currentFrameScore.value) return false

    if (gameType.value === 'pyramid') {
        return (currentFrameScore.value.player1 === 8 && currentFrameScore.value.player2 < 8) ||
            (currentFrameScore.value.player2 === 8 && currentFrameScore.value.player1 < 8)
    } else if (gameType.value === 'snooker') {
        return currentFrameScore.value.player1 !== currentFrameScore.value.player2
    }

    return false
})

const frameWinner = computed(() => {
    if (!currentFrameScore.value || !canFinishFrame.value) return null

    if (gameType.value === 'pyramid') {
        return currentFrameScore.value.player1 === 8 ? 'player1' : 'player2'
    } else if (gameType.value === 'snooker') {
        return currentFrameScore.value.player1 > currentFrameScore.value.player2 ? 'player1' : 'player2'
    }

    return null
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
                primary: 'bg-blue-500 hover:bg-blue-600 active:bg-blue-700',
                secondary: 'bg-gray-500 hover:bg-gray-600 active:bg-gray-700',
                disabled: 'bg-gray-300 cursor-not-allowed'
            }
        case 'transparent':
            return {
                increment: 'bg-green-500/80 hover:bg-green-500/90 active:bg-green-500',
                decrement: 'bg-red-500/80 hover:bg-red-500/90 active:bg-red-500',
                primary: 'bg-blue-500/80 hover:bg-blue-500/90 active:bg-blue-500',
                secondary: 'bg-gray-500/80 hover:bg-gray-500/90 active:bg-gray-500',
                disabled: 'bg-gray-500/30 cursor-not-allowed'
            }
        default:
            return {
                increment: 'bg-green-600 hover:bg-green-700 active:bg-green-800',
                decrement: 'bg-red-600 hover:bg-red-700 active:bg-red-800',
                primary: 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800',
                secondary: 'bg-gray-600 hover:bg-gray-700 active:bg-gray-800',
                disabled: 'bg-gray-700 cursor-not-allowed'
            }
    }
})

const fetchMatchData = async (): Promise<void> => {
    if (!tournamentId || !tableId) {
        error.value = t('Missing tournament or table ID')
        return
    }

    try {
        const response = await fetch(`/api/widgets/table-match/tournaments/${tournamentId}/tables/${tableId}`)
        if (!response.ok) throw new Error(`HTTP ${response.status}`)

        matchData.value = await response.json()
        error.value = null

        // Initialize current frame data for frame-based games
        if (isFrameBased.value && matchData.value?.active_match) {
            const frames = matchData.value.active_match.frame_scores || []
            const lastUnfinishedIndex = frames.findIndex(f => !f.finished)

            if (lastUnfinishedIndex >= 0) {
                currentFrameIndex.value = lastUnfinishedIndex
                currentFrameScore.value = {
                    player1: frames[lastUnfinishedIndex].player1 || 0,
                    player2: frames[lastUnfinishedIndex].player2 || 0
                }
            } else {
                currentFrameIndex.value = frames.length
                currentFrameScore.value = { player1: 0, player2: 0 }
            }
        }
    } catch (err: any) {
        error.value = t('Failed to load match data')
        console.error('Failed to fetch match data:', err)
    } finally {
        isLoading.value = false
    }
}

const updatePoolScore = async (player: 'player1' | 'player2', action: 'increment' | 'decrement') => {
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
            body: JSON.stringify({ player, action })
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

const updateFrameScore = async (player: 'player1' | 'player2', action: 'increment' | 'decrement') => {
    if (isUpdating.value || !matchData.value?.active_match || isMatchFinished.value) return

    const currentScore = currentFrameScore.value[player]

    if (action === 'decrement' && currentScore <= 0) return
    if (action === 'increment' && currentScore >= maxFrameScore.value) return

    if (action === 'increment') {
        currentFrameScore.value[player]++
    } else {
        currentFrameScore.value[player]--
    }

    // Auto-save frame scores without finishing
    await saveFrameScore(false)
}

const saveFrameScore = async (finishFrame: boolean) => {
    if (isUpdating.value || !matchData.value?.active_match) return

    isUpdating.value = true

    try {
        const response = await fetch(`/api/widgets/table-match/tournaments/${tournamentId}/tables/${tableId}/score`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                frame_index: currentFrameIndex.value,
                player1_score: currentFrameScore.value.player1,
                player2_score: currentFrameScore.value.player2,
                finish_frame: finishFrame
            })
        })

        if (!response.ok) {
            const error = await response.json()
            throw new Error(error.error || `HTTP ${response.status}`)
        }

        const result = await response.json()

        if (matchData.value?.active_match) {
            matchData.value.active_match.player1_score = result.player1_score
            matchData.value.active_match.player2_score = result.player2_score
            matchData.value.active_match.frame_scores = result.frame_scores
            matchData.value.active_match.status = result.status
        }

        if (finishFrame && !isMatchFinished.value) {
            // Move to next frame
            currentFrameIndex.value++
            currentFrameScore.value = { player1: 0, player2: 0 }
        }
    } catch (err: any) {
        console.error('Failed to update frame score:', err)
        alert(err.message)
        await fetchMatchData()
    } finally {
        isUpdating.value = false
    }
}

const cancelFrame = () => {
    currentFrameScore.value = { player1: 0, player2: 0 }
}

const startPolling = (): void => {
    fetchMatchData()
    dataPollingInterval = setInterval(fetchMatchData, 30000) as unknown as number
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
    <div :class="[themeClasses, 'min-h-screen p-4 font-sans select-none']">
        <!-- Loading -->
        <div v-if="isLoading" class="flex items-center justify-center h-64">
            <div class="text-xl animate-pulse">{{ t('Loading...') }}</div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex items-center justify-center h-64">
            <div class="text-center">
                <div class="text-red-400 text-lg mb-4">{{ error }}</div>
                <button
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg text-base"
                    @click="fetchMatchData"
                >
                    {{ t('Retry') }}
                </button>
            </div>
        </div>

        <!-- No Active Match -->
        <div v-else-if="!matchData?.active_match" class="flex items-center justify-center h-64">
            <div :class="[cardClasses, 'rounded-lg border p-8 text-center']">
                <div class="text-2xl font-bold mb-2">{{ matchData?.tournament.name }}</div>
                <div class="text-lg opacity-70 mb-4">{{ matchData?.table.name }}</div>
                <div class="text-base opacity-50">{{ t('No active match') }}</div>
            </div>
        </div>

        <!-- Active Match -->
        <div v-else class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold">{{ matchData.tournament.name }}</h1>
                <p class="text-sm opacity-70 mt-1">
                    {{ matchData.table.name }} • {{ t('Race to') }} {{ matchData.active_match.races_to }}
                </p>
                <p v-if="gameType !== 'pool'" class="text-xs opacity-50 mt-1">
                    {{ gameType === 'pyramid' ? t('First to 8 balls wins frame') : t('Highest score wins frame') }}
                </p>
            </div>

            <!-- Match Finished Message -->
            <div v-if="isMatchFinished" class="mb-6 p-4 bg-green-500/20 rounded-lg text-center">
                <p class="text-lg font-bold text-green-400">{{ t('Match Finished!') }}</p>
                <p class="text-sm opacity-70 mt-1">{{ t('Waiting for admin verification') }}</p>
            </div>

            <!-- Pool Score Board -->
            <div v-if="!isFrameBased" class="grid grid-cols-2 gap-4 md:gap-8">
                <!-- Player 1 -->
                <div :class="[cardClasses, 'rounded-xl border p-6']">
                    <h2 class="text-lg font-bold text-center mb-4 truncate">
                        {{ matchData.active_match.player1?.full_name || t('Player 1') }}
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
                            @click="updatePoolScore('player1', 'decrement')"
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
                            @click="updatePoolScore('player1', 'increment')"
                        >
                            <PlusIcon class="w-8 h-8 mx-auto"/>
                        </button>
                    </div>
                </div>

                <!-- Player 2 -->
                <div :class="[cardClasses, 'rounded-xl border p-6']">
                    <h2 class="text-lg font-bold text-center mb-4 truncate">
                        {{ matchData.active_match.player2?.full_name || t('Player 2') }}
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
                            @click="updatePoolScore('player2', 'decrement')"
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
                            @click="updatePoolScore('player2', 'increment')"
                        >
                            <PlusIcon class="w-8 h-8 mx-auto"/>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Frame-based Score Board (Pyramid/Snooker) -->
            <div v-else>
                <!-- Match Score Summary -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div :class="[cardClasses, 'rounded-lg border p-4 text-center']">
                        <h3 class="text-sm opacity-70 mb-1">{{ matchData.active_match.player1?.full_name || t('Player 1') }}</h3>
                        <div :class="[
                            'text-3xl font-bold',
                            matchData.active_match.player1_score >= matchData.active_match.races_to ? 'text-green-500' : ''
                        ]">
                            {{ matchData.active_match.player1_score }}
                        </div>
                        <div class="text-xs opacity-50 mt-1">{{ t('Frames') }}</div>
                    </div>
                    <div :class="[cardClasses, 'rounded-lg border p-4 text-center']">
                        <h3 class="text-sm opacity-70 mb-1">{{ matchData.active_match.player2?.full_name || t('Player 2') }}</h3>
                        <div :class="[
                            'text-3xl font-bold',
                            matchData.active_match.player2_score >= matchData.active_match.races_to ? 'text-green-500' : ''
                        ]">
                            {{ matchData.active_match.player2_score }}
                        </div>
                        <div class="text-xs opacity-50 mt-1">{{ t('Frames') }}</div>
                    </div>
                </div>

                <!-- Current Frame -->
                <div :class="[cardClasses, 'rounded-xl border p-6 mb-6']">
                    <h3 class="text-center text-lg font-semibold mb-4">
                        {{ t('Frame') }} {{ currentFrameIndex + 1 }}
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Player 1 Frame Score -->
                        <div>
                            <h4 class="text-center text-sm opacity-70 mb-2 truncate">
                                {{ matchData.active_match.player1?.full_name || t('Player 1') }}
                            </h4>
                            <div :class="[
                                'text-center text-4xl font-bold mb-4',
                                frameWinner === 'player1' ? 'text-green-500' : ''
                            ]">
                                {{ currentFrameScore.player1 }}
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    :disabled="isUpdating || currentFrameScore.player1 <= 0 || isMatchFinished"
                                    :class="[
                                        'py-4 rounded-lg text-white font-semibold transition-all touch-manipulation',
                                        isUpdating || currentFrameScore.player1 <= 0 || isMatchFinished
                                            ? buttonClasses.disabled
                                            : buttonClasses.decrement
                                    ]"
                                    @click="updateFrameScore('player1', 'decrement')"
                                >
                                    <MinusIcon class="w-6 h-6 mx-auto"/>
                                </button>
                                <button
                                    :disabled="isUpdating || currentFrameScore.player1 >= maxFrameScore || isMatchFinished"
                                    :class="[
                                        'py-4 rounded-lg text-white font-semibold transition-all touch-manipulation',
                                        isUpdating || currentFrameScore.player1 >= maxFrameScore || isMatchFinished
                                            ? buttonClasses.disabled
                                            : buttonClasses.increment
                                    ]"
                                    @click="updateFrameScore('player1', 'increment')"
                                >
                                    <PlusIcon class="w-6 h-6 mx-auto"/>
                                </button>
                            </div>
                        </div>

                        <!-- Player 2 Frame Score -->
                        <div>
                            <h4 class="text-center text-sm opacity-70 mb-2 truncate">
                                {{ matchData.active_match.player2?.full_name || t('Player 2') }}
                            </h4>
                            <div :class="[
                                'text-center text-4xl font-bold mb-4',
                                frameWinner === 'player2' ? 'text-green-500' : ''
                            ]">
                                {{ currentFrameScore.player2 }}
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    :disabled="isUpdating || currentFrameScore.player2 <= 0 || isMatchFinished"
                                    :class="[
                                        'py-4 rounded-lg text-white font-semibold transition-all touch-manipulation',
                                        isUpdating || currentFrameScore.player2 <= 0 || isMatchFinished
                                            ? buttonClasses.disabled
                                            : buttonClasses.decrement
                                    ]"
                                    @click="updateFrameScore('player2', 'decrement')"
                                >
                                    <MinusIcon class="w-6 h-6 mx-auto"/>
                                </button>
                                <button
                                    :disabled="isUpdating || currentFrameScore.player2 >= maxFrameScore || isMatchFinished"
                                    :class="[
                                        'py-4 rounded-lg text-white font-semibold transition-all touch-manipulation',
                                        isUpdating || currentFrameScore.player2 >= maxFrameScore || isMatchFinished
                                            ? buttonClasses.disabled
                                            : buttonClasses.increment
                                    ]"
                                    @click="updateFrameScore('player2', 'increment')"
                                >
                                    <PlusIcon class="w-6 h-6 mx-auto"/>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Frame Actions -->
                    <div class="grid grid-cols-2 gap-3 mt-6">
                        <button
                            :disabled="isUpdating || isMatchFinished"
                            :class="[
                                'py-3 px-4 rounded-lg text-white font-semibold transition-all touch-manipulation flex items-center justify-center gap-2',
                                isUpdating || isMatchFinished ? buttonClasses.disabled : buttonClasses.secondary
                            ]"
                            @click="cancelFrame"
                        >
                            <XIcon class="w-5 h-5"/>
                            {{ t('Reset Frame') }}
                        </button>
                        <button
                            :disabled="isUpdating || !canFinishFrame || isMatchFinished"
                            :class="[
                                'py-3 px-4 rounded-lg text-white font-semibold transition-all touch-manipulation flex items-center justify-center gap-2',
                                isUpdating || !canFinishFrame || isMatchFinished
                                    ? buttonClasses.disabled
                                    : buttonClasses.primary
                            ]"
                            @click="saveFrameScore(true)"
                        >
                            <CheckIcon class="w-5 h-5"/>
                            {{ t('Finish Frame') }}
                        </button>
                    </div>
                </div>

                <!-- Frame History -->
                <div v-if="matchData.active_match.frame_scores.length > 0" :class="[cardClasses, 'rounded-lg border p-4']">
                    <h3 class="text-sm font-semibold mb-3 opacity-70">{{ t('Frame History') }}</h3>
                    <div class="space-y-2">
                        <div
                            v-for="(frame, index) in matchData.active_match.frame_scores"
                            :key="index"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="opacity-50">{{ t('Frame') }} {{ index + 1 }}</span>
                            <span :class="[
                                'font-mono',
                                gameType === 'pyramid'
                                    ? (frame.player1 === 8 ? 'text-green-500' : '')
                                    : (frame.player1 > frame.player2 ? 'text-green-500' : '')
                            ]">
                                {{ frame.player1 }} - {{ frame.player2 }}
                            </span>
                        </div>
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
