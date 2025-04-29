<script setup lang="ts">
interface Props {
    placeholder?: string;
}
defineProps<Props>();
// Этот компонент обычно используется внутри Select для отображения
// выбранного значения. Логика отображения будет в родительском Select.
</script>

<template>
  <span>
    <slot /> </span>
</template>
