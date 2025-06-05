<template>
    <div class="fixed top-4 right-4 z-50 space-y-2">
        <Toast
            v-for="toast in toasts"
            :key="toast.id"
            :duration="0"
            :message="toast.message"
            :show="true"
            :title="toast.title"
            :type="toast.type"
            @hide="removeToast(toast.id)"
        />
    </div>
</template>

<script lang="ts" setup>
import {storeToRefs} from 'pinia';
import {useToastStore} from '@/stores/toast';
import Toast from '@/Components/ui/Toast.vue';

const toastStore = useToastStore();
const {toasts} = storeToRefs(toastStore);
const {removeToast} = toastStore;
</script>
