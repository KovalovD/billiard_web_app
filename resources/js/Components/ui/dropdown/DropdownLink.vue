<script lang="ts" setup>
import {Link} from '@inertiajs/vue3';

interface Props {
    href?: string;
    as?: 'a' | 'button' | 'Link';
}

// eslint-disable-next-line
const props = withDefaults(defineProps<Props>(), {
    as: 'Link',
});
</script>

<template>
    <div>
        <button
            v-if="as === 'button'"
            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800"
            type="submit"
        >
            <slot/>
        </button>

        <a
            v-else-if="as === 'a'"
            :href="href"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800"
        >
            <slot/>
        </a>

        <Link
            v-else
            :href="href || '#'"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800"
        >
            <slot/>
        </Link>
    </div>
</template>
