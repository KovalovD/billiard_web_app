<!-- resources/js/Components/ui/accordion/Accordion.vue -->
<script lang="ts" setup>
import {provide, ref} from 'vue'

const openItems = ref<string[]>([])

provide('accordion', {
    openItems,
    toggle(value: string) {
        const index = openItems.value.indexOf(value)
        if (index > -1) {
            openItems.value.splice(index, 1)
        } else {
            openItems.value.push(value)
        }
    },
    isOpen(value: string) {
        return openItems.value.includes(value)
    }
})
</script>

<template>
    <div class="space-y-2">
        <slot/>
    </div>
</template>
