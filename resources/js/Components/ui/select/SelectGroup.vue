<script lang="ts" setup>
import {cn} from '@/lib/utils'

interface Props {
    class?: string
}

const props = defineProps<Props>()
</script>

<template>
    <div :class="cn('p-1', props.class)">
        <slot/>
    </div>
</template>
