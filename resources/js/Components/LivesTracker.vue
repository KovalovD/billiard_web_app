// resources/js/Components/LivesTracker.vue
<script lang="ts" setup>
import {HeartIcon} from 'lucide-vue-next';
import {computed} from 'vue';

interface Props {
    lives: number;
    maxLives?: number;
    size?: 'sm' | 'md' | 'lg';
}

const props = defineProps<Props>();

// Determine the size of hearts
const heartSize = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-3 w-3';
        case 'lg':
            return 'h-5 w-5';
        case 'md':
        default:
            return 'h-4 w-4';
    }
});

// Create array of hearts
const hearts = computed(() => {
    const maxLives = props.maxLives || Math.max(props.lives, 5); // Default to 5 or current lives if greater
    return Array.from({length: maxLives}, (_, i) => i < props.lives);
});
</script>

<template>
    <div class="flex items-center">
        <HeartIcon
            v-for="(filled, index) in hearts"
            :key="index"
            :class="[
                heartSize,
                filled ? 'text-red-500' : 'text-gray-300 dark:text-gray-600',
                index > 0 ? '-ml-0.5' : ''
            ]"
            ? fill={filled
        'currentColor' : 'none'}
        />
        <span class="ml-1.5 text-sm font-medium">{{ props.lives }}</span>
    </div>
</template>
