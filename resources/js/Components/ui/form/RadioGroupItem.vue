<script lang="ts" setup>
import {computed, inject} from 'vue';

interface Props {
    value: string | number;
    id?: string;
    disabled?: boolean;
    class?: string;
}

const props = defineProps<Props>();

const radioGroup = inject<{
    selectedValue: { value: string | number | null };
    updateValue: (value: string | number) => void;
    disabled: { value: boolean };
    name?: string;
}>('radioGroup');

const isSelected = computed(() => {
    return String(props.value) === String(radioGroup?.selectedValue?.value);
});

const isDisabled = computed(() => {
    return props.disabled || radioGroup?.disabled?.value;
});

const handleChange = () => {
    if (!isDisabled.value && radioGroup) {
        radioGroup.updateValue(props.value);
    }
};
</script>

<template>
    <div class="flex items-center space-x-2">
        <input
            :id="props.id"
            :checked="isSelected"
            :disabled="isDisabled"
            :name="radioGroup?.name"
            :value="props.value"
            class="h-4 w-4 text-primary border-gray-300 focus:ring-primary focus:ring-2"
            type="radio"
            @change="handleChange"
        />
        <slot/>
    </div>
</template>
