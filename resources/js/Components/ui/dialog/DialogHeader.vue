// resources/js/Components/ui/DialogHeader.vue
<script lang="ts" setup>
interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div :class="['flex flex-col space-y-1.5 text-center sm:text-left', $props.class]">
        <slot/>
    </div>
</template>
