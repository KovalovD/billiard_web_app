<script lang="ts" setup>
import {cn} from '@/lib/utils'
import {ref} from 'vue';

interface Props {
    class?: string;
    // Позиционирование можно добавить через пропсы или классы Tailwind
    position?: 'item-aligned' | 'popper';
}

const props = withDefaults(defineProps<Props>(), {
    position: 'popper',
});

// TODO: Реализовать логику открытия/закрытия и позиционирования,
// возможно, с использованием Radix Vue или плавающей UI библиотеки.
// Пока что это просто стилизованный контейнер.
const isOpen = ref(true); // Для примера, должна управляться извне

</script>

<template>
    <div
        v-if="isOpen"
        :class="cn(
      'relative z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md animate-in fade-in-80', // Базовые стили shadcn
      // Доп. классы для позиционирования
      props.position === 'popper' ? 'translate-y-1' : '',
      props.class
   )"
    >
        <div class="p-1">
            <slot/>
        </div>
    </div>
</template>
