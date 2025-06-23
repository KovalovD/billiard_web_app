<!-- resources/js/Components/ui/accordion/AccordionContent.vue -->
<script lang="ts" setup>
import {computed, inject} from 'vue'
import {cn} from '@/lib/utils'

interface Props {
    value: string
    class?: string
}

const props = defineProps<Props>()

const accordion = inject<{
    isOpen: (value: string) => boolean
}>('accordion')

const isOpen = computed(() => accordion?.isOpen(props.value) ?? false)
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="max-h-0 opacity-0"
        enter-to-class="max-h-[2000px] opacity-100"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="max-h-[2000px] opacity-100"
        leave-to-class="max-h-0 opacity-0"
    >
        <div v-if="isOpen" class="overflow-visible">
            <div :class="cn('px-4 pb-4 pt-0 overflow-visible', props.class)">
                <slot/>
            </div>
        </div>
    </Transition>
</template>
