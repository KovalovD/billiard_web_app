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
      'flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2',
      props.class
    )"
    >
        <slot/>
    </div>
</template>
