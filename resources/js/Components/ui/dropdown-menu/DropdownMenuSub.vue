<!-- resources/js/Components/ui/dropdown-menu/DropdownMenuSub.vue -->
<script lang="ts" setup>
import {DropdownMenuSub, type DropdownMenuSubProps} from 'reka-ui'

defineProps<DropdownMenuSubProps>()
</script>
<template>
    <DropdownMenuSub v-bind="$props">
        <slot/>
    </DropdownMenuSub>
</template>
