<script lang="ts" setup>
import {cn} from '@/lib/utils'

interface Props {
    class?: string
}

const props = defineProps<Props>()
</script>

<template>
    <div
        :class="cn(
            'py-1.5 pl-8 pr-2 text-sm font-semibold text-muted-foreground',
            props.class
        )"
    >
        <slot/>
    </div>
</template>
