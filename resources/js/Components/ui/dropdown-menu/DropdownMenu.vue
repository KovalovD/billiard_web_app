<!-- resources/js/Components/ui/dropdown-menu/DropdownMenu.vue -->
<script lang="ts" setup>
import {DropdownMenuRoot, type DropdownMenuRootEmits, type DropdownMenuRootProps, useForwardPropsEmits,} from 'reka-ui'

const props = defineProps<DropdownMenuRootProps>()
const emits = defineEmits<DropdownMenuRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
    <DropdownMenuRoot v-bind="forwarded">
        <slot/>
    </DropdownMenuRoot>
</template>
