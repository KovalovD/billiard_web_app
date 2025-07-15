<script lang="ts" setup>
import {cn} from '@/lib/utils'

interface Props {
    class?: string | string[];
}

const props = defineProps<Props>()

</script>

<template>
    <div
        :class="cn('rounded-lg border bg-card text-card-foreground shadow-sm', props.class)"
    >
        <slot/>
    </div>
</template>
