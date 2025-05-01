<script lang="ts" setup>
import {computed} from 'vue';
import {cva, type VariantProps} from 'class-variance-authority';
import {cn} from '@/lib/utils';

// CVA конфигурация остается прежней
const buttonVariants = cva(
    'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                default: 'bg-primary text-primary-foreground hover:bg-primary/90',
                destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
                secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                ghost: 'hover:bg-accent hover:text-accent-foreground',
                link: 'text-primary underline-offset-4 hover:underline', // Этот вариант часто используется с as='a'
            },
            size: {
                default: 'h-10 px-4 py-2',
                sm: 'h-9 rounded-md px-3',
                lg: 'h-11 rounded-md px-8',
                icon: 'h-10 w-10',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);

type ButtonProps = VariantProps<typeof buttonVariants>;

// Определяем пропсы. Можно ограничить 'as' только нужными тегами.
interface Props {
    variant?: ButtonProps['variant'];
    size?: ButtonProps['size'];
    as?: 'button' | 'a'; // Ограничиваем тип для 'as' или оставляем string, если нужны другие теги
}

// Дефолтное значение 'button' для 'as'
const props = withDefaults(defineProps<Props>(), {
    as: 'button',
});

// Вычисляемые классы - эта часть работала нормально
const computedClasses = computed(() => cn(buttonVariants({variant: props.variant, size: props.size})));

defineOptions({
    // eslint-disable-next-line
    name: 'Button'
})
</script>

<template>
    <button v-if="as === 'button'" :class="computedClasses" v-bind="$attrs">
        <slot/>
    </button>

    <a v-else-if="as === 'a'" :class="computedClasses" v-bind="$attrs">
        <slot/>
    </a>

</template>
