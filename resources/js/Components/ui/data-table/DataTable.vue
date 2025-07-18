<script generic="T" setup lang="ts">
import {computed, ref} from 'vue';
import {cn} from '@/lib/utils';
import {useLocale} from '@/composables/useLocale';
import {ChevronDown, ChevronsUpDown, ChevronUp} from 'lucide-vue-next';

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
    mobileLabel?: string;
    sortKey?: string;
    sortFn?: (a: T, b: T) => number;
}

interface Props {
    columns: Column<T>[];
    data: T[];
    loading?: boolean;
    emptyMessage?: string;
    stickyHeader?: boolean;
    compactMode?: boolean;
    rowClass?: string | ((item: T, index: number) => string);
    rowAttributes?: (item: T, index: number) => Record<string, any>; //  ⚠️  changed `string` -> `any`
    mobileCardMode?: boolean;
    showHeader?: boolean;
    defaultSortColumn?: string;
    defaultSortDirection?: 'asc' | 'desc';
}

const {t} = useLocale();
const props = withDefaults(defineProps<Props>(), {
    loading: false,
    emptyMessage: 'No data found',
    stickyHeader: true,
    compactMode: false,
    rowClass: '',
    rowAttributes: () => ({}),
    mobileCardMode: true,
    showHeader: true,
    defaultSortColumn: '',
    defaultSortDirection: 'asc'
});

const tableRef = ref<HTMLDivElement>();

const sortColumn = ref<string>(props.defaultSortColumn);
const sortDirection = ref<'asc' | 'desc'>(props.defaultSortDirection);

const handleSort = (column: Column<T>) => {
    if (!column.sortable) return;
    const sortKey = column.sortKey || column.key;
    if (sortColumn.value === sortKey) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = sortKey;
        sortDirection.value = 'asc';
    }
};

const getSortIcon = (column: Column<T>) => {
    const sortKey = column.sortKey || column.key;
    if (sortColumn.value !== sortKey) return ChevronsUpDown;
    return sortDirection.value === 'asc' ? ChevronUp : ChevronDown;
};

const sortedData = computed(() => {
    if (!sortColumn.value) return props.data;
    const column = props.columns.find(col => (col.sortKey || col.key) === sortColumn.value);
    if (!column) return props.data;
    return [...props.data].sort((a, b) => {
        if (column.sortFn) {
            const result = column.sortFn(a, b);
            return sortDirection.value === 'asc' ? result : -result;
        }
        const aVal = column.sortKey ? a[column.sortKey] : a[column.key];
        const bVal = column.sortKey ? b[column.sortKey] : b[column.key];
        if (aVal == null && bVal == null) return 0;
        if (aVal == null) return sortDirection.value === 'asc' ? 1 : -1;
        if (bVal == null) return sortDirection.value === 'asc' ? -1 : 1;
        if (typeof aVal === 'number' && typeof bVal === 'number') {
            return sortDirection.value === 'asc' ? aVal - bVal : bVal - aVal;
        }
        const aStr = String(aVal).toLowerCase();
        const bStr = String(bVal).toLowerCase();
        if (aStr < bStr) return sortDirection.value === 'asc' ? -1 : 1;
        if (aStr > bStr) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const mobileColumns = computed(() => props.columns.filter(col => !col.hideOnMobile));

const getCellClasses = (column: Column<T>) =>
    cn(
        'whitespace-nowrap',
        column.hideOnMobile && 'hidden sm:table-cell',
        column.hideOnTablet && 'hidden lg:table-cell',
        column.align === 'center' && 'text-center',
        column.align === 'right' && 'text-right',
        column.sticky &&
        'sticky right-0 bg-white dark:bg-gray-900 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]',
        props.compactMode ? 'px-3 py-2' : 'px-6 py-4'
    );

const getHeaderClasses = (column: Column<T>) =>
    cn(
        getCellClasses(column),
        'text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400',
        column.sticky && 'z-10',
        column.sortable &&
        'cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-200 transition-colors'
    );

const getRowClasses = (item: T, index: number) =>
    cn(
        'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors',
        typeof props.rowClass === 'function' ? props.rowClass(item, index) : props.rowClass
    );

const getCardClasses = (item: T, index: number) =>
    cn(
        'mobile-card border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors',
        typeof props.rowClass === 'function' ? props.rowClass(item, index) : props.rowClass
    );

defineExpose({tableRef});
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
                    v-for="(item, index) in sortedData"
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
                    v-if="showHeader"
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
                        @click="handleSort(column)"
                    >
                        <div class="flex items-center gap-1"
                             :class="[column.align === 'center' && 'justify-center', column.align === 'right' && 'justify-end']">
                            <span>{{ column.label }}</span>
                            <component
                                v-if="column.sortable"
                                :is="getSortIcon(column)"
                                class="h-3 w-3 text-gray-400"
                            />
                        </div>
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
                        v-for="(item, index) in sortedData"
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
