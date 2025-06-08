<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuCheckboxItem.vue -->
<script lang="ts" setup>
import {DropdownMenuCheckboxItem, type DropdownMenuCheckboxItemProps} from 'reka-ui'

defineProps<DropdownMenuCheckboxItemProps>()
</script>
<template>
    <DropdownMenuCheckboxItem v-bind="$props">
        <slot/>
    </DropdownMenuCheckboxItem>
</template>
