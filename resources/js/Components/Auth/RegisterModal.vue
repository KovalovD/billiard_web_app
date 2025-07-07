<script lang="ts" setup>
import RegisterForm from '@/Components/Auth/RegisterForm.vue';
import {Modal} from '@/Components/ui';
import type {User} from '@/types/api';
import {onMounted, watch} from 'vue';

interface Props {
    show: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'success', 'error']);

// Watch for changes to show prop to ensure modal is visible when needed
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            // When modal opens, do any setup if needed
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        } else {
            document.body.style.overflow = ''; // Re-enable scrolling when closed
        }
    },
);

// Clean up on unmount
onMounted(() => {
    return () => {
        document.body.style.overflow = ''; // Ensure scrolling is re-enabled
    };
});

const handleRegisterSuccess = (user: User) => {
    emit('success', user);
};

const handleRegisterError = (error: any) => {
    emit('error', error);
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <!-- Remove padding from modal wrapper and use transparent background -->
    <Modal :show="show" maxWidth="lg" @close="closeModal" class="!p-0 !bg-transparent">
        <!-- RegisterForm already has its own Card styling, so no additional wrapper needed -->
        <RegisterForm
            @cancel="closeModal"
            @error="handleRegisterError"
            @success="handleRegisterSuccess"
        />
    </Modal>
</template>
