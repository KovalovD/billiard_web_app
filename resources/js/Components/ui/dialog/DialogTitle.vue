// resources/js/Components/ui/DialogTitle.vue
<script lang="ts" setup>
interface Props {
  class?: string;
}

defineProps<Props>();
</script>

<template>
  <h2 :class="['text-lg font-semibold leading-none tracking-tight', $props.class]">
    <slot/>
  </h2>
</template>
