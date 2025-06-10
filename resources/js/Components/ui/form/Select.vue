//resources/js/Components/ui/Select.vue
<script lang="ts" setup>
import {computed, provide, ref} from 'vue';

interface Props {
    modelValue?: string | number | null;
    disabled?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const triggerRef = ref<HTMLElement>();

provide('select', {
    selectedValue: computed(() => props.modelValue ?? ''),
    isOpen,
    disabled: computed(() => Boolean(props.disabled)),
    toggle() {
        if (!props.disabled) {
            isOpen.value = !isOpen.value;
        }
    },
    select(value: string | number) {
        emit('update:modelValue', value);
        isOpen.value = false;
    }
});

// Close when clicking outside
const handleClickOutside = (e: MouseEvent) => {
    if (!triggerRef.value?.contains(e.target as Node)) {
        isOpen.value = false;
    }
};

if (typeof window !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
}
</script>

<template>
    <div ref="triggerRef" class="relative">
        <slot/>
    </div>
</template>
