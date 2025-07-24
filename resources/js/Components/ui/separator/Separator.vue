<script lang="ts" setup>
import {computed} from 'vue'
import {cn} from '@/lib/utils'

interface Props {
    class?: string
    orientation?: 'horizontal' | 'vertical'
    decorative?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    orientation: 'horizontal',
    decorative: true,
})

const computedClasses = computed(() =>
    cn(
        'shrink-0 bg-border',
        props.orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]',
        props.class
    )
)

const ariaOrientation = computed(() =>
    props.decorative ? undefined : props.orientation
)
</script>

<template>
    <div
        :aria-orientation="ariaOrientation"
        :class="computedClasses"
        :role="decorative ? 'none' : 'separator'"
    />
</template>
