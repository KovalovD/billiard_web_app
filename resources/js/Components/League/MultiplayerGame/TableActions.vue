<!-- resources/js/Components/TableActions.vue -->
<script lang="ts" setup>
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {MoreVerticalIcon} from 'lucide-vue-next';
import {computed} from 'vue';
import {Button} from "@/Components/ui";
import {Link} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';

const {t} = useLocale();

export interface ActionItem {
    label: string;
    icon?: any;
    action?: () => void;
    href?: string;
    variant?: 'default' | 'destructive' | 'secondary';
    show?: boolean;
    separator?: boolean;
}

interface Props {
    actions: ActionItem[];
    size?: 'sm' | 'default' | 'lg' | 'icon';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'sm'
});

const visibleActions = computed(() =>
    props.actions.filter(action => action.show !== false)
);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button :size="size" class="h-8 w-8 p-0" variant="ghost">
                <span class="sr-only">{{ t('Open menu') }}</span>
                <MoreVerticalIcon class="h-4 w-4"/>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-[160px]">
            <template v-for="(action, index) in visibleActions" :key="index">
                <DropdownMenuSeparator v-if="action.separator"/>
                <Link
                    v-else-if="action.href"
                    :class="action.variant === 'destructive' ? 'text-red-600 dark:text-red-400' : ''"
                    :href="action.href"
                    as="button"
                    class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                >
                    <component :is="action.icon" v-if="action.icon" class="mr-2 h-4 w-4"/>
                    {{ action.label }}
                </Link>
                <DropdownMenuItem
                    v-else
                    :class="action.variant === 'destructive' ? 'text-red-600 dark:text-red-400' : ''"
                    @click="action.action"
                >
                    <component :is="action.icon" v-if="action.icon" class="mr-2 h-4 w-4"/>
                    {{ action.label }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
