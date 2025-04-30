<script lang="ts" setup>
import {computed, onMounted, onUnmounted, ref} from 'vue';

interface Props {
    align?: 'left' | 'right';
    width?: '48' | string; // '48' - стандарт Tailwind w-48
    contentClasses?: string;
    triggerClasses?: string;
}

const props = withDefaults(defineProps<Props>(), {
    align: 'right',
    width: '48',
    contentClasses: 'py-1 bg-white dark:bg-gray-700 rounded-md shadow-lg', // Дефолтные классы контента
    triggerClasses: '', // Классы для триггера (если нужны)
});

const open = ref(false);

const closeOnEscape = (e: KeyboardEvent) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    // Простое сопоставление для w-48, можно расширить
    return props.width === '48' ? 'w-48' : props.width;
});

const alignmentClasses = computed(() => {
    return props.align === 'left' ? 'origin-top-left left-0' : 'origin-top-right right-0';
});
</script>

<template>
    <div class="relative">
        <div :class="triggerClasses" @click="open = !open">
            <slot name="trigger"/>
        </div>

        <div v-show="open" class="fixed inset-0 z-40" @click="open = false"></div>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="open"
                :class="[widthClass, alignmentClasses]"
                class="absolute z-50 mt-2 rounded-md shadow-lg"
                style="display: none"
                @click="open = false"
            >
                <div :class="contentClasses" class="rounded-md ring-1 ring-black ring-opacity-5">
                    <slot name="content"/>
                </div>
            </div>
        </transition>
    </div>
</template>
