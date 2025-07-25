<script lang="ts" setup>
import {computed} from 'vue'
import {cva, type VariantProps} from 'class-variance-authority'
import {cn} from '@/lib/utils'

const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
    {
        variants: {
            variant: {
                default:
                    'border-transparent bg-primary text-primary-foreground hover:bg-primary/80',
                secondary:
                    'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
                destructive:
                    'border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80',
                outline:
                    'text-foreground',
                success:
                    'border-transparent bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                warning:
                    'border-transparent bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                info:
                    'border-transparent bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
)

type BadgeVariants = VariantProps<typeof badgeVariants>

interface Props {
    variant?: BadgeVariants['variant']
    class?: string
}

const props = defineProps<Props>()

const computedClasses = computed(() =>
    cn(badgeVariants({variant: props.variant}), props.class)
)
</script>

<template>
    <div :class="computedClasses">
        <slot/>
    </div>
</template>
