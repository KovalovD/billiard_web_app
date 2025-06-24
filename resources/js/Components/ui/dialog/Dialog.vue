// resources/js/Components/ui/Dialog.vue
<script lang="ts" setup>
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';

interface Props {
    open?: boolean;
    closeOnOverlayClick?: boolean;
    closeOnEscape?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    closeOnOverlayClick: true,
    closeOnEscape: true,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const dialogRef = ref<HTMLElement>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const handleOverlayClick = (event: MouseEvent) => {
    if (props.closeOnOverlayClick && event.target === event.currentTarget) {
        isOpen.value = false;
    }
};

const handleEscape = (event: KeyboardEvent) => {
    if (props.closeOnEscape && event.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }
};

const handleClose = () => {
    isOpen.value = false;
};

// Focus management
watch(isOpen, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden';
        // Focus the dialog when it opens
        setTimeout(() => {
            dialogRef.value?.focus();
        }, 0);
    } else {
        document.body.style.overflow = '';
    }
});

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <!-- Overlay -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click="handleOverlayClick"
            >
                <!-- Dialog Content -->
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="isOpen"
                        ref="dialogRef"
                        aria-modal="true"
                        class="relative w-full max-w-lg max-h-[90vh] overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800"
                        role="dialog"
                        tabindex="-1"
                        @click.stop
                    >
                        <slot :close="handleClose"/>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
