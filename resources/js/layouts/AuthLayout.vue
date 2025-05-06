<script lang="ts" setup>
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout :description="description" :title="title">
        <slot/>
    </AuthLayout>
</template>
