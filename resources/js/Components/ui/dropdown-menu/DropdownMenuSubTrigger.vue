<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuSubTrigger.vue -->
<script lang="ts" setup>
import {DropdownMenuSubTrigger, type DropdownMenuSubTriggerProps} from 'reka-ui'

defineProps<DropdownMenuSubTriggerProps>()
</script>
<template>
    <DropdownMenuSubTrigger v-bind="$props">
        <slot/>
    </DropdownMenuSubTrigger>
</template>
