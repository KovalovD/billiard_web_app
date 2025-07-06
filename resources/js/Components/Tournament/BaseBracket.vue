<template>
    <div ref="bracketContainerRef" class="bracket-fullscreen-container">
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ title }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ canEdit ? t('Click on a match to view details') : t('View only mode') }}
                        </p>
                    </div>

                    <!-- Zoom and Fullscreen Controls -->
                    <div class="flex items-center gap-2">
                        <!-- Find Me Button -->
                        <Button
                            v-if="hasCurrentUserActiveMatch"
                            :title="t('Find my match')"
                            size="sm"
                            @click="onFindMyMatch"
                        >
                            <UserIcon class="h-4 w-4 mr-1"/>
                            {{ t('Find Me') }}
                        </Button>

                        <!-- Zoom Controls -->
                        <div class="flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                            <Button
                                :title="t('Zoom Out')"
                                size="sm"
                                variant="ghost"
                                @click="zoomOut"
                            >
                                <MinusIcon class="h-4 w-4"/>
                            </Button>
                            <span class="px-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ Math.round(zoomLevel * 100) }}%
                            </span>
                            <Button
                                :title="t('Zoom In')"
                                size="sm"
                                variant="ghost"
                                @click="zoomIn"
                            >
                                <PlusIcon class="h-4 w-4"/>
                            </Button>
                            <Button
                                :title="t('Reset Zoom')"
                                size="sm"
                                variant="ghost"
                                @click="resetZoom"
                            >
                                <RotateCcwIcon class="h-4 w-4"/>
                            </Button>
                        </div>

                        <!-- Fullscreen Button -->
                        <Button
                            :title="isFullscreen ? t('Exit Fullscreen') : t('Enter Fullscreen')"
                            size="sm"
                            variant="outline"
                            @click="toggleFullscreen"
                        >
                            <ExpandIcon v-if="!isFullscreen" class="h-4 w-4"/>
                            <ShrinkIcon v-else class="h-4 w-4"/>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Keyboard shortcuts hint -->
            <div
                class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                {{ t('Keyboard shortcuts') }}:
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">+/-</kbd> {{ t('zoom') }},
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">0</kbd> {{ t('reset') }},
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">F11</kbd>
                {{ t('fullscreen') }}
                <span class="ml-2">• {{ t('Pinch to zoom on touch devices') }}</span>
            </div>

            <div
                ref="bracketScrollContainerRef"
                class="bracket-container overflow-auto bg-gray-50 dark:bg-gray-900/50 touch-none"
                @touchend="handleTouchEnd"
                @touchmove="handleTouchMove"
                @touchstart="handleTouchStart"
                @wheel="handleWheel"
            >
                <div :style="{ transform: `scale(${zoomLevel})` }" class="bracket-zoom-wrapper">
                    <slot/>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {Button} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {ExpandIcon, MinusIcon, PlusIcon, RotateCcwIcon, ShrinkIcon, UserIcon} from 'lucide-vue-next';
import {inject} from "vue";

interface Props {
    title: string;
    canEdit: boolean;
    hasCurrentUserActiveMatch: boolean;
    zoomLevel: number;
    isFullscreen: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    'find-my-match': [];
}>();

const {t} = useLocale();

// Expose refs and methods from parent through template refs
defineExpose({
    bracketContainerRef: undefined,
    bracketScrollContainerRef: undefined,
});

const onFindMyMatch = () => {
    emit('find-my-match');
};

// These will be injected from parent
const zoomOut = inject<() => void>('zoomOut')!;
const zoomIn = inject<() => void>('zoomIn')!;
const resetZoom = inject<() => void>('resetZoom')!;
const toggleFullscreen = inject<() => void>('toggleFullscreen')!;
const handleTouchEnd = inject<(e: TouchEvent) => void>('handleTouchEnd')!;
const handleTouchMove = inject<(e: TouchEvent) => void>('handleTouchMove')!;
const handleTouchStart = inject<(e: TouchEvent) => void>('handleTouchStart')!;
const handleWheel = inject<(e: WheelEvent) => void>('handleWheel')!;
</script>

<style scoped>
.bracket-container {
    max-height: calc(100vh - 400px);
    min-height: 600px;
}

/* Fullscreen adjustments */
.bracket-fullscreen-container:fullscreen {
    background: white;
    padding: 1rem;
}

.bracket-fullscreen-container:fullscreen .bracket-container {
    max-height: calc(100vh - 120px);
}

.bracket-zoom-wrapper {
    transform-origin: top left;
    transition: transform 0.2s ease-out;
}

/* Prevent text selection on touch devices */
.touch-none {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

/* Scrollbar styling */
.bracket-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.bracket-container::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}

.bracket-container::-webkit-scrollbar-thumb {
    background: #9ca3af;
    border-radius: 4px;
}

.bracket-container::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
