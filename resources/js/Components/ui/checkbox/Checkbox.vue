// resources/js/Components/ui/checkbox/Checkbox.vue

<script lang="ts" setup>
import {computed} from 'vue';
import {cn} from '@/lib/utils';

interface Props {
    modelValue?: boolean;
    disabled?: boolean;
    id?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const checked = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <input
        :id="id"
        v-model="checked"
        type="checkbox"
        :disabled="disabled"
        :class="cn(
            'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700',
            props.class
        )"
    />
</template>
