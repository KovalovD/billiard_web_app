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
    <!-- Use maxWidth="md" to ensure modal is visible and properly sized -->
    <Modal :show="show" maxWidth="md" @close="closeModal">
        <div class="p-1">
            <RegisterForm @cancel="closeModal" @error="handleRegisterError" @success="handleRegisterSuccess"/>
        </div>
    </Modal>
</template>
