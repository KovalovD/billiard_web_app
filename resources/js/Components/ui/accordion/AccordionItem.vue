<!-- resources/js/Components/ui/accordion/AccordionItem.vue -->
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
    <div :class="cn('border rounded-lg', props.class)">
        <slot :is-open="isOpen"/>
    </div>
</template>
