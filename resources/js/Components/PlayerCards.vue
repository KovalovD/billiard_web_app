// resources/js/Components/PlayerCards.vue
<script lang="ts" setup>
import {Button} from '@/Components/ui';
import type {MultiplayerGamePlayer} from '@/types/api';
import {computed} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {ArrowDownIcon, ArrowRightIcon, HandIcon} from "lucide-vue-next";

interface Props {
    player: MultiplayerGamePlayer;
    selectedCard?: string | null;
    disabled?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['select-card']);
const { t } = useLocale();

// Check if a card is available
const hasCard = (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot'): boolean => {
    return Boolean(props.player.cards && props.player.cards[cardType]);
};

// Check if player has any cards
const hasAnyCards = computed(() => {
    return hasCard('skip_turn') || hasCard('pass_turn') || hasCard('hand_shot');
});

// Handle card selection
const selectCard = (cardType: 'skip_turn' | 'pass_turn' | 'hand_shot') => {
    if (props.disabled || !hasCard(cardType)) return;
    emit('select-card', cardType);
};

// Get card display text
const getCardDisplayText = (cardType: string): string => {
    switch (cardType) {
        case 'skip_turn':
            return t('Skip Turn');
        case 'pass_turn':
            return t('Pass Turn');
        case 'hand_shot':
            return t('Hand Shot');
        default:
            return cardType;
    }
};

// Get card description
const getCardDescription = (cardType: string): string => {
    switch (cardType) {
        case 'skip_turn':
            return t('Skip your turn, game moves to the next player');
        case 'pass_turn':
            return t('Pass your turn to another player. After they play, the turn will return to you');
        case 'hand_shot':
            return t('Place the cue ball anywhere on the table and play any ball');
        default:
            return '';
    }
};

</script>

<template>
    <div class="space-y-2">
        <div v-if="!hasAnyCards"
             class="rounded-md bg-gray-100 p-3 text-center text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            <p>{{ t('No cards available') }}</p>
        </div>

        <div v-else class="flex flex-wrap gap-2">
            <Button
                v-if="hasCard('skip_turn')"
                :disabled="disabled"
                :variant="selectedCard === 'skip_turn' ? 'default' : 'outline'"
                class="w-full sm:w-auto"
                size="sm"
                @click="selectCard('skip_turn')"
            >
                <ArrowDownIcon class="h-3 w-3 text-orange-600 dark:text-orange-400"/>
                <span class="pl-2">{{ getCardDisplayText('skip_turn') }}</span>
            </Button>

            <Button
                v-if="hasCard('pass_turn')"
                :disabled="disabled"
                :variant="selectedCard === 'pass_turn' ? 'default' : 'outline'"
                class="w-full sm:w-auto"
                size="sm"
                @click="selectCard('pass_turn')"
            >
                <ArrowRightIcon class="h-3 w-3 text-blue-600 dark:text-blue-400"/>
                <span class="pl-2">{{ getCardDisplayText('pass_turn') }}</span>
            </Button>

            <Button
                v-if="hasCard('hand_shot')"
                :disabled="disabled"
                :variant="selectedCard === 'hand_shot' ? 'default' : 'outline'"
                class="w-full sm:w-auto"
                size="sm"
                @click="selectCard('hand_shot')"
            >
                <HandIcon class="h-3 w-3 text-purple-600 dark:text-purple-400"/>
                <span class="pl-2">{{ getCardDisplayText('hand_shot') }}</span>
            </Button>
        </div>

        <div v-if="selectedCard"
             class="rounded-md bg-blue-50 p-2 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
            <p>{{ getCardDescription(selectedCard) }}</p>
        </div>
    </div>
</template>
