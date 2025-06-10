//resources/js/Components/ui/SelectItem.vue
<script lang="ts" setup>
import {computed, inject} from 'vue';
import {cn} from '@/lib/utils';
import {CheckIcon} from 'lucide-vue-next';

interface Props {
    value: string | number;
    disabled?: boolean;
    class?: string;
}

const props = defineProps<Props>();

const select = inject<{
    select: (value: string | number) => void;
    selectedValue: { value: string | number | null };
}>('select');

const isSelected = computed(() => {
    return String(props.value) === String(select?.selectedValue?.value);
});

const handleClick = () => {
    if (!props.disabled && select) {
        select.select(props.value);
    }
};
</script>

<template>
    <div
        :class="cn(
            'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground',
            props.disabled && 'pointer-events-none opacity-50',
            !props.disabled && 'cursor-pointer hover:bg-accent',
            props.class
        )"
        :data-value="value"
        role="option"
        @click="handleClick"
    >
        <span v-if="isSelected" class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
            <CheckIcon class="h-4 w-4"/>
        </span>
        <slot/>
    </div>
</template>
