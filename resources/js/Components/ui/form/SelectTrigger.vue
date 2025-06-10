<script lang="ts" setup>
import {computed, inject} from 'vue';
import {cn} from '@/lib/utils';
import {ChevronDownIcon} from 'lucide-vue-next';

interface Props {
    class?: string;
}

const props = defineProps<Props>();

const select = inject<{
    toggle: () => void;
    disabled: { value: boolean };
}>('select', {
    toggle: () => {
    },
    disabled: computed(() => false),
});

const handleClick = () => {
    if (!select.disabled.value) {
        select.toggle();
    }
};
</script>

<template>
    <button
        :class="cn(
            'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            props.class
        )"
        :disabled="select.disabled.value"
        type="button"
        @click="handleClick"
    >
        <slot/>
        <ChevronDownIcon class="h-4 w-4 opacity-50"/>
    </button>
</template>
