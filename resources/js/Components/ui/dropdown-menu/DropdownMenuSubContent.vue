<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuSubContent.vue -->
<script lang="ts" setup>
import {DropdownMenuSubContent, type DropdownMenuSubContentProps} from 'reka-ui'

defineProps<DropdownMenuSubContentProps>()
</script>
<template>
    <DropdownMenuSubContent v-bind="$props">
        <slot/>
    </DropdownMenuSubContent>
</template>
