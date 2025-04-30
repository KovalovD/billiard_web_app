<script lang="ts" setup>
import {computed} from 'vue';
import {cva} from 'class-variance-authority';
import {cn} from '@/lib/utils';

const labelVariants = cva(
    'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70'
);

// Определяем пропсы для вариантов (если нужны)
// type LabelProps = VariantProps<typeof labelVariants>;

interface Props {
    // variant?: LabelProps['variant']; // Если добавишь варианты
    class?: string;
    // Добавляем 'for', хотя он будет передан через attrs
    for?: string;
}

const props = defineProps<Props>();

const computedClasses = computed(() => cn(labelVariants(), props.class));
</script>

<template>
    <label :class="computedClasses" :for="for">
        <slot/>
    </label>
</template>
