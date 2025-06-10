<script lang="ts" setup>
import {computed} from 'vue';
import {cva} from 'class-variance-authority';
import {cn} from '@/lib/utils';

const labelVariants = cva(
    'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70'
);

interface Props {
    class?: string;
    for?: string;
}

const props = defineProps<Props>();

const computedClasses = computed(() => cn(labelVariants(), props.class));
</script>

<template>
    <label :class="computedClasses" :for="props.for">
        <slot/>
    </label>
</template>
