<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="transform translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="transform translate-y-0 opacity-100 sm:translate-x-0"
            leave-to-class="transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        >
            <div
                v-if="show"
                class="fixed top-4 right-4 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden z-50"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <!-- Іконка успіху -->
                            <svg
                                v-if="type === 'success'"
                                class="h-6 w-6 text-green-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </svg>

                            <!-- Іконка помилки -->
                            <svg
                                v-else-if="type === 'error'"
                                class="h-6 w-6 text-red-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </svg>

                            <!-- Іконка інформації -->
                            <svg
                                v-else
                                class="h-6 w-6 text-blue-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </svg>
                        </div>

                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900">
                                {{ title }}
                            </p>
                            <p v-if="message" class="mt-1 text-sm text-gray-500">
                                {{ message }}
                            </p>
                        </div>

                        <div class="ml-4 flex-shrink-0 flex">
                            <button
                                class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                @click="hide"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        clip-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        fill-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script lang="ts" setup>
import {ref, watch, onMounted} from 'vue';

interface Props {
    show: boolean;
    type?: 'success' | 'error' | 'info';
    title: string;
    message?: string;
    duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    duration: 5000
});

const emit = defineEmits<{
    hide: [];
}>();

const timeoutId = ref<number | null>(null);

const hide = () => {
    emit('hide');
    if (timeoutId.value) {
        clearTimeout(timeoutId.value);
        timeoutId.value = null;
    }
};

// Автоматичне приховування через заданий час
watch(() => props.show, (newShow) => {
    if (newShow && props.duration > 0) {
        timeoutId.value = setTimeout(() => {
            hide();
        }, props.duration) as unknown as number;
    }
});

onMounted(() => {
    if (props.show && props.duration > 0) {
        timeoutId.value = setTimeout(() => {
            hide();
        }, props.duration) as unknown as number;
    }
});
</script>
