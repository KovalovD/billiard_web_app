<script setup lang="ts">
import { cn } from '@/lib/utils'
import { ChevronDownIcon } from 'lucide-vue-next';

interface Props {
    class?: string;
    disabled?: boolean;
}
const props = defineProps<Props>()
</script>

<template>
    <button
        type="button"
        :disabled="disabled"
        :class="cn(
      'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
      props.class
    )"
    >
        <slot /> <ChevronDownIcon class="w-4 h-4 opacity-50" />
    </button>
</template>
