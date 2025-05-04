//resources/js/Components/ui/SelectValue.vue
<script lang="ts" setup>
import {computed, inject} from 'vue';

interface Props {
    placeholder?: string;
}

const props = defineProps<Props>();

const select = inject<{
    selectedValue: { value: string | number | null };
}>('select');

const displayText = computed(() => {
    const value = select?.selectedValue?.value;

    if (!value || value === '') {
        return props.placeholder || '';
    }

    // Find the DOM element with matching data-value
    const element = document.querySelector(`[data-value="${value}"]`);

    // Extract text content (city/club name)
    if (element && element.textContent) {
        return element.textContent.trim();
    }

    // Fallback to placeholder if can't find element
    return props.placeholder || String(value);
});
</script>

<template>
    <span class="pointer-events-none">{{ displayText }}</span>
</template>
