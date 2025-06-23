<script lang="ts" setup>
import {computed, onBeforeUnmount, onMounted, provide, ref, watch} from 'vue'

interface Props {
    modelValue?: string | number | null
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false
})

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null]
}>()

const isOpen = ref(false)
const triggerElement = ref<HTMLElement>()
const contentElement = ref<HTMLElement>()
const selectedLabel = ref('')

const selectedValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// Clear label when value changes externally
watch(() => props.modelValue, (newValue, oldValue) => {
    if (newValue !== oldValue && !selectedLabel.value) {
        // Label will be found by SelectValue component
        selectedLabel.value = ''
    }
})

// Provide context to child components
provide('select', {
    selectedValue,
    selectedLabel,
    isOpen,
    disabled: computed(() => props.disabled),
    toggle() {
        if (!props.disabled) {
            isOpen.value = !isOpen.value
        }
    },
    close() {
        isOpen.value = false
    },
    select(value: string | number, label: string) {
        selectedValue.value = value
        selectedLabel.value = label
        isOpen.value = false
    },
    setTriggerElement(el: HTMLElement) {
        triggerElement.value = el
    },
    setContentElement(el: HTMLElement) {
        contentElement.value = el
    }
})

// Handle click outside
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as Node
    if (
        triggerElement.value &&
        contentElement.value &&
        !triggerElement.value.contains(target) &&
        !contentElement.value.contains(target)
    ) {
        isOpen.value = false
    }
}

// Handle escape key
const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && isOpen.value) {
        isOpen.value = false
        triggerElement.value?.focus()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    document.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
    document.removeEventListener('keydown', handleEscape)
})
</script>

<template>
    <div class="relative">
        <slot/>
    </div>
</template>
