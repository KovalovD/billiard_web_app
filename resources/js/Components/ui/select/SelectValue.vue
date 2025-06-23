// resources/js/Components/ui/form/SelectValue.vue
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

    // For status and stage, we need to handle the display differently
    // Check if we're dealing with status or stage values
    const statusMap: Record<string, string> = {
        'upcoming': 'Upcoming',
        'active': 'Active',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    };

    const stageMap: Record<string, string> = {
        'registration': 'Registration',
        'seeding': 'Seeding',
        'group': 'Group Stage',
        'bracket': 'Bracket Stage',
        'completed': 'Completed'
    };

    // Check if value matches any status or stage
    if (typeof value === 'string') {
        if (statusMap[value]) {
            return statusMap[value];
        }
        if (stageMap[value]) {
            return stageMap[value];
        }
    }

    // Try to find the DOM element with matching data-value
    const element = document.querySelector(`[data-value="${value}"]`);

    // Extract text content if element exists
    if (element && element.textContent) {
        return element.textContent.trim();
    }

    // Fallback to placeholder or value
    return props.placeholder || String(value);
});
</script>

<template>
    <span class="pointer-events-none">{{ displayText }}</span>
</template>
