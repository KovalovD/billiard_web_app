<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuContent.vue -->
<script lang="ts" setup>
import {computed, type HTMLAttributes} from 'vue'
import {DropdownMenuContent, type DropdownMenuContentProps, DropdownMenuPortal, useForwardPropsEmits,} from 'reka-ui'
import {cn} from '@/lib/utils'

const props = withDefaults(
    defineProps<DropdownMenuContentProps & { class?: HTMLAttributes['class'] }>(),
    {
        sideOffset: 4,
        align: 'center',
        side: 'bottom',
        avoidCollisions: true,
    }
)

const emits = defineEmits<{
    escapeKeyDown: [event: KeyboardEvent]
    pointerDownOutside: [event: MouseEvent]
    focusOutside: [event: FocusEvent]
    interactOutside: [event: MouseEvent | FocusEvent]
}>()

const delegatedProps = computed(() => {
    // eslint-disable-next-line
    const {class: _, ...delegated} = props
    return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
    <DropdownMenuPortal>
        <DropdownMenuContent
            :class="
                cn(
                    'z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
                    props.class
                )
            "
            v-bind="forwarded"
        >
            <slot/>
        </DropdownMenuContent>
    </DropdownMenuPortal>
</template>
