<script lang="ts" setup>
// ИМПОРТ НЕ НУЖЕН, так как файл в public

// defineOptions не обязателен, но полезен для Vue DevTools
defineOptions({
    name: 'ApplicationLogo',
});

// Можно оставить пропсы для размера, если они нужны
// interface Props {
//   width?: string | number;
//   height?: string | number;
// }
// const props = defineProps<Props>();
</script>

<template>
    <img alt="Application Logo" src="/logo-min.PNG" v-bind="$attrs"/>
</template>
