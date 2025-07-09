<script lang="ts" setup>
import {computed, inject, ref} from 'vue'
import {cn} from '@/lib/utils'
import {CheckIcon} from 'lucide-vue-next'

interface Props {
    value: string | number | null
    disabled?: boolean
    class?: string
}

const props = defineProps<Props>()

const itemRef = ref<HTMLDivElement>()

const select = inject<{
    select: (value: string | number, label: string) => void
    selectedValue: { value: string | number | null }
}>('select')

const isSelected = computed(() => {
    return select?.selectedValue.value === props.value
})

const handleClick = () => {
    if (!props.disabled && select && itemRef.value) {
        const label = itemRef.value.textContent?.trim() || String(props.value)
        select.select(props.value, label)
    }
}
</script>

<template>
    <div
        ref="itemRef"
        :aria-selected="isSelected"
        :class="cn(
            'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none transition-colors',
            'hover:bg-accent hover:text-accent-foreground',
            'focus:bg-accent focus:text-accent-foreground',
            props.disabled && 'pointer-events-none opacity-50',
            isSelected && 'bg-accent/50',
            props.class
        )"
        :data-value="value"
        role="option"
        tabindex="-1"
        @click.stop="handleClick"
    >
        <span
            v-if="isSelected"
            class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center"
        >
            <CheckIcon class="h-4 w-4"/>
        </span>
        <slot/>
    </div>
</template>
