<script setup lang="ts">
import { cn } from '@/lib/utils';
import { CheckIcon } from 'lucide-vue-next';

interface Props {
    class?: string;
    value: string | number; // Значение этого пункта
    disabled?: boolean;
    isSelected?: boolean; // Флаг, выбран ли этот пункт
}
const props = defineProps<Props>();
const emit = defineEmits(['select']);

const handleClick = () => {
    if (!props.disabled) {
        emit('select', props.value);
    }
}
</script>

<template>
    <div
        @click="handleClick"
        :class="cn(
      'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      props.disabled && 'opacity-50 cursor-not-allowed',
      !props.disabled && 'hover:bg-accent', // Эффект при наведении для активных
      props.class
    )"
        :data-disabled="disabled ? '' : undefined"
    >
    <span v-if="isSelected" class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
        <CheckIcon class="h-4 w-4" />
    </span>
        <slot /> </div>
</template>
