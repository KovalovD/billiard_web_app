<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuTrigger.vue -->
<script lang="ts" setup>
import {DropdownMenuTrigger, type DropdownMenuTriggerProps} from 'reka-ui'

const props = defineProps<DropdownMenuTriggerProps>()
</script>

<template>
    <DropdownMenuTrigger as-child v-bind="props">
        <slot/>
    </DropdownMenuTrigger>
</template>
