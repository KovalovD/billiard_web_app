<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuSeparator.vue -->
<script lang="ts" setup>
import {computed, type HTMLAttributes} from 'vue'
import {DropdownMenuSeparator, type DropdownMenuSeparatorProps,} from 'reka-ui'
import {cn} from '@/lib/utils'

const props = defineProps<
    DropdownMenuSeparatorProps & { class?: HTMLAttributes['class'] }
>()

const delegatedProps = computed(() => {
    // eslint-disable-next-line
    const {class: _, ...delegated} = props

    return delegated
})
</script>

<template>
    <DropdownMenuSeparator
        :class="cn('-mx-1 my-1 h-px bg-muted', props.class)"
        v-bind="delegatedProps"
    />
</template>
