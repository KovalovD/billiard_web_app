<script lang="ts" setup>
import {cn} from '@/lib/utils'

interface Props {
    class?: string
}

const props = defineProps<Props>()
</script>

<template>
    <div
        :class="cn('-mx-1 my-1 h-px bg-muted', props.class)"
        role="separator"
    />
</template>
