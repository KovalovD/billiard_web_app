// resources/js/Components/ui/DialogDescription.vue
<script lang="ts" setup>
interface Props {
  class?: string;
}

defineProps<Props>();
</script>

<template>
  <p :class="['text-sm text-gray-500 dark:text-gray-400', $props.class]">
    <slot/>
  </p>
</template>
