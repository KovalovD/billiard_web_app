<script lang="ts" setup>
import {computed, inject, nextTick, onMounted, watch} from 'vue'

interface Props {
    placeholder?: string
}

const props = defineProps<Props>()

const select = inject<{
    selectedValue: { value: string | number | null }
    selectedLabel: { value: string }
    isOpen: { value: boolean }
}>('select')

const displayText = computed(() => {
    // If no value is selected, show placeholder
    if (!select?.selectedValue.value) {
        return props.placeholder || ''
    }

    // Use the stored label if available
    if (select.selectedLabel.value) {
        return select.selectedLabel.value
    }

    // Fallback to placeholder while loading
    return props.placeholder || String(select.selectedValue.value)
})

// Function to find label from DOM
const findLabelForValue = async (value: string | number | null) => {
    if (!value || !select) return

    await nextTick()

    // Try multiple times with a small delay to handle async rendering
    let attempts = 0
    const maxAttempts = 10

    const tryFindLabel = () => {
        const items = document.querySelectorAll('[data-value]')
        for (const item of items) {
            if (item.getAttribute('data-value') === String(value)) {
                const text = item.textContent?.trim()
                if (text && select) {
                    select.selectedLabel.value = text
                    return true
                }
            }
        }
        return false
    }

    const attemptFind = () => {
        if (tryFindLabel() || attempts >= maxAttempts) {
            return
        }
        attempts++
        setTimeout(attemptFind, 50)
    }

    attemptFind()
}

// Watch for value changes
watch(() => select?.selectedValue.value, (newValue) => {
    if (newValue && (!select?.selectedLabel.value || select.selectedLabel.value === String(newValue))) {
        findLabelForValue(newValue)
    }
})

// Also watch for when dropdown opens (items might be rendered then)
watch(() => select?.isOpen.value, (isOpen) => {
    if (isOpen && select?.selectedValue.value && !select.selectedLabel.value) {
        findLabelForValue(select.selectedValue.value)
    }
})

// Try to find label on mount
onMounted(() => {
    if (select?.selectedValue.value) {
        findLabelForValue(select.selectedValue.value)
    }
})
</script>

<template>
    <span class="block truncate">{{ displayText }}</span>
</template>
