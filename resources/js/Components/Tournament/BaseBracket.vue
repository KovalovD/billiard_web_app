<template>
    <div ref="bracketContainerRef" class="bracket-fullscreen-container">
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-3 sm:p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ title }}
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                            {{ canEdit ? t('Click on a match to view details') : t('View only mode') }}
                        </p>
                    </div>

                    <!-- Mobile Controls -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Find Me Button -->
                        <Button
                            v-if="hasCurrentUserActiveMatch"
                            :title="t('Find my match')"
                            size="sm"
                            class="text-xs sm:text-sm"
                            @click="onFindMyMatch"
                        >
                            <UserIcon class="h-3 w-3 sm:h-4 sm:w-4 mr-1"/>
                            <span class="hidden sm:inline">{{ t('Find Me') }}</span>
                            <span class="sm:hidden">{{ t('Me') }}</span>
                        </Button>

                        <!-- Zoom Controls -->
                        <div class="flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                            <Button
                                :title="t('Zoom Out')"
                                size="sm"
                                variant="ghost"
                                class="p-1 sm:p-2"
                                @click="onZoomOut"
                            >
                                <MinusIcon class="h-3 w-3 sm:h-4 sm:w-4"/>
                            </Button>
                            <span
                                class="px-1 sm:px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 min-w-[40px] text-center">
                                {{ Math.round(zoomLevel * 100) }}%
                            </span>
                            <Button
                                :title="t('Zoom In')"
                                size="sm"
                                variant="ghost"
                                class="p-1 sm:p-2"
                                @click="onZoomIn"
                            >
                                <PlusIcon class="h-3 w-3 sm:h-4 sm:w-4"/>
                            </Button>
                            <Button
                                :title="t('Reset Zoom')"
                                size="sm"
                                variant="ghost"
                                class="p-1 sm:p-2 hidden sm:block"
                                @click="onResetZoom"
                            >
                                <RotateCcwIcon class="h-4 w-4"/>
                            </Button>
                        </div>

                        <!-- Fullscreen Button - Desktop only -->
                        <Button
                            :title="isFullscreen ? t('Exit Fullscreen') : t('Enter Fullscreen')"
                            size="sm"
                            variant="outline"
                            class="hidden sm:flex"
                            @click="onToggleFullscreen"
                        >
                            <ExpandIcon v-if="!isFullscreen" class="h-4 w-4"/>
                            <ShrinkIcon v-else class="h-4 w-4"/>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Keyboard shortcuts hint - Desktop only -->
            <div
                class="hidden sm:block border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                {{ t('Keyboard shortcuts') }}:
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">+/-</kbd> {{ t('zoom') }},
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">0</kbd> {{ t('reset') }},
                <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">F11</kbd>
                {{ t('fullscreen') }}
                <span class="ml-2">• {{ t('Scroll horizontally to navigate') }}</span>
            </div>

            <!-- Mobile touch hint -->
            <div
                class="sm:hidden border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center justify-center gap-2">
                    <MoveIcon class="h-3 w-3"/>
                    {{ t('Scroll') }}
                    <span class="mx-1">•</span>
                    <ScaleIcon class="h-3 w-3"/>
                    {{ t('Pinch zoom') }}
                </div>
            </div>

            <div
                ref="bracketScrollContainerRef"
                class="bracket-container overflow-x-auto bg-gray-50 dark:bg-gray-900/50"
                @touchend="onTouchEnd"
                @touchmove="onTouchMove"
                @touchstart="onTouchStart"
                @wheel="onWheel"
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
import {
    ExpandIcon,
    MinusIcon,
    MoveIcon,
    PlusIcon,
    RotateCcwIcon,
    ScaleIcon,
    ShrinkIcon,
    UserIcon
} from 'lucide-vue-next';
import {inject, ref} from "vue";

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

// Component refs
const bracketContainerRef = ref<HTMLDivElement>();
const bracketScrollContainerRef = ref<HTMLDivElement>();

// Expose refs for parent components
defineExpose({
    bracketContainerRef,
    bracketScrollContainerRef,
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

// Wrapper methods to ensure injected functions are called
const onZoomOut = () => zoomOut?.();
const onZoomIn = () => zoomIn?.();
const onResetZoom = () => resetZoom?.();
const onToggleFullscreen = () => toggleFullscreen?.();
const onTouchEnd = (e: TouchEvent) => handleTouchEnd?.(e);
const onTouchMove = (e: TouchEvent) => handleTouchMove?.(e);
const onTouchStart = (e: TouchEvent) => handleTouchStart?.(e);
const onWheel = (e: WheelEvent) => handleWheel?.(e);
</script>

<style scoped>
.bracket-container {
    /* Remove vertical scroll, only horizontal */
    overflow-y: visible;
    overflow-x: auto;
    /* Remove touch-action to allow custom touch handling */
    touch-action: pan-x pan-y;
    position: relative;
}

/* Fullscreen adjustments */
.bracket-fullscreen-container:fullscreen {
    background: white;
    padding: 0.5rem;
    overflow: auto;
}

.bracket-fullscreen-container:fullscreen .bracket-container {
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}

.bracket-zoom-wrapper {
    transform-origin: top left;
    transition: transform 0.2s ease-out;
    /* Add minimum size to ensure scrollability on mobile */
    min-width: max-content;
    position: relative;
    /* Allow vertical expansion */
    display: inline-block;
}

/* Custom scrollbar styling - only for horizontal */
.bracket-container::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

@media (min-width: 640px) {
    .bracket-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
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

/* Ensure smooth scrolling on iOS */
.bracket-container {
    -webkit-overflow-scrolling: touch;
}

/* Prevent text selection on touch devices */
.bracket-container {
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

/* Dark mode adjustments */
:root.dark .bracket-fullscreen-container:fullscreen {
    background: #1f2937;
}

:root.dark .bracket-container::-webkit-scrollbar-track {
    background: #374151;
}

:root.dark .bracket-container::-webkit-scrollbar-thumb {
    background: #6b7280;
}

:root.dark .bracket-container::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
