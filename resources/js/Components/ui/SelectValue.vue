<script lang="ts" setup>
import {computed, inject, onMounted, onUnmounted, ref, watch} from 'vue';

interface Props {
    placeholder?: string;
}

const props = defineProps<Props>();

const select = inject<{
    selectedValue: { value: string | number | null };
}>('select', {
    selectedValue: computed(() => null),
});

const selectedContent = ref<string>('');

const updateContent = () => {
    const value = select.selectedValue.value;

    if (value === null || value === 'null') {
        selectedContent.value = '';
        return;
    }

    // Find all options
    const options = document.querySelectorAll('[role="option"]');
    for (const option of options) {
        const dataValue = option.getAttribute('data-value');
        if (dataValue === String(value)) {
            selectedContent.value = option.textContent?.trim() || '';
            return;
        }
    }

    selectedContent.value = '';
};

const observer = new MutationObserver(() => {
    updateContent();
});

watch(() => select.selectedValue.value, () => {
    updateContent();
}, {immediate: true});

onMounted(() => {
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    updateContent();
});

onUnmounted(() => {
    observer.disconnect();
});
</script>

<template>
    <span>{{ selectedContent || props.placeholder }}</span>
</template>
