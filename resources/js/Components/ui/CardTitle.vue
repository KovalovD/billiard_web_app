<script lang="ts" setup>
import {cn} from '@/lib/utils'

interface Props {
    class?: string;
}

const props = defineProps<Props>()
</script>

<template>
    <h3 :class="cn('text-lg font-semibold leading-none tracking-tight', props.class)">
        <slot/>
    </h3>
</template>
