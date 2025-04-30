<script lang="ts" setup>
import {cn} from '@/lib/utils';

interface Props {
    class?: string;
    modelValue?: string | number | null; // Для v-model
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);

const handleChange = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLSelectElement).value);
}
</script>

<template>
    <select
        :class="cn('flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50', props.class)"
        :value="modelValue"
        @change="handleChange"
    >
        <slot/>
    </select>
</template>
