<template>
    <div class="flex flex-col items-center">
        <a
            :class="buttonClasses"
            :href="link"
            rel="noopener"
            target="_blank"
        >
            {{ t('Support') }}
        </a>

        <img
            v-if="showQr"
            :alt="t('QR Code for donation')"
            :src="qrSrc"
            class="mt-2"
            height="160"
            width="160"
        />

        <div
            v-if="showCard"
            class="mt-2 font-mono tracking-wider text-sm text-muted-foreground"
        >
            {{ card }}
        </div>
    </div>
</template>

<script lang="ts" setup>
import {cn} from '@/lib/utils';
import {computed} from 'vue';
import {useLocale} from '@/composables/useLocale';

interface Props {
    class?: string;
    showQr?: boolean;
    showCard?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
    showQr: true,
    showCard: true
});

const {t} = useLocale();

const link = computed(() => `https://send.monobank.ua/jar/${import.meta.env.VITE_MONO_SEND_ID}`);
const qrSrc = computed(() => `https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(link.value)}`);
const card = '4441 1111 2027 7292';

const buttonClasses = computed(() =>
    cn(
        'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
        'bg-primary text-primary-foreground hover:bg-primary/90',
        'h-10 px-4 py-2',
        props.class
    )
);
</script>

<style scoped>
.card-num {
    font-family: monospace;
    letter-spacing: 2px;
}
</style>
