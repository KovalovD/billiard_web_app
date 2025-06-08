<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuLabel.vue -->
<script lang="ts" setup>
import {type HTMLAttributes, computed} from 'vue'
import {DropdownMenuLabel, type DropdownMenuLabelProps} from 'reka-ui'
import {cn} from '@/lib/utils'

const props = defineProps<
    DropdownMenuLabelProps & { class?: HTMLAttributes['class']; inset?: boolean }
>()

const delegatedProps = computed(() => {
    // eslint-disable-next-line
    const {class: _, inset: __, ...delegated} = props

    return delegated
})
</script>

<template>
    <DropdownMenuLabel
        :class="
      cn('px-2 py-1.5 text-sm font-semibold', inset && 'pl-8', props.class)
    "
        v-bind="delegatedProps"
    >
        <slot/>
    </DropdownMenuLabel>
</template>
