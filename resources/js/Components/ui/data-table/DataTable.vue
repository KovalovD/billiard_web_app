<!-- resources/js/Components/ui/data-table/DataTable.vue -->
<script generic="T" lang="ts" setup>
import {computed, ref} from 'vue';
import {cn} from '@/lib/utils';
import {useLocale} from "@/composables/useLocale";

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
    mobileLabel?: string; // For mobile card view
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
    mobileCardMode?: boolean; // Enable card mode on mobile
}

const {t} = useLocale();
const props = withDefaults(defineProps<Props>(), {
    loading: false,
    emptyMessage: 'No data found',
    stickyHeader: true,
    compactMode: false,
    rowClass: '',
    rowAttributes: () => ({}),
    mobileCardMode: true
});

const tableRef = ref<HTMLDivElement>();

// Computed columns for mobile view (exclude hidden columns)
const mobileColumns = computed(() =>
    props.columns.filter(col => !col.hideOnMobile)
);

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

const getCardClasses = (item: T, index: number) => {
    return cn(
        'mobile-card', // Add consistent class for mobile cards
        'border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors',
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
        <!-- Mobile Card View -->
        <div v-if="mobileCardMode" class="sm:hidden mobile-cards-container">
            <!-- Loading state -->
            <div v-if="loading" class="flex items-center justify-center py-10">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                <span class="ml-2 text-gray-500 dark:text-gray-400">{{ t('Loading...') }}</span>
            </div>

            <!-- Empty state -->
            <div v-else-if="data.length === 0" class="text-center py-10 text-gray-500 dark:text-gray-400">
                {{ emptyMessage }}
            </div>

            <!-- Data cards -->
            <div v-else class="space-y-3">
                <div
                    v-for="(item, index) in data"
                    :key="index"
                    :class="getCardClasses(item, index)"
                    v-bind="props.rowAttributes(item, index)"
                >
                    <!-- Primary info (first non-hidden column) -->
                    <div v-if="mobileColumns[0]" class="mb-3">
                        <slot
                            :item="item"
                            :name="`mobile-primary`"
                            :value="mobileColumns[0].render ? mobileColumns[0].render(item) : item[mobileColumns[0].key]"
                        >
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                <slot
                                    :item="item"
                                    :name="`cell-${mobileColumns[0].key}`"
                                    :value="mobileColumns[0].render ? mobileColumns[0].render(item) : item[mobileColumns[0].key]"
                                >
                                    {{
                                        mobileColumns[0].render ? mobileColumns[0].render(item) : item[mobileColumns[0].key]
                                    }}
                                </slot>
                            </div>
                        </slot>
                    </div>

                    <!-- Other columns as key-value pairs -->
                    <div class="space-y-2 text-sm">
                        <div
                            v-for="(column) in mobileColumns.slice(1)"
                            :key="column.key"
                            class="flex items-center justify-between"
                        >
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ column.mobileLabel || column.label }}:
                            </span>
                            <span :class="[
                                'font-medium',
                                column.align === 'right' && 'text-right',
                                column.align === 'center' && 'text-center'
                            ]">
                                <slot
                                    :item="item"
                                    :name="`cell-${column.key}`"
                                    :value="column.render ? column.render(item) : item[column.key]"
                                >
                                    {{ column.render ? column.render(item) : item[column.key] }}
                                </slot>
                            </span>
                        </div>
                    </div>

                    <!-- Mobile actions slot -->
                    <slot name="mobile-actions" :item="item" :index="index"/>
                </div>
            </div>
        </div>

        <!-- Desktop Table View -->
        <div
            :class="[
                'overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700',
                mobileCardMode && 'hidden sm:block'
            ]"
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
                            <span class="ml-2 text-gray-500 dark:text-gray-400">{{ t('Loading...') }}</span>
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

        <!-- Scroll indicators (desktop only) -->
        <div
            v-if="tableRef?.scrollWidth > tableRef?.clientWidth"
            class="hidden sm:block absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white dark:from-gray-900 pointer-events-none"
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
