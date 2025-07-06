<script lang="ts" setup>
import {computed, ref} from 'vue';
import {Button} from '@/Components/ui';
import {CameraIcon, TrashIcon, UploadIcon} from 'lucide-vue-next';
import {useLocale} from '@/composables/useLocale';

interface Props {
    modelValue?: File | null;
    currentPicture?: string | null;
    label: string;
    maxSize?: number; // in MB
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    currentPicture: null,
    maxSize: 5,
    disabled: false
});

const emit = defineEmits<{
    'update:modelValue': [file: File | null];
    'delete': [];
}>();

const {t} = useLocale();

const fileInput = ref<HTMLInputElement>();
const previewUrl = ref<string | null>(null);
const error = ref<string | null>(null);

const hasFile = computed(() => !!props.modelValue || !!props.currentPicture);

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        error.value = t('Please select an image file');
        return;
    }

    // Validate file size
    const maxSizeBytes = props.maxSize * 1024 * 1024;
    if (file.size > maxSizeBytes) {
        error.value = t(`File size must not exceed ${props.maxSize}MB`);
        return;
    }

    error.value = null;
    emit('update:modelValue', file);

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
};

const clearFile = () => {
    emit('update:modelValue', null);
    previewUrl.value = null;
    error.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const handleDelete = () => {
    clearFile();
    emit('delete');
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const getImageUrl = () => {
    if (previewUrl.value) return previewUrl.value;
    if (props.currentPicture) return `/storage/${props.currentPicture}`;
    return null;
};
</script>

<template>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ label }}
        </label>

        <div class="flex items-center gap-4">
            <!-- Picture Preview -->
            <div class="relative">
                <div
                    v-if="getImageUrl()"
                    class="h-24 w-24 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800"
                >
                    <img
                        :src="getImageUrl()"
                        :alt="label"
                        class="h-full w-full object-cover"
                    >
                </div>
                <div
                    v-else
                    class="h-24 w-24 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center"
                >
                    <CameraIcon class="h-8 w-8 text-gray-400"/>
                </div>

                <!-- Delete button overlay -->
                <button
                    v-if="hasFile && !disabled"
                    type="button"
                    class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                    @click="handleDelete"
                >
                    <TrashIcon class="h-4 w-4"/>
                </button>
            </div>

            <!-- Upload Button -->
            <div class="flex-1">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    :disabled="disabled"
                    @change="handleFileSelect"
                >

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="disabled"
                    @click="triggerFileInput"
                >
                    <UploadIcon class="mr-2 h-4 w-4"/>
                    {{ hasFile ? t('Change Picture') : t('Upload Picture') }}
                </Button>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ t('Max size:') }} {{ maxSize }}MB
                </p>

                <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">
                    {{ error }}
                </p>
            </div>
        </div>
    </div>
</template>
