<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuRadioGroup.vue -->
<script lang="ts" setup>
import {DropdownMenuRadioGroup, type DropdownMenuRadioGroupProps} from 'reka-ui'

defineProps<DropdownMenuRadioGroupProps>()
</script>
<template>
    <DropdownMenuRadioGroup v-bind="$props">
        <slot/>
    </DropdownMenuRadioGroup>
</template>
