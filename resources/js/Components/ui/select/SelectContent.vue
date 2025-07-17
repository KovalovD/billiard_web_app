<script lang="ts" setup>
import {computed, inject, nextTick, onMounted, ref, watch} from 'vue'
import {cn} from '@/lib/utils'

interface Props {
    class?: string
    position?: 'top' | 'bottom'
}

const props = withDefaults(defineProps<Props>(), {
    position: 'bottom'
})

const select = inject<{
    isOpen: { value: boolean }
    setContentElement: (el: HTMLElement) => void
}>('select')

const contentRef = ref<HTMLDivElement>()
const triggerRect = ref<DOMRect | null>(null)

// Get trigger element position
const updatePosition = async () => {
    await nextTick()
    const trigger = document.querySelector('[aria-expanded="true"]') as HTMLElement
    if (trigger) {
        triggerRect.value = trigger.getBoundingClientRect()
    }
}

onMounted(() => {
    if (contentRef.value && select?.setContentElement) {
        select.setContentElement(contentRef.value)
    }
})

// Update position when dropdown opens
const isOpen = computed(() => select?.isOpen.value)
watch(isOpen, (newVal) => {
    if (newVal) {
        updatePosition()
    }
})

const positionStyles = computed(() => {
    if (!triggerRect.value) return {}

    const rect = triggerRect.value
    const top = props.position === 'top'
        ? rect.top - 8 // 8px gap
        : rect.bottom + 4 // 4px gap

    return {
        position: 'fixed' as const,
        top: `${top}px`,
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        maxHeight: props.position === 'top'
            ? `${rect.top - 20}px`
            : `calc(100vh - ${rect.bottom + 20}px)`
    }
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="select?.isOpen.value"
                ref="contentRef"
                :class="cn(
                    'z-[99999] min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md',
                    props.class
                )"
                :style="positionStyles"
                role="listbox"
                @click.stop
            >
                <div class="max-h-[300px] overflow-y-auto p-1">
                    <slot/>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
