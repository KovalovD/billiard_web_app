<script lang="ts" setup>
import {inject, onMounted, ref} from 'vue'
import {cn} from '@/lib/utils'
import {ChevronDownIcon} from 'lucide-vue-next'

interface Props {
    class?: string
}

const props = defineProps<Props>()

const select = inject<{
    toggle: () => void
    disabled: { value: boolean }
    isOpen: { value: boolean }
    setTriggerElement: (el: HTMLElement) => void
}>('select')

const triggerRef = ref<HTMLButtonElement>()

onMounted(() => {
    if (triggerRef.value && select?.setTriggerElement) {
        select.setTriggerElement(triggerRef.value)
    }
})

const handleClick = () => {
    select?.toggle()
}
</script>

<template>
    <button
        ref="triggerRef"
        :aria-expanded="select?.isOpen.value"
        :class="cn(
            'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            props.class
        )"
        :disabled="select?.disabled.value"
        aria-haspopup="listbox"
        type="button"
        @click.stop="handleClick"
    >
        <slot/>
        <ChevronDownIcon
            :class="cn(
                'h-4 w-4 opacity-50 transition-transform duration-200',
                select?.isOpen.value && 'rotate-180'
            )"
        />
    </button>
</template>
