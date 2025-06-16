<!-- resources/js/Components/ui/accordion/AccordionTrigger.vue -->
<script lang="ts" setup>
import {computed, inject} from 'vue'
import {ChevronDownIcon} from 'lucide-vue-next'
import {cn} from '@/lib/utils'

interface Props {
    value: string
    class?: string
}

const props = defineProps<Props>()

const accordion = inject<{
    toggle: (value: string) => void
    isOpen: (value: string) => boolean
}>('accordion')

const isOpen = computed(() => accordion?.isOpen(props.value) ?? false)

const handleClick = () => {
    accordion?.toggle(props.value)
}
</script>

<template>
    <button
        :class="cn(
      'flex w-full items-center justify-between px-4 py-4 text-sm font-medium transition-all hover:bg-gray-50 dark:hover:bg-gray-800',
      props.class
    )"
        type="button"
        @click="handleClick"
    >
        <slot/>
        <ChevronDownIcon
            :class="cn(
        'h-4 w-4 shrink-0 transition-transform duration-200',
        isOpen && 'rotate-180'
      )"
        />
    </button>
</template>
