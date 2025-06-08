<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuRadioItem.vue -->
<script lang="ts" setup>
import {DropdownMenuRadioItem, type DropdownMenuRadioItemProps} from 'reka-ui'

defineProps<DropdownMenuRadioItemProps>()
</script>
<template>
    <DropdownMenuRadioItem v-bind="$props">
        <slot/>
    </DropdownMenuRadioItem>
</template>
