<script lang="ts" setup>
import {computed, inject} from 'vue';
import {cn} from '@/lib/utils';

interface Props {
    class?: string;
}

const props = defineProps<Props>();

const select = inject<{
    isOpen: { value: boolean };
}>('select', {
    isOpen: computed(() => false),
});
</script>

<template>
    <div
        v-if="select.isOpen.value"
        :class="cn(
            'absolute z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md',
            'animate-in data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
            props.class
        )"
        role="listbox"
    >
        <div class="p-1">
            <slot/>
        </div>
    </div>
</template>
