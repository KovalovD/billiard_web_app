<script lang="ts" setup>
import {computed, provide, ref, watch} from 'vue'
import type {Tournament, TournamentMatch} from '@/types/api'
import {useLocale} from '@/composables/useLocale'
import {useBracket} from '@/composables/useBracket'
import BaseBracket from '@/Components/Tournament/BaseBracket.vue'
import BracketStyles from '@/Components/Tournament/BracketStyles.vue'
import MatchCard from '@/Components/Tournament/MatchCard.vue'

const props = defineProps<{
    matches: TournamentMatch[]
    tournament: Tournament
    canEdit: boolean
    currentUserId?: number
}>()

const emit = defineEmits<{
    'open-match': [matchId: number]
}>()

const {t} = useLocale()

// Compact dimensions
const nodeWidth = 180
const nodeHeight = 50
const hGap = 100
const vGap = 20

const {
    zoomLevel,
    isFullscreen,
    bracketContainerRef,
    bracketScrollContainerRef,
    transformMatch,
    zoomIn,
    zoomOut,
    resetZoom,
    handleTouchStart,
    handleTouchMove,
    handleTouchEnd,
    handleWheel,
    toggleFullscreen,
    findMyMatch,
    scrollToMatch,
    highlightMatchIds,
    highlightMatch,
    highlightMatches,
} = useBracket(props.currentUserId, {initialZoom: 0.8})

provide('zoomIn', zoomIn)
provide('zoomOut', zoomOut)
provide('resetZoom', resetZoom)
provide('toggleFullscreen', toggleFullscreen)
provide('handleTouchStart', handleTouchStart)
provide('handleTouchMove', handleTouchMove)
provide('handleTouchEnd', handleTouchEnd)
provide('handleWheel', handleWheel)
provide('scrollToMatch', scrollToMatch)

const upperBracketMatches = computed(() => {
    const roundMap: Record<string, number> = {
        round_128: 0,
        round_64: 1,
        round_32: 2,
        round_16: 3,
        quarterfinals: 4,
        semifinals: 5,
        finals: 6,
    }

    // Get all upper bracket matches
    const upperMatches = props.matches
        .filter(m => m.bracket_side === 'upper' && m.round && roundMap[m.round] !== undefined)

    // Find the minimum round index to normalize from 0
    const minRoundIndex = Math.min(...upperMatches.map(m => roundMap[m.round!]))

    return upperMatches
        .map(m => transformMatch(m, 'upper', roundMap[m.round!] - minRoundIndex))
        .sort((a, b) => a.round - b.round || a.slot - b.slot)
})

const lowerBracketMatches = computed(() => {
    const lowerMatches = props.matches
        .filter(m => m.bracket_side === 'lower')

    // Find the minimum round number to normalize from 0
    const roundNumbers = lowerMatches.map(m =>
        parseInt(m.match_code.split('_R')[1]?.split('M')[0] || '1') - 1
    )
    const minRound = Math.min(...roundNumbers)

    return lowerMatches
        .map(m => {
            const absoluteRound = parseInt(m.match_code.split('_R')[1]?.split('M')[0] || '1') - 1
            return transformMatch(m, 'lower', absoluteRound - minRound)
        })
        .sort((a, b) => a.round - b.round || a.slot - b.slot)
})

const grandFinals = computed(() => {
    return props.matches
        .filter(m => m.match_code === 'GF' || m.match_code === 'GF_RESET')
        .map(m => transformMatch(m, null, 0))
        .sort((a, b) => a.slot - b.slot)
})

const upperRounds = computed(() => {
    const map = new Map<number, ReturnType<typeof transformMatch>[]>()
    upperBracketMatches.value.forEach(m => {
        ;(map.get(m.round) || map.set(m.round, []).get(m.round)!).push(m)
    })
    return [...map.entries()]
        .sort(([a], [b]) => a - b)
        .map(([, ms]) => ms.sort((a, b) => a.slot - b.slot))
})

const lowerRounds = computed(() => {
    const map = new Map<number, ReturnType<typeof transformMatch>[]>()
    lowerBracketMatches.value.forEach(m => {
        ;(map.get(m.round) || map.set(m.round, []).get(m.round)!).push(m)
    })
    return [...map.entries()]
        .sort(([a], [b]) => a - b)
        .map(([, ms]) => ms.sort((a, b) => a.slot - b.slot))
})

const matchesById = computed(() => {
    const map = new Map<number, TournamentMatch>()
    props.matches.forEach(match => map.set(match.id, match))
    return map
})

const upperBracketHeight = computed(() => {
    if (upperRounds.value.length === 0) return 0
    const firstRoundMatches = upperRounds.value[0]?.length || 0
    return firstRoundMatches * (nodeHeight + vGap) - vGap + 60
})

const positionedUpperMatches = computed(() => {
    const list: Array<ReturnType<typeof transformMatch> & { x: number; y: number }> = []
    const block = nodeHeight + vGap
    upperRounds.value.forEach((roundMatches, roundIndex) => {
        const spacing = Math.pow(2, roundIndex) * block
        roundMatches.forEach((match, matchIndex) => {
            const x = roundIndex * (nodeWidth + hGap)
            const y = 40 + matchIndex * spacing + (spacing - block) / 2
            list.push({...match, x, y})
        })
    })
    return list
})

const positionedLowerMatches = computed(() => {
    const list: Array<ReturnType<typeof transformMatch> & { x: number; y: number }> = []
    const baseY = upperBracketHeight.value + 80
    const block = nodeHeight + vGap
    lowerRounds.value.forEach((roundMatches, roundIndex) => {
        const spacing = Math.pow(2, Math.floor(roundIndex / 2)) * block
        roundMatches.forEach((match, matchIndex) => {
            const x = roundIndex * (nodeWidth + hGap)
            const y = baseY + 40 + matchIndex * spacing
            list.push({...match, x, y})
        })
    })
    return list
})

const positionedGrandFinals = computed(() => {
    const maxUpperX = Math.max(...positionedUpperMatches.value.map(m => m.x), 0)
    const maxLowerX = Math.max(...positionedLowerMatches.value.map(m => m.x), 0)
    const grandFinalX = Math.max(maxUpperX, maxLowerX) + nodeWidth + hGap * 1.5
    const upperFinal = positionedUpperMatches.value.find(
        m => m.round === upperRounds.value.length - 1,
    )
    const lowerFinal = positionedLowerMatches.value[positionedLowerMatches.value.length - 1]
    let centerY = 40
    if (upperFinal && lowerFinal) centerY = (upperFinal.y + lowerFinal.y) / 2
    else if (upperFinal) centerY = upperFinal.y
    return grandFinals.value.map((match, index) => ({
        ...match,
        x: grandFinalX,
        y:
            centerY +
            index * (nodeHeight + vGap * 2) -
            ((grandFinals.value.length - 1) * (nodeHeight + vGap)) / 2,
    }))
})

const allPositionedMatches = computed(() => [
    ...positionedUpperMatches.value,
    ...positionedLowerMatches.value,
    ...positionedGrandFinals.value,
])

const matchPositionMap = computed(() => {
    const map = new Map<number, { x: number; y: number }>()
    allPositionedMatches.value.forEach(match => map.set(match.id, {x: match.x, y: match.y}))
    return map
})

// All matches where the current user participates
const currentUserMatches = computed(() => {
    if (!props.currentUserId) return []
    return allPositionedMatches.value.filter(
        match => match.player1?.id === props.currentUserId || match.player2?.id === props.currentUserId
    )
})

const connectorSegments = computed(() => {
    const segs: any[] = []
    allPositionedMatches.value.forEach(match => {
        if (match.next_match_id) {
            const nextPos = matchPositionMap.value.get(match.next_match_id)
            if (nextPos) {
                const fromX = match.x + nodeWidth
                const fromY = match.y + nodeHeight / 2
                const toX = nextPos.x
                const toY = nextPos.y + nodeHeight / 2
                const midX = fromX + (toX - fromX) / 2
                segs.push({
                    id: `${match.id}-h1`,
                    x1: fromX,
                    y1: fromY,
                    x2: midX,
                    y2: fromY,
                    type: match.bracketSide === 'lower' ? 'lower' : 'upper',
                })
                segs.push({
                    id: `${match.id}-v`,
                    x1: midX,
                    y1: fromY,
                    x2: midX,
                    y2: toY,
                    type: match.bracketSide === 'lower' ? 'lower' : 'upper',
                })
                segs.push({
                    id: `${match.id}-h2`,
                    x1: midX,
                    y1: toY,
                    x2: toX,
                    y2: toY,
                    type: match.bracketSide === 'lower' ? 'lower' : 'upper',
                })
            }
        }
    })
    return segs
})

const svgWidth = computed(() => {
    const maxX = Math.max(
        ...positionedUpperMatches.value.map(m => m.x),
        ...positionedLowerMatches.value.map(m => m.x),
        ...positionedGrandFinals.value.map(m => m.x),
        0,
    )
    return maxX + nodeWidth + 80
})

const svgHeight = computed(() => {
    const maxY = Math.max(
        ...positionedUpperMatches.value.map(m => m.y),
        ...positionedLowerMatches.value.map(m => m.y),
        ...positionedGrandFinals.value.map(m => m.y),
        0,
    )
    return maxY + nodeHeight + 80
})

// Separator line Y-coordinate
const groupSeparatorY = computed(() => upperBracketHeight.value + 30)

const handleMatchClick = (matchId: number) => {
    if (props.canEdit) emit('open-match', matchId)
}

const handleFindMyMatch = () => {
    findMyMatch(allPositionedMatches.value)
}

const handleLoserDropClick = (targetMatchId: number) => {
    const targetMatch = allPositionedMatches.value.find(m => m.id === targetMatchId)
    if (targetMatch) {
        highlightMatch(targetMatch)
    }
}

const handleDropsFromClick = (matchIds: number[]) => {
    const sourceMatches = allPositionedMatches.value.filter(m => matchIds.includes(m.id))
    if (sourceMatches.length > 0) {
        highlightMatches(sourceMatches)
    }
}

const baseBracketRef = ref<InstanceType<typeof BaseBracket>>()
watch(baseBracketRef, newRef => {
    if (newRef) {
        bracketContainerRef.value = newRef.bracketContainerRef
        bracketScrollContainerRef.value = newRef.bracketScrollContainerRef
    }
})
</script>

<template>
    <BaseBracket
        ref="baseBracketRef"
        :title="t('Double Elimination Bracket')"
        :can-edit="canEdit"
        :has-current-user-active-match="currentUserMatches.length > 0"
        :zoom-level="zoomLevel"
        :is-fullscreen="isFullscreen"
        @find-my-match="handleFindMyMatch"
    >
        <BracketStyles/>
        <div class="p-4">
            <svg :height="svgHeight" :width="svgWidth" class="bracket-svg" style="min-width: 100%">
                <!-- Labels -->
                <text class="bracket-label" x="20" y="25">{{ t('Upper Bracket') }}</text>
                <text :y="upperBracketHeight + 65" class="bracket-label" x="20">
                    {{ t('Lower Bracket') }}
                </text>
                <text
                    v-if="positionedGrandFinals.length > 0"
                    :x="positionedGrandFinals[0].x"
                    :y="positionedGrandFinals[0].y - 15"
                    class="bracket-label text-center"
                >
                    {{ t('Grand Finals') }}
                </text>

                <!-- Separator between upper & lower -->
                <line
                    class="bracket-separator"
                    :x1="20"
                    :x2="svgWidth - 20"
                    :y1="groupSeparatorY"
                    :y2="groupSeparatorY"
                />

                <!-- Connector lines -->
                <g class="connectors">
                    <line
                        v-for="seg in connectorSegments"
                        :key="seg.id"
                        :class="[
                            'connector-line',
                            seg.type === 'lower' ? 'connector-line-lower' : '',
                        ]"
                        :x1="seg.x1"
                        :x2="seg.x2"
                        :y1="seg.y1"
                        :y2="seg.y2"
                    />
                </g>

                <!-- Matches -->
                <g class="matches">
                    <MatchCard
                        v-for="m in allPositionedMatches"
                        :key="m.id"
                        :match="m"
                        :x="m.x"
                        :y="m.y"
                        :can-edit="canEdit"
                        :current-user-id="currentUserId"
                        :card-width="nodeWidth"
                        :card-height="nodeHeight"
                        :show-loser-drop="true"
                        :matches-by-id="matchesById"
                        :all-positioned-matches="allPositionedMatches"
                        :highlight-match-ids="highlightMatchIds"
                        @click="handleMatchClick"
                        @loser-drop-click="handleLoserDropClick"
                        @drops-from-click="handleDropsFromClick"
                    />
                </g>
            </svg>
        </div>
    </BaseBracket>
</template>
