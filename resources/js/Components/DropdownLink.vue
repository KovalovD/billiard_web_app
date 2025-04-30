<script lang="ts" setup>
import {Link} from '@inertiajs/vue3';

interface Props {
    href?: string;
    as?: 'a' | 'button' | 'Link';
}

const props = withDefaults(defineProps<Props>(), {
    as: 'Link', // По умолчанию используем Inertia Link, если есть href
});
</script>

<template>
    <div>
        <button
            v-if="as === 'button'"
            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
            type="submit"
        >
            <slot/>
        </button>

        <a
            v-else-if="as === 'a'"
            :href="href"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
        >
            <slot/>
        </a>

        <Link
            v-else
            :href="href || '#'"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
        >
            <slot/>
        </Link>
    </div>
</template>
