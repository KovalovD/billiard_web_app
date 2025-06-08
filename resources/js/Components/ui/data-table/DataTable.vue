<!-- resources/js/Components/ui/data-table/DataTable.vue -->
<script generic="T" lang="ts" setup>
import {ref} from 'vue';
import {cn} from '@/lib/utils';

export interface Column<T> {
    key: string;
    label: string;
    sortable?: boolean;
    hideOnMobile?: boolean;
    hideOnTablet?: boolean;
    align?: 'left' | 'center' | 'right';
    width?: string;
    sticky?: boolean;
    render?: (item: T) => any;
}

interface Props {
    columns: Column<T>[];
    data: T[];
    loading?: boolean;
    emptyMessage?: string;
    stickyHeader?: boolean;
    compactMode?: boolean;
    rowClass?: string | ((item: T, index: number) => string);
    rowAttributes?: (item: T, index: number) => Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    emptyMessage: 'No data found',
    stickyHeader: true,
    compactMode: false,
    rowClass: '',
    rowAttributes: () => ({})
});

const tableRef = ref<HTMLDivElement>();

const getCellClasses = (column: Column<T>) => {
    return cn(
        'whitespace-nowrap',
        column.hideOnMobile && 'hidden sm:table-cell',
        column.hideOnTablet && 'hidden lg:table-cell',
        column.align === 'center' && 'text-center',
        column.align === 'right' && 'text-right',
        column.sticky && 'sticky right-0 bg-white dark:bg-gray-900 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]',
        props.compactMode ? 'px-3 py-2' : 'px-6 py-4'
    );
};

const getHeaderClasses = (column: Column<T>) => {
    return cn(
        getCellClasses(column),
        'text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400',
        column.sticky && 'z-10'
    );
};

const getRowClasses = (item: T, index: number) => {
    return cn(
        'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors',
        typeof props.rowClass === 'function' ? props.rowClass(item, index) : props.rowClass
    );
};

// Expose the table ref to parent components
defineExpose({
    tableRef
});
</script>

<template>
    <div ref="tableRef" class="relative">
        <!-- Wrapper with horizontal scroll -->
        <div
            class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700"
        >
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead
                    :class="[
                        'bg-gray-50 dark:bg-gray-800',
                        stickyHeader && 'sticky top-0 z-10'
                    ]"
                >
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="getHeaderClasses(column)"
                        :style="{ width: column.width }"
                        scope="col"
                    >
                        {{ column.label }}
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                <!-- Loading state -->
                <tr v-if="loading">
                    <td :colspan="columns.length" class="px-6 py-10 text-center">
                        <div class="flex items-center justify-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                            <span class="ml-2 text-gray-500 dark:text-gray-400">Loading...</span>
                        </div>
                    </td>
                </tr>

                <!-- Empty state -->
                <tr v-else-if="data.length === 0">
                    <td :colspan="columns.length" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                        {{ emptyMessage }}
                    </td>
                </tr>

                <!-- Data rows -->
                <template v-else>
                    <tr
                        v-for="(item, index) in data"
                        :key="index"
                        :class="getRowClasses(item, index)"
                        v-bind="props.rowAttributes(item, index)"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            :class="getCellClasses(column)"
                        >
                            <slot :item="item" :name="`cell-${column.key}`"
                                  :value="column.render ? column.render(item) : item[column.key]">
                                {{ column.render ? column.render(item) : item[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>

        <!-- Scroll indicators -->
        <div
            v-if="tableRef?.scrollWidth > tableRef?.clientWidth"
            class="absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white dark:from-gray-900 pointer-events-none"
        />
    </div>
</template>

<style scoped>
/* Custom scrollbar styles */
.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar {
    height: 6px;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
    background-color: rgb(209 213 219);
    border-radius: 3px;
}

.dark .scrollbar-thumb-gray-700::-webkit-scrollbar-thumb {
    background-color: rgb(55 65 81);
}

/* Smooth horizontal scrolling on mobile */
@media (max-width: 768px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }
}
</style>
