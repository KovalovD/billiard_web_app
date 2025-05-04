<script lang="ts" setup>
import {computed, provide, ref, watch} from 'vue';

interface Props {
    modelValue?: string | number | null;
    disabled?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const selectedValue = computed(() => props.modelValue);

// Provide select context
provide('select', {
    isOpen,
    toggle: () => {
        if (!props.disabled) {
            isOpen.value = !isOpen.value;
        }
    },
    select: (value: string | number) => {
        emit('update:modelValue', value);
        isOpen.value = false;
    },
    selectedValue,
    disabled: computed(() => props.disabled),
});

// Close when clicking outside
const closeOnClickOutside = (e: Event) => {
    const path = e.composedPath();
    const isSelectElement = path.some(el => {
        const element = el as HTMLElement;
        return element.hasAttribute?.('role') && element.getAttribute('role') === 'combobox';
    });

    if (isOpen.value && !isSelectElement) {
        isOpen.value = false;
    }
};

// Watch for modelValue changes to ensure sync
watch(() => props.modelValue, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        isOpen.value = false; // Close dropdown when value changes
    }
});

if (typeof window !== 'undefined') {
    window.addEventListener('click', closeOnClickOutside, true);
    window.addEventListener('touchstart', closeOnClickOutside, true);
}
</script>

<template>
    <div class="relative" role="combobox">
        <slot></slot>
    </div>
</template>
