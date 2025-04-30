<script lang="ts" setup>
import {useAttrs} from 'vue'
import {cn} from '@/lib/utils'

defineOptions({
    inheritAttrs: false,
})

const modelValue = defineModel<string | number>()

const {class: className, ...restAttrs} = useAttrs()
</script>

<template>
  <textarea
      v-model="modelValue"
      :class="cn('flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50', className as string ?? '')"
      v-bind="restAttrs"
  />
</template>
