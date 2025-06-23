<script lang="ts" setup>
import {computed, inject, onMounted, ref} from 'vue'
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

onMounted(() => {
    if (contentRef.value && select?.setContentElement) {
        select.setContentElement(contentRef.value)
    }
})

const positionClasses = computed(() => {
    return props.position === 'top'
        ? 'bottom-full mb-1'
        : 'top-full mt-1'
})
</script>

<template>
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
                'absolute left-0 right-0 z-[9999] min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md',
                positionClasses,
                props.class
            )"
            role="listbox"
            @click.stop
        >
            <div class="max-h-[300px] overflow-y-auto p-1">
                <slot/>
            </div>
        </div>
    </Transition>
</template>
