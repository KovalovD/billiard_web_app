<script lang="ts" setup>
import {computed, provide, ref} from 'vue';

interface Props {
    modelValue?: string | number | null;
    disabled?: boolean;
    name?: string;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);

const selectedValue = ref(props.modelValue);

const updateValue = (value: string | number) => {
    if (!props.disabled) {
        selectedValue.value = value;
        emit('update:modelValue', value);
    }
};

provide('radioGroup', {
    selectedValue: computed(() => selectedValue.value),
    updateValue,
    disabled: computed(() => Boolean(props.disabled)),
    name: props.name
});

// Watch for external changes
const watchModelValue = computed(() => props.modelValue);
watchModelValue && (selectedValue.value = watchModelValue.value);
</script>

<template>
    <div class="space-y-2">
        <slot/>
    </div>
</template>
