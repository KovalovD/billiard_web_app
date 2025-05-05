<script lang="ts" setup>
import {computed, onMounted, onUnmounted, watch} from 'vue';
import {XIcon} from 'lucide-vue-next';
import {Button} from '@/Components/ui';

interface Props {
    show: boolean;
    title?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    closeable?: boolean;
    modalWrapperClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    maxWidth: 'md',
    closeable: true,
    modalWrapperClass: 'bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:mx-auto'
});

const emit = defineEmits(['close']);

const closeModal = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        closeModal();
    }
};

// Apply body styles when modal is shown/hidden
watch(() => props.show, (value) => {
    if (value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}, {immediate: true});

// Add event listener for escape key
onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
});

// Clean up event listener
onUnmounted(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', closeOnEscape);
});

// Class for maximum width
const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <teleport to="body">
        <transition leave-active-class="duration-200">
            <div v-show="show"
                 class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50 flex items-center justify-center"
                 scroll-region>
                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="closeModal">
                        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900/80 opacity-75"/>
                    </div>
                </transition>

                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        :class="[props.modalWrapperClass, maxWidthClass]"
                        class="relative">
                        <div v-if="title || closeable"
                             class="flex justify-between items-center p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 v-if="title" id="modal-title"
                                class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
                            <div v-else></div>
                            <Button v-if="closeable"
                                    class="-m-2 p-2 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                                    size="icon" variant="ghost"
                                    @click="closeModal">
                                <XIcon class="h-5 w-5"/>
                                <span class="sr-only">Close modal</span>
                            </Button>
                        </div>

                        <div class="p-4 sm:p-6">
                            <slot/>
                        </div>

                        <div v-if="$slots.footer"
                             class="px-4 sm:px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-600">
                            <slot name="footer"/>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>
