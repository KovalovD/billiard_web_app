<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuItem.vue -->
<script lang="ts" setup>
import {type HTMLAttributes, computed} from 'vue'
import {
    DropdownMenuItem,
    type DropdownMenuItemProps,
    useForwardPropsEmits,
} from 'reka-ui'
import {cn} from '@/lib/utils'

const props = withDefaults(
    defineProps<DropdownMenuItemProps & { class?: HTMLAttributes['class'] }>(),
    {
        disabled: false,
    }
)

const emits = defineEmits<{
    select: [event: Event]
}>()

const delegatedProps = computed(() => {
    // eslint-disable-next-line
    const {class: _, ...delegated} = props
    return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
    <DropdownMenuItem
        :class="
            cn(
                'relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                props.class
            )
        "
        v-bind="forwarded"
    >
        <slot/>
    </DropdownMenuItem>
</template>
